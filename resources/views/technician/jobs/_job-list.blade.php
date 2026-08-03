@forelse($paginatedJobs as $job)
    @php
        $statusMap = [
            'completed'   => ['#2E9E5B', 'Completed'],
            'in_progress' => ['#17A2B8', 'In Progress'],
            'pending'     => ['#F0A202', 'Pending'],
        ];
        $isOverdue = $job->status === 'pending' && $job->scheduled_date->isPast();
        [$color, $label] = $isOverdue ? ['#D64545', 'Overdue'] : ($statusMap[$job->status] ?? ['#8792a2', ucfirst($job->status)]);
    @endphp
    <a href="{{ route('technician.jobs.show', $job->id) }}" class="text-decoration-none">
        <div class="card mb-2" style="">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="mb-1 fw-bold text-dark">{{ optional($job->unit)->identification_no ?? 'Unassigned unit' }}</p>
                        <p class="mb-1 small text-muted">{{ optional($job->contract)->project_name ?? 'Unknown project' }}</p>
                        <p class="mb-0 small text-muted">
                            <iconify-icon icon="solar:calendar-outline" style="font-size:13px;"></iconify-icon>
                            {{ $job->scheduled_date->format('d M Y') }}
                        </p>
                    </div>
                    <span class="badge" style="background:{{ $color }}; color:#fff;">{{ $label }}</span>
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="text-center text-muted py-5">
        <iconify-icon icon="solar:calendar-mark-outline" style="font-size: 40px; opacity: 0.4;"></iconify-icon>
        <p class="mt-2 mb-0">
            @if(request('unit_search'))
                No jobs found for unit "{{ request('unit_search') }}" in this period.
            @else
                No jobs assigned for this period.
            @endif
        </p>
    </div>
@endforelse

<div class="d-flex justify-content-center mt-3">
    {{ $paginatedJobs->links() }}
</div>