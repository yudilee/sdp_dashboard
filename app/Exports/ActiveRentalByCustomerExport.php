<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActiveRentalByCustomerExport implements FromArray, WithHeadings, WithStyles
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            '#',
            'Customer',
            'Lot Count',
            'Lot Numbers',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $idx = 1;
        foreach ($this->data as $row) {
            $lots = collect($row['lots'])->pluck('lot_number')->implode(', ');
            $rows[] = [
                $idx++,
                $row['customer'],
                $row['lot_count'],
                $lots,
            ];
        }
        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
