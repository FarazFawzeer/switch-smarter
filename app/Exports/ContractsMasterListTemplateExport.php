<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ContractsMasterListTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            'Project Name',
            'Project Type',
            'Location',
            'Contract Start Date',
            'Contract End Date',
            'Route No',
            'Engineer Name',
            'Unit ID',
            'Unit Number',
            'Unit Type',
            'Elevator Type',
            'Speed',
            'Capacity',
            'Brand',
            'Model',
        ];
    }

    /**
     * Sample rows so the client sees exactly how to fill it in.
     * Repeat the project columns (A-G) on every unit row that
     * belongs to the same project — that's what groups rows into
     * one contract with multiple elevator units.
     */
    public function array(): array
    {
        return [
            [
                'Liberty Plaza', 'Commercial', 'Colombo 03', '2026-01-01', '2027-01-01',
                'RT-01', 'John Perera', 'LP-A-01', 'L1', 'Elevator', 'Passenger', '1.5 m/s', '1000kg / 13 persons', 'KONE', 'MonoSpace 500',
            ],
            [
                'Liberty Plaza', 'Commercial', 'Colombo 03', '2026-01-01', '2027-01-01',
                'RT-01', 'John Perera', 'LP-A-02', 'L2', 'Elevator', 'Passenger', '1.5 m/s', '1000kg / 13 persons', 'KONE', 'MonoSpace 500',
            ],
            [
                'City Tower', 'Residential', 'Colombo 05', '2026-02-01', '2027-02-01',
                'RT-02', 'Nimal Fernando', 'CT-B-01', 'SL3', 'Escalator', 'Passenger', '0.5 m/s', '', 'Otis', 'Gen2',
            ],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '071A3D'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22, 'B' => 16, 'C' => 18, 'D' => 18, 'E' => 18,
            'F' => 12, 'G' => 20, 'H' => 14, 'I' => 13, 'J' => 14,
            'K' => 14, 'L' => 14, 'M' => 18, 'N' => 14, 'O' => 16,
        ];
    }
}