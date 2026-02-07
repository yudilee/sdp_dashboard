<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportLog extends Model
{
    protected $fillable = [
        'source',
        'filename',
        'imported_at',
        'items_count',
        'summary_json',
        'status',
        'error_message',
    ];

    protected $casts = [
        'imported_at' => 'datetime',
        'summary_json' => 'array',
    ];

    /**
     * Get a display-friendly label for the source
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'excel' => 'Excel Upload',
            'odoo_manual' => 'Odoo (Manual)',
            'odoo_scheduled' => 'Odoo (Scheduled)',
            default => ucfirst($this->source),
        };
    }

    /**
     * Get status color class for display
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'text-green-600 dark:text-green-400',
            'failed' => 'text-red-600 dark:text-red-400',
            default => 'text-slate-600 dark:text-slate-400',
        };
    }

    /**
     * Scope for successful imports
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed imports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
