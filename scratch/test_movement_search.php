<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

try {
    // Search movement by order name directly
    $movements = $odoo->execute('product.movement', 'search_read', [
        [['order_id.name', 'in', ['R/2025/03732']]]
    ], ['fields' => ['order_id', 'quantity']]);
    
    print_r($movements);
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
