<?php

namespace App\Services;

use App\Constants\Location;
use App\Services\InventoryService;
use Maatwebsite\Excel\Facades\Excel;

class SummaryGenerator
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function generate($file)
    {
        // specific reader setup might be needed if date formatting is an issue, 
        // but default import usually works.
        // We'll calculate everything in memory.
        
        // Support both file paths (string) and pre-parsed arrays (from Odoo API)
        if (is_array($file)) {
            // Already parsed data (from Odoo export_data)
            $data = $file;
        } else {
            // File path - parse via Excel
            $sheets = Excel::toArray([], $file);
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
                'rental_status' => [
                    'pure_stock' => 0,
                    'original_with_replace' => 0,
                    'original_without_replace' => 0,
                    'reserve' => 0, // Maps to active future rentals or specific status
                ],
                'details' => [
                    Location::OPERATION => ['count' => 0, 'qty' => 0],
                    Location::SOLD_STOCK => [
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

        // Initialize Summary Structure
        // ... (lines 45-123 same, but skipping to logic) ...

        // Column Mapping
        // Define Required Headers
        $requiredHeaders = [
            'Product', 
            'Lot/Serial Number', 
            'Location', 
            'On Hand Quantity'
        ];
        
        $header = array_shift($data);
        if (!$header) {
             throw new \InvalidArgumentException("The file is empty or missing headers.");
        }
        
        // Cast to string and trim
        $header = array_map(function($h) { return trim((string)$h); }, $header);
        $colMap = array_flip($header);
        
        // Validate Required Headers
        $missingHeaders = [];
        foreach ($requiredHeaders as $req) {
            if (!isset($colMap[$req])) {
                $missingHeaders[] = $req;
            }
        }
        
        if (!empty($missingHeaders)) {
            throw new \InvalidArgumentException("Missing required columns: " . implode(', ', $missingHeaders));
        }

        $idxParams = [
            'on_hand_qty' => $colMap['On Hand Quantity'],
            'is_vendor_rent' => $colMap['Is Vendor Rent'] ?? -1,
            'in_stock' => $colMap['In Stock?'] ?? -1,
            'location' => $colMap['Location'],
            'lot_no' => $colMap['Lot/Serial Number'],
            'reserved_lot' => $colMap['Rental ID/Order Lines/Reserved Lot'] ?? -1,
            'rental_id' => $colMap['Rental ID'] ?? -1,
            'rental_type' => $colMap['Rental ID/Tipe Rental'] ?? -1,
            'actual_start_rental' => $colMap['Rental ID/Actual Start Rental'] ?? -1,
            'actual_end_rental' => $colMap['Rental ID/Actual End Rental'] ?? -1,
            'km_last' => $colMap['Last Odometer (KM)'] ?? -1,
            'is_on_hand' => $colMap['Is On Hand?'] ?? -1,
            'last_customer' => $colMap['Partner/Cust.'] ?? -1, // NEW
            'current_customer' => $colMap['Rental ID/Customer'] ?? -1, // NEW
            'warehouse' => $colMap['Rental ID/Warehouse'] ?? -1, // NEW
            'internal_reference' => $colMap['Internal Reference'] ?? -1, // No Rangka
            'year' => $colMap['Year'] ?? -1,
            'purchase_date' => $colMap['Purchase Date'] ?? -1,
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
        $nonIdSubscriptionItems = 0;
        $nonIdRegularItems = 0;

        $items = [];
        foreach ($data as $i => $row) {
             // Skip row if Lot Number is missing
             $lotNo = trim($row[$idxParams['lot_no']] ?? '');
             if (empty($lotNo)) continue;

             // Helper to safely get value or default
             $getValue = function($key) use ($row, $idxParams) {
                 $idx = $idxParams[$key];
                 return ($idx !== -1) ? ($row[$idx] ?? null) : null;
             };

            $qty = (float)($getValue('on_hand_qty') ?? 0);
            
            // Boolean Checks
            $isVendorRentVal = $getValue('is_vendor_rent');
            $isVendorRent = !empty($isVendorRentVal) && $isVendorRentVal !== false && $isVendorRentVal !== 'False';
            
            $inStockVal = $getValue('in_stock');
            // If explicit "In Stock?" column exists, use it. Otherwise assume TRUE if qty > 0 (fallback logic if needed, but stick to column for now)
            $inStock = !empty($inStockVal) && $inStockVal !== false && $inStockVal !== 'False';
            
            $location = trim($getValue('location') ?? '');
            
            $reservedLot = trim($getValue('reserved_lot') ?? '');
            $rentalType = trim($getValue('rental_type') ?? '');
            
            $actualStartRental = $getValue('actual_start_rental');
            $actualEndRental = $getValue('actual_end_rental');
            $rentalId = trim($getValue('rental_id') ?? '');

            // Rental Status Check - Active if today is between start and end dates
            $isActiveRental = true;
            $startComparison = $this->compareDateToToday($actualStartRental);
            $endComparison = $this->compareDateToToday($actualEndRental);
            
            // Check if rental has started
            if ($startComparison === 1) {
                // If start date is in the future, it is PENDING
                $isActiveRental = false;
            }
            
            // Check if rental has ended
            if ($endComparison !== null && $endComparison === -1) {
                // If end date is in the past, it is EXPIRED
                $isActiveRental = false;
            }
            
            // Note: If dates are empty/null, we assume Active (existing behavior)

            // Check for SOLD
            // User clarified: "SDP/STOCK SOLD" (26 items) is valid stock.
            // "SDP/SOLD" (1 item) is the only one to exclude.
            // Be precise with the check.
            $isSold = ($location === Location::SOLD);

            if ($isSold) {
                // Skip adding to active summary counts
                // We add to items list later for record, but don't count in dashboard totals.
            } else {
                // SDP Stock Accumulator (Total Active Stock)
                $summary['sdp_stock'] += $qty;
                
                // Reserved Rental Logic (Future Start Date Only)
                if ($startComparison === 1) {
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
                        if (!empty($rentalId)) {
                            if (!isset($uniqueSubscriptionRentals[$rentalId])) {
                                $uniqueSubscriptionRentals[$rentalId] = true;
                            }
                        } else {
                            $nonIdSubscriptionItems += $qty;
                        }
                    } elseif ($rentalType === 'Regular') {
                        $summary['rental_type_summary']['Regular'] += $qty;
                        // Track unique rental contracts
                        if (!empty($rentalId)) {
                            if (!isset($uniqueRegularRentals[$rentalId])) {
                                $uniqueRegularRentals[$rentalId] = true;
                            }
                        } else {
                            $nonIdRegularItems += $qty;
                        }
                    }
                } else {
                    // Track INACTIVE (Expired) and RESERVED (Future) Subscriptions
                    if ($rentalType === 'Subscription') {
                        if ($endComparison !== null && $endComparison === -1) {
                            // Expired: end date is in the past
                            $summary['inactive_subscription'] += $qty;
                        } elseif ($startComparison === 1) {
                            // Reserved: start date is in the future
                            $summary['reserved_subscription'] += $qty;
                        }
                    }
                }
            }

            // In Stock
            if (!$isSold && $inStock) {
                $summary['in_stock']['total'] += $qty;
                
                // --- RENTAL STATUS BREAKDOWN ---
                // Helper vars
                $sRentalId = $rentalId;
                $sLotNo = $lotNo;
                $sReservedLot = $reservedLot;
                $sIsOriginal = (!empty($sRentalId) && $sLotNo == $sReservedLot);
                $sRentalCount = $rentalIdCounts[$sRentalId] ?? 0;
                
                // Check Reserve (Future Start)
                // Use compareDateToToday for both Excel serial and Odoo ISO formats
                $isFuture = ($startComparison === 1);
                
                if ($isFuture) {
                    $summary['in_stock']['rental_status']['reserve'] += $qty;
                } elseif ($isActiveRental && !empty($sRentalId)) {
                    // Active Rental in Stock
                    if ($sIsOriginal) {
                        if ($sRentalCount > 1) {
                            $summary['in_stock']['rental_status']['original_with_replace'] += $qty;
                        } else {
                            $summary['in_stock']['rental_status']['original_without_replace'] += $qty;
                        }
                    } else {
                        // Active Rental but IS Replacement in stock?
                        // Treat as Pure Stock for now or track separately if needed.
                        // For the user request "Original in stock breakdown", we focus on originals.
                        // If we add to pure_stock efficiently:
                        $summary['in_stock']['rental_status']['pure_stock'] += $qty; 
                    }
                } else {
                    // No Active Rental ID (Pure Stock or Expired)
                    $summary['in_stock']['rental_status']['pure_stock'] += $qty;
                }
                
                // Categorize Location
                if (stripos($location, 'Operation') !== false) {
                     $summary['in_stock']['details'][Location::OPERATION]['count'] += $qty; 
                } else {
                     $loc = $location; 
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
            elseif (!$isSold && $location == Location::RENTAL_CUSTOMER) {
                $summary['rented_in_customer']['total'] += $qty;
                $rentalIdCount = $rentalIdCounts[$rentalId] ?? 0;
                
                // Sub-logic
                if ($isVendorRent) {
                     $summary['rented_in_customer']['details']['Vendor Rent'] += $qty;
                } elseif ($this->inventoryService->isOriginal($lotNo, $reservedLot)) {
                     $summary['rented_in_customer']['details']['Original in Customer'] += $qty;
                } elseif ($this->inventoryService->isReplacement($lotNo, $reservedLot, $rentalId, $isVendorRent)) {
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
            elseif (!$isSold && stripos($location, Location::SERVICE_EXTERNAL) === 0) {
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
            elseif (!$isSold && $location == Location::SERVICE_INTERNAL) {
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
            elseif (!$isSold && stripos($location, Location::INSURANCE) === 0) {
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
                'internal_reference' => $getValue('internal_reference') ?? '',
                'year' => $getValue('year') ?? '',
                'purchase_date' => $this->excelDateToCarbon($getValue('purchase_date')),
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
                'category_flags' => $this->determineCategory($summary, $location, $isVendorRent, $lotNo, $reservedLot, $rentalId, $qty),
                'vehicle_role' => $vehicleRole,
                'linked_vehicle' => null, // Will be populated in second pass
                'rental_id_count' => $rentalIdCounts[$rentalId] ?? 0,
                'last_customer' => $row[$idxParams['last_customer']] ?? null,
                'current_customer' => $row[$idxParams['current_customer']] ?? null,
                'warehouse' => $row[$idxParams['warehouse']] ?? null,
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
        $summary['unique_rental_contracts']['Subscription'] = count($uniqueSubscriptionRentals) + $nonIdSubscriptionItems;
        $summary['unique_rental_contracts']['Regular'] = count($uniqueRegularRentals) + $nonIdRegularItems;
        
        return ['summary' => $summary, 'items' => $items];
    }
    
    // Helper to tag items for drilldown (simplified for DB)
    private function determineCategory($summary, $location, $isVendorRent, $lotNo, $reservedLot, $rentalId, $qty) {
        $tags = [];
        if ($isVendorRent) $tags[] = 'vendor_rent';
        return $tags;
    }

    public function saveToDatabase($items, $summary, $source = 'excel', $filename = null)
    {
        // 0. Log Import Start
        $importLog = \App\Models\ImportLog::create([
            'source' => $source,
            'filename' => $filename,
            'imported_at' => now(),
            'items_count' => count($items),
            'summary_json' => $summary,
            'status' => 'success',
        ]);

        try {
            // 1. Save Items
            \App\Models\Item::truncate();
        
        $chunkedItems = array_chunk($items, 500);
        foreach ($chunkedItems as $chunk) {
            $insertData = [];
            foreach ($chunk as $item) {
                // Convert Dates
                $start = $this->excelDateToCarbon($item['actual_start_rental']);
                $end = $this->excelDateToCarbon($item['actual_end_rental']);
                
                $insertData[] = [
                    'product' => $item['product'],
                    'lot_number' => $item['lot_number'],
                    'internal_reference' => $item['internal_reference'],
                    'year' => $item['year'],
                    'purchase_date' => $item['purchase_date'],
                    'location' => $item['location'],
                    'on_hand_quantity' => $item['on_hand_quantity'],
                    'is_vendor_rent' => $item['is_vendor_rent'],
                    'is_on_hand' => $item['is_on_hand'],
                    'in_stock' => $item['in_stock'], 
                    // 'is_stock' => $item['in_stock'], // Mapping if renamed
                    'is_sold' => $item['is_sold'],
                    'is_active_rental' => $item['is_active_rental'],
                    'rental_id' => $item['rental_id'],
                    'reserved_lot' => $item['reserved_lot'],
                    'rental_type' => $item['rental_type'],
                    'actual_start_rental' => $start,
                    'actual_end_rental' => $end,
                    'km_last' => $item['km_last'],
                    'vehicle_role' => $item['vehicle_role'],
                    'rental_id_count' => $item['rental_id_count'],
                    'linked_vehicle' => $item['linked_vehicle'],
                    'category_flags' => json_encode($item['category_flags']),
                    'last_customer' => $item['last_customer'],
                    'current_customer' => $item['current_customer'],
                    'warehouse' => $item['warehouse'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            \App\Models\Item::insert($insertData);
        }

        // 2. Save History Snapshot
        $history = \App\Models\History::updateOrCreate(
            ['snapshot_date' => now()->toDateString()],
            [
                'sdp_stock' => $summary['sdp_stock'],
                'in_stock' => $summary['in_stock']['total'],
                'rented' => $summary['rented_in_customer']['total'],
                'in_service' => $summary['stock_external_service']['total'] + $summary['stock_internal_service']['total'] + ($summary['stock_insurance']['total'] ?? 0),
                'summary_json' => $summary,
            ]
        );
        
        // Force update timestamp even if data is identical (for "Updated X mins ago" display)
        if ($history->wasRecentlyCreated === false) {
             $history->touch();
        }
        
        // 3. Update Metadata (optional now as History handles date, but for compat)
        // We can retire the JSON metadata file usage in Controller.
        } catch (\Exception $e) {
            // Update import log with failure status
            $importLog->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function excelDateToCarbon($serial)
    {
        if (empty($serial)) return null;
        
        try {
            // Handle Excel serial date (numeric)
            if (is_numeric($serial)) {
                // Excel base date: 1899-12-30
                $date = \Carbon\Carbon::create(1899, 12, 30)->addDays((int)$serial);
                return $date->format('Y-m-d');
            }
            
            // Handle ISO date string from Odoo (e.g., '2026-02-06' or '2026-02-06 00:00:00')
            if (is_string($serial)) {
                $date = \Carbon\Carbon::parse($serial);
                return $date->format('Y-m-d');
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Compare a date (Excel serial or ISO string) to today
     * Returns: -1 if date < today, 0 if same day, 1 if date > today, null if invalid
     */
    private function compareDateToToday($dateValue): ?int
    {
        if (empty($dateValue)) return null;
        
        try {
            $today = \Carbon\Carbon::now()->startOfDay();
            
            // Handle Excel serial date (numeric)
            if (is_numeric($dateValue)) {
                $baseDate = \Carbon\Carbon::create(1899, 12, 30);
                $date = $baseDate->copy()->addDays((int)$dateValue)->startOfDay();
            }
            // Handle ISO date string from Odoo
            elseif (is_string($dateValue)) {
                $date = \Carbon\Carbon::parse($dateValue)->startOfDay();
            }
            else {
                return null;
            }
            
            if ($date->lt($today)) return -1;
            if ($date->gt($today)) return 1;
            return 0;
        } catch (\Exception $e) {
            return null;
        }
    }
}
