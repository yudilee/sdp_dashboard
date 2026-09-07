<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Setting;
use App\Models\Item;
use App\Models\SuratKuasaLog;
use App\Models\SuratKuasaSystemLog;
use App\Services\OdooService;

class SuratKuasaController extends Controller
{
    /**
     * Clean product string by stripping leading bracketed prefix code e.g. [DHT-GMAXMB13-MT-B]
     */
    public static function cleanProductName(?string $product): string
    {
        if (empty($product)) {
            return '-';
        }
        return trim(preg_replace('/^\s*\[[^\]]+\]\s*/', '', $product));
    }

    /**
     * Convert integer month to Roman numeral
     */
    public static function getRomanMonth(int $month): string
    {
        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII'
        ];
        return $romanMonths[$month] ?? 'I';
    }

    /**
     * Format a date string with Indonesian month name (e.g. 31 Agustus 2026)
     */
    public static function formatIndonesianDate($date = null): string
    {
        try {
            $carbon = $date ? (\Carbon\Carbon::parse($date)) : \Carbon\Carbon::now();
        } catch (\Exception $e) {
            $carbon = \Carbon\Carbon::now();
        }

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $day = $carbon->format('d');
        $month = $months[(int) $carbon->format('n')] ?? $carbon->format('F');
        $year = $carbon->format('Y');

        return "{$day} {$month} {$year}";
    }

    /**
     * Generate the next sequential Surat Kuasa document number
     * Format: {sequence}/{prefix}/{RomanMonth}/{TwoDigitYear} e.g. 1546/HRCJ/FOD/VIII/26
     */
    public static function generateNextDocNo(?int $sequence = null, ?string $prefix = null): string
    {
        $prefix = $prefix ?: Setting::get('surat_kuasa_doc_prefix', 'HRCJ/FOD');
        if ($sequence === null) {
            $lastSeq = (int) Setting::get('surat_kuasa_last_sequence', 1545);
            $sequence = $lastSeq + 1;

            // Automatically check and skip any sequence numbers already taken in SuratKuasaLog
            while (
                SuratKuasaLog::where('doc_no', 'like', "{$sequence}/%")
                    ->orWhere('doc_no', 'like', str_pad((string) $sequence, 4, '0', STR_PAD_LEFT) . "/%")
                    ->exists()
            ) {
                $sequence++;
            }
        }

        $paddedSequence = str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        $romanMonth = self::getRomanMonth((int) date('n'));
        $twoDigitYear = date('y');

        return "{$paddedSequence}/{$prefix}/{$romanMonth}/{$twoDigitYear}";
    }

    /**
     * Advance the last sequence in settings based on the document number used
     */
    public static function advanceDocSequence(?string $docNo): void
    {
        if (empty($docNo))
            return;

        if (preg_match('/^(\d+)/', trim($docNo), $matches)) {
            $usedSeq = (int) $matches[1];
            $currentLast = (int) Setting::get('surat_kuasa_last_sequence', 1545);
            if ($usedSeq >= $currentLast) {
                Setting::set('surat_kuasa_last_sequence', $usedSeq);
            }
        }
    }

    /**
     * Display the Surat Kuasa dashboard or password unlock prompt
     */
    public function index(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('surat-kuasa')) {
            abort(403, 'Unauthorized access to Surat Kuasa.');
        }

        if (!$this->checkSuratKuasaSession()) {
            return view('surat_kuasa.index', [
                'authenticated' => false,
                'session_expired' => session('session_expired', false)
            ]);
        }

        $search = $request->input('search');

        // Query Lot/Serial vehicle units fulfilling conditions:
        // 1. on_hand_quantity == 0 AND surat_kuasa_tracked == true (units being tracked for Surat Kuasa)
        // 2. Exclude Vendor Rent (is_vendor_rent == false)
        // NOTE: Units with rangka/mesin filled stay in the list with ✅ status (ready to generate SK)
        //       Units with empty rangka/mesin show ❌ status (waiting for Odoo data)
        $query = Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where('surat_kuasa_tracked', true)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')
                    ->orWhere('is_vendor_rent', false);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('lot_number', 'like', "%{$search}%")
                    ->orWhere('internal_reference', 'like', "%{$search}%")
                    ->orWhere('engine_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('bbn', 'like', "%{$search}%")
                    ->orWhere('rental_id', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('product')->orderBy('lot_number')->paginate(50);

        // Fetch map of item IDs that already have generated Surat Kuasa logs with their latest log data
        $generatedLogsByItemId = SuratKuasaLog::latest('id')->get()->keyBy('item_id');
        $generatedItemIds = $generatedLogsByItemId->keys()->toArray();

        // Fetch dynamic Surat Kuasa settings from UTILITIES -> Settings
        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
            'default_recipient_email' => Setting::get('surat_kuasa_default_recipient_email', ''),
            'doc_prefix' => Setting::get('surat_kuasa_doc_prefix', 'HRCJ/FOD'),
            'last_sequence' => (int) Setting::get('surat_kuasa_last_sequence', 1545),
        ];

        // Fetch recent sync updates for notification bell
        $recentNotifications = Item::where('surat_kuasa_tracked', true)
            ->where(function ($q) {
                $q->whereNotNull('internal_reference')->where('internal_reference', '!=', '')
                    ->orWhereNotNull('engine_number')->where('engine_number', '!=', '');
            })
            ->latest('updated_at')
            ->limit(15)
            ->get()
            ->map(function ($item) {
                $isReady = !empty($item->internal_reference) && !empty($item->engine_number);
                $changes = [];
                if (!empty($item->internal_reference))
                    $changes[] = "No. Rangka: " . $item->internal_reference;
                if (!empty($item->engine_number))
                    $changes[] = "No. Mesin: " . $item->engine_number;
                return [
                    'key' => 'sk_' . $item->id . '_' . strtotime($item->updated_at),
                    'lot_number' => $item->lot_number,
                    'product' => $item->product,
                    'internal_reference' => $item->internal_reference,
                    'engine_number' => $item->engine_number,
                    'changes' => $changes,
                    'is_ready' => $isReady,
                    'status_label' => $isReady ? 'Ready to Generate' : 'Awaiting Data',
                ];
            })->values();

        return view('surat_kuasa.index', [
            'authenticated' => true,
            'items' => $items,
            'generatedItemIds' => $generatedItemIds,
            'generatedLogsByItemId' => $generatedLogsByItemId,
            'settings' => $settings,
            'search' => $search,
            'nextDocNo' => self::generateNextDocNo(),
            'defaultDate' => self::formatIndonesianDate(),
            'recentNotifications' => $recentNotifications
        ]);
    }

    /**
     * Authenticate for Surat Kuasa page
     */
    public function authenticate(Request $request)
    {
        $password = (string) $request->input('password');
        $storedPassword = (string) Setting::get('surat_kuasa_password', env('SURAT_KUASA_DEFAULT_PASSWORD', 'admin'));

        $isBcrypt = str_starts_with($storedPassword, '$2y$') || str_starts_with($storedPassword, '$2a$') || str_starts_with($storedPassword, '$2b$');

        if ($isBcrypt) {
            $isMatch = Hash::check($password, $storedPassword);
        } else {
            $isMatch = ($password === $storedPassword);
            if ($isMatch) {
                // Auto-upgrade legacy plaintext to Bcrypt hash
                Setting::set('surat_kuasa_password', Hash::make($password));
            }
        }

        if ($isMatch) {
            session([
                'surat_kuasa_authenticated' => true,
                'surat_kuasa_authenticated_at' => now()->timestamp,
            ]);
            return redirect()->route('surat-kuasa.index')->with('success', 'Surat Kuasa unlocked successfully.');
        }

        return redirect()->back()->with('error', 'Incorrect secondary password.');
    }

    /**
     * Dedicated Sync Odoo Data specifically for Surat Kuasa units (INITIAL DISCOVERY).
     *
     * Pulls units from Odoo matching the strict SK discovery criteria:
     *   - On Hand Quantity = 0
     *   - Internal Reference (No. Rangka) is NOT set
     *   - No. Mesin (engine_number) is NOT set
     *   - Is Vendor Rent = false
     *
     * Matching is done by odoo_lot_id first (stable across lot renames), then lot_number fallback.
     * Only tracks new units if BOTH ref AND engine are empty (rule 1).
     * Auto-reconcile (branch-scoped) removes tracking for units no longer qualifying.
     */
    public function syncOdooData(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }

        try {
            $odooService = app(OdooService::class);
            $res = $odooService->fetchSuratKuasaUnits(); // strict: empty ref+engine only

            if (!$res['success']) {
                return response()->json(['success' => false, 'message' => 'Sync failed: ' . ($res['message'] ?? 'Unknown error')], 500);
            }

            $records = $res['data'] ?? [];
            if (empty($records)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Sync completed. No pending units found in Odoo (all units already have No. Rangka & Mesin, or no qualifying units exist).',
                    'updated_count' => 0,
                    'total_sk' => $this->countTrackedSK(),
                    'ready_sk' => $this->countReadySK(),
                    'pending_sk' => $this->countTrackedSK() - $this->countReadySK(),
                    'changes' => []
                ]);
            }

            $syncedCount = 0;
            $changedDetails = [];
            $activeOdooIds = [];

            foreach ($records as $itemData) {
                if (empty($itemData['lot_number']))
                    continue;

                $odooLotId = $itemData['odoo_lot_id'] ?? null;

                // fetchSuratKuasaUnits already filters for empty ref+engine in Odoo.
                // Both will be null here; check is a safety guard.
                $bothEmpty = empty($itemData['internal_reference']) && empty($itemData['engine_number']);

                // Match by odoo_lot_id first (survives lot renames), fallback to lot_number
                $existing = null;
                if ($odooLotId) {
                    $existing = Item::where('odoo_lot_id', $odooLotId)->first();
                    $activeOdooIds[] = $odooLotId;
                }
                if (!$existing) {
                    $existing = Item::where('lot_number', $itemData['lot_number'])->first();
                }

                if ($existing) {
                    $wasTracked = $existing->surat_kuasa_tracked;

                    // Persistence: keep tracking if already tracked.
                    // Tier 1 returns units with both fields empty; Tier 2 returns recently created
                    // lots (create_date <= 30 days) regardless of fill state. Both are valid to track.
                    if ($wasTracked || $bothEmpty || !empty($itemData['odoo_lot_id'])) {
                        $existing->surat_kuasa_tracked = true;
                    }

                    // Store odoo_lot_id for stable future matching
                    if ($odooLotId && !$existing->odoo_lot_id) {
                        $existing->odoo_lot_id = $odooLotId;
                    }

                    $existing->on_hand_quantity = 0;
                    $existing->is_on_hand = true;
                    $existing->is_order_only = false;

                    if (!empty($itemData['product']) && $existing->product !== $itemData['product'])
                        $existing->product = $itemData['product'];
                    if (isset($itemData['vehicle_category']) && $existing->vehicle_category !== $itemData['vehicle_category'])
                        $existing->vehicle_category = $itemData['vehicle_category'];
                    if (!empty($itemData['year']) && $existing->year !== $itemData['year'])
                        $existing->year = $itemData['year'];
                    if (!empty($itemData['location']))
                        $existing->location = $itemData['location'];
                    if (isset($itemData['bbn']) && $existing->bbn !== $itemData['bbn'])
                        $existing->bbn = $itemData['bbn'];
                    if (!empty($itemData['current_customer']) && $existing->current_customer !== $itemData['current_customer'])
                        $existing->current_customer = $itemData['current_customer'];

                    // Tier 2: If Odoo has ref/engine data and local DB is still empty, populate them now.
                    // This ensures early-filled units don't stay blank until Fast Sync runs.
                    if (empty($existing->internal_reference) && !empty($itemData['internal_reference']))
                        $existing->internal_reference = $itemData['internal_reference'];
                    if (empty($existing->engine_number) && !empty($itemData['engine_number']))
                        $existing->engine_number = $itemData['engine_number'];

                    if ($existing->isDirty()) {
                        $existing->save();
                        if ($existing->surat_kuasa_tracked && !$wasTracked) {
                            $syncedCount++;
                            $isReady = !empty($existing->internal_reference) && !empty($existing->engine_number);
                            $changedDetails[] = [
                                'lot_number'         => $existing->lot_number,
                                'product'            => $existing->product,
                                'internal_reference' => $existing->internal_reference,
                                'engine_number'      => $existing->engine_number,
                                'changes'            => [$isReady
                                    ? 'Tracking confirmed — data complete (Ready to Generate!)'
                                    : 'Tracking confirmed (awaiting No. Rangka & Mesin)'],
                                'is_ready'           => $isReady,
                                'status_label'       => $isReady ? 'Ready to Generate' : 'Awaiting Data',
                                'is_new'             => false,
                            ];
                        }
                    }
                } else {
                    // Brand new lot not in DB.
                    // All records returned by fetchSuratKuasaUnits() are pre-qualified:
                    // Tier 1 ensures ref+engine empty; Tier 2 ensures create_date <= 30 days.
                    // Save actual numbers if already filled (Tier 2 early-entry scenario).
                    $newItem = Item::create([
                        'odoo_lot_id'        => $odooLotId,
                        'lot_number'         => $itemData['lot_number'],
                        'product'            => $itemData['product'] ?? '',
                        'vehicle_category'   => $itemData['vehicle_category'] ?? null,
                        'year'               => $itemData['year'] ?? date('Y'),
                        'location'           => $itemData['location'] ?? '',
                        'bbn'                => $itemData['bbn'] ?? null,
                        'current_customer'   => $itemData['current_customer'] ?? null,
                        'internal_reference' => $itemData['internal_reference'] ?? null,
                        'engine_number'      => $itemData['engine_number'] ?? null,
                        'on_hand_quantity'   => 0,
                        'is_on_hand'         => true,
                        'is_order_only'      => false,
                        'is_vendor_rent'     => false,
                        'surat_kuasa_tracked' => true,
                    ]);
                    $syncedCount++;
                    $isReady = !empty($newItem->internal_reference) && !empty($newItem->engine_number);
                    $changedDetails[] = [
                        'lot_number'         => $newItem->lot_number,
                        'product'            => $newItem->product,
                        'internal_reference' => $newItem->internal_reference,
                        'engine_number'      => $newItem->engine_number,
                        'changes'            => [$isReady
                            ? 'New unit tracked with data complete (Ready to Generate!)'
                            : 'New staging unit tracked (awaiting No. Rangka & Mesin)'],
                        'is_ready'           => $isReady,
                        'status_label'       => $isReady ? 'Ready to Generate' : 'Awaiting Data',
                        'is_new'             => true,
                    ];
                }
            }

            // Auto-reconcile (branch-scoped): remove SK tracking for units no longer in Odoo's
            // strict discovery list. Only removes items with NO data filled yet to avoid
            // accidentally removing units that are partially updated.
            $activeOdooIds = array_filter($activeOdooIds);
            $activeLotNumbers = collect($records)->pluck('lot_number')->filter()->toArray();

            if (!empty($activeOdooIds)) {
                Item::forUserBranch()
                    ->where('surat_kuasa_tracked', true)
                    ->whereNotNull('odoo_lot_id')
                    ->whereNotIn('odoo_lot_id', $activeOdooIds)
                    ->whereNull('internal_reference')
                    ->whereNull('engine_number')
                    ->update(['surat_kuasa_tracked' => false]);
            }

            if (!empty($activeLotNumbers)) {
                Item::forUserBranch()
                    ->where('surat_kuasa_tracked', true)
                    ->whereNull('odoo_lot_id')
                    ->whereNotIn('lot_number', $activeLotNumbers)
                    ->whereNull('internal_reference')
                    ->whereNull('engine_number')
                    ->update(['surat_kuasa_tracked' => false]);
            }

            $totalSK = $this->countTrackedSK();
            $readySK = $this->countReadySK();
            $pendingSK = $totalSK - $readySK;

            $msg = ($syncedCount > 0)
                ? "Sync completed! {$syncedCount} new unit(s) added to tracking. ({$readySK}/{$totalSK} units ready to generate Surat Kuasa)"
                : "Sync completed! No new pending units found in Odoo. ({$readySK}/{$totalSK} units ready to generate Surat Kuasa)";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'updated_count' => $syncedCount,
                'total_sk' => $totalSK,
                'ready_sk' => $readySK,
                'pending_sk' => $pendingSK,
                'changes' => $changedDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Surat Kuasa Sync failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Fast Sync Odoo: Detects updates to No. Rangka & No. Mesin for currently tracked SK units.
     *
     * KEY DESIGN: Does NOT use the discovery domain (empty ref/engine filter).
     * Instead fetches from Odoo by the stable odoo_lot_id of each tracked unit.
     * Handles lot renames: when Anna fills No. Rangka in Odoo, the lot name changes
     * (e.g. "00161-GRANMAX" -> "00921-GRANMAX") but the Odoo lot ID stays the same.
     * We detect the rename and update lot_number in our DB, keeping the same record + tracking history.
     */
    public function fastSync(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['error' => 'Unauthenticated or session expired'], 401);
        }

        try {
            $odooService = app(OdooService::class);

            // Get all currently tracked SK items (branch-scoped)
            $trackedItems = Item::forUserBranch()
                ->where('surat_kuasa_tracked', true)
                ->get();

            if ($trackedItems->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fast Sync: No tracked Surat Kuasa units found. Run "Sync Odoo Data" first to discover units.',
                    'updated_count' => 0,
                    'total_sk' => 0,
                    'ready_sk' => 0,
                    'pending_sk' => 0,
                    'changes' => [],
                ]);
            }

            $itemsWithOdooId = $trackedItems->filter(fn($i) => !empty($i->odoo_lot_id));
            $itemsWithoutOdooId = $trackedItems->filter(fn($i) => empty($i->odoo_lot_id));

            $odooIds = $itemsWithOdooId->pluck('odoo_lot_id')->unique()->values()->toArray();

            // Fetch current data from Odoo by stable lot IDs
            $odooData = [];
            if (!empty($odooIds)) {
                $res = $odooService->fetchSuratKuasaByOdooIds($odooIds);
                if (!$res['success']) {
                    return response()->json(['success' => false, 'message' => 'Fast Sync failed: ' . ($res['message'] ?? 'Unknown error')], 500);
                }
                $odooData = $res['data']; // keyed by odoo_lot_id
            }

            // For legacy items without odoo_lot_id: search by lot_name and populate odoo_lot_id
            $legacyOdooData = [];
            $legacyLotNames = $itemsWithoutOdooId->pluck('lot_number')->toArray();
            if (!empty($legacyLotNames)) {
                try {
                    $legacyIds = $odooService->execute('stock.lot', 'search', [[['name', 'in', $legacyLotNames]]]);
                    if (!empty($legacyIds)) {
                        $legacyRes = $odooService->fetchSuratKuasaByOdooIds($legacyIds);
                        if ($legacyRes['success']) {
                            foreach ($legacyRes['data'] as $row) {
                                $legacyOdooData[$row['lot_number']] = $row;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('SK Fast Sync: could not resolve legacy lot IDs: ' . $e->getMessage());
                }
            }

            $updatedCount = 0;
            $changedDetails = [];

            // --- Process items WITH odoo_lot_id (primary rename-safe path) ---
            foreach ($itemsWithOdooId as $item) {
                $odooRow = $odooData[$item->odoo_lot_id] ?? null;
                if (!$odooRow)
                    continue;

                $lotChanges = [];
                $wasReady = !empty($item->internal_reference) && !empty($item->engine_number);

                // Detect lot RENAME (Odoo name changed, same lot ID)
                if (!empty($odooRow['lot_number']) && $odooRow['lot_number'] !== $item->lot_number) {
                    $lotChanges[] = 'Lot renamed: ' . $item->lot_number . ' → ' . $odooRow['lot_number'];
                    $item->lot_number = $odooRow['lot_number'];
                }

                // Update No. Rangka (rule 3: partial fill OK)
                if (!empty($odooRow['internal_reference']) && $item->internal_reference !== $odooRow['internal_reference']) {
                    $lotChanges[] = 'No. Rangka: ' . $odooRow['internal_reference'];
                    $item->internal_reference = $odooRow['internal_reference'];
                }

                // Update No. Mesin (rule 3: partial fill OK)
                if (!empty($odooRow['engine_number']) && $item->engine_number !== $odooRow['engine_number']) {
                    $lotChanges[] = 'No. Mesin: ' . $odooRow['engine_number'];
                    $item->engine_number = $odooRow['engine_number'];
                }

                if (!empty($odooRow['product']) && $item->product !== $odooRow['product'])
                    $item->product = $odooRow['product'];
                if (isset($odooRow['vehicle_category']) && $item->vehicle_category !== $odooRow['vehicle_category'])
                    $item->vehicle_category = $odooRow['vehicle_category'];
                if (!empty($odooRow['year']) && $item->year !== $odooRow['year'])
                    $item->year = $odooRow['year'];
                if (isset($odooRow['bbn']) && $item->bbn !== $odooRow['bbn']) {
                    $lotChanges[] = 'BBN: ' . ($odooRow['bbn'] ?: 'No BBN on Odoo');
                    $item->bbn = $odooRow['bbn'];
                }
                if (!empty($odooRow['current_customer']) && $item->current_customer !== $odooRow['current_customer'])
                    $item->current_customer = $odooRow['current_customer'];

                if ($item->isDirty()) {
                    $item->save();
                    if (!empty($lotChanges)) {
                        $updatedCount++;
                        $isNowReady = !empty($item->internal_reference) && !empty($item->engine_number);
                        $changedDetails[] = [
                            'lot_number' => $item->lot_number,
                            'product' => $item->product,
                            'internal_reference' => $item->internal_reference,
                            'engine_number' => $item->engine_number,
                            'changes' => $lotChanges,
                            'is_ready' => $isNowReady,
                            'status_label' => $isNowReady ? 'Ready to Generate' : 'Awaiting Data',
                            'is_new' => false,
                        ];
                    }
                }
            }

            // --- Process legacy items WITHOUT odoo_lot_id ---
            foreach ($itemsWithoutOdooId as $item) {
                $odooRow = $legacyOdooData[$item->lot_number] ?? null;
                if (!$odooRow)
                    continue;

                $lotChanges = [];

                // Populate odoo_lot_id for future rename tracking
                if (!empty($odooRow['odoo_lot_id'])) {
                    $item->odoo_lot_id = $odooRow['odoo_lot_id'];
                }

                if (!empty($odooRow['internal_reference']) && $item->internal_reference !== $odooRow['internal_reference']) {
                    $lotChanges[] = 'No. Rangka: ' . $odooRow['internal_reference'];
                    $item->internal_reference = $odooRow['internal_reference'];
                }
                if (!empty($odooRow['engine_number']) && $item->engine_number !== $odooRow['engine_number']) {
                    $lotChanges[] = 'No. Mesin: ' . $odooRow['engine_number'];
                    $item->engine_number = $odooRow['engine_number'];
                }
                if (!empty($odooRow['product']) && $item->product !== $odooRow['product'])
                    $item->product = $odooRow['product'];
                if (isset($odooRow['vehicle_category']) && $item->vehicle_category !== $odooRow['vehicle_category'])
                    $item->vehicle_category = $odooRow['vehicle_category'];
                if (!empty($odooRow['year']) && $item->year !== $odooRow['year'])
                    $item->year = $odooRow['year'];
                if (isset($odooRow['bbn']) && $item->bbn !== $odooRow['bbn']) {
                    $lotChanges[] = 'BBN: ' . ($odooRow['bbn'] ?: 'No BBN on Odoo');
                    $item->bbn = $odooRow['bbn'];
                }

                if ($item->isDirty()) {
                    $item->save();
                    if (!empty($lotChanges)) {
                        $updatedCount++;
                        $isNowReady = !empty($item->internal_reference) && !empty($item->engine_number);
                        $changedDetails[] = [
                            'lot_number' => $item->lot_number,
                            'product' => $item->product,
                            'internal_reference' => $item->internal_reference,
                            'engine_number' => $item->engine_number,
                            'changes' => $lotChanges,
                            'is_ready' => $isNowReady,
                            'status_label' => $isNowReady ? 'Ready to Generate' : 'Awaiting Data',
                            'is_new' => false,
                        ];
                    }
                }
            }

            $totalSK = $this->countTrackedSK();
            $readySK = $this->countReadySK();
            $pendingSK = $totalSK - $readySK;

            $msg = ($updatedCount > 0)
                ? "Fast Sync completed! {$updatedCount} unit(s) updated. ({$readySK}/{$totalSK} units ready to generate Surat Kuasa)"
                : "Fast Sync completed! No new No. Rangka/Mesin updates in Odoo. ({$readySK}/{$totalSK} units ready to generate Surat Kuasa)";

            return response()->json([
                'success' => true,
                'message' => $msg,
                'updated_count' => $updatedCount,
                'total_sk' => $totalSK,
                'ready_sk' => $readySK,
                'pending_sk' => $pendingSK,
                'changes' => $changedDetails,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Fast Sync failed: ' . $e->getMessage()], 500);
        }
    }

    /** Count total tracked SK units (branch-scoped, non-vendor-rent, qty=0) */
    private function countTrackedSK(): int
    {
        return Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where('surat_kuasa_tracked', true)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')->orWhere('is_vendor_rent', false); })
            ->count();
    }

    /** Count SK units with both No. Rangka AND No. Mesin filled (ready to generate) */
    private function countReadySK(): int
    {
        return Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where('surat_kuasa_tracked', true)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')->orWhere('is_vendor_rent', false); })
            ->whereNotNull('internal_reference')->where('internal_reference', '!=', '')
            ->whereNotNull('engine_number')->where('engine_number', '!=', '')
            ->count();
    }

    /**
     * Generate & Print Surat Kuasa document
     */

    public function print(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired. Please enter secondary password.');
        }

        $item = Item::findOrFail($id);

        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot print: Both No Rangka (Internal Reference) and No Mesin (Engine Number) must be populated in Odoo before printing.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        // Printable inputs from query string or modal
        $docNo = $request->query('doc_no', self::generateNextDocNo());
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model') ?: ($item->vehicle_category ?: 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $rawDate = $request->query('date');
        $printDate = self::formatIndonesianDate($rawDate);
        $cleanProduct = self::cleanProductName($item->product);

        // Dynamic settings
        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        return view('surat_kuasa.print', [
            'item' => $item,
            'cleanProduct' => $cleanProduct,
            'noRangka' => $noRangka,
            'noMesin' => $noMesin,
            'docNo' => $docNo,
            'penerimaNama' => $penerimaNama,
            'penerimaAlamat' => $penerimaAlamat,
            'jenisModel' => $jenisModel,
            'warna' => $warna,
            'tahun' => $tahun,
            'printDate' => $printDate,
            'settings' => $settings,
        ]);
    }

    /**
     * Generate & Download Surat Kuasa Word Document (.docx)
     */
    public function downloadDocx(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired.');
        }

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot generate document: Both No Rangka and No Mesin must be populated.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $docNo = $request->query('doc_no', self::generateNextDocNo());
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model') ?: ($item->vehicle_category ?: 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $rawDate = $request->query('date');
        $printDate = self::formatIndonesianDate($rawDate);
        $cleanProduct = self::cleanProductName($item->product);

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        // Section Margins matching SURAT KELUAR 2026 -2.docx (Top: 2.54cm/1440, Bottom: 1.8cm/1020, Left: 2.0cm/1134, Right: 2.0cm/1139)
        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginBottom' => 1020,
            'marginLeft' => 1134,
            'marginRight' => 1139,
            'headerHeight' => 720,
        ]);

        // Gap for physical KOP SURAT header (3 empty lines before title)
        $section->addTextBreak(3);

        // Title Header (15pt BOLD UNDERLINED centered matching SURAT KELUAR 2026 -2.docx)
        $section->addText('SURAT KUASA', ['name' => 'Times New Roman', 'size' => 15, 'bold' => true, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        $section->addText($docNo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 140]);

        $section->addText('Yang bertanda tangan dibawah ini:', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 40]);

        // Pemberi Kuasa Table
        $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15];
        $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

        $table1 = $section->addTable($tableStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table1->addRow();
        $table1->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table1->addCell(6500)->addText($settings['pemberi_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addTextBreak(1);
        $section->addText('Dengan ini memberi kuasa kepada :', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Penerima Kuasa Table
        $table2 = $section->addTable($tableStyle);
        $table2->addRow();
        $table2->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(6500)->addText($penerimaNama ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table2->addRow();
        $table2->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table2->addCell(6500)->addText($penerimaAlamat ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addTextBreak(1);
        $section->addText('Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 0, 'spaceAfter' => 40]);

        // Vehicle Details Table
        $table3 = $section->addTable($tableStyle);
        $table3->addRow();
        $table3->addCell(2200)->addText('Nama Pemilik', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($settings['pemilik_nama'], ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);

        if (!empty($settings['pemilik_alamat'])) {
            $table3->addRow();
            $table3->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
            $table3->addCell(6500)->addText($settings['pemilik_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        }

        $table3->addRow();
        $table3->addCell(2200)->addText('Merk/Type', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($cleanProduct, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Jenis / Model', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($jenisModel, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Tahun', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($tahun, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('No. Rangka', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($noRangka, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('No. Mesin', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($noMesin, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $table3->addRow();
        $table3->addCell(2200)->addText('Warna', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
        $table3->addCell(6500)->addText($warna, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $section->addText('Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 120, 'spaceAfter' => 140]);

        // Signatures Table - Clean 3-Column Layout
        $sigTable = $section->addTable(['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15]);

        // Row 1: Dates & Titles
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

        // Row 2: Names & Positions (Underlined)
        $sigTable->addRow();
        $c1 = $sigTable->addCell(3000);
        $c1->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c1->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $c2 = $sigTable->addCell(3000);
        $c2->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], $pStyle);
        $c2->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

        $c3 = $sigTable->addCell(3000);
        if ($penerimaNama) {
            $c3->addText('( ' . $penerimaNama . ' )', ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        } else {
            $c3->addText('(                                          )', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
        }

        $cleanDoc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $docNo);
        $cleanLot = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number);
        $filename = "Surat_Kuasa_{$cleanDoc}_{$cleanLot}.docx";

        $tempPath = storage_path('app/temp_documents');
        if (!file_exists($tempPath)) {
            mkdir($tempPath, 0777, true);
        }
        $tempFile = $tempPath . '/' . uniqid('sk_') . '.docx';

        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $fileSize = filesize($tempFile);

        $isReprint = $request->boolean('reprint') || SuratKuasaLog::where('item_id', $item->id)->where('doc_no', $docNo)->exists();

        if (!$isReprint) {
            // Log Surat Kuasa generation
            SuratKuasaLog::create([
                'item_id' => $item->id,
                'doc_no' => $docNo,
                'lot_number' => $item->lot_number,
                'product' => $cleanProduct,
                'customer' => $item->current_customer,
                'penerima_nama' => $penerimaNama,
                'penerima_alamat' => $penerimaAlamat,
                'jenis_model' => $jenisModel,
                'warna' => $warna,
                'tahun' => $tahun,
                'no_rangka' => $noRangka,
                'no_mesin' => $noMesin,
                'print_date' => $printDate,
                'action_type' => 'word',
                'generated_by_id' => auth()->id(),
                'generated_by_name' => auth()->check() ? auth()->user()->name : 'System',
            ]);
            self::advanceDocSequence($docNo);
        }

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Generate & Download Surat Kuasa PDF Document (.pdf)
     */
    public function downloadPdf(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Session expired.');
        }

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return redirect()->route('surat-kuasa.index')->with('error', 'Cannot generate PDF: Both No Rangka and No Mesin must be populated.');
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $docNo = $request->query('doc_no', self::generateNextDocNo());
        $penerimaNama = $request->query('penerima_nama', '');
        $penerimaAlamat = $request->query('penerima_alamat', '');
        $jenisModel = $request->query('jenis_model') ?: ($item->vehicle_category ?: 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $rawDate = $request->query('date');
        $printDate = self::formatIndonesianDate($rawDate);
        $cleanProduct = self::cleanProductName($item->product);

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        $html = view('surat_kuasa.print', [
            'item' => $item,
            'cleanProduct' => $cleanProduct,
            'noRangka' => $noRangka,
            'noMesin' => $noMesin,
            'docNo' => $docNo,
            'penerimaNama' => $penerimaNama,
            'penerimaAlamat' => $penerimaAlamat,
            'jenisModel' => $jenisModel,
            'warna' => $warna,
            'tahun' => $tahun,
            'printDate' => $printDate,
            'settings' => $settings,
        ])->render();

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $cleanDoc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $docNo);
        $cleanLot = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number);
        $filename = "Surat_Kuasa_{$cleanDoc}_{$cleanLot}.pdf";
        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Send Surat Kuasa document via Email
     */
    public function sendEmail(Request $request, $id)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired. Please re-authenticate.'], 401);
        }

        $request->validate([
            'recipient_email' => 'required|string',
            'file_format' => 'required|in:pdf,docx',
        ]);

        $item = Item::findOrFail($id);
        $isItAdmin = auth()->check() && auth()->user()->isItAdmin();
        $noRangka = trim((string) $item->internal_reference);
        $noMesin = trim((string) $item->engine_number);

        if (!$isItAdmin && (empty($noRangka) || empty($noMesin))) {
            return response()->json(['success' => false, 'message' => 'Cannot email document: Both No Rangka and No Mesin must be populated.'], 422);
        }

        if (empty($noRangka))
            $noRangka = '[EMPTY - IT ADMIN TEST]';
        if (empty($noMesin))
            $noMesin = '[EMPTY - IT ADMIN TEST]';

        $format = $request->input('file_format', 'pdf');
        $recipientEmail = trim($request->input('recipient_email'));
        $penerimaNama = trim((string) $request->input('penerima_nama'));
        $penerimaAlamat = trim((string) $request->input('penerima_alamat'));
        $docNo = $request->input('doc_no') ?: self::generateNextDocNo();
        $jenisModel = $request->input('jenis_model') ?: ($item->vehicle_category ?: 'Mobil Barang');
        $warna = $item->color ?: 'Putih';
        $tahun = $item->year ?: date('Y');
        $rawDate = $request->input('date');
        $printDate = self::formatIndonesianDate($rawDate);
        $cleanProduct = self::cleanProductName($item->product);
        $customSubject = $request->input('subject', 'Surat Kuasa Document - ' . $item->lot_number);
        $customMessage = $request->input('message', 'Please find attached the Surat Kuasa document for vehicle unit ' . $item->lot_number . '.');

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        try {
            $tempDir = storage_path('app/temp_documents');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0777, true);
            }

            $cleanDoc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $docNo);
            $cleanLot = preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number);

            if ($format === 'docx') {
                $filename = "Surat_Kuasa_{$cleanDoc}_{$cleanLot}.docx";
                $filePath = $tempDir . '/' . $filename;

                $phpWord = new \PhpOffice\PhpWord\PhpWord();
                $phpWord->setDefaultFontName('Times New Roman');
                $phpWord->setDefaultFontSize(12);

                $section = $phpWord->addSection([
                    'marginTop' => 1440,
                    'marginBottom' => 1020,
                    'marginLeft' => 1134,
                    'marginRight' => 1139,
                    'headerHeight' => 720,
                ]);

                // Gap for physical KOP SURAT header (3 empty lines before title)
                $section->addTextBreak(3);

                $section->addText('SURAT KUASA', ['name' => 'Times New Roman', 'size' => 15, 'bold' => true, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                $section->addText($docNo, ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 140]);

                $section->addText('Yang bertanda tangan dibawah ini:', ['name' => 'Times New Roman', 'size' => 12], ['spaceAfter' => 40]);

                $tableStyle = ['borderSize' => 0, 'borderColor' => 'FFFFFF', 'cellMargin' => 15];
                $pStyle = ['spaceAfter' => 0, 'spaceBefore' => 0];

                $table1 = $section->addTable($tableStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_1_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_1_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_2_nama'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Jabatan', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_2_jabatan'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addRow();
                $table1->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table1->addCell(6500)->addText($settings['pemberi_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Dengan ini memberi kuasa kepada :', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 80, 'spaceAfter' => 40]);

                $table2 = $section->addTable($tableStyle);
                $table2->addRow();
                $table2->addCell(2200)->addText('Nama', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(6500)->addText($penerimaNama ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addRow();
                $table2->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table2->addCell(6500)->addText($penerimaAlamat ?: '....................................................................................', ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Untuk mengurus Surat Tanda Nomor Kendaraan ( STNK ) &amp; BPKB', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 80, 'spaceAfter' => 40]);

                $table3 = $section->addTable($tableStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Nama Pemilik', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($settings['pemilik_nama'], ['name' => 'Times New Roman', 'size' => 12, 'bold' => true], $pStyle);
                if (!empty($settings['pemilik_alamat'])) {
                    $table3->addRow();
                    $table3->addCell(2200)->addText('Alamat', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                    $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                    $table3->addCell(6500)->addText($settings['pemilik_alamat'], ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                }
                $table3->addRow();
                $table3->addCell(2200)->addText('Merk/Type', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($cleanProduct, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Jenis / Model', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($jenisModel, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Tahun', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($tahun, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('No. Rangka', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($noRangka, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('No. Mesin', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($noMesin, ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addRow();
                $table3->addCell(2200)->addText('Warna', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(300)->addText(':', ['name' => 'Times New Roman', 'size' => 12], $pStyle);
                $table3->addCell(6500)->addText($warna, ['name' => 'Times New Roman', 'size' => 12], $pStyle);

                $section->addText('Demikian Surat Kuasa ini kami buat untuk dipergunakan sebagaimana mestinya.', ['name' => 'Times New Roman', 'size' => 12], ['spaceBefore' => 120, 'spaceAfter' => 140]);

                // Signatures Table - Clean 3-Column Layout
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
                if ($penerimaNama) {
                    $c3->addText('( ' . $penerimaNama . ' )', ['name' => 'Times New Roman', 'size' => 12, 'underline' => 'single'], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                } else {
                    $c3->addText('(                                          )', ['name' => 'Times New Roman', 'size' => 12], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 0]);
                }

                $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($filePath);
                $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            } else {
                $filename = 'Surat_Kuasa_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item->lot_number) . '.pdf';
                $filePath = $tempDir . '/' . $filename;

                $html = view('surat_kuasa.print', [
                    'item' => $item,
                    'cleanProduct' => $cleanProduct,
                    'noRangka' => $noRangka,
                    'noMesin' => $noMesin,
                    'docNo' => $docNo,
                    'penerimaNama' => $penerimaNama,
                    'penerimaAlamat' => $penerimaAlamat,
                    'jenisModel' => $jenisModel,
                    'warna' => $warna,
                    'tahun' => $tahun,
                    'printDate' => $printDate,
                    'settings' => $settings,
                ])->render();

                $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                file_put_contents($filePath, $dompdf->output());
                $mimeType = 'application/pdf';
            }

            // Parse multiple email recipients separated by comma or semicolon
            $recipients = array_map('trim', preg_split('/[,;]+/', $recipientEmail));
            $recipients = array_values(array_filter($recipients, function ($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                return response()->json(['success' => false, 'message' => 'Please configure a valid recipient email address.'], 422);
            }

            \Illuminate\Support\Facades\Mail::raw($customMessage, function ($mail) use ($recipients, $customSubject, $filePath, $filename, $mimeType) {
                foreach ($recipients as $recipient) {
                    $mail->to($recipient);
                }
                $mail->subject($customSubject)
                    ->attach($filePath, ['as' => $filename, 'mime' => $mimeType]);
            });

            @unlink($filePath);

            // Log Surat Kuasa email generation
            SuratKuasaLog::create([
                'item_id' => $item->id,
                'doc_no' => $docNo,
                'lot_number' => $item->lot_number,
                'product' => $cleanProduct,
                'customer' => $item->current_customer,
                'penerima_nama' => $penerimaNama,
                'penerima_alamat' => $penerimaAlamat,
                'jenis_model' => $jenisModel,
                'warna' => $warna,
                'tahun' => $tahun,
                'no_rangka' => $noRangka,
                'no_mesin' => $noMesin,
                'print_date' => $printDate,
                'action_type' => 'email',
                'recipient_email' => $recipientEmail,
                'generated_by_id' => auth()->id(),
                'generated_by_name' => auth()->check() ? auth()->user()->name : 'System',
            ]);
            self::advanceDocSequence($docNo);

            return response()->json(['success' => true, 'message' => "Surat Kuasa document successfully emailed to {$recipientEmail}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display generated Surat Kuasa Report log list
     */
    public function report(Request $request)
    {
        if (!auth()->user()->hasMenuPermission('surat-kuasa')) {
            abort(403, 'Unauthorized access to Surat Kuasa Report.');
        }

        if (!$this->checkSuratKuasaSession()) {
            return view('surat_kuasa.report', [
                'authenticated' => false,
                'session_expired' => session('session_expired', false)
            ]);
        }

        $search = $request->input('search');
        $query = SuratKuasaLog::with('item', 'generatedBy')->orderBy('id', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('doc_no', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhere('product', 'like', "%{$search}%")
                    ->orWhere('penerima_nama', 'like', "%{$search}%")
                    ->orWhere('customer', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        $settings = [
            'pemberi_1_nama' => Setting::get('surat_kuasa_pemberi_1_nama', 'Suzanna Caroline'),
            'pemberi_1_jabatan' => Setting::get('surat_kuasa_pemberi_1_jabatan', 'General Manager'),
            'pemberi_2_nama' => Setting::get('surat_kuasa_pemberi_2_nama', 'Aldian Prayoga Darwis'),
            'pemberi_2_jabatan' => Setting::get('surat_kuasa_pemberi_2_jabatan', 'Fleet Operation Manager'),
            'pemberi_alamat' => Setting::get('surat_kuasa_pemberi_alamat', 'Jl. Daan Mogot KM 1 No. 99 Jakarta Barat 11510'),
            'pemilik_nama' => Setting::get('surat_kuasa_pemilik_nama', 'PT Surya Darma Perkasa'),
            'pemilik_alamat' => Setting::get('surat_kuasa_pemilik_alamat', 'Kel. Duri Kepa Kec. Kebon Jeruk Kota Jakarta Barat'),
        ];

        return view('surat_kuasa.report', [
            'authenticated' => true,
            'logs' => $logs,
            'settings' => $settings,
            'search' => $search
        ]);
    }

    /**
     * Delete a single Surat Kuasa generation log (IT Admin only)
     */
    public function deleteLog(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only IT Admin can delete logs.'], 403);
        }

        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $log = SuratKuasaLog::findOrFail($id);
        $log->delete();

        return response()->json(['success' => true, 'message' => 'Log record deleted successfully.']);
    }

    /**
     * Clear all Surat Kuasa generation logs (IT Admin only)
     */
    public function clearAllLogs(Request $request)
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized: Only IT Admin can clear logs.'], 403);
        }

        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        SuratKuasaLog::truncate();

        return response()->json(['success' => true, 'message' => 'All Surat Kuasa logs cleared successfully.']);
    }

    /**
     * Export Surat Kuasa list to CSV
     */
    public function export(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return redirect()->route('surat-kuasa.index');
        }

        $items = Item::forUserBranch()
            ->where('on_hand_quantity', 0)
            ->where(function ($q) {
                $q->whereNull('is_vendor_rent')
                    ->orWhere('is_vendor_rent', false);
            })
            ->where(function ($q) {
                $q->whereNull('internal_reference')
                    ->orWhere('internal_reference', '');
            })
            ->where(function ($q) {
                $q->whereNull('engine_number')
                    ->orWhere('engine_number', '');
            })
            ->orderBy('product')
            ->get();

        $filename = 'surat_kuasa_units_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Lot Serial', 'No Rangka (Internal Ref)', 'No Mesin (Engine No)', 'Merk/Type', 'Warna', 'Tahun', 'Location', 'Status']);

            foreach ($items as $item) {
                $noRangka = $item->internal_reference;
                $noMesin = $item->engine_number;
                $isReady = !empty($noRangka) && !empty($noMesin);

                fputcsv($file, [
                    $item->lot_number,
                    $noRangka ?: 'EMPTY',
                    $noMesin ?: 'EMPTY',
                    $item->product,
                    $item->color ?: '-',
                    $item->year ?: '-',
                    $item->bbn ?: 'No BBN on Odoo',
                    $isReady ? 'Ready to Print' : 'Incomplete Odoo Data',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Update Surat Kuasa password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:4'
        ]);

        Setting::set('surat_kuasa_password', Hash::make($request->input('password')));

        return redirect()->to(url('/import#surat-kuasa'))->with('success', 'Surat Kuasa password updated successfully.');
    }

    /**
     * Send a test email to verify SMTP configuration
     */
    public function testEmail(Request $request)
    {
        if (!$this->checkSuratKuasaSession()) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        try {
            $recipientEmail = \App\Models\Setting::get('surat_kuasa_default_recipient_email', '');

            // Parse multiple email recipients separated by comma or semicolon
            $recipients = array_map('trim', preg_split('/[,;]+/', $recipientEmail));
            $recipients = array_values(array_filter($recipients, function ($e) {
                return filter_var($e, FILTER_VALIDATE_EMAIL);
            }));

            if (empty($recipients)) {
                return response()->json(['success' => false, 'message' => 'Please configure a valid Default Recipient Email Address in Settings.'], 422);
            }

            \Illuminate\Support\Facades\Mail::raw('This is a test email from the SDP Dashboard Surat Kuasa module to verify SMTP settings are working correctly.', function ($mail) use ($recipients) {
                foreach ($recipients as $recipient) {
                    $mail->to($recipient);
                }
                $mail->subject('Test Email - SDP Dashboard Surat Kuasa');
            });

            $recipientStr = implode(', ', $recipients);
            return response()->json(['success' => true, 'message' => "Test email successfully sent to {$recipientStr}."]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send test email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reset the auto_sk_sent flag for an item (IT Admin only)
     * Allows the auto-scheduler to re-process this unit on the next run.
     */
    public function resetAutoFlag(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $item = Item::findOrFail($id);
        $item->auto_sk_sent = null;
        $item->save();

        // Also remove from SuratKuasaLog so auto-generate picks it up cleanly
        SuratKuasaLog::where('item_id', $item->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "Auto SK flag reset for unit {$item->lot_number}. It will be re-processed on the next auto-generate run.",
        ]);
    }

    /**
     * Show Surat Kuasa System & Automation Logs (IT Admin only)
     */
    public function systemLogs(Request $request)
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            abort(403, 'Unauthorized access. IT Admin role required.');
        }

        $authenticated = $this->checkSuratKuasaSession();

        $search = trim((string) $request->input('search', ''));
        $level = trim((string) $request->input('level', ''));
        $eventType = trim((string) $request->input('event_type', ''));

        $query = SuratKuasaSystemLog::orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('lot_number', 'like', "%{$search}%")
                    ->orWhere('doc_no', 'like', "%{$search}%");
            });
        }

        if (!empty($level) && in_array($level, ['error', 'warning', 'success', 'info'])) {
            $query->where('level', $level);
        }

        if (!empty($eventType)) {
            $query->where('event_type', $eventType);
        }

        $logs = $query->paginate(30)->appends($request->query());

        $stats = [
            'total' => SuratKuasaSystemLog::count(),
            'error' => SuratKuasaSystemLog::where('level', 'error')->count(),
            'warning' => SuratKuasaSystemLog::where('level', 'warning')->count(),
            'success' => SuratKuasaSystemLog::where('level', 'success')->count(),
            'info' => SuratKuasaSystemLog::where('level', 'info')->count(),
        ];

        return view('surat_kuasa.logs', compact('logs', 'stats', 'search', 'level', 'eventType', 'authenticated'));
    }

    /**
     * Delete a single system log entry (IT Admin only)
     */
    public function deleteSystemLog($id)
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $log = SuratKuasaSystemLog::findOrFail($id);
        $log->delete();

        return response()->json(['success' => true, 'message' => 'Log entry deleted successfully.']);
    }

    /**
     * Clear all system logs (IT Admin only)
     */
    public function clearSystemLogs()
    {
        if (!auth()->check() || !auth()->user()->isItAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        SuratKuasaSystemLog::truncate();

        return response()->json(['success' => true, 'message' => 'All system logs cleared successfully.']);
    }

    /**
     * Check if current Surat Kuasa secondary authentication is valid (15 minutes inactivity limit)
     */
    private function checkSuratKuasaSession(): bool
    {
        if (!session('surat_kuasa_authenticated')) {
            return false;
        }

        $lastAuth = session('surat_kuasa_authenticated_at');
        $timeoutSeconds = 15 * 60; // 15 minutes

        if (!$lastAuth || (now()->timestamp - (int) $lastAuth) > $timeoutSeconds) {
            session()->forget(['surat_kuasa_authenticated', 'surat_kuasa_authenticated_at']);
            session()->flash('session_expired', true);
            return false;
        }

        session(['surat_kuasa_authenticated_at' => now()->timestamp]);
        return true;
    }
}
