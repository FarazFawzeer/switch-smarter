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
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <span class="text-muted small">Engineer</span>
                    <p class="mb-0">{{ optional($contract->engineer)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Supervisor</span>
                    <p class="mb-0">{{ optional($contract->supervisor)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Route</span>
                    <p class="mb-0">{{ optional($contract->route)->route_no ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">PPM Start Date</span>
                    <p class="mb-0">{{ $contract->ppm_start_date->format('d M Y') }}</p>
                </div>
            </div>
            @if ($contract->renewals->count())
                <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                    <iconify-icon icon="solar:refresh-outline" style="font-size: 18px;"></iconify-icon>
                    This contract has been renewed {{ $contract->renewals->count() }} time(s).
                    Latest renewal: {{ $contract->renewals->first()->new_start_date->format('d M Y') }} –
                    {{ $contract->renewals->first()->new_end_date->format('d M Y') }}.
                </div>
            @endif

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
                        @foreach ($contract->ppmJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                                <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => 'badge-soft-warning',
                                        };
                                    @endphp
                                    <span
                                        class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
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
