<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Setting;
use App\Services\OdooService;
use App\Services\SummaryGenerator;
use App\Models\ImportLog;

class ImportController extends Controller
{
    /**
     * Show import data page
     */
    public function index()
    {
        $odooConfig = Setting::getOdooConfig();
        return view('import', compact('odooConfig'));
    }

    /**
     * Handle Excel file upload (existing functionality)
     */
    public function uploadExcel(Request $request, SummaryGenerator $generator)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('file');
        
        try {
            $result = $generator->generate($file);
            $originalFilename = $file->getClientOriginalName();
            $generator->saveToDatabase($result['items'], $result['summary'], 'excel', $originalFilename);
            
            return redirect()->back()->with('success', 'Excel data imported successfully! ' . count($result['items']) . ' items processed.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Save Odoo configuration
     */
    public function saveOdooConfig(Request $request)
    {
        $request->validate([
            'odoo_url' => 'required|url',
            'odoo_db' => 'required|string',
            'odoo_user' => 'required|string',
            'odoo_password' => 'required|string',
        ]);

        Setting::set('odoo_url', $request->input('odoo_url'));
        Setting::set('odoo_db', $request->input('odoo_db'));
        Setting::set('odoo_user', $request->input('odoo_user'));
        Setting::set('odoo_password', $request->input('odoo_password'));

        return response()->json(['success' => true, 'message' => 'Configuration saved successfully.']);
    }

    /**
     * Test Odoo connection
     */
    public function testOdooConnection()
    {
        $odoo = new OdooService();
        $result = $odoo->testConnection();
        
        return response()->json($result);
    }

    /**
     * Sync data from Odoo using Option A (export_data API)
     */
    public function syncOdoo(SummaryGenerator $generator)
    {
        try {
            $odoo = new OdooService();
            
            // Use export_data API (Option A) for Excel parity
            $result = $odoo->fetchViaExport();
            
            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Odoo fetch failed: ' . ($result['message'] ?? 'Unknown error')
                ]);
            }
            
            // Pass Excel-like data directly to SummaryGenerator
            // This reuses the same parsing logic as Excel import
            $processedData = $generator->generate($result['data']);
            
            // Save to database (same as Excel import)
            $generator->saveToDatabase($processedData['items'], $processedData['summary'], 'odoo_manual');
            
            // --- Repair Order Enrichment ---
            $this->enrichWithRepairData($odoo);
            
            return response()->json([
                'success' => true,
                'message' => "Synced {$result['count']} items from Odoo",
                'summary' => $processedData['summary']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Enrich in-service items with repair order data from Odoo.
     */
    public function enrichWithRepairData(OdooService $odoo): void
    {
        $serviceLocations = [
            \App\Constants\Location::SERVICE_EXTERNAL,
            \App\Constants\Location::SERVICE_INTERNAL,
            \App\Constants\Location::INSURANCE,
        ];

        // Find all in-service items
        $inServiceItems = \App\Models\Item::where(function ($q) use ($serviceLocations) {
            foreach ($serviceLocations as $loc) {
                $q->orWhere('location', 'like', $loc . '%');
            }
        })->whereNotNull('lot_number')->pluck('lot_number')->unique()->toArray();

        if (empty($inServiceItems)) {
            return;
        }

        // Resolve lot numbers to Odoo IDs
        $lotMap = $odoo->resolveLotIds($inServiceItems);

        if (empty($lotMap)) {
            return;
        }

        // Fetch active repair orders
        $repairData = $odoo->fetchRepairOrders($lotMap);

        // Update items with repair data
        foreach ($repairData as $lotNumber => $data) {
            \App\Models\Item::where('lot_number', $lotNumber)->update($data);
        }

        // Clear repair data for in-service items that have NO active repair
        $lotsWithRepairs = array_keys($repairData);
        $lotsWithoutRepairs = array_diff($inServiceItems, $lotsWithRepairs);

        if (!empty($lotsWithoutRepairs)) {
            \App\Models\Item::whereIn('lot_number', $lotsWithoutRepairs)->update([
                'repair_order_name' => null,
                'repair_state' => null,
                'repair_schedule_date' => null,
                'repair_service_type' => null,
                'repair_vendor' => null,
                'repair_odometer' => null,
                'repair_estimation_end' => null,
            ]);
        }
    }

    /**
     * Get schedule configuration
     */
    public function getSchedule(): JsonResponse
    {
        return response()->json([
            'enabled' => Setting::getValue('odoo_schedule_enabled', 'false') === 'true',
            'interval' => Setting::getValue('odoo_schedule_interval', 'daily'),
            'last_sync' => Setting::getValue('odoo_last_sync', null),
        ]);
    }

    /**
     * Save schedule configuration
     */
    public function saveSchedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'interval' => 'required|in:hourly,every_2_hours,every_4_hours,every_6_hours,every_12_hours,daily',
        ]);

        Setting::setValue('odoo_schedule_enabled', $validated['enabled'] ? 'true' : 'false');
        Setting::setValue('odoo_schedule_interval', $validated['interval']);

        return response()->json([
            'success' => true,
            'message' => $validated['enabled'] 
                ? "Auto-sync enabled ({$validated['interval']})" 
                : 'Auto-sync disabled',
        ]);
    }

    /**
     * Get import history
     */
    public function history(): JsonResponse
    {
        $logs = ImportLog::orderBy('imported_at', 'desc')
            ->take(50)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'source' => $log->source,
                    'source_label' => $log->source_label,
                    'filename' => $log->filename,
                    'imported_at' => $log->imported_at->toIso8601String(),
                    'items_count' => $log->items_count,
                    'status' => $log->status,
                    'status_color' => $log->status_color,
                    'error_message' => $log->error_message,
                    'summary' => $log->summary_json,
                ];
            });

        return response()->json($logs);
    }
}
