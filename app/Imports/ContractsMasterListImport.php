<?php

namespace App\Imports;

use App\Models\Contract;
use App\Models\Route;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContractsMasterListImport implements ToCollection, WithHeadingRow
{
    /** @var string[] One line per contract successfully created */
    public array $createdContracts = [];

    /** @var string[] One line per row that was skipped, with the reason */
    public array $errors = [];

    /** Header row is row 1, so the first data row is row 2 */
    private int $rowOffset = 2;

    public function collection(Collection $rows)
    {
        $groups = [];
        $order = [];

        foreach ($rows as $i => $row) {
            $excelRow = $i + $this->rowOffset;

            $projectName  = trim((string) ($row['project_name'] ?? ''));
            $location     = trim((string) ($row['location'] ?? ''));
            $unitId       = trim((string) ($row['unit_id'] ?? ''));
            $engineerName = trim((string) ($row['engineer_name'] ?? ''));
            $startDate    = $this->parseDate($row['contract_start_date'] ?? null);
            $endDate      = $this->parseDate($row['contract_end_date'] ?? null);

            // Skip a fully blank row (e.g. trailing empty rows in the sheet)
            if ($projectName === '' && $location === '' && $unitId === '') {
                continue;
            }

            $rowErrors = [];
            if ($projectName === '') $rowErrors[] = 'Project Name is required';
            if ($location === '') $rowErrors[] = 'Location is required';
            if (! $startDate) $rowErrors[] = 'Contract Start Date is required/invalid';
            if (! $endDate) $rowErrors[] = 'Contract End Date is required/invalid';
            if ($startDate && $endDate && $endDate->lt($startDate)) $rowErrors[] = 'Contract End Date must be after Start Date';
            if ($unitId === '') $rowErrors[] = 'Unit ID is required';
            if ($engineerName === '') $rowErrors[] = 'Engineer Name is required';

            $engineer = null;
            if ($engineerName !== '') {
                $engineer = User::where('type', 'Engineer')
                    ->whereRaw('LOWER(name) = ?', [strtolower($engineerName)])
                    ->first();

                if (! $engineer) {
                    $rowErrors[] = "Engineer \"{$engineerName}\" not found (must match an existing Engineer's name exactly)";
                }
            }

            if (! empty($rowErrors)) {
                $this->errors[] = "Row {$excelRow}: " . implode('; ', $rowErrors);
                continue;
            }

            $key = strtolower($projectName) . '|' . strtolower($location) . '|' .
                $startDate->format('Y-m-d') . '|' . $endDate->format('Y-m-d');

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'project_name'        => $projectName,
                    'project_type'        => trim((string) ($row['project_type'] ?? '')),
                    'location'            => $location,
                    'contract_start_date' => $startDate,
                    'contract_end_date'   => $endDate,
                    'route_no'            => trim((string) ($row['route_no'] ?? '')),
                    'engineer_id'         => $engineer->id,
                    'units'               => [],
                ];
                $order[] = $key;
            }

            $groups[$key]['units'][] = [
                'identification_no' => $unitId,
                'unit_type'         => $this->normalizeChoice((string) ($row['unit_type'] ?? ''), ['Elevator', 'Escalator', 'Dumbwaiter'], 'Elevator'),
                'elevator_type'     => $this->normalizeChoice((string) ($row['elevator_type'] ?? ''), ['Passenger', 'Service', 'Freight']),
                'speed'             => trim((string) ($row['speed'] ?? '')) ?: null,
                'capacity'          => trim((string) ($row['capacity'] ?? '')) ?: null,
                'brand'             => trim((string) ($row['brand'] ?? '')) ?: null,
                'model'             => trim((string) ($row['model'] ?? '')) ?: null,
            ];
        }

        if (empty($groups)) {
            return;
        }

        DB::transaction(function () use ($groups, $order) {
            foreach ($order as $key) {
                $group = $groups[$key];

                $routeId = null;
                if ($group['route_no'] !== '') {
                    $route = Route::firstOrCreate(['route_no' => $group['route_no']]);
                    $routeId = $route->id;
                }

                $contract = Contract::create([
                    'project_name'         => $group['project_name'],
                    'project_type'         => $group['project_type'] !== '' ? $group['project_type'] : null,
                    'location'             => $group['location'],
                    'contract_start_date'  => $group['contract_start_date'],
                    'contract_end_date'    => $group['contract_end_date'],
                    'number_of_elevators'  => count($group['units']),
                    'route_id'             => $routeId,
                    'assigned_engineer_id' => $group['engineer_id'],
                    'status'               => 'active',
                    'custom_fields'        => [],
                    'created_by'           => Auth::id(),
                    'contract_number'      => $this->generateContractNumber(),
                ]);

                foreach ($group['units'] as $unit) {
                    $unit['custom_fields'] = [];
                    $contract->elevatorUnits()->create($unit);
                }

                $this->createdContracts[] = $contract->project_name . ' (' . $contract->contract_number . ') — ' . count($group['units']) . ' unit(s)';
            }
        });
    }

    private function normalizeChoice(string $value, array $allowed, ?string $default = null): ?string
    {
        $value = trim($value);
        if ($value === '') return $default;

        foreach ($allowed as $option) {
            if (strcasecmp($option, $value) === 0) return $option;
        }

        return $value; // keep whatever was typed if it doesn't match a known option
    }

    private function parseDate($value): ?Carbon
    {
        if (blank($value)) return null;

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            } catch (\Throwable $e) {
                return null;
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function generateContractNumber(): string
    {
        $last = Contract::lockForUpdate()->orderByDesc('id')->first();

        $nextNumber = 1;

        if ($last && $last->contract_number && preg_match('/(\d+)$/', $last->contract_number, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } elseif ($last) {
            $nextNumber = $last->id + 1;
        }

        return str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }
}