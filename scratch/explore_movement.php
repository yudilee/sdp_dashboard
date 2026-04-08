<?php

require 'vendor/autoload.php';

// Boot Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

$rentalId = 'R/2025/03732';

echo "Searching for Rental Order: $rentalId\n";

try {
    // 1. Find the Rental Order (Sale Order)
    $soIds = $odoo->execute('sale.order', 'search', [[['name', '=', $rentalId]]]);
    
    if (empty($soIds)) {
        die("Rental Order $rentalId not found.\n");
    }
    
    $soId = $soIds[0];
    echo "Found SO ID: $soId\n";
    
    // 2. Look for fields related to 'Product Movement' or 'Move'
    $fields = $odoo->detectFields('sale.order')['fields'];
    $movementFields = array_filter(array_keys($fields), function($f) {
        return strpos($f, 'move') !== false || strpos($f, 'switch') !== false;
    });
    
    echo "Movement/Switch related fields on sale.order:\n";
    print_r($movementFields);
    
    // 3. Check for specific model from screenshot: 'Is Switch Unit', 'Switch Unit Reason'
    // Let's try to find a model that might be 'Product Movement'
    // Commonly it's a one2many on sale.order
    
    // I'll check all one2many fields on sale.order to see which one looks like Product Movement
    $o2mFields = array_filter($fields, function($f) {
        return $f['type'] === 'one2many';
    });
    
    echo "One2Many fields on sale.order:\n";
    foreach ($o2mFields as $name => $info) {
        echo " - $name (pointing to {$fields[$name]['relation']})\n";
    }

    // 4. Also check stock.move linked to this SO
    $moves = $odoo->execute('stock.move', 'search_read', [[['sale_line_id.order_id', '=', $soId]]]);
    echo "Found " . count($moves) . " stock moves for this SO.\n";
    if (!empty($moves)) {
        // print_r($moves[0]);
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
