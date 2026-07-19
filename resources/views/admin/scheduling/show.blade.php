@extends('layouts.vertical', ['subtitle' => 'PPM Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Schedule'])

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                <p class="card-subtitle">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
            </div>
            <span class="badge badge-soft-success">Scheduled</span>
        </div>
        <div class="row mb-3">
    <div class="col-md-4">
        <span class="text-muted small">Engineer</span>
        <p class="mb-0">{{ optional($contract->engineer)->name ?? '—' }}</p>
    </div>
    <div class="col-md-4">
        <span class="text-muted small">Supervisor</span>
        <p class="mb-0">{{ optional($contract->supervisor)->name ?? '—' }}</p>
    </div>
    <div class="col-md-4">
        <span class="text-muted small">Default Technician</span>
        <p class="mb-0">{{ optional($contract->technician)->name ?? '—' }}</p>
    </div>
</div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <span class="text-muted small">PPM Start Date</span>
                    <p class="mb-0">{{ $contract->ppm_start_date->format('d M Y') }}</p>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small">Total Visits Scheduled</span>
                    <p class="mb-0">{{ $contract->ppmJobs->count() }}</p>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small">Next Upcoming Visit</span>
                    <p class="mb-0">
                        {{ optional($contract->ppmJobs->where('status', 'pending')->first())->scheduled_date?->format('d M Y') ?? '—' }}
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Scheduled Date</th>
                            <th>Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contract->ppmJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                                <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                                <td>
                                    @php
                                        $statusClass = match($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => 'badge-soft-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>
@endsection