<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\JsonItemRepository;

class DashboardController extends Controller
{
    /**
     * Convert Excel serial date to PHP DateTime
     */
    private function excelSerialToDate($serial)
    {
        if (empty($serial) || !is_numeric($serial)) {
            return null;
        }
        // Excel serial date starts from 1899-12-30
        $excelBaseDate = new \DateTime('1899-12-30');
        $excelBaseDate->modify('+' . intval($serial) . ' days');
        return $excelBaseDate;
    }

    /**
     * Get today's date as Excel serial number
     */
    private function getTodayExcelSerial()
    {
        $excelBaseDate = new \DateTime('1899-12-30');
        $today = new \DateTime('today');
        return $excelBaseDate->diff($today)->days;
    }

    public function index(JsonItemRepository $repo)
    {
        $summary = $repo->getSummary();
        if (!$summary) {
            // Empty state
            return view('dashboard', ['summary' => null, 'metadata' => null, 'history' => [], 'reserveRentalData' => [], 'stockByRentalStatus' => []]);
        }

        $metadata = $repo->getMetadata();
        $history = $repo->getHistory();
        
        // Calculate dynamic rental status based on today's date
        $items = $repo->all();
        $todaySerial = $this->getTodayExcelSerial();
        
        // Reserve Rental: Has rental_id but start date > today
        $reserveRentals = $items->filter(function($item) use ($todaySerial) {
            if (!empty($item['rental_id']) && !empty($item['actual_start_rental'])) {
                return $item['actual_start_rental'] > $todaySerial;
            }
            return false;
        });
        
        $reserveRentalData = [
            'count' => $reserveRentals->count(),
            'items' => $reserveRentals->values()->take(20)->toArray(),
        ];
        
        // Stock breakdown by rental status (only for items in stock)
        $stockItems = $items->filter(function($item) {
            return ($item['in_stock'] ?? false) == true;
        });
        
        $pureStock = $stockItems->filter(function($item) {
            return empty($item['rental_id']);
        })->count();
        
        $originalStock = $stockItems->filter(function($item) use ($todaySerial) {
            if (!empty($item['rental_id']) && !empty($item['actual_start_rental'])) {
                return $item['actual_start_rental'] <= $todaySerial;
            }
            return false;
        })->count();
        
        $reserveStock = $stockItems->filter(function($item) use ($todaySerial) {
            if (!empty($item['rental_id']) && !empty($item['actual_start_rental'])) {
                return $item['actual_start_rental'] > $todaySerial;
            }
            return false;
        })->count();
        
        $stockByRentalStatus = [
            'pure_stock' => $pureStock,
            'original' => $originalStock,
            'reserve' => $reserveStock,
        ];

        return view('dashboard', compact('summary', 'metadata', 'history', 'reserveRentalData', 'stockByRentalStatus'));
    }

    public function print(JsonItemRepository $repo)
    {
        $summary = $repo->getSummary();
        if (!$summary) {
            return redirect()->route('dashboard');
        }

        $metadata = $repo->getMetadata();

        return view('print', compact('summary', 'metadata'));
    }

    public function details(Request $request, JsonItemRepository $repo)
    {
        $category = $request->query('category');
        $sub = $request->query('sub');
        $searchQuery = $request->query('q');
        
        $items = $repo->all();
        
        $filtered = $items->filter(function($item) use ($category, $sub, $searchQuery) {
            // Global Filter: Exclude Sold items (unless specialized view?)
            if (!empty($item['is_sold'])) {
                 return false; 
            }

            // Search filter
            if ($category == 'search' && $searchQuery) {
                $q = strtolower($searchQuery);
                $lotNo = strtolower($item['lot_number'] ?? '');
                $product = strtolower($item['product'] ?? '');
                $location = strtolower($item['location'] ?? '');
                $internalRef = strtolower($item['internal_reference'] ?? '');
                
                return strpos($lotNo, $q) !== false 
                    || strpos($product, $q) !== false 
                    || strpos($location, $q) !== false
                    || strpos($internalRef, $q) !== false;
            }

            // Replicate logic based on category
           if ($category == 'vendor_rent') {
               return $item['is_vendor_rent'] == true;
           }
           if ($category == 'reserve_rental') {
               // Vehicles with rental_id but start date > today
               $rentalId = $item['rental_id'] ?? '';
               $startDate = $item['actual_start_rental'] ?? null;
               $startDate = $item['actual_start_rental'] ?? null;
               if (empty($startDate)) {
                   return false;
               }
               // Calculate today's Excel serial
               $excelBase = new \DateTime('1899-12-30');
               $today = new \DateTime('today');
               $todaySerial = $excelBase->diff($today)->days;
               return $startDate > $todaySerial;
           }
           if ($category == 'stock_pure') {
               // In stock with no rental_id
               return ($item['in_stock'] ?? false) && empty($item['rental_id']);
           }
           if ($category == 'stock_original') {
               // In stock with rental_id and start <= today (active rental returned)
               if (!($item['in_stock'] ?? false)) return false;
               $rentalId = $item['rental_id'] ?? '';
               $startDate = $item['actual_start_rental'] ?? null;
               if (empty($rentalId) || empty($startDate)) return false;
               $excelBase = new \DateTime('1899-12-30');
               $today = new \DateTime('today');
               $todaySerial = $excelBase->diff($today)->days;
               return $startDate <= $todaySerial;
           }
           if ($category == 'stock_reserve') {
               // In stock with rental_id and start > today (reserved for future)
               if (!($item['in_stock'] ?? false)) return false;
               $rentalId = $item['rental_id'] ?? '';
               $startDate = $item['actual_start_rental'] ?? null;
               if (empty($rentalId) || empty($startDate)) return false;
               $excelBase = new \DateTime('1899-12-30');
               $today = new \DateTime('today');
               $todaySerial = $excelBase->diff($today)->days;
               return $startDate > $todaySerial;
           }
           if ($category == 'sdp_stock') {
               return true;
           }
           if ($category == 'in_stock') {
                if ($sub) {
                    $loc = $item['location'];
                    // Operation check
                    if ($sub == 'Operation' && stripos($loc, 'OPERATION') !== false) return true;
                    // Exact location match (since we use actual locations as keys now)
                    if ($loc == $sub) return true;
                    return false;
                }
                return $item['in_stock'] == true;
           }
           if ($category == 'rented') {
                // First check: must be in rental location
                if ($item['location'] != 'Partners/Customers/Rental') {
                    return false;
                }
                
                if ($sub) {
                    $isVendor = $item['is_vendor_rent'] ?? false;
                    $rentalId = $item['rental_id'] ?? '';
                    $lotNo = $item['lot_number'] ?? '';
                    $reservedLot = $item['reserved_lot'] ?? '';
                    $rentalIdCount = $item['rental_id_count'] ?? 0;

                    if ($sub == 'Vendor Rent') return $isVendor == true;
                    if ($sub == 'Original in Customer') return ($lotNo == $reservedLot && !empty($reservedLot) && !$isVendor);
                    if ($sub == 'Replacement') return (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor);
                    if ($sub == 'Replacement - Service') return (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor && $rentalIdCount > 1);
                    if ($sub == 'Replacement - RBO') return (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor && $rentalIdCount == 1);
                    if ($sub == 'Check Rent position') return (empty($rentalId) && !$isVendor);
                    return false;
                }
                return true;
           }
           if ($category == 'external_service') {
                // First check: must be in external service location
                if (stripos($item['location'], 'Partners/Vendors/Service') !== 0) {
                    return false;
                }
                
                if ($sub) {
                    $rentalId = $item['rental_id'] ?? '';
                    $lotNo = $item['lot_number'] ?? '';
                    $reservedLot = $item['reserved_lot'] ?? '';
                    $rentalIdCount = $item['rental_id_count'] ?? 0;
                   
                    if ($sub == 'Original Rented with Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount > 1);
                    if ($sub == 'Original Rented without Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount == 1);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Service') return empty($rentalId);
                    return false;
                }
                return true;
           }
           if ($category == 'internal_service') {
                // First check: must be in internal service location
                if ($item['location'] != 'Physical Locations/Service') {
                    return false;
                }
                
                if ($sub) {
                    $rentalId = $item['rental_id'] ?? '';
                    $lotNo = $item['lot_number'] ?? '';
                    $reservedLot = $item['reserved_lot'] ?? '';
                    $rentalIdCount = $item['rental_id_count'] ?? 0;

                    if ($sub == 'Original Rented with Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount > 1);
                    if ($sub == 'Original Rented without Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount == 1);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Internal Service') return empty($rentalId);
                    return false;
                }
                return true;
           }
           if ($category == 'insurance') {
                // First check: must be in insurance location
                if (stripos($item['location'], 'Partners/Vendors/Insurance') !== 0) {
                    return false;
                }
                
                if ($sub) {
                    $rentalId = $item['rental_id'] ?? '';
                    $lotNo = $item['lot_number'] ?? '';
                    $reservedLot = $item['reserved_lot'] ?? '';
                    $rentalIdCount = $item['rental_id_count'] ?? 0;
                   
                    if ($sub == 'Original Rented with Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount > 1);
                    if ($sub == 'Original Rented without Replace') return (!empty($rentalId) && $lotNo == $reservedLot && $rentalIdCount == 1);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Insurance') return empty($rentalId);
                    return false;
                }
                return true;
           }
           if ($category == 'rental_type') {
                $rentalType = $item['rental_type'] ?? '';
                if ($sub) {
                    return $rentalType === $sub;
                }
                return !empty($rentalType);
           }
           if ($category == 'in_service') {
                // Combined view for all service locations
                $loc = $item['location'] ?? '';
                $isExternal = stripos($loc, 'Partners/Vendors/Service') === 0;
                $isInternal = $loc == 'Physical Locations/Service';
                $isInsurance = stripos($loc, 'Partners/Vendors/Insurance') === 0;
                return $isExternal || $isInternal || $isInsurance;
           }
           
           return true;
       });

        // For DataTables, return all items (client-side pagination)
        $items = $filtered->values();

        return view('details', compact('items', 'category', 'sub'));
    }

    public function export(Request $request, JsonItemRepository $repo)
    {
        $category = $request->query('category');
        $sub = $request->query('sub');
        
        $items = $repo->all();
        
        $filtered = $items->filter(function($item) use ($category, $sub) {
            // Global Filter: Exclude Sold items
            if (!empty($item['is_sold'])) {
                 return false; 
            }

             // Replicate logic based on category
            if ($category == 'vendor_rent') {
                return $item['is_vendor_rent'] == true;
            }
            if ($category == 'sdp_stock') {
                return true;
            }
            if ($category == 'in_stock') {
                 if ($sub) {
                     $loc = $item['location'];
                     if ($sub == 'SDP/OPERATION' && stripos($loc, 'Operation') !== false) return true;
                     // Logic for sub-locations
                     if ($sub == 'Jakarta' && (stripos($loc, 'JKT') !== false || stripos($loc, 'Jakarta') !== false)) return true;
                     if ($sub == 'Surabaya' && (stripos($loc, 'SBY') !== false || stripos($loc, 'SUB') !== false || stripos($loc, 'Surabaya') !== false)) return true;
                     if ($sub == 'Semarang' && (stripos($loc, 'SMG') !== false || stripos($loc, 'Semarang') !== false)) return true;
                     if ($sub == 'Bandung' && (stripos($loc, 'BDG') !== false || stripos($loc, 'Bandung') !== false)) return true;
                     if ($sub == 'Cirebon' && (stripos($loc, 'CRB') !== false || stripos($loc, 'CBN') !== false || stripos($loc, 'Cirebon') !== false)) return true;
                     if ($sub == 'Cilegon' && (stripos($loc, 'CLG') !== false || stripos($loc, 'Cilegon') !== false)) return true;
                     if ($sub == 'in-Transit' && stripos($loc, 'Transit') !== false) return true; // Note: 'in-Transit' from URL
                 }
                 return $item['in_stock'] == true;
            }
            if ($category == 'rented') {
                 if ($sub) {
                     // Filter by sub description
                     // "Vendor Rent", "Original in Customer", etc.
                     // This is tricky because we only stored raw fields.
                     // We need to re-apply the logic helper?
                     // Or just generic location filter?
                     // For "Rented", items are in 'Partners/Customers/Rental'.
                     // Sub-logic:
                     $isVendor = $item['is_vendor_rent'];
                     $rentalId = $item['rental_id'];
                     $lotNo = $item['lot_number'];
                     $reservedLot = $item['reserved_lot'];

                     if ($sub == 'Vendor Rent') return $isVendor;
                     if ($sub == 'Original in Customer') return ($lotNo == $reservedLot && !empty($reservedLot));
                     if ($sub == 'Replacement') return (!empty($rentalId) && $lotNo != $reservedLot && !$isVendor);
                     if ($sub == 'Check Rent position') return empty($rentalId);
                 }
                 return $item['location'] == 'Partners/Customers/Rental';
            }
            if ($category == 'uncategorized') {
                // Logic: NOT In Stock AND NOT Rented AND NOT External AND NOT Internal
                // Simpler: Just rely on the buckets defined in SummaryGenerator? 
                // But here we are filtering from raw items.
                // We need to inverse the matching logic.
                
                $loc = $item['location'];
                $isExt = stripos($loc, 'Partners/Vendors/Service') === 0;
                $isInt = $loc == 'Physical Locations/Service';
                $isIns = stripos($loc, 'Partners/Vendors/Insurance') === 0;
                $isRented = $loc == 'Partners/Customers/Rental';
                $isInStock = $item['in_stock']; // Or use logic if strictly location based? 
                // SummaryGenerator uses: if ($inStock) ... elseif ...
                // So if InStock is true, it's captured there.
                
                return !$isInStock && !$isRented && !$isExt && !$isInt && !$isIns;
            }
            if ($category == 'external_service') {
                 if ($sub) {
                    $rentalId = $item['rental_id'];
                    $lotNo = $item['lot_number'];
                    $reservedLot = $item['reserved_lot'];
                    
                    if ($sub == 'Original Rented') return (!empty($rentalId) && $lotNo == $reservedLot);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Service') return empty($rentalId);
                 }
                 return stripos($item['location'], 'Partners/Vendors/Service') === 0;
            }
            if ($category == 'internal_service') {
                 if ($sub) {
                    $rentalId = $item['rental_id'];
                    $lotNo = $item['lot_number'];
                    $reservedLot = $item['reserved_lot'];

                    if ($sub == 'Original Rented') return (!empty($rentalId) && $lotNo == $reservedLot);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Internal Service') return empty($rentalId);
                 }
                 return $item['location'] == 'Physical Locations/Service';
            }
            if ($category == 'insurance') {
                 if ($sub) {
                    $rentalId = $item['rental_id'];
                    $lotNo = $item['lot_number'];
                    $reservedLot = $item['reserved_lot'];

                    if ($sub == 'Original Rented') return (!empty($rentalId) && $lotNo == $reservedLot);
                    if ($sub == 'Rented Replacement') return (!empty($rentalId) && $lotNo != $reservedLot);
                    if ($sub == 'Stock in Insurance') return empty($rentalId);
                 }
                 return stripos($item['location'], 'Partners/Vendors/Insurance') === 0;
            }
            if ($category == 'rental_type') {
                // Only show active rentals (within date range)
                $isActive = $item['is_active_rental'] ?? true;
                if (!$isActive) return false;
                
                $rentalType = $item['rental_type'] ?? '';
                if ($sub) {
                    return $rentalType === $sub;
                }
                return !empty($rentalType);
            }
            if ($category == 'reserved_subscription') {
                // Future subscriptions (start > today)
                $rentalType = $item['rental_type'] ?? '';
                if ($rentalType !== 'Subscription') return false;
                
                $startDate = $item['actual_start_rental'] ?? null;
                if (empty($startDate) || !is_numeric($startDate)) return false;
                
                $excelBase = new \DateTime('1899-12-30');
                $today = new \DateTime('today');
                $todaySerial = $excelBase->diff($today)->days;
                
                return $startDate > $todaySerial;
            }
            if ($category == 'inactive_subscription') {
                // Expired subscriptions (end < today)
                $rentalType = $item['rental_type'] ?? '';
                if ($rentalType !== 'Subscription') return false;
                
                $endDate = $item['actual_end_rental'] ?? null;
                if (empty($endDate) || !is_numeric($endDate)) return false;
                
                $excelBase = new \DateTime('1899-12-30');
                $today = new \DateTime('today');
                $todaySerial = $excelBase->diff($today)->days;
                
                return $endDate < $todaySerial;
            }
            
            return true;
        });

        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="export_'.date('Y-m-d_H-i-s').'.csv"',
        ];

        $callback = function() use ($filtered) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Product', 'Lot Number', 'Internal Ref', 'Location', 'On Hand Qty', 'Vendor Rent', 'In Stock', 'Rental ID']);

            foreach ($filtered as $item) {
                fputcsv($file, [
                    $item['product'],
                    $item['lot_number'],
                    $item['internal_reference'],
                    $item['location'],
                    $item['on_hand_quantity'],
                    $item['is_vendor_rent'] ? 'Yes' : 'No',
                    $item['in_stock'] ? 'Yes' : 'No',
                    $item['rental_id']
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function rentalPairs(JsonItemRepository $repo)
    {
        $items = $repo->all();
        $summary = $repo->getSummary();
        $metadata = $repo->getMetadata();
        
        // Filter out sold items
        $items = $items->filter(function($item) {
            return empty($item['is_sold']);
        });
        
        // Group by rental_id
        $rentalGroups = [];
        foreach ($items as $item) {
            $rentalId = $item['rental_id'] ?? '';
            if (!empty($rentalId)) {
                if (!isset($rentalGroups[$rentalId])) {
                    $rentalGroups[$rentalId] = [];
                }
                $rentalGroups[$rentalId][] = $item;
            }
        }
        
        // Filter to only pairs (count > 1)
        $rentalPairs = [];
        foreach ($rentalGroups as $rentalId => $group) {
            if (count($group) > 1) {
                // Sort so Main comes first
                usort($group, function($a, $b) {
                    $roleA = $a['vehicle_role'] ?? '';
                    $roleB = $b['vehicle_role'] ?? '';
                    if ($roleA === 'Main' && $roleB !== 'Main') return -1;
                    if ($roleB === 'Main' && $roleA !== 'Main') return 1;
                    return 0;
                });
                
                $rentalPairs[$rentalId] = [
                    'rental_id' => $rentalId,
                    'vehicles' => $group,
                    'main_vehicle' => collect($group)->firstWhere('vehicle_role', 'Main'),
                    'replacement_vehicles' => collect($group)->where('vehicle_role', 'Replacement')->values()->all(),
                ];
            }
        }
        
        $pairsCount = count($rentalPairs);
        
        return view('rental_pairs', compact('rentalPairs', 'pairsCount', 'metadata'));
    }
}
