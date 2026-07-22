<?php

namespace App\Imports;

use App\Models\ElevatorUnit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ElevatorUnitsImport implements ToModel, WithHeadingRow, WithValidation
{
    protected int $contractId;

    public function __construct(int $contractId)
    {
        $this->contractId = $contractId;
    }

    public function model(array $row)
    {
        return new ElevatorUnit([
            'contract_id'       => $this->contractId,
            'identification_no' => $row['identification_no'] ?? null,
            'unit_type'         => in_array($row['unit_type'] ?? '', ['Elevator', 'Escalator', 'Dumbwaiter'])
                                        ? $row['unit_type'] : 'Elevator',
            'elevator_type'     => $row['elevator_type'] ?? null,
            'speed'             => $row['speed'] ?? null,
            'capacity'          => $row['capacity'] ?? null,
            'brand'             => $row['brand'] ?? null,
            'model'             => $row['model'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'identification_no' => 'required|string|max:100',
        ];
    }
}