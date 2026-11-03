{{-- Live Maintenance Activity — today's work. Re-rendered every minute via admin.dashboard.live --}}
@php
    $typeMeta = [
        'ppm'        => ['PPM', '#2E5AAC', 'clipboard'],
        'breakdown'  => ['Breakdown', '#D64545', 'alert'],
        'repair'     => ['Repair', '#7B4FD6', 'settings'],
        'casual_job' => ['Casual Job', '#E08A00', 'wrench'],
    ];
    $statusMeta = [
        'in_progress' => ['In Progress', 'db-st-progress'],
        'on_hold'     => ['On Hold', 'db-st-hold'],
        'pending'     => ['Not Started', 'db-st-pending'],
        'completed'   => ['Completed', 'db-st-done'],
    ];
@endphp

<div class="db-live-stats">
    <div class="db-live-stat">
        <div class="db-live-icon" style="background: color-mix(in srgb, var(--ss-green) 15%, transparent); color: var(--ss-green);"><x-ui-icon name="user-check" size="20" /></div>
        <div>
            <div class="db-live-label">Active Technicians</div>
            <div class="db-live-num">{{ $live['active'] }} <span class="db-dot" style="background: var(--ss-green);"></span></div>
        </div>
    </div>
    <div class="db-live-stat">
        <div class="db-live-icon" style="background: color-mix(in srgb, var(--ss-blue) 15%, transparent); color: var(--ss-blue);"><x-ui-icon name="truck" size="20" /></div>
        <div>
            <div class="db-live-label">Idle / Travelling</div>
            <div class="db-live-num">{{ $live['idle'] }} <span class="db-dot" style="background: var(--ss-blue);"></span></div>
        </div>
    </div>
    @foreach ($typeMeta as $key => [$label, $color, $icon])
        <div class="db-live-type">
            <div class="db-live-label"><span style="color: {{ $color }}; display:inline-flex;"><x-ui-icon :name="$icon" size="14" stroke="2.2" /></span> {{ $label }}</div>
            <div class="db-live-num" style="color: {{ $color }};">{{ $live['by_type'][$key] }}</div>
        </div>
    @endforeach
</div>

<div class="table-responsive">
    <table class="db-live-table">
        <thead>
            <tr>
                <th>Unit / Location</th>
                <th>Type</th>
                <th>Technician</th>
                <th>Status</th>
                <th>Started</th>
                <th>Duration</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($live['jobs'] as $job)
                @php
                    [$typeLabel, $typeColor, $typeIcon] = $typeMeta[$job->job_type] ?? [ucfirst($job->job_type), '#8792a2', 'clipboard'];
                    [$statusLabel, $statusClass] = $statusMeta[$job->status] ?? [ucfirst($job->status), 'db-st-pending'];
                    $end = $job->check_out_time ?? now();
                    $minutes = $job->check_in_time ? (int) $job->check_in_time->diffInMinutes($end) : null;
                    $detailUrl = in_array($job->job_type, ['breakdown', 'repair', 'casual_job'])
                        ? route('admin.job-types.show', $job->id)
                        : route('admin.tracking.show', $job->id);
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="db-unit-ic" style="color: {{ $typeColor }};"><x-ui-icon :name="$typeIcon" size="16" /></span>
                            <div class="min-w-0">
                                <div class="db-unit-name">
                                    {{ optional($job->unit)->identification_no ?? '—' }}
                                    @if (optional($job->unit)->unit_number)<span class="db-sub fw-normal">· {{ $job->unit->unit_number }}</span>@endif
                                </div>
                                <div class="db-sub text-truncate" style="max-width: 170px;">{{ optional($job->contract)->project_name }}{{ optional($job->contract)->location ? ' — ' . $job->contract->location : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="db-type-pill" style="background: {{ $typeColor }};">{{ $typeLabel }}</span></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ storage_asset(optional($job->technician)->image_path) ?? asset('images/users/avatar-6.jpg') }}" class="db-avatar" alt="">
                            <span>{{ optional($job->technician)->name ?? 'Unassigned' }}</span>
                        </div>
                    </td>
                    <td><span class="db-status {{ $statusClass }}">{{ $statusLabel }}</span></td>
                    <td class="text-nowrap">{{ $job->check_in_time?->format('h:i A') ?? '—' }}</td>
                    <td class="text-nowrap">{{ $minutes === null ? '—' : intdiv($minutes, 60) . 'h ' . ($minutes % 60) . 'm' }}</td>
                    <td class="text-end"><a href="{{ $detailUrl }}" class="db-row-link" aria-label="Open job"><x-ui-icon name="chevron-right" size="18" /></a></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5" style="color: var(--ss-muted);">
                        <x-ui-icon name="activity" size="28" /><div class="mt-2">No maintenance activity yet today.</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="db-sub text-end mt-2">Updated {{ $live['updated_at']->format('h:i A') }} · refreshes every minute</div>
