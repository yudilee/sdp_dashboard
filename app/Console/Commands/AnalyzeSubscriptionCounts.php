<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SummaryGenerator;
use Maatwebsite\Excel\Facades\Excel;

class AnalyzeSubscriptionCounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:subscription-counts {file : Path to the Excel file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze subscription counts using two methods and identify discrepancies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(SummaryGenerator $generator)
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return 1;
        }

        $this->info("Analyzing file: $filePath");

        $result = $generator->generate($filePath);
        $items = $result['items'];
        $summary = $result['summary'];

        // --- ANALYSIS METHOD: Group by Rental ID ---
        $rentalGroups = [];
        $itemsWithoutId = [];
        
        // Track User Method Count per Rental ID
        $userMethodCounts = [];

        // Track and list IDs for stock breakdown
        $activeInStockWithReplace = 0;
        $activeInStockWithoutReplace = 0;
        
        $orphanedList = [];
        $vendorRentList = [];
        // $activeInStockList = []; // Replaced by specific lists
        $listStockWithReplace = [];
        $listStockWithoutReplace = [];

        foreach ($items as $item) {
            // Filter: Only Active Subscriptions
            if (!$item['is_active_rental'] || $item['rental_type'] !== 'Subscription') {
                continue;
            }

            $rentalId = $item['rental_id'];
            if (empty($rentalId)) {
                $itemsWithoutId[] = $item;
                continue;
            }
            
            // --- USER METHOD CALCULATION (Per Item) ---
            $qty = $item['on_hand_quantity'];
            $location = $item['location'];
            $lotNo = $item['lot_number'];
            $reservedLot = $item['reserved_lot'];
            $isVendorRent = $item['is_vendor_rent'];
            $isOriginal = (!empty($reservedLot) && $lotNo == $reservedLot);
            $isReplacement = (!empty($rentalId) && $lotNo != $reservedLot && !$isVendorRent);
            
            $userCountedThis = 0;
            
            // User Rules:
            // 1. Original in Customer
            if ($location == 'Partners/Customers/Rental' && $isOriginal) $userCountedThis = $qty;
            // 2. Original in Internal Service
            elseif ($location == 'Physical Locations/Service' && $isOriginal) $userCountedThis = $qty;
            // 3. Replacement in Internal Service
            elseif ($location == 'Physical Locations/Service' && $isReplacement) $userCountedThis = $qty;
            // 4. Original in External Service
            elseif (stripos($location, 'Partners/Vendors/Service') === 0 && $isOriginal) $userCountedThis = $qty;
            // 5. Replacement in External Service
            elseif (stripos($location, 'Partners/Vendors/Service') === 0 && $isReplacement) $userCountedThis = $qty;
            // 6. Original in Insurance
            elseif (stripos($location, 'Partners/Vendors/Insurance') === 0 && $isOriginal) $userCountedThis = $qty;
            
            if (!isset($userMethodCounts[$rentalId])) $userMethodCounts[$rentalId] = 0;
            $userMethodCounts[$rentalId] += $userCountedThis;

            // --- METHOD 3 CALCULATION (User's New Proposal) ---
            // 1. All Rented in Customer
            // 2. Original without replace internal service
            // 3. Original without replace external service
            // 4. Original in stock without replace
            
            // To evaluate "without replace", we need the GROUP context, not just item context.
            // So we must calculate Method 3 AFTER the loop, or build a preliminary flag list.
            // We'll process Method 3 in the group loop below.


            if (!isset($rentalGroups[$rentalId])) {
                $rentalGroups[$rentalId] = [
                    'items' => [],
                    'has_original' => false,
                    'has_replacement' => false,
                ];
            }
            
            if ($isOriginal && !empty($reservedLot)) { // Strict check
                 $rentalGroups[$rentalId]['has_original'] = true;
            } else {
                 $rentalGroups[$rentalId]['has_replacement'] = true;
            }

            $rentalGroups[$rentalId]['items'][] = $item;
        }

        // --- CATEGORIZE DISCREPANCIES ---
        $missedByList = [];
        $doubleCountedList = [];
        $correctlyCounted = 0;
        
        $method3Count = 0;

        foreach ($rentalGroups as $rid => $group) {
            $userCount = $userMethodCounts[$rid] ?? 0;
            
            // --- METHOD 3 CALCULATION (User's Refined Proposal) ---
            $rentedInCustomer = 0;
            $originalWithoutReplaceService = 0;
            $originalWithoutReplaceStock = 0;
            $originalWithoutReplaceInsurance = 0;
            $replacementInServiceInsurance = 0;
            
            $hasReplacement = false;
            
            // 1. All Rented in Customer (Sum Qty)
            foreach ($group['items'] as $item) {
                if ($item['location'] == 'Partners/Customers/Rental') {
                    $rentedInCustomer += $item['on_hand_quantity'];
                }
                
                // Identify Replacement Existence
                $isOriginal = (!empty($item['reserved_lot']) && $item['lot_number'] == $item['reserved_lot']);
                if (!$isOriginal && !$item['is_vendor_rent']) {
                    $hasReplacement = true;
                }
                
                // User removed "Rented Replacement in Service/Insurance" from the latest definition.
                // Reverting that addition to strictly follow the new request.
            }
            
            // Checks for Originals without Replace
            foreach ($group['items'] as $item) {
                $isOriginal = (!empty($item['reserved_lot']) && $item['lot_number'] == $item['reserved_lot']);
                
                // STOCK ANALYSIS (Independent Check)
                if ($isOriginal && $item['in_stock']) {
                     // Check if there are other items (replacements) in this group
                     // Definition of replacement in this context: Any identifying active unit that is not THIS original
                     // Simplest proxy: count($group['items']) > 1 (assuming duplicates merged or all active)
                     // Better: check if any OTHER item in group is a replacement
                     
                     $hasActiveRepl = false;
                     foreach ($group['items'] as $subItem) {
                         if ($subItem !== $item) { // Not the same item analysis
                             $hasActiveRepl = true; // Use more strict check if needed
                         }
                     }
                     
                     if ($hasActiveRepl) {
                         $activeInStockWithReplace++;
                         $listStockWithReplace[] = $rid;
                     } else {
                         $activeInStockWithoutReplace++;
                         $listStockWithoutReplace[] = $rid;
                     }
                }

                if ($isOriginal && !$hasReplacement) {
                    $qty = $item['on_hand_quantity'];
                    
                    // 2. Original without replace internal service
                    if ($item['location'] == 'Physical Locations/Service') {
                        $originalWithoutReplaceService += $qty;
                    }
                    // 3. Original without replace external service
                    elseif (stripos($item['location'], 'Partners/Vendors/Service') === 0) {
                        $originalWithoutReplaceService += $qty;
                    }
                    // 4. Original in stock without replace
                    elseif ($item['in_stock']) { 
                        $originalWithoutReplaceStock += $qty;
                    }
                    // 8. Original without replace in Insurance (NEW)
                    elseif (stripos($item['location'], 'Partners/Vendors/Insurance') === 0) {
                        $originalWithoutReplaceInsurance += $qty;
                    }
                }
            }
            
            $method3Count += ($rentedInCustomer + $originalWithoutReplaceService + $originalWithoutReplaceStock + $originalWithoutReplaceInsurance + $replacementInServiceInsurance);
            
            
            if ($userCount == 1) {
                // Perfect Match
                $correctlyCounted++;
            } elseif ($userCount == 0) {
                // MISSED by User
                // Why?
                $reason = 'Unknown';
                
                // Check if In Stock
                $inStock = false;
                foreach ($group['items'] as $item) if ($item['in_stock']) $inStock = true;
                
                // Check if Vendor Rent Only
                $allVR = true;
                foreach ($group['items'] as $item) if (!$item['is_vendor_rent']) $allVR = false;
                
                // Check if Orphaned (in Customer)
                $hasOriginal = $group['has_original'];
                
                if ($inStock) {
                    $reason = 'Active In Stock (Excluded by User)';
                } elseif ($allVR) {
                    $reason = 'Vendor Rent (Excluded by User)';
                } elseif (!$hasOriginal) {
                    $reason = 'Orphaned Replacement in Customer (Missing Original)';
                }
                
                $missedByList[] = ['rid' => $rid, 'reason' => $reason, 'items' => count($group['items'])];
            } elseif ($userCount > 1) {
                // DOUBLE COUNTED by User
                $doubleCountedList[] = ['rid' => $rid, 'count' => $userCount];
            }
        }

        $uniqueIdCount = count($rentalGroups) + count($itemsWithoutId);
        $totalUserMethod = array_sum($userMethodCounts);
        
        // --- REPORT ---
        $this->info("--------------------------------------------------");
        $this->info("PRECISE OVERLAP ANALYSIS");
        $this->info("--------------------------------------------------");
        $this->info("Method 1 (Dashboard - Unique IDs): $uniqueIdCount");
        $this->info("Method 2 (Physical Sum):           $totalUserMethod");
        $this->info("Method 3 (New Proposed):           $method3Count");
        $this->info("--------------------------------------------------");
        $this->info("Comparison:");
        $this->info("- Method 1 vs Method 3 Diff:       " . ($method3Count - $uniqueIdCount));
        $this->info("--------------------------------------------------");
        $this->info("Breakdown of User Method Accuracy:");
        $this->info("- Correctly Counted (1:1):     $correctlyCounted");
        $this->info("- Missed (Count 0):            " . count($missedByList));
        $this->info("- Double Counted (Count > 1):  " . count($doubleCountedList));
        $this->info("--------------------------------------------------");
        
        if (count($missedByList) > 0) {
            $this->warn("MISSED CONTRACTS (" . count($missedByList) . ")");
            // Group by reason
            $reasons = [];
            foreach ($missedByList as $m) {
                $r = $m['reason'];
                if (!isset($reasons[$r])) $reasons[$r] = 0;
                $reasons[$r]++;
            }
            foreach ($reasons as $r => $c) {
                $this->info("- $r: $c");
            }
        if ($activeInStockWithReplace > 0) {
             $this->warn("ORIGINAL IN STOCK (WITH REPLACEMENT) - $activeInStockWithReplace");
             $this->info("Original is in stock, but a replacement is active. Contract is VALID.");
             $this->info("IDs: " . implode(', ', $listStockWithReplace));
        }
        
            $this->table(['ID', 'Reason'], array_slice($missedByList, 0, 10));
        }

        if (count($doubleCountedList) > 0) {
            $this->warn("DOUBLE COUNTED CONTRACTS (" . count($doubleCountedList) . ")");
            $this->info("This happens when User sums multiple units (e.g. Original + Replacement in Service) for the same contract."); 
             $this->table(['ID', 'User Count'], $doubleCountedList);
        }

        // --- NEW COMPARISON: Replacement in Customer vs Original in Service ---
        $groupA = []; // Replacement in Customer
        $groupB = []; // Original in Service (with Replacement)
        
        foreach ($rentalGroups as $rid => $group) {
            $hasReplInCustomer = false;
            $hasOrigInService = false;
            $hasOrigInInsurance = false;
            $hasOrigInExtService = false;
            
            foreach ($group['items'] as $item) {
                $isOriginal = (!empty($item['reserved_lot']) && $item['lot_number'] == $item['reserved_lot']);
                $isReplacement = (!$isOriginal && !$item['is_vendor_rent']);
                $loc = $item['location'];
                
                // Group A: Replacement in Customer
                if ($isReplacement && $loc == 'Partners/Customers/Rental') {
                    $hasReplInCustomer = true;
                }
                
                // Group B Condition: Original in Service/Insurance/External
                if ($isOriginal) {
                    if ($loc == 'Physical Locations/Service') $hasOrigInService = true;
                    if (stripos($loc, 'Partners/Vendors/Service') === 0) $hasOrigInExtService = true;
                    if (stripos($loc, 'Partners/Vendors/Insurance') === 0) $hasOrigInInsurance = true;
                }
            }
            
            if ($hasReplInCustomer) {
                $groupA[] = $rid;
            }
            
            // Check if Original is in Service AND there is a replacement (implied by this analysis context?)
            // "original with replacement in internal, external and insurance service"
            // We check if Original is in Service AND Replacement exists (anywhere? or assumes replacement is active?)
            // Usually if Original is in Service, there MUST be a replacement for the contract to be valid.
            // But we specifically want to compare these lines.
            
            if ($group['has_replacement'] && ($hasOrigInService || $hasOrigInExtService || $hasOrigInInsurance)) {
                $groupB[] = $rid;
            }
        }
        
        $countA = count($groupA);
        $countB = count($groupB);
        
        $this->info("--------------------------------------------------");
        $this->info("COMPARISON: Replacement in Customer vs Original in Service");
        $this->info("--------------------------------------------------");
        $this->info("Group A: Replacement in Customer:                $countA");
        $this->info("Group B: Original in Service (Internal/Ext/Ins): $countB");
        $this->info("Difference:                                      " . ($countA - $countB));
        $this->info("--------------------------------------------------");
        
        // Find Mismatches
        $diffA = array_diff($groupA, $groupB); // In A but not B
        $diffB = array_diff($groupB, $groupA); // In B but not A
        
        if (count($diffA) > 0) {
            $this->warn("IN GROUP A BUT NOT B (" . count($diffA) . ")");
            $this->info("These have a Replacement in Customer, but Original is NOT in Service (maybe Sold? or Stock?)");
            $this->info("IDs: " . implode(', ', array_slice($diffA, 0, 10)) . (count($diffA)>10 ? '...' : ''));
        }
        
        if (count($diffB) > 0) {
            $this->warn("IN GROUP B BUT NOT A (" . count($diffB) . ")");
            $this->info("These have Original in Service with Replacement, but that Replacement is NOT in Customer (maybe Replacement is in Service too?)");
            $this->info("IDs: " . implode(', ', array_slice($diffB, 0, 10)) . (count($diffB)>10 ? '...' : ''));
        }

        return 0;
    }
}
