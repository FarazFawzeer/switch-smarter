@extends('layouts.vertical', ['subtitle' => $contract->project_name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => $contract->project_name])

    @php
        $statusColor = match($contract->status) {
            'active' => 'success', 'expired' => 'warning', 'cancelled' => 'danger', default => 'secondary',
        };
    @endphp

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                    <span class="badge badge-soft-{{ $statusColor }}">{{ ucfirst($contract->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><span class="text-muted small">Location</span><p class="mb-0">{{ $contract->location }}</p></div>
                        <div class="col-md-6"><span class="text-muted small">Contract Number</span><p class="mb-0">{{ $contract->contract_number ?? '—' }}</p></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6"><span class="text-muted small">Start Date</span><p class="mb-0">{{ $contract->contract_start_date->format('d M Y') }}</p></div>
                        <div class="col-md-6"><span class="text-muted small">End Date</span><p class="mb-0">{{ $contract->contract_end_date->format('d M Y') }}</p></div>
                    </div>
                    @if($contract->contract_document)
                        <a href="{{ storage_asset($contract->contract_document) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <iconify-icon icon="solar:file-text-outline"></iconify-icon> View Contract Document
                        </a>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Elevator / Unit Details ({{ $contract->elevatorUnits->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered">
                            <thead class="table-light">
                                <tr>
                                    <th>ID No</th><th>Unit Type</th><th>Elevator Type</th><th>Speed</th><th>Capacity</th><th>Brand</th><th>Model</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contract->elevatorUnits as $unit)
                                    <tr>
                                        <td>{{ $unit->identification_no }}</td>
                                        <td>{{ $unit->unit_type }}</td>
                                        <td>{{ $unit->elevator_type ?? '—' }}</td>
                                        <td>{{ $unit->speed ?? '—' }}</td>
                                        <td>{{ $unit->capacity ?? '—' }}</td>
                                        <td>{{ $unit->brand ?? '—' }}</td>
                                        <td>{{ $unit->model ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h5 class="card-title mb-0">Staff Assignment</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="text-muted small">Route No</span>
                        <p class="mb-0">{{ $contract->route_no ?? '—' }}</p>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small">Engineer</span>
                        <p class="mb-0">{{ optional($contract->engineer)->name ?? '—' }}</p>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small">Supervisor</span>
                        <p class="mb-0">{{ optional($contract->supervisor)->name ?? '—' }}</p>
                    </div>
                    <div class="mb-0">
                        <span class="text-muted small">Technician</span>
                        <p class="mb-0">{{ optional($contract->technician)->name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.contracts.edit', $contract->id) }}" class="btn btn-primary flex-fill">Edit Project</a>
                <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary flex-fill">Back to List</a>
            </div>
        </div>
    </div>
@endsection