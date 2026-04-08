<?php

require 'vendor/autoload.php';

// Boot Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

try {
    $res = $odoo->detectFields('sale.order');
    $fields = $res['fields'];
    
    if (isset($fields['product_movement_ids'])) {
        echo "Field product_movement_ids relation: " . ($fields['product_movement_ids']['relation'] ?? 'unknown') . "\n";
        $model = $fields['product_movement_ids']['relation'];
        
        // Now find fields of this model
        $movementFieldsRes = $odoo->detectFields($model);
        echo "Fields of model $model:\n";
        print_r(array_keys($movementFieldsRes['fields']));
        
        // Query records for R/2025/03732 (SO ID 21752)
        // I'll search for the field that links back to sale.order. Usually 'order_id' or 'sale_id'
        $backLink = array_filter(array_keys($movementFieldsRes['fields']), function($f) use ($movementFieldsRes) {
            return ($movementFieldsRes['fields'][$f]['type'] ?? '') === 'many2one' && ($movementFieldsRes['fields'][$f]['relation'] ?? '') === 'sale.order';
        });
        
        echo "Backlink fields to sale.order from $model:\n";
        print_r($backLink);
        
        $linkField = reset($backLink);
        if ($linkField) {
            $movements = $odoo->execute($model, 'search_read', [[[$linkField, '=', 21752]]]);
            echo "Movements for SO 21752:\n";
            print_r($movements);
        }
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
