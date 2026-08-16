@extends('layouts.technician', ['subtitle' => 'Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Schedule', 'subtitle' => now()->format('F Y')])

    @php
        $typeColorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $typeLabelMap = ['ppm' => 'PPM', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
    @endphp

    @forelse($jobs as $date => $dayJobs)
        <p class="fw-bold small text-muted mb-2 mt-3">{{ $date }}</p>
        @foreach($dayJobs as $job)
            <a href="{{ route('technician.activities.show', $job->id) }}" class="text-decoration-none">
                <div class="card mb-2" style="border-left: 4px solid {{ $typeColorMap[$job->job_type] ?? '#8792a2' }};">
                    <div class="card-body py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-1 fw-bold text-dark">{{ optional($job->unit)->identification_no ?? '—' }}</p>
                            <p class="mb-0 small text-muted">{{ $typeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }} · {{ optional($job->contract)->project_name ?? '' }}</p>
                        </div>
                        <span class="small text-muted">{{ $job->scheduled_date->format('h:i A') }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    @empty
        <div class="text-center text-muted py-5">
            <iconify-icon icon="solar:calendar-mark-outline" style="font-size: 40px; opacity: 0.4;"></iconify-icon>
            <p class="mt-2 mb-0">No jobs scheduled this month.</p>
        </div>
    @endforelse
@endsection