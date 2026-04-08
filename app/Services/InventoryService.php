<?php

namespace App\Services;

use App\Constants\Location;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;

class InventoryService
{
    /**
     * Determine if an item is an Original unit in a rental pair.
     * Criteria: Lot number matches the Reserved Lot number.
     */
    public function isOriginal(string $lotNumber, ?string $reservedLot): bool
    {
        return !empty($reservedLot) && $lotNumber === $reservedLot;
    }

    /**
     * Determine if an item is a Replacement unit in a rental pair.
     * Criteria: Has Rental ID but Lot Number does NOT match Reserved Lot.
     */
    public function isReplacement(string $lotNumber, ?string $reservedLot, ?string $rentalId, bool $isVendorRent): bool
    {
        return !empty($rentalId) && !$isVendorRent && !$this->isOriginal($lotNumber, $reservedLot);
    }

    /**
     * Determine if a rental is currently active based on dates.
     */
    public function isActiveRental(?string $start, ?string $end, string $today): bool
    {
        // If dates are missing, assume active (legacy logic)
        if (empty($start) && empty($end)) return true;
        
        // Use string comparison for Y-m-d format
        $started = empty($start) || $start <= $today;
        $notEnded = empty($end) || $end >= $today;
        
        return $started && $notEnded;
    }

    /**
     * Check if item is in specific location via string matching
     */
    public function isInLocation(string $itemLocation, string $targetLocation): bool
    {
        return stripos($itemLocation, $targetLocation) !== false;
    }

    /**
     * Scope: In Stock items
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('in_stock', true);
    }

    /**
     * Scope: Rented items (Partners/Customers/Rental)
     */
    public function scopeRented(Builder $query): Builder
    {
        return $query->where('location', Location::RENTAL_CUSTOMER);
    }
    
    /**
     * Scope: External Service
     */
    public function scopeExternalService(Builder $query): Builder
    {
        return $query->where('location', 'like', Location::SERVICE_EXTERNAL . '%');
    }

    /**
     * Scope: Internal Service
     */
    public function scopeInternalService(Builder $query): Builder
    {
        return $query->where('location', Location::SERVICE_INTERNAL);
    }
    
    /**
     * Scope: Insurance
     */
    public function scopeInsurance(Builder $query): Builder
    {
        return $query->where('location', 'like', Location::INSURANCE . '%');
    }

    /**
     * Scope: All Service (Internal + External + Insurance)
     */
    public function scopeInService(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('location', Location::SERVICE_INTERNAL)
              ->orWhere('location', 'like', Location::SERVICE_EXTERNAL . '%')
              ->orWhere('location', 'like', Location::INSURANCE . '%');
        });
    }

    /**
     * Scope: SDP Owned (Not Vendor Rent)
     */
    public function scopeSdpOwned(Builder $query): Builder
    {
        return $query->where('is_vendor_rent', false);
    }

    /**
     * Scope: Active Rentals (Rented in Customer OR Original w/o Replacement in Service/Stock)
     */
    public function scopeActiveRentals(Builder $query): Builder
    {
        return $query->where(function ($q) {
            // 1. Rented in Customer
            $q->where('location', Location::RENTAL_CUSTOMER)
            // 2. In Service (Active) = In Service + Original + No Replace (Count=1)
              ->orWhere(function ($sub) {
                  $this->scopeInService($sub)
                       ->whereColumn('lot_number', 'reserved_lot')
                       ->whereNotNull('rental_id')
                       ->where('rental_id_count', 1);
              })
            // 3. In Stock (Active) = In Stock + Original + No Replace (Count=1)
              ->orWhere(function ($sub) {
                  $this->scopeInStock($sub)
                       ->whereColumn('lot_number', 'reserved_lot')
                       ->whereNotNull('rental_id')
                       ->where('rental_id_count', 1);
              });
        });
    }

    /**
     * Apply complex nested filters
     */
    public function applyAdvancedFilters(Builder $query, array $group): Builder
    {
        $rules = $group['rules'] ?? [];
        
        $query->where(function ($q) use ($rules) {
            foreach ($rules as $index => $rule) {
                // Logic: AND / OR for this rule relative to the chain
                $logic = isset($rule['logic']) ? strtoupper($rule['logic']) : 'AND';

                // Closure to apply the single rule
                $applyRule = function($subQ) use ($rule) {
                    // Check if it's a nested group (recursive)
                    if (isset($rule['rules'])) {
                        $this->applyAdvancedFilters($subQ, $rule);
                    } else {
                        // It's a condition
                        $this->applyCondition($subQ, $rule['field'] ?? '', $rule['operator'] ?? '', $rule['value'] ?? '');
                    }
                };

                if ($index === 0) {
                    $q->where($applyRule);
                } else {
                    if ($logic === 'OR') {
                        $q->orWhere($applyRule);
                    } else {
                        $q->where($applyRule);
                    }
                }
            }
        });

        return $query;
    }

    /**
     * Apply a single condition to the query
     */
    public function applyCondition(Builder $query, string $field, string $op, $value): Builder
    {
        if ($field === 'category') {
            // Map 'value' to logic
            if ($value === 'in_stock') $this->scopeInStock($query);
            elseif ($value === 'rented') $this->scopeRented($query);
            elseif ($value === 'active_rentals') $this->scopeActiveRentals($query); // New scope
            elseif ($value === 'overdue_rentals') {
                // Vehicles still at customer location with rental end date today or past
                $today = now()->format('Y-m-d');
                $query->where('location', Location::RENTAL_CUSTOMER)
                      ->where('is_sold', false)
                      ->whereNotNull('actual_end_rental')
                      ->whereDate('actual_end_rental', '<=', $today);
            }
            elseif ($value === 'in_service') $this->scopeInService($query);
            elseif ($value === 'vendor_rent') $query->where('is_vendor_rent', true);
            elseif ($value === 'stock_pure') {
                $this->scopeInStock($query)->where(function($q) {
                    $q->whereNull('rental_id')->orWhere('rental_id', '');
                });
            }
            elseif ($value === 'stock_reserve') {
                $today = now()->format('Y-m-d');
                $this->scopeInStock($query)->whereNotNull('rental_id')->where('actual_start_rental', '>', $today);
            }
            elseif ($value === 'rented_visual') {
                $query->where('location', Location::RENTAL_CUSTOMER);
            }
            elseif ($value === 'rented_original') {
                $this->scopeRented($query)
                    ->whereColumn('lot_number', 'reserved_lot')
                    ->whereNotNull('reserved_lot')
                    ->where('reserved_lot', '!=', '')
                    ->where('is_vendor_rent', false);
            }
            elseif ($value === 'rented_replacement_service') {
                 $this->scopeRented($query)
                    ->whereNotNull('rental_id')
                    ->where('rental_id', '!=', '')
                    ->whereColumn('lot_number', '!=', 'reserved_lot')
                    ->where('is_vendor_rent', false)
                    ->where('product_movement_count', '>', 1);
            }
            elseif ($value === 'rented_replacement_rbo') {
                 $this->scopeRented($query)
                    ->whereNotNull('rental_id')
                    ->where('rental_id', '!=', '')
                    ->whereColumn('lot_number', '!=', 'reserved_lot')
                    ->where('is_vendor_rent', false)
                    ->where('product_movement_count', '=', 1);
            }
             elseif ($value === 'rented_check_position') {
                 $this->scopeRented($query)
                    ->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); })
                    ->where('is_vendor_rent', false);
            }
            elseif ($value === 'stock_original_with_replace') {
                $this->scopeInStock($query)->whereColumn('lot_number', 'reserved_lot')->whereNotNull('rental_id')->where('rental_id_count', '>', 1);
            }
            elseif ($value === 'stock_original_no_replace') {
                $this->scopeInStock($query)->whereColumn('lot_number', 'reserved_lot')->whereNotNull('rental_id')->where('rental_id_count', 1);
            }
            elseif (str_starts_with($value, 'service_')) {
                 $parts = explode('_', $value, 3); // service, TYPE, SUB
                 if (count($parts) < 3) {
                     if ($value == 'service_external') $this->scopeExternalService($query);
                     elseif ($value == 'service_internal') $this->scopeInternalService($query);
                     elseif ($value == 'service_insurance') $this->scopeInsurance($query);
                     return $query;
                 }
                 
                 $type = $parts[1]; // external, internal, insurance
                 $sub = $parts[2]; // original_with_replace, etc.
                 
                 if ($type == 'external') $this->scopeExternalService($query);
                 elseif ($type == 'internal') $this->scopeInternalService($query);
                 elseif ($type == 'insurance') $this->scopeInsurance($query);
                 
                 if ($sub == 'original_with_replace') {
                      $query->whereNotNull('rental_id')->whereColumn('lot_number', 'reserved_lot')->where('rental_id_count', '>', 1);
                 } elseif ($sub == 'original_no_replace') {
                      $query->whereNotNull('rental_id')->whereColumn('lot_number', 'reserved_lot')->where('rental_id_count', 1);
                 } elseif ($sub == 'rented_replacement') {
                      $query->whereNotNull('rental_id')->whereColumn('lot_number', '!=', 'reserved_lot');
                 } elseif ($sub == 'stock') {
                      $query->where(function($q) { $q->whereNull('rental_id')->orWhere('rental_id', ''); });
                 }
            }
            
            return $query;
        }

        if ($op === 'contains') {
            $query->where($field, 'like', '%' . $value . '%');
        } elseif ($op === 'not_contains') {
            $query->where($field, 'not like', '%' . $value . '%');
        } elseif ($op === 'starts_with') {
            $query->where($field, 'like', $value . '%');
        } elseif ($op === 'ends_with') {
            $query->where($field, 'like', '%' . $value);
        } elseif ($op === 'is_empty') {
            $query->where(function($q) use ($field) {
                $q->whereNull($field)->orWhere($field, '');
            });
        } elseif ($op === 'is_not_empty') {
             $query->whereNotNull($field)->where($field, '!=', '');
        } else {
            // =, !=, >, <, >=, <=
            $query->where($field, $op, $value);
        }

        return $query;
    }
}
