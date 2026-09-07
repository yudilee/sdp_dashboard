<?php

namespace App\Console\Commands;

use App\Http\Controllers\SuratKuasaController;
use App\Models\Item;
use App\Models\Setting;
use App\Models\SuratKuasaLog;
use App\Models\SuratKuasaSystemLog;
use App\Services\OdooService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoGenerateSuratKuasa extends Command
{
    protected $signature = 'sk:auto-generate {--dry-run : Show which units would be processed without actually sending}';
    protected $description = 'Automatically generate and email Surat Kuasa for units with complete data (No. Rangka + No. Mesin filled)';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        $this->info('=== Auto Surat Kuasa Generator ===');
        $this->info('Started at: ' . now()->format('Y-m-d H:i:s'));

        // 1. Check if auto-mode is enabled
        $autoEnabled = Setting::get('surat_kuasa_auto_enabled', 'false') === 'true';
        if (!$autoEnabled && !$this->option('dry-run')) {
            $this->warn('Auto SK generation is disabled. Enable it in Settings → Surat Kuasa Configuration → Auto SK.');
            return 0;
        }

        // 2. Check recipient email is configured
        $recipientEmail = Setting::get('surat_kuasa_default_recipient_email', '');
        if (empty(trim($recipientEmail))) {
            $this->error('No recipient email configured. Set it in Settings → Surat Kuasa Configuration → Default Recipient Email.');
            Log::error('[Auto SK] Aborted: no recipient email configured.');
            SuratKuasaSystemLog::error('Auto SK generation aborted: No default recipient email configured.', 'auto_generate');
            return 1;
        }

        // 3. Validate recipient emails
        $recipients = array_values(array_filter(
            array_map('trim', preg_split('/[,;]+/', $recipientEmail)),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        ));
        if (empty($recipients)) {
            $this->error("Configured recipient email(s) are invalid: {$recipientEmail}");
            Log::error('[Auto SK] Aborted: invalid recipient email.', ['raw' => $recipientEmail]);
            return 1;
        }

        // 4. Run Complete Odoo Sync Pipeline for Surat Kuasa
        // STEP A: Initial Discovery Sync (Find new delivered units in Odoo with empty No. Rangka/Mesin and track them)
        $this->info('Step 1/2: Checking Odoo for newly delivered units (Initial Discovery)...');
        try {
            $odooService = app(OdooService::class);
            $discRes = $odooService->fetchSuratKuasaUnits();
            $newUnitsCount = 0;

            if ($discRes['success'] ?? false) {
                foreach ($discRes['data'] ?? [] as $rec) {
                    if (empty($rec['lot_number'])) continue;

                    // Match by odoo_lot_id first (survives lot renames), then lot_number fallback.
                    // This prevents duplicate rows when Odoo renames a staging lot to its real plate.
                    $existing = null;
                    if (!empty($rec['odoo_lot_id'])) {
                        $existing = Item::where('odoo_lot_id', $rec['odoo_lot_id'])->first();
                    }
                    if (!$existing) {
                        $existing = Item::where('lot_number', $rec['lot_number'])->first();
                    }

                    if ($existing) {
                        $existing->surat_kuasa_tracked = true;
                        if (!empty($rec['odoo_lot_id'])) $existing->odoo_lot_id = $rec['odoo_lot_id'];
                        if (!empty($rec['vehicle_category'])) $existing->vehicle_category = $rec['vehicle_category'];
                        // Tier 2: populate numbers immediately if Odoo has them and local DB is empty
                        if (empty($existing->internal_reference) && !empty($rec['internal_reference']))
                            $existing->internal_reference = $rec['internal_reference'];
                        if (empty($existing->engine_number) && !empty($rec['engine_number']))
                            $existing->engine_number = $rec['engine_number'];
                        $existing->save();
                    } else {
                        Item::create([
                            'odoo_lot_id'         => $rec['odoo_lot_id'] ?? null,
                            'lot_number'          => $rec['lot_number'],
                            'product'             => $rec['product'] ?? '',
                            'vehicle_category'    => $rec['vehicle_category'] ?? null,
                            'year'                => $rec['year'] ?? date('Y'),
                            'location'            => $rec['location'] ?? '',
                            'bbn'                 => $rec['bbn'] ?? null,
                            'current_customer'    => $rec['current_customer'] ?? null,
                            'internal_reference'  => $rec['internal_reference'] ?? null,
                            'engine_number'       => $rec['engine_number'] ?? null,
                            'on_hand_quantity'    => 0,
                            'is_on_hand'          => true,
                            'is_order_only'       => false,
                            'is_vendor_rent'      => false,
                            'surat_kuasa_tracked' => true,
                        ]);
                        $newUnitsCount++;
                    }
                }
                $this->info("Initial Discovery complete ({$newUnitsCount} brand new unit(s) added).");
            }
        } catch (\Exception $e) {
            $this->warn('Initial Discovery Sync notice: ' . $e->getMessage());
            Log::warning('[Auto SK] Initial Discovery failed.', ['error' => $e->getMessage()]);
        }

        // STEP B: Fast Sync (Update No. Rangka & No. Mesin and lot renames from Odoo for all tracked units)
        $this->info('Step 2/2: Running Fast Sync to check updated No. Rangka & No. Mesin...');
        try {
            $skItems = Item::where('surat_kuasa_tracked', true)
                ->where('on_hand_quantity', 0)
                ->whereNull('auto_sk_sent')
                ->get();

            $odooIds = $skItems->pluck('odoo_lot_id')->filter()->unique()->values()->toArray();

            if (!empty($odooIds)) {
                $res = $odooService->fetchSuratKuasaByOdooIds($odooIds);
                if ($res['success'] ?? false) {
                    $odooData = $res['data']; // keyed by odoo_lot_id
                    foreach ($skItems as $item) {
                        $row = $odooData[$item->odoo_lot_id] ?? null;
                        if ($row) {
                            if (!empty($row['lot_number']))         $item->lot_number = $row['lot_number'];
                            if (!empty($row['internal_reference'])) $item->internal_reference = $row['internal_reference'];
                            if (!empty($row['engine_number']))      $item->engine_number = $row['engine_number'];
                            if (!empty($row['year']))               $item->year = $row['year'];
                            if (!empty($row['vehicle_category']))   $item->vehicle_category = $row['vehicle_category'];
                            $item->save();
                        }
                    }
                }
            }
            $this->info('Fast sync completed — ' . $skItems->count() . ' tracked units updated.');
        } catch (\Exception $e) {
            $this->warn('Fast sync notice: ' . $e->getMessage());
            Log::warning('[Auto SK] Fast sync failed.', ['error' => $e->getMessage()]);
        }

        // 5. Query qualifying units: tracked, delivered, both rangka+mesin filled, not yet auto-sent and not yet generated in logs
        $existingLogItemIds = SuratKuasaLog::pluck('item_id')->unique()->toArray();

        $readyItems = Item::where('surat_kuasa_tracked', true)
            ->where('on_hand_quantity', 0)
            ->where('is_vendor_rent', false)
            ->whereNotNull('internal_reference')
            ->where('internal_reference', '!=', '')
            ->whereNotNull('engine_number')
            ->where('engine_number', '!=', '')
            ->whereNull('auto_sk_sent')
            ->whereNotIn('id', $existingLogItemIds)
            ->get();

        $totalReady = $readyItems->count();
        $this->info("Found {$totalReady} unit(s) ready for auto-generation.");

        if ($totalReady === 0) {
            $this->info('Nothing to process. All ready units have already been auto-generated.');
            return 0;
        }

        // Dry run: just list units, do nothing
        if ($this->option('dry-run')) {
            $this->table(
                ['ID', 'Lot Number', 'Product', 'No. Rangka', 'No. Mesin'],
                $readyItems->map(fn($i) => [
                    $i->id,
                    $i->lot_number,
                    SuratKuasaController::cleanProductName($i->product),
                    $i->internal_reference,
                    $i->engine_number,
                ])->toArray()
            );
            $this->warn('DRY RUN: No documents generated or emails sent.');
            return 0;
        }

        // 6. Settings for SK documents
        $settings = [
            'pemberi_1_nama'    => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama'    => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat'    => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama'      => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat'    => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        $format         = Setting::get('surat_kuasa_auto_format', 'docx');
        $autoPenerimaNama    = Setting::get('surat_kuasa_auto_penerima_nama', '');
        $autoPenerimaAlamat  = Setting::get('surat_kuasa_auto_penerima_alamat', '');

        $successCount = 0;
        $failCount    = 0;

        $tempDir = storage_path('app/temp_documents');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        // 7. Process each qualifying unit
        foreach ($readyItems as $item) {
            $lotNumber    = $item->lot_number;
            $cleanProduct = SuratKuasaController::cleanProductName($item->product);
            $noRangka     = trim((string) $item->internal_reference);
            $noMesin      = trim((string) $item->engine_number);
            $warna        = $item->color ?: 'Putih';
            $tahun        = $item->year ?: date('Y');
            $jenisModel   = $item->vehicle_category ?: 'Mobil Barang';
            $printDate    = SuratKuasaController::formatIndonesianDate();
            $docNo        = SuratKuasaController::generateNextDocNo();

            $this->line("  → Processing: {$lotNumber} | Doc: {$docNo}");

            try {
                // Generate document file
                $filePath = $this->buildDocument(
                    format: $format,
                    tempDir: $tempDir,
                    lotNumber: $lotNumber,
                    docNo: $docNo,
                    cleanProduct: $cleanProduct,
                    noRangka: $noRangka,
                    noMesin: $noMesin,
                    warna: $warna,
                    tahun: $tahun,
                    jenisModel: $jenisModel,
                    printDate: $printDate,
                    penerimaNama: $autoPenerimaNama,
                    penerimaAlamat: $autoPenerimaAlamat,
                    settings: $settings,
                    item: $item,
                );

                $cleanDoc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $docNo);
                $cleanLot = preg_replace('/[^A-Za-z0-9_\-]/', '_', $lotNumber);
                $filename = 'Surat_Kuasa_' . $cleanDoc . '_' . $cleanLot . '.' . $format;
                $mimeType = $format === 'docx'
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                    : 'application/pdf';

                // Send email with clear unit and doc number in subject
                $subject  = "Surat Kuasa - {$lotNumber} ({$docNo})";
                $body     = "Dear Team,\n\nTerlampir Surat Kuasa otomatis untuk unit kendaraan:\n\nUnit      : {$lotNumber}\nMerk/Type : {$cleanProduct}\nNo. Rangka: {$noRangka}\nNo. Mesin : {$noMesin}\nDoc No    : {$docNo}\n\nDokumen ini digenerate secara otomatis oleh sistem SDP Dashboard.\n\nSalam,\nSDP Dashboard Auto-System";

                Mail::raw($body, function ($mail) use ($recipients, $subject, $filePath, $filename, $mimeType) {
                    foreach ($recipients as $r) {
                        $mail->to($r);
                    }
                    $mail->subject($subject)->attach($filePath, ['as' => $filename, 'mime' => $mimeType]);
                });

                @unlink($filePath);

                // Write audit log (same as manual flow, with action_type = 'auto')
                SuratKuasaLog::create([
                    'item_id'            => $item->id,
                    'doc_no'             => $docNo,
                    'lot_number'         => $lotNumber,
                    'product'            => $cleanProduct,
                    'customer'           => $item->current_customer,
                    'penerima_nama'      => $autoPenerimaNama,
                    'penerima_alamat'    => $autoPenerimaAlamat,
                    'jenis_model'        => $jenisModel,
                    'warna'              => $warna,
                    'tahun'              => $tahun,
                    'no_rangka'          => $noRangka,
                    'no_mesin'           => $noMesin,
                    'print_date'         => $printDate,
                    'action_type'        => 'auto',
                    'recipient_email'    => $recipientEmail,
                    'generated_by_id'    => null,
                    'generated_by_name'  => 'System (Auto)',
                ]);

                // Advance sequence counter
                SuratKuasaController::advanceDocSequence($docNo);

                // Mark unit as auto-processed (prevents re-processing)
                $item->auto_sk_sent = now();
                $item->save();

                $this->info("    ✓ Sent to: " . implode(', ', $recipients));
                Log::info("[Auto SK] Generated & emailed SK for unit {$lotNumber}.", [
                    'doc_no'    => $docNo,
                    'recipients'=> $recipients,
                ]);

                SuratKuasaSystemLog::success(
                    "Auto SK generated & emailed successfully for unit {$lotNumber} ({$docNo})",
                    'email_send',
                    [
                        'doc_no'       => $docNo,
                        'product'      => $cleanProduct,
                        'no_rangka'    => $noRangka,
                        'no_mesin'     => $noMesin,
                        'recipients'   => $recipients,
                        'file_format'  => $format,
                    ],
                    $lotNumber,
                    $docNo
                );

                $successCount++;

                // 2-second pacing to prevent SMTP burst spam filtering / rate limits
                sleep(2);

            } catch (\Exception $e) {
                @unlink($filePath ?? '');
                $this->error("    ✗ Failed: " . $e->getMessage());
                Log::error("[Auto SK] Failed for unit {$lotNumber}.", [
                    'error' => $e->getMessage(),
                ]);

                SuratKuasaSystemLog::error(
                    "Auto SK failed for unit {$lotNumber} ({$docNo}): " . $e->getMessage(),
                    'auto_generate',
                    [
                        'doc_no'    => $docNo,
                        'error'     => $e->getMessage(),
                        'product'   => $cleanProduct,
                        'no_rangka' => $noRangka,
                        'no_mesin'  => $noMesin,
                        'trace'     => substr($e->getTraceAsString(), 0, 1500),
                    ],
                    $lotNumber,
                    $docNo
                );

                $failCount++;
            }
        }

        $this->newLine();
        $this->info("=== Completed: {$successCount} sent, {$failCount} failed ===");
        Log::info("[Auto SK] Run complete.", ['success' => $successCount, 'failed' => $failCount]);

        if ($successCount > 0 || $failCount > 0) {
            SuratKuasaSystemLog::info(
                "Auto SK batch run finished: {$successCount} sent successfully, {$failCount} failed.",
                'auto_generate',
                ['success_count' => $successCount, 'failed_count' => $failCount]
            );
        }

        return $failCount > 0 ? 1 : 0;
    }

    /**
     * Build the SK document (docx or pdf) and return the file path.
     */
    private function buildDocument(
        string $format,
        string $tempDir,
        string $lotNumber,
        string $docNo,
        string $cleanProduct,
        string $noRangka,
        string $noMesin,
        string $warna,
        string|int $tahun,
        string $jenisModel,
        string $printDate,
        string $penerimaNama,
        string $penerimaAlamat,
        array  $settings,
        Item   $item,
    ): string {
        if ($format === 'docx') {
            return $this->buildDocx(
                tempDir: $tempDir, lotNumber: $lotNumber, docNo: $docNo,
                cleanProduct: $cleanProduct, noRangka: $noRangka, noMesin: $noMesin,
                warna: $warna, tahun: $tahun, jenisModel: $jenisModel,
                printDate: $printDate, penerimaNama: $penerimaNama,
                penerimaAlamat: $penerimaAlamat, settings: $settings,
            );
        }

        return $this->buildPdf(
            tempDir: $tempDir, lotNumber: $lotNumber, docNo: $docNo,
            cleanProduct: $cleanProduct, noRangka: $noRangka, noMesin: $noMesin,
            warna: $warna, tahun: $tahun, jenisModel: $jenisModel,
            printDate: $printDate, penerimaNama: $penerimaNama,
            penerimaAlamat: $penerimaAlamat, settings: $settings, item: $item,
        );
    }

    private function buildDocx(
        string $tempDir,
        string $lotNumber,
        string $docNo,
        string $cleanProduct,
        string $noRangka,
        string $noMesin,
        string $warna,
        string|int $tahun,
        string $jenisModel,
        string $printDate,
        string $penerimaNama,
        string $penerimaAlamat,
        array  $settings,
    ): string {
        $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $lotNumber) . '.docx';
        $filePath = $tempDir . '/' . uniqid('auto_sk_') . '_' . $filename;

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop'    => 1440, 'marginBottom' => 1020,
            'marginLeft'   => 1134, 'marginRight'  => 1139,
            'headerHeight' => 720,
        ]);

        $section->addTextBreak(3);
        $section->addText('SURAT KUASA', ['name' => 'Times New Roman', 'size' => 15, 'bold' => true, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($docNo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 140]);
        $section->addText('Yang bertanda tangan dibawah ini:', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 40]);

        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15];
        $pStyle     = ['spaceAfter' => 0, 'spaceBefore' => 0];

        // Pemberi Kuasa
        $t1 = $section->addTable($tableStyle);
        $this->addTableRow($t1, 'Nama',    $settings['pemberi_1_nama'],    $pStyle);
        $this->addTableRow($t1, 'Jabatan', $settings['pemberi_1_jabatan'], $pStyle);
        $this->addTableRow($t1, 'Nama',    $settings['pemberi_2_nama'],    $pStyle);
        $this->addTableRow($t1, 'Jabatan', $settings['pemberi_2_jabatan'], $pStyle);
        $this->addTableRow($t1, 'Alamat',  $settings['pemberi_alamat'],    $pStyle);

        $section->addTextBreak(1);
        $section->addText('Dengan ini memberi kuasa kepada :', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Penerima Kuasa (blank dotted lines for auto)
        $t2 = $section->addTable($tableStyle);
        $this->addTableRow($t2, 'Nama',   $penerimaNama   ?: '....................................................................................', $pStyle);
        $this->addTableRow($t2, 'Alamat', $penerimaAlamat ?: '....................................................................................', $pStyle);

        $section->addTextBreak(1);
        $section->addText('Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Vehicle Details
        $t3 = $section->addTable($tableStyle);
        $t3->addRow();
        $t3->addCell(2200)->addText('Nama Pemilik', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
        $t3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $t3->addCell(6500)->addText($settings['pemilik_nama'], ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
        if (!empty($settings['pemilik_alamat'])) {
            $this->addTableRow($t3, 'Alamat', $settings['pemilik_alamat'], $pStyle);
        }
        $this->addTableRow($t3, 'Merk/Type',    $cleanProduct, $pStyle);
        $this->addTableRow($t3, 'Jenis / Model', $jenisModel,  $pStyle);
        $this->addTableRow($t3, 'Tahun',         (string) $tahun,    $pStyle);
        $this->addTableRow($t3, 'No. Rangka',    $noRangka,    $pStyle);
        $this->addTableRow($t3, 'No. Mesin',     $noMesin,     $pStyle);
        $this->addTableRow($t3, 'Warna',         $warna,       $pStyle);

        $section->addText('Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 120, 'spaceAfter' => 140]);

        // Signatures
        $sigTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15]);
        $sigTable->addRow();
        $cellP1 = $sigTable->addCell(3000);
        $cellP1->addText('Jakarta , ' . $printDate, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $cellP1->addText('Pemberi Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);
        $cellP2 = $sigTable->addCell(3000);
        $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $cellP2->addText('', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 1300]);
        $cellRec = $sigTable->addCell(3000);
        $cellRec->addText('', ['name' => 'Times New Roman', 'size' => 12]);
        $cellRec->addText('Penerima Kuasa', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 1300]);

        $sigTable->addRow();
        $c1 = $sigTable->addCell(3000);
        $c1->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c1->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $c2 = $sigTable->addCell(3000);
        $c2->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c2->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $c3 = $sigTable->addCell(3000);
        $c3->addText('(                                          )', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return $filePath;
    }

    private function buildPdf(
        string $tempDir,
        string $lotNumber,
        string $docNo,
        string $cleanProduct,
        string $noRangka,
        string $noMesin,
        string $warna,
        string|int $tahun,
        string $jenisModel,
        string $printDate,
        string $penerimaNama,
        string $penerimaAlamat,
        array  $settings,
        Item   $item,
    ): string {
        $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $lotNumber) . '.pdf';
        $filePath = $tempDir . '/' . uniqid('auto_sk_') . '_' . $filename;

        $html = view('surat_kuasa.print', [
            'item'           => $item,
            'cleanProduct'   => $cleanProduct,
            'noRangka'       => $noRangka,
            'noMesin'        => $noMesin,
            'docNo'          => $docNo,
            'penerimaNama'   => $penerimaNama,
            'penerimaAlamat' => $penerimaAlamat,
            'jenisModel'     => $jenisModel,
            'warna'          => $warna,
            'tahun'          => $tahun,
            'printDate'      => $printDate,
            'settings'       => $settings,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        file_put_contents($filePath, $dompdf->output());

        return $filePath;
    }

    /** Helper: add a label : value row to a PhpWord table */
    private function addTableRow(\PhpOffice\PhpWord\Element\Table $table, string $label, string $value, array $pStyle): void
    {
        $table->addRow();
        $table->addCell(2200)->addText($label, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table->addCell(6500)->addText($value, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
    }
}
