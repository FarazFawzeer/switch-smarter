@extends('layouts.technician', ['subtitle' => 'My Timesheet'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Dashboard', 'subtitle' => 'My Timesheet'])

    <p class="text-muted small mb-3">{{ now()->format('F Y') }}</p>

    <div class="row g-2 mb-3">
        <div class="col-6">
            <div class="card text-center mb-0">
                <div class="card-body py-3">
                    <p class="text-muted small mb-1">Total Hours</p>
                    <p class="fw-bold mb-0 fs-22">{{ number_format($totalHours, 1) }}</p>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card text-center mb-0">
                <div class="card-body py-3">
                    <p class="text-muted small mb-1">OT Hours</p>
                    <p class="fw-bold mb-0 fs-22" style="color:#D64545;">{{ number_format($totalOt, 1) }}</p>
                </div>
            </div>
        </div>
    </div>

    @forelse($records as $record)
        <div class="card mb-2">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="fw-bold mb-1">{{ $record->work_date->format('d M Y') }}</p>
                        <p class="text-muted small mb-0">
                            {{ $record->check_in_time?->format('h:i A') ?? '—' }} – {{ $record->check_out_time?->format('h:i A') ?? '—' }}
                        </p>
                    </div>
                    <div class="text-end">
                        <p class="mb-0 fw-bold">{{ $record->total_hours ?? '—' }} hrs</p>
                        @if($record->ot_hours > 0)
                            <p class="mb-0 small" style="color:#D64545;">+{{ $record->ot_hours }} OT</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">
            <iconify-icon icon="solar:clock-circle-outline" style="font-size: 40px; opacity: 0.4;"></iconify-icon>
            <p class="mt-2 mb-0">No attendance records yet this month.</p>
        </div>
    @endforelse
@endsection