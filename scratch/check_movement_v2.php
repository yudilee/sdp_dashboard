<?php

require 'vendor/autoload.php';

// Boot Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\OdooService;

$odoo = new OdooService();

$rentalId = 'R/2025/03732'; // The one the user wants me to check
$targetLot = 'B9036BXE';

echo "Rental ID: $rentalId\n";
echo "Target Lot: $targetLot\n";

try {
    // 1. Find SO ID
    $soIds = $odoo->execute('sale.order', 'search', [[['name', '=', $rentalId]]]);
    if (empty($soIds)) {
        die("SO $rentalId not found\n");
    }
    $soId = $soIds[0];
    echo "SO ID: $soId\n";

    // 2. Identify model for product_movement_ids
    $res = $odoo->execute('sale.order', 'fields_get', [['product_movement_ids']], ['attributes' => ['relation']]);
    $model = $res['product_movement_ids']['relation'] ?? null;
    if (!$model) {
        die("Could not find relation for product_movement_ids\n");
    }
    echo "Movement model: $model\n";

    // 3. Find the field that links to sale.order
    $fields = $odoo->execute($model, 'fields_get', [], ['attributes' => ['relation', 'type']]);
    $linkField = null;
    foreach ($fields as $name => $info) {
        if (($info['type'] ?? '') === 'many2one' && ($info['relation'] ?? '') === 'sale.order') {
            $linkField = $name;
            break;
        }
    }
    
    if (!$linkField) {
        die("Could not find backlink to sale.order on $model\n");
    }
    echo "Link field: $linkField\n";

    // 4. Query movements
    $movements = $odoo->execute($model, 'search_read', [[[$linkField, '=', $soId]]]);
    echo "Total movements found: " . count($movements) . "\n";
    
    foreach ($movements as $idx => $m) {
        $lot = is_array($m['lot_id'] ?? null) ? $m['lot_id'][1] : ($m['lot_id'] ?? '-');
        $resLot = is_array($m['reserved_lot'] ?? null) ? $m['reserved_lot'][1] : ($m['reserved_lot'] ?? '-'); // Assuming reserved_lot name pattern
        // The field might have a different name, let's look at the movement keys
        if ($idx === 0) {
            echo "Movement fields found: " . implode(', ', array_keys($m)) . "\n";
        }
        
        echo "Line " . ($idx + 1) . ":\n";
        echo "  - Product: " . (is_array($m['product_id'] ?? null) ? $m['product_id'][1] : ($m['product_id'] ?? '-')) . "\n";
        // Check for fields in the screenshot
        echo "  - Lot/SN: $lot\n";
        // Let's guess 'x_reserved_lot' or similar based on screenshot 'Reserved Lot'
        $guessResLot = $m['x_reserved_lot'] ?? $m['reserved_lot'] ?? $m['reserved_lot_id'] ?? '-';
        if (is_array($guessResLot)) $guessResLot = $guessResLot[1];
        echo "  - Reserved Lot: $guessResLot\n";
        echo "  - Is Switch Unit: " . ($m['x_is_switch_unit'] ?? $m['is_switch_unit'] ?? '-') . "\n";
        echo "  - Date: " . ($m['date'] ?? '-') . "\n";
        echo "  - Switch Unit Reason: " . ($m['x_switch_unit_reason'] ?? $m['switch_unit_reason'] ?? '-') . "\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
