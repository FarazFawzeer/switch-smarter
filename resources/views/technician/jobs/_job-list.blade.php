@forelse($paginatedJobs as $job)
    @php
        $typeColorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $typeLabelMap = ['ppm' => 'PPM', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
        $statusMap = [
            'completed'   => ['#2E9E5B', 'Completed'],
            'in_progress' => ['#17A2B8', 'In Progress'],
            'pending'     => ['#F0A202', 'Pending'],
            'on_hold'     => ['#8792a2', 'On Hold'],
        ];
        $isOverdue = $job->status === 'pending' && $job->scheduled_date->isPast();
        [$statusColor, $statusLabel] = $isOverdue ? ['#D64545', 'Overdue'] : ($statusMap[$job->status] ?? ['#8792a2', ucfirst($job->status)]);
        $typeColor = $typeColorMap[$job->job_type] ?? '#8792a2';
    @endphp
    <a href="{{ route('technician.activities.show', $job->id) }}" class="text-decoration-none">
        <div class="card mb-2" style="border-left: 4px solid {{ $typeColor }};">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="badge mb-1" style="background:{{ $typeColor }}1A; color:{{ $typeColor }}; font-weight:700;">{{ $typeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }}</span>
                        <p class="mb-1 fw-bold text-dark">{{ optional($job->unit)->identification_no ?? 'Unassigned unit' }}{{ optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : '' }}</p>
                        <p class="mb-1 small text-muted">
                            {{ optional($job->contract)->project_name ?? 'Unknown project' }}
                            @if($job->job_code) · {{ $job->job_code }} @endif
                        </p>
                        <p class="mb-0 small text-muted">
                            <x-ui-icon name="calendar" size="13" />
                            {{ $job->scheduled_date->format('d M Y') }}
                        </p>
                    </div>
                    <span class="badge" style="background:{{ $statusColor }}; color:#fff;">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="text-center text-muted py-5">
        <x-ui-icon name="calendar" size="40" style="opacity: 0.4" />
        <p class="mt-2 mb-0">
            @php
                $statusNames = ['pending' => 'pending', 'in_progress' => 'in-progress', 'completed' => 'completed', 'on_hold' => 'on-hold'];
                $statusWord = $statusNames[request('status')] ?? null;
            @endphp
            @if(request('unit_search'))
                No {{ $statusWord ? $statusWord . ' ' : '' }}jobs found for "{{ request('unit_search') }}" this month.
            @elseif($statusWord)
                No {{ $statusWord }} jobs this month.
            @else
                No jobs assigned for this month.
            @endif
        </p>
    </div>
@endforelse

<div class="d-flex justify-content-center mt-3">
    {{ $paginatedJobs->links() }}
</div>