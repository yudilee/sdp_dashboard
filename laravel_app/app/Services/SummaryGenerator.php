<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;

class SummaryGenerator
{
    public function generate($filePath)
    {
        // specific reader setup might be needed if date formatting is an issue, 
        // but default import usually works.
        // We'll calculate everything in memory.
        
        $sheets = Excel::toArray([], $filePath);
        $data = null;
        
        foreach ($sheets as $sheetData) {
            if (empty($sheetData)) continue;
            // Check if specific column exists in first row
            $firstRow = $sheetData[0] ?? [];
            // Cast to string for search
            $firstRowStr = array_map('strval', $firstRow);
            if (in_array('Product', $firstRowStr)) {
                $data = $sheetData;
                break;
            }
        }
        
        if (!$data) {
             // Fallback or error
             $data = $sheets[0] ?? []; 
        }

        // Initialize Summary Structure
        $summary = [
            'vendor_rent' => 0,
            'sdp_stock' => 0,
            'pending_rental' => 0, // NEW: Pending Rental Count
            'rental_type_summary' => [
                'Subscription' => 0,
                'Regular' => 0,
            ],
            'unique_rental_contracts' => [
                'Subscription' => 0,
                'Regular' => 0,
            ],
            'inactive_subscription' => 0, // Expired subscriptions (end < today)
            'reserved_subscription' => 0, // Future subscriptions (start > today)
            'in_stock' => [
                'total' => 0,
                'details' => [
                    'SDP/OPERATION' => ['count' => 0, 'qty' => 0],
                    'SDP/STOCK SOLD' => [
                        'Stock for Sold' => 0,
                        'Jakarta' => 0,
                        'Surabaya' => 0,
                        'Semarang' => 0,
                        'Bandung' => 0,
                        'Cirebon' => 0,
                        'Cilegon' => 0,
                        'in Transit' => 0,
                        'Daerah Lain2' => 0,
                    ]
                ]
            ],
            'rented_in_customer' => [
                'total' => 0,
                'details' => [
                    'Vendor Rent' => 0,
                    'Original in Customer' => 0,
                    'Replacement - Service' => 0,
                    'Replacement - RBO' => 0,
                    'Check Rent position' => 0,
                ]
            ],
            'stock_external_service' => [
                'total' => 0,
                'details' => [
                    'Original Rented with Replace' => 0,
                    'Original Rented without Replace' => 0,
                    'Rented Replacement' => 0,
                    'Stock in Service' => 0,
                ]
            ],
            'stock_internal_service' => [
                'total' => 0,
                'details' => [
                    'Original Rented with Replace' => 0,
                    'Original Rented without Replace' => 0,
                    'Rented Replacement' => 0,
                    'Stock in Internal Service' => 0,
                ]
            ],
            'stock_insurance' => [
                'total' => 0,
                'details' => [
                    'Original Rented with Replace' => 0,
                    'Original Rented without Replace' => 0,
                    'Rented Replacement' => 0,
                    'Stock in Insurance' => 0,
                ]
            ],
            'uncategorized' => [
                'total' => 0,
                'details' => []
            ],
        ];

        // Column Mapping (based on analyze_excel output)
        // 0: Product, 1: Lot/Serial Number, 2: Internal Ref, 3: Tahun, 4: Location, 
        // 5: On Hand Qty, 6: Is Vendor Rent, 7: Is On Hand, 8: In Stock?
        // 11: Rental ID, 20: Reserved Lot (Rental ID/Order Lines/Reserved Lot)
        
        // Skip header row
        $header = array_shift($data);
        
        // Helper to find index by name
        // Filter out nulls/empties and cast to string to avoid array_flip warnings
        // $header = array_map(function($h) { return (string)$h; }, $header);
        $header = array_map(function($h) { return (string)$h; }, $header);
        $colMap = array_flip($header); 

        $idxParams = [
            'on_hand_qty' => $colMap['On Hand Quantity'] ?? 5,
            'is_vendor_rent' => $colMap['Is Vendor Rent'] ?? 6,
            'in_stock' => $colMap['In Stock?'] ?? 8,
            'location' => $colMap['Location'] ?? 4,
            'lot_no' => $colMap['Lot/Serial Number'] ?? 1,
            'reserved_lot' => $colMap['Rental ID/Order Lines/Reserved Lot'] ?? 20,
            'rental_id' => $colMap['Rental ID'] ?? 11,
            'rental_type' => $colMap['Rental ID/Tipe Rental'] ?? 23,
            'actual_start_rental' => $colMap['Rental ID/Actual Start Rental'] ?? 15,
            'actual_end_rental' => $colMap['Rental ID/Actual End Rental'] ?? 16,
        ];
        
        // Pre-compute rental_id occurrence counts
        $rentalIdCounts = [];
        foreach ($data as $row) {
            $rentalId = trim($row[$idxParams['rental_id']] ?? '');
            if (!empty($rentalId)) {
                $rentalIdCounts[$rentalId] = ($rentalIdCounts[$rentalId] ?? 0) + 1;
            }
        }
        
        // Date Logic Setup
        $baseDate = \Carbon\Carbon::create(1899, 12, 30);
        $today = \Carbon\Carbon::now();
        // Calculate today's Excel serial number
        $todaySerial = $baseDate->diffInDays($today); // e.g., ~46044 for 2026-01-23

        // Track unique rental IDs for contract counting
        $uniqueSubscriptionRentals = [];
        $uniqueRegularRentals = [];

        $items = [];
        foreach ($data as $i => $row) {
             // if ($i < 2) echo "Row $i: " . print_r($row, true) . "\n";
            $qty = (float)($row[$idxParams['on_hand_qty']] ?? 0);
            $isVendorRent = !empty($row[$idxParams['is_vendor_rent']]) && $row[$idxParams['is_vendor_rent']] !== false && $row[$idxParams['is_vendor_rent']] !== 'False';
            $inStock = !empty($row[$idxParams['in_stock']]) && $row[$idxParams['in_stock']] !== false && $row[$idxParams['in_stock']] !== 'False';
            $location = trim($row[$idxParams['location']] ?? '');
            $lotNo = trim($row[$idxParams['lot_no']] ?? '');
            $reservedLot = trim($row[$idxParams['reserved_lot']] ?? '');
            $rentalType = trim($row[$idxParams['rental_type']] ?? '');
            $actualStartRental = $row[$idxParams['actual_start_rental']] ?? null;
            $actualEndRental = $row[$idxParams['actual_end_rental']] ?? null;
            $rentalId = trim($row[$idxParams['rental_id']] ?? '');

            // Rental Status Check - Active if today is between start and end dates
            $isActiveRental = true;
            
            // Check if rental has started
            if (is_numeric($actualStartRental) && $actualStartRental > $todaySerial) {
                // If start date is in the future, it is PENDING
                $isActiveRental = false;
            }
            
            // Check if rental has ended
            if (is_numeric($actualEndRental) && $actualEndRental < $todaySerial) {
                // If end date is in the past, it is EXPIRED
                $isActiveRental = false;
            }
            
            // Note: If dates are empty/null, we assume Active (existing behavior)

            // Check for SOLD
            // User clarified: "SDP/STOCK SOLD" (26 items) is valid stock.
            // "SDP/SOLD" (1 item) is the only one to exclude.
            // Be precise with the check.
            $isSold = ($location === 'SDP/SOLD');

            if ($isSold) {
                // Skip adding to active summary counts
                // We add to items list later for record, but don't count in dashboard totals.
            } else {
                // SDP Stock Accumulator (Total Active Stock)
                $summary['sdp_stock'] += $qty;
                
                // Reserved Rental Logic (Future Start Date Only)
                if (is_numeric($actualStartRental) && $actualStartRental > $todaySerial) {
                    $summary['pending_rental'] += $qty;
                }
                
                // Vendor Rent Count (Independent of category, but part of active stock)
                if ($isVendorRent) {
                    $summary['vendor_rent'] += $qty;
                }
                
                // Rental Type Count (Subscription/Regular) - Only count ACTIVE rentals
                // MODIFIED: Exclude expired rentals (end < today) and pending (start > today)
                if ($isActiveRental) {
                    if ($rentalType === 'Subscription') {
                        $summary['rental_type_summary']['Subscription'] += $qty;
                        // Track unique rental contracts (not double-counting replacements)
                        if (!empty($rentalId) && !isset($uniqueSubscriptionRentals[$rentalId])) {
                            $uniqueSubscriptionRentals[$rentalId] = true;
                        }
                    } elseif ($rentalType === 'Regular') {
                        $summary['rental_type_summary']['Regular'] += $qty;
                        // Track unique rental contracts
                        if (!empty($rentalId) && !isset($uniqueRegularRentals[$rentalId])) {
                            $uniqueRegularRentals[$rentalId] = true;
                        }
                    }
                } else {
                    // Track INACTIVE (Expired) and RESERVED (Future) Subscriptions
                    if ($rentalType === 'Subscription') {
                        if (is_numeric($actualEndRental) && $actualEndRental < $todaySerial) {
                            // Expired: end date is in the past
                            $summary['inactive_subscription'] += $qty;
                        } elseif (is_numeric($actualStartRental) && $actualStartRental > $todaySerial) {
                            // Reserved: start date is in the future
                            $summary['reserved_subscription'] += $qty;
                        }
                    }
                }
            }

            // In Stock
            if (!$isSold && $inStock) {
                $summary['in_stock']['total'] += $qty;
                
                // Categorize Location
                // "SDP/OPERATION" -> Logic?
                // "SDP/STOCK SOLD" -> Logic?
                
                // Inferring from location names:
                // If Location contains "Operation", classify as "SDP/OPERATION" -> "Operation"?
                // Else "SDP/STOCK SOLD"
                
                if (stripos($location, 'Operation') !== false) {
                     $summary['in_stock']['details']['SDP/OPERATION']['count'] += $qty; // Or just add to 'Operation' key if I simplify structure
                     // Wait, structure in Excel:
                     // SDP/OPERATION | Operation | 7
                     // SDP/STOCK SOLD | Stock for Sold | 26
                     //                 | Jakarta | ...
                     
                     // I will put it in a flat list or nested? Nested is better for display.
                } else {
                     // Use actual location as key for display
                     $loc = $location; // Keep original case for display
                     
                     // Initialize if not exists
                     if (!isset($summary['in_stock']['details']['locations'])) {
                         $summary['in_stock']['details']['locations'] = [];
                     }
                     if (!isset($summary['in_stock']['details']['locations'][$loc])) {
                         $summary['in_stock']['details']['locations'][$loc] = 0;
                     }
                     $summary['in_stock']['details']['locations'][$loc] += $qty;
                }
            }
            
            // Rented in Customer
            elseif (!$isSold && $location == 'Partners/Customers/Rental') {
                $summary['rented_in_customer']['total'] += $qty;
                $rentalIdCount = $rentalIdCounts[$rentalId] ?? 0;
                
                // Sub-logic
                if ($isVendorRent) {
                     $summary['rented_in_customer']['details']['Vendor Rent'] += $qty;
                } elseif ($lotNo == $reservedLot && !empty($reservedLot)) {
                     $summary['rented_in_customer']['details']['Original in Customer'] += $qty;
                } elseif (!empty($rentalId) && $lotNo != $reservedLot && !$isVendorRent) {
                     // Replacement - split into Service vs RBO
                     if ($rentalIdCount > 1) {
                         // Service: rental_id appears more than once (main vehicle exists, likely in service)
                         $summary['rented_in_customer']['details']['Replacement - Service'] += $qty;
                     } else {
                         // RBO: Rental Before Original - only this replacement exists
                         $summary['rented_in_customer']['details']['Replacement - RBO'] += $qty;
                     }
                } elseif (empty($rentalId)) {
                     // "Check Rent position" -> rental ID=Blank
                     $summary['rented_in_customer']['details']['Check Rent position'] += $qty;
                }
                
            }
            
            // External Service
            elseif (!$isSold && stripos($location, 'Partners/Vendors/Service') === 0) {
                $summary['stock_external_service']['total'] += $qty;
                $rentalIdCount = $rentalIdCounts[$rentalId] ?? 0;
                
                // Sub-logic with replacement detection
                if (!empty($rentalId) && $lotNo == $reservedLot) {
                    // Original Rented - check if replacement exists
                    if ($rentalIdCount > 1) {
                        $summary['stock_external_service']['details']['Original Rented with Replace'] += $qty;
                    } else {
                        $summary['stock_external_service']['details']['Original Rented without Replace'] += $qty;
                    }
                } elseif (!empty($rentalId) && $lotNo != $reservedLot) {
                    $summary['stock_external_service']['details']['Rented Replacement'] += $qty;
                } elseif (empty($rentalId)) {
                    $summary['stock_external_service']['details']['Stock in Service'] += $qty;
                }
            }
            // Internal Service
            elseif (!$isSold && $location == 'Physical Locations/Service') {
                $summary['stock_internal_service']['total'] += $qty;
                $rentalIdCount = $rentalIdCounts[$rentalId] ?? 0;
                
                if (!empty($rentalId) && $lotNo == $reservedLot) {
                    if ($rentalIdCount > 1) {
                        $summary['stock_internal_service']['details']['Original Rented with Replace'] += $qty;
                    } else {
                        $summary['stock_internal_service']['details']['Original Rented without Replace'] += $qty;
                    }
                } elseif (!empty($rentalId) && $lotNo != $reservedLot) {
                    $summary['stock_internal_service']['details']['Rented Replacement'] += $qty;
                } elseif (empty($rentalId)) {
                    $summary['stock_internal_service']['details']['Stock in Internal Service'] += $qty;
                }
            }
            // Insurance (Partners/Vendors/Insurance)
            elseif (!$isSold && stripos($location, 'Partners/Vendors/Insurance') === 0) {
                $summary['stock_insurance']['total'] += $qty;
                $rentalIdCount = $rentalIdCounts[$rentalId] ?? 0;
                
                if (!empty($rentalId) && $lotNo == $reservedLot) {
                    if ($rentalIdCount > 1) {
                        $summary['stock_insurance']['details']['Original Rented with Replace'] += $qty;
                    } else {
                        $summary['stock_insurance']['details']['Original Rented without Replace'] += $qty;
                    }
                } elseif (!empty($rentalId) && $lotNo != $reservedLot) {
                    $summary['stock_insurance']['details']['Rented Replacement'] += $qty;
                } elseif (empty($rentalId)) {
                    $summary['stock_insurance']['details']['Stock in Insurance'] += $qty;
                }
            }
            // Uncategorized / Error
            elseif (!$isSold) {
                $summary['uncategorized']['total'] = ($summary['uncategorized']['total'] ?? 0) + $qty;
            }
            // Determine vehicle role (Main = lot matches reserved, Replacement = lot differs from reserved)
            $vehicleRole = null;
            if (!empty($rentalId)) {
                if (!empty($reservedLot)) {
                    $vehicleRole = ($lotNo === $reservedLot) ? 'Main' : 'Replacement';
                }
            }

            // Collect Item Data for Persistence
            $items[] = [
                'product' => $row[0] ?? '',
                'lot_number' => $lotNo,
                'internal_reference' => $row[2] ?? '',
                'year' => $row[3] ?? '',
                'location' => $location,
                'on_hand_quantity' => $qty,
                'is_vendor_rent' => $isVendorRent,
                'is_on_hand' => !empty($row[$idxParams['is_on_hand'] ?? 7]) && $row[$idxParams['is_on_hand'] ?? 7] !== 'False',
                'in_stock' => $inStock,
                'rental_id' => $rentalId,
                'reserved_lot' => $reservedLot,
                'km_last' => $row[$idxParams['km_last'] ?? 10] ?? 0,
                'is_sold' => $isSold,
                'rental_type' => $rentalType,
                'actual_start_rental' => $actualStartRental,
                'actual_end_rental' => $actualEndRental,
                'is_active_rental' => $isActiveRental, // NEW
                'category_flag' => $this->determineCategory($summary, $location, $isVendorRent, $lotNo, $reservedLot, $rentalId, $qty),
                'vehicle_role' => $vehicleRole,
                'linked_vehicle' => null, // Will be populated in second pass
                'rental_id_count' => $rentalIdCounts[$rentalId] ?? 0,
            ];
        }
        
        // Second pass: Find linked vehicles (vehicles sharing the same rental_id)
        // Build a map of rental_id -> list of lot_numbers
        $rentalGroups = [];
        foreach ($items as $idx => $item) {
            $rid = $item['rental_id'];
            if (!empty($rid)) {
                if (!isset($rentalGroups[$rid])) {
                    $rentalGroups[$rid] = [];
                }
                $rentalGroups[$rid][] = ['index' => $idx, 'lot_number' => $item['lot_number']];
            }
        }
        
        // For each rental group with multiple vehicles, link them
        foreach ($rentalGroups as $rid => $group) {
            if (count($group) > 1) {
                // Find linked vehicles for each item in the group
                foreach ($group as $member) {
                    $otherLots = [];
                    foreach ($group as $other) {
                        if ($other['lot_number'] !== $member['lot_number']) {
                            $otherLots[] = $other['lot_number'];
                        }
                    }
                    $items[$member['index']]['linked_vehicle'] = implode(', ', $otherLots);
                }
            }
        }
        
        // Count rental pairs for summary
        $rentalPairsCount = 0;
        foreach ($rentalGroups as $group) {
            if (count($group) > 1) {
                $rentalPairsCount++;
            }
        }
        $summary['rental_pairs_count'] = $rentalPairsCount;
        
        // Populate unique rental contract counts
        $summary['unique_rental_contracts']['Subscription'] = count($uniqueSubscriptionRentals);
        $summary['unique_rental_contracts']['Regular'] = count($uniqueRegularRentals);
        
        return ['summary' => $summary, 'items' => $items];
    }
    
    // Helper to tag items for drilldown
    private function determineCategory($summary, $location, $isVendorRent, $lotNo, $reservedLot, $rentalId, $qty) {
        $tags = [];
        if ($isVendorRent) $tags[] = 'vendor_rent';
        
        // In Stock Logic
        $inStock = false; // Need to access inStock var? passed in args? 
        // Re-deriving slightly inefficient but safer if I don't pass everything. 
        // Let's just return raw items and let Repository/Controller filter?
        // Filtering purely by JSON query might be slow if complex logic.
        // I will just add properties used in logic.
        return $tags;
    }
}
