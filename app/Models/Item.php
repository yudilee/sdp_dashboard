<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'on_hand_quantity' => 'float',
        'is_vendor_rent' => 'boolean',
        'is_on_hand' => 'boolean',
        'is_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_sold' => 'boolean',
        'is_active_rental' => 'boolean',
        'actual_start_rental' => 'date',
        'actual_end_rental' => 'date',
        'category_flags' => 'array',
        'km_last' => 'float',
        'rental_id_count' => 'integer',
    ];

    // Scopes for common queries
    public function scopeActiveRental($query)
    {
        return $query->where('is_active_rental', true);
    }
    
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeVendorRent($query)
    {
        return $query->where('is_vendor_rent', true);
    }

    public function scopeSold($query)
    {
        return $query->where('is_sold', true);
    }
    
    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
