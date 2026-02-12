<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\OdooService;
use App\Services\SummaryGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncOdooScheduled extends Command
{
    protected $signature = 'odoo:sync {--force : Force sync even if disabled}';
    protected $description = 'Sync inventory data from Odoo API';

    public function handle(): int
    {
        $this->info('Starting Odoo sync...');
        
        // Check if sync is enabled (unless --force)
        if (!$this->option('force')) {
            $enabled = Setting::getValue('odoo_schedule_enabled', 'false');
            if ($enabled !== 'true') {
                $this->warn('Odoo auto-sync is disabled. Use --force to override.');
                return 0;
            }
        }

        try {
            $odooService = app(OdooService::class);
            $generator = app(SummaryGenerator::class);

            $this->info('Fetching data from Odoo...');
            $result = $odooService->fetchViaExport();

            if (!$result['success']) {
                $this->error('Failed to fetch from Odoo: ' . ($result['error'] ?? 'Unknown error'));
                Log::error('Odoo scheduled sync failed', ['error' => $result['error'] ?? 'Unknown']);
                return 1;
            }

            $this->info("Processing {$result['count']} items...");
            $processedData = $generator->generate($result['data']);
            $generator->saveToDatabase($processedData['items'], $processedData['summary'], 'odoo_scheduled');

            // Enrich in-service items with repair order data
            $this->info('Enriching in-service items with repair data...');
            $importController = new \App\Http\Controllers\ImportController();
            $importController->enrichWithRepairData($odooService);

            // Update last sync time
            Setting::setValue('odoo_last_sync', now()->toISOString());

            $this->info("✓ Successfully synced {$result['count']} items from Odoo");
            Log::info('Odoo scheduled sync completed', ['count' => $result['count']]);

            return 0;
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            Log::error('Odoo scheduled sync exception', ['exception' => $e->getMessage()]);
            return 1;
        }
    }
}
