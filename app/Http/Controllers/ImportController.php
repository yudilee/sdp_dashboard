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
