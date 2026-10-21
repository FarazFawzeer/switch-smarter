@extends('layouts.vertical', ['subtitle' => $contract->project_name])

@section('content')
    @include('layouts.partials.page-title', [
        'title' => 'Contracts',
        'subtitle' => $contract->project_name,
    ])

    <style>
        .sh-info-label {
            font-size: 12px;
            color: #8792a2;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }

        .sh-info-value {
            font-size: 14px;
            color: #16233b;
            font-weight: 500;
        }

        .sh-status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        .sh-unit-extra {
            font-size: 12px;
            color: #6b7280;
        }

        .sh-unit-extra strong {
            color: #16233b;
        }
    </style>

    @php
        $statusMap = [
            'active' => '#2E9E5B',
            'expired' => '#F0A202',
            'cancelled' => '#D64545',
        ];
        $statusColor = $statusMap[$contract->status] ?? '#8792a2';

        $typeColorMap = [
            'Residential' => '#2E5AAC',
            'Commercial' => '#17A2B8',
            'Industrial' => '#6b7280',
            'Hospital' => '#D64545',
            'Hotel' => '#9B59B6',
            'Mixed Use' => '#F0A202',
        ];
        $typeColor = $typeColorMap[$contract->project_type] ?? '#56606f';

        // Pair up custom fields two-per-row so they blend into the same grid as the fixed fields
        $customFieldPairs = collect($contract->custom_fields ?? [])->chunk(2);
    @endphp

    <div class="row">
        <div class="col-md-8">
            {{-- Project Information --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                    <div class="d-flex gap-2">
                        @if ($contract->project_type)
                            <span class="sh-status-pill"
                                style="background: {{ $typeColor }};">{{ $contract->project_type }}</span>
                        @endif
                        <span class="sh-status-pill"
                            style="background: {{ $statusColor }};">{{ ucfirst($contract->status) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="sh-info-label">Location</p>
                            <p class="sh-info-value">{{ $contract->location }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="sh-info-label">Contract Number</p>
                            <p class="sh-info-value">{{ $contract->contract_number ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="sh-info-label">Start Date</p>
                            <p class="sh-info-value">{{ $contract->contract_start_date->format('d M Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="sh-info-label">End Date</p>
                            <p class="sh-info-value">{{ $contract->contract_end_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Custom fields blended into the same grid, same style, no separate section --}}
                    @foreach ($customFieldPairs as $pair)
                        <div class="row mb-3">
                            @foreach ($pair as $field)
                                <div class="col-md-6">
                                    <p class="sh-info-label">{{ $field['label'] }}</p>
                                    <p class="sh-info-value">{{ $field['value'] ?: '—' }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    @if ($contract->contract_document)
                        <a href="{{ storage_asset($contract->contract_document) }}" target="_blank"
                            class="btn btn-sm btn-outline-secondary mt-1">
                            <iconify-icon icon="solar:file-text-outline"></iconify-icon> View Contract Document
                        </a>
                    @endif
                </div>
            </div>

            {{-- Elevator / Unit Details --}}
            {{-- Elevator / Unit Details --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Elevator / Unit Details ({{ $contract->elevatorUnits->count() }})</h5>
                </div>
                <div class="card-body">
                    @php
                        // Collect every distinct custom field label used across all units, in first-seen order
                        $unitCustomLabels = collect();
                        foreach ($contract->elevatorUnits as $unit) {
                            foreach ($unit->custom_fields ?? [] as $field) {
                                if (!$unitCustomLabels->contains($field['label'])) {
                                    $unitCustomLabels->push($field['label']);
                                }
                            }
                        }
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID No</th>
                                    <th>Unit No</th>
                                    <th>Unit Type</th>
                                    <th>Elevator Type</th>
                                    <th>Speed</th>
                                    <th>Capacity</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    @foreach ($unitCustomLabels as $label)
                                        <th>{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contract->elevatorUnits as $unit)
                                    @php
                                        $unitFieldMap = collect($unit->custom_fields ?? [])->pluck('value', 'label');
                                    @endphp
                                    <tr>
                                        <td>{{ $unit->identification_no }}</td>
                                        <td>{{ $unit->unit_number ?? '—' }}</td>
                                        <td>{{ $unit->unit_type }}</td>
                                        <td>{{ $unit->elevator_type ?? '—' }}</td>
                                        <td>{{ $unit->speed ?? '—' }}</td>
                                        <td>{{ $unit->capacity ?? '—' }}</td>
                                        <td>{{ $unit->brand ?? '—' }}</td>
                                        <td>{{ $unit->model ?? '—' }}</td>
                                        @foreach ($unitCustomLabels as $label)
                                            <td>{{ $unitFieldMap->get($label, '—') }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 8 + $unitCustomLabels->count() }}"
                                            class="text-center text-muted py-3">No units recorded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Renewal History --}}
            @if ($contract->renewals->count())
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Renewal History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Previous Term</th>
                                    <th>Renewed To</th>
                                    <th>Renewed By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($contract->renewals as $renewal)
                                    <tr>
                                        <td>{{ $renewal->previous_start_date->format('d M Y') }} –
                                            {{ $renewal->previous_end_date->format('d M Y') }}</td>
                                        <td>{{ $renewal->new_start_date->format('d M Y') }} –
                                            {{ $renewal->new_end_date->format('d M Y') }}</td>
                                        <td>{{ optional($renewal->renewedBy)->name ?? '—' }}</td>
                                        <td>{{ $renewal->created_at->format('d M Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-4">
            {{-- Route & Engineer Assignment --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Route & Engineer Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="sh-info-label">Route</p>
                        <p class="sh-info-value">
                            @if ($contract->route)
                                <span class="badge badge-soft-secondary">{{ $contract->route->route_no }}</span>
                                @if ($contract->route->description)
                                    <span class="text-muted small d-block mt-1">{{ $contract->route->description }}</span>
                                @endif
                            @else
                                <span class="text-muted">Not assigned yet</span>
                            @endif
                        </p>
                    </div>
                    <div class="mb-0">
                        <p class="sh-info-label">Engineer</p>
                        <p class="sh-info-value">{{ optional($contract->engineer)->name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Scheduling status --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">PPM Scheduling</h5>
                </div>
                <div class="card-body">
                    @if ($contract->is_scheduled)
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <iconify-icon icon="solar:check-circle-bold"
                                style="color:#2E9E5B; font-size:20px;"></iconify-icon>
                            <span class="fw-semibold">Scheduled</span>
                        </div>
                        <p class="sh-info-label mb-0">PPM Start Date</p>
                        <p class="sh-info-value">{{ optional($contract->ppm_start_date)->format('d M Y') ?? '—' }}</p>
                    @else
                        <div class="d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:clock-circle-outline"
                                style="color:#F0A202; font-size:20px;"></iconify-icon>
                            <span class="text-muted">Not scheduled yet</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('admin.contracts.edit', $contract->id) }}" class="btn btn-primary flex-fill">Edit
                    Project</a>
                @if ($contract->status === 'expired')
                    <a href="{{ route('admin.contracts.renew.form', $contract->id) }}" class="btn btn-warning flex-fill">
                        <iconify-icon icon="solar:refresh-outline" style="margin-right:4px;"></iconify-icon> Renew Contract
                    </a>
                @endif
                <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary flex-fill">Back to List</a>
            </div>
        </div>
    </div>
@endsection
