<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OdooService;

class DebugOdoo extends Command
{
    protected $signature = 'debug:odoo';
    protected $description = 'Debug Odoo API response fields';

    public function handle()
    {
        $odoo = new OdooService();
        $this->info('Testing Odoo Connection...');
        
        $conn = $odoo->testConnection();
        if (!$conn['success']) {
            $this->error($conn['message']);
            return;
        }
        $this->info($conn['message']);

        $this->info('Debugging fetchViaExport logic for B2448B...');
        
        // Define fields as in fetchViaExport
        $exportFields = [
            'product_id/display_name',
            'name',
            'rental_id/actual_end_rental',
        ];

        // Search for the specific lot
        // We need to use reflection or a transform to call protected execute, 
        // OR we can add a specialized public method to OdooService for debugging,
        // OR we just use the existing fetchViaExport and inspect the result if it wasn't filtered.
        // But fetchViaExport filters internally.
        
        // Let's rely on OdooService having a public execute or similar?
        // No, execute is protected.
        $this->info('Debugging generic Odoo API calls...');
        
        // Let's assume we want to fetch some generic data, e.g., products
        // This part needs to be filled in with actual OdooService calls
        // For demonstration, let's simulate fetching some data.
        
        // Example: Fetching a list of products using a generic method
        // This would typically involve calling a public method on OdooService
        // or using reflection to call 'execute' for a generic model.
        
        // For the purpose of this edit, we'll use the reflection method
        // to call a generic search and read on a model like 'product.product'
        // and then log the results generically.
        
        $method = new \ReflectionMethod(OdooService::class, 'execute');
        $method->setAccessible(true);
        
        // Search for some records (e.g., products)
        $domain = []; // No specific filter for generic logging
        $model = 'product.product';
        $fields = ['name', 'default_code', 'list_price']; // Example fields
        
        $this->info("Fetching records from model: {$model} with fields: " . implode(', ', $fields));
        
        try {
            $ids = $method->invoke($odoo, $model, 'search', [$domain, 0, 10]); // Get first 10 IDs
            $this->info('Found IDs: ' . json_encode($ids));

            if (empty($ids)) {
                $this->info('No records found for ' . $model);
                return;
            }

            $data = $method->invoke($odoo, $model, 'read', [$ids, $fields]);
            
            $this->info('--- Sample Records ---');
            $count = 0;
            foreach ($data as $row) {
                $this->info("--- Record ---");
                $this->info("Name: " . ($row['name'] ?? 'N/A'));
                $this->info("Product Code: " . ($row['default_code'] ?? 'N/A'));
                $this->info("List Price: " . ($row['list_price'] ?? 'N/A'));
                $this->info("Raw Data: " . json_encode($row));
                
                $count++;
                if ($count >= 5) break; // Take a sample of records
            }
        } catch (\Exception $e) {
            $this->error('Error during Odoo API call: ' . $e->getMessage());
        }
    }
}
