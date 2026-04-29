<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ItemsExport implements FromQuery, WithHeadings, WithMapping
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Lot Number',
            'Product',
            'Internal Reference',
            'Year',
            'Vendor Unit',
            'Location',
            'Warehouse',
            'Quantity',
            'Type (Custom)',
            'Rental Status (Custom)',
            'Is Vendor Rent',
            'Is On Hand',
            'Is Stock',
            'In Stock',
            'Is Sold',
            'Is Active Rental',
            'Rental ID',
            'Reserved Lot',
            'Rental Type',
            'Actual Start',
            'Actual End',
            'KM Last',
            'Vehicle Role',
            'Rental ID Count',
            'Linked Vehicle',
            'Last Customer',
            'Current Customer',
            'Repair Order Name',
            'Repair State',
            'Repair Schedule Date',
            'Repair Service Type',
            'Repair Vendor',
            'Repair Odometer',
            'Repair Estimation End',
        ];
    }

    public function map($item): array
    {
        $type = $item->is_vendor_rent ? 'Vendor' : 'Owned';
        
        $status = '-';
        if ($item->rental_id) {
             $status = $item->rental_type; // e.g., Subscription, Regular
        } elseif ($item->in_stock) {
             $status = 'In Stock';
        }

        return [
            $item->lot_number,
            $item->product,
            $item->internal_reference,
            $item->year,
            $item->vendor_unit,
            $item->location,
            $item->warehouse,
            $item->on_hand_quantity,
            $type,
            $status,
            $item->is_vendor_rent ? 'Yes' : 'No',
            $item->is_on_hand ? 'Yes' : 'No',
            $item->is_stock ? 'Yes' : 'No',
            $item->in_stock ? 'Yes' : 'No',
            $item->is_sold ? 'Yes' : 'No',
            $item->is_active_rental ? 'Yes' : 'No',
            $item->rental_id,
            $item->reserved_lot,
            $item->rental_type,
            $item->actual_start_rental ? $item->actual_start_rental->format('Y-m-d') : '',
            $item->actual_end_rental ? $item->actual_end_rental->format('Y-m-d') : '',
            $item->km_last,
            $item->vehicle_role,
            $item->rental_id_count,
            $item->linked_vehicle,
            $item->last_customer,
            $item->current_customer,
            $item->repair_order_name,
            $item->repair_state,
            $item->repair_schedule_date ? $item->repair_schedule_date->format('Y-m-d') : '',
            $item->repair_service_type,
            $item->repair_vendor,
            $item->repair_odometer,
            $item->repair_estimation_end ? $item->repair_estimation_end->format('Y-m-d') : '',
        ];
    }
}
