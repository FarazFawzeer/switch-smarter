@extends('layouts.technician', ['subtitle' => 'Dashboard'])

@section('content')

<style>
    /* Profile card — dark navy, matches topbar/bottomnav */
    .th-profile-card {
        background: linear-gradient(135deg, #071A3D, #0F2A5C);
        border-radius: 16px; padding: 18px; box-shadow: 0 4px 14px rgba(7,26,61,0.25);
        display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
    }
    .th-profile-card img {
        width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.25);
    }
    .th-profile-name { color: #fff; font-weight: 700; font-size: 16px; }
    .th-profile-role { color: #AEBBD0; font-size: 13px; }
    .th-online-dot { width: 8px; height: 8px; border-radius: 50%; background: #2E9E5B; display: inline-block; margin-right: 5px; box-shadow: 0 0 0 3px rgba(46,158,91,0.2); }
    .th-online-text { color: #6FE3A0; font-size: 12px; font-weight: 600; }

    .th-section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .th-section-title span:first-child { font-weight: 700; color: #16233b; font-size: 15px; }
    .th-section-title a { font-size: 12px; color: #071A3D; text-decoration: none; font-weight: 600; }

    .th-overview-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 1px 4px rgba(15,42,67,0.06); margin-bottom: 16px; }
    .th-overview-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .th-stat-tile { display: block; border-radius: 12px; padding: 14px 6px; text-align: center; color: #fff; text-decoration: none; transition: transform .12s ease, box-shadow .12s ease; }
    .th-stat-tile:hover, .th-stat-tile:focus { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(15,42,67,0.18); }
    .th-stat-tile:active { transform: scale(0.97); }
    .th-stat-tile .num { font-size: 22px; font-weight: 800; line-height: 1; }
    .th-stat-tile .lbl { font-size: 10.5px; margin-top: 5px; opacity: 0.95; font-weight: 600; }

    .th-schedule-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 1px 4px rgba(15,42,67,0.06); margin-bottom: 24px; }
    .th-schedule-item {
        display: flex; align-items: center; gap: 12px; padding: 11px 4px; text-decoration: none; color: inherit;
    }
    .th-schedule-item + .th-schedule-item { border-top: 1px solid #f0f1f4; }
    .th-schedule-icon {
        width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; color: #fff; flex-shrink: 0;
    }
    .th-schedule-item .title { font-weight: 700; color: #16233b; font-size: 14px; }
    .th-schedule-item .sub { font-size: 12px; color: #8792a2; }
    .th-schedule-item .time { font-size: 12px; color: #8792a2; margin-left: auto; white-space: nowrap; font-weight: 600; }

    .th-status { display: inline-block; margin-left: 6px; padding: 2px 8px; border-radius: 10px; font-size: 10.5px; font-weight: 700; vertical-align: middle; }
    .th-status-ongoing { background: rgba(46,134,222,0.12); color: #2E86DE; }
    .th-status-done { background: rgba(46,158,91,0.12); color: #2E9E5B; }
    .th-status-hold { background: rgba(135,146,162,0.15); color: #56606f; }
    .th-status-todo { background: rgba(240,162,2,0.12); color: #B57900; }

    /* Today's Schedule status groups */
    .th-group-head {
        display: flex; align-items: center; gap: 6px; margin: 12px 0 2px; padding: 6px 10px;
        border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
    }
    .th-group-head:first-of-type { margin-top: 4px; }
    .th-group-head .ui-icon { width: 16px; height: 16px; }
    .th-group-count { margin-left: auto; background: #fff; border-radius: 10px; padding: 0 8px; font-size: 11px; }
    .th-group-ongoing { background: rgba(46,134,222,0.10); color: #2E86DE; }
    .th-group-hold { background: rgba(135,146,162,0.14); color: #56606f; }
    .th-group-todo { background: rgba(240,162,2,0.12); color: #B57900; }
    .th-group-done { background: rgba(46,158,91,0.10); color: #2E9E5B; }
    .th-group-head + .th-schedule-item { border-top: none; }
    .th-schedule-item.is-done .title, .th-schedule-item.is-done .sub { opacity: .7; }

    /* Compact floating action button — bottom right, above the bottom nav */
    .th-fab {
        position: fixed; bottom: 82px; right: 20px; z-index: 999;
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #2E5AAC, #1F4590); color: #fff;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 16px rgba(46,90,172,0.4);
        text-decoration: none; font-size: 26px;
    }
    .th-fab:hover { color: #fff; }
</style>

{{-- Profile card --}}
<div class="th-profile-card">
    <img src="{{ Auth::user()->image_path ? asset(Auth::user()->image_path) : asset('images/users/avatar-17.jpg') }}" alt="">
    <div>
        <div class="th-profile-name">{{ Auth::user()->name }}</div>
        <div class="th-profile-role">Technician</div>
        <div class="th-online-text"><span class="th-online-dot"></span>Online</div>
    </div>
</div>

{{-- Job Overview --}}
<div class="th-overview-card">
    <div class="th-section-title">
        <span>Job Overview</span>
        <a href="{{ route('technician.jobs.browse') }}">View All</a>
    </div>
    <div class="th-overview-grid">
        {{-- each tile opens the Jobs page filtered to that status --}}
        <a href="{{ route('technician.jobs.browse', ['status' => 'pending']) }}" class="th-stat-tile" style="background:#F0A202;">
            <div class="num">{{ $pendingCount }}</div>
            <div class="lbl">Pending</div>
        </a>
        <a href="{{ route('technician.jobs.browse', ['status' => 'in_progress']) }}" class="th-stat-tile" style="background:#2E86DE;">
            <div class="num">{{ $inProgressCount }}</div>
            <div class="lbl">In Progress</div>
        </a>
        <a href="{{ route('technician.jobs.browse', ['status' => 'completed']) }}" class="th-stat-tile" style="background:#2E9E5B;">
            <div class="num">{{ $completedCount }}</div>
            <div class="lbl">Completed</div>
        </a>
        <a href="{{ route('technician.jobs.browse', ['status' => 'on_hold']) }}" class="th-stat-tile" style="background:#8792a2;">
            <div class="num">{{ $onHoldCount }}</div>
            <div class="lbl">On Hold</div>
        </a>
    </div>
</div>

{{-- Today's Schedule --}}
<div class="th-schedule-card">
    <div class="th-section-title">
        <span>Today's Schedule</span>
        <a href="{{ route('technician.schedule') }}">View All</a>
    </div>

    @php
        $typeColorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $typeIconMap = ['ppm' => 'calendar', 'casual_job' => 'briefcase', 'repair' => 'wrench', 'breakdown' => 'alert'];
        $typeLabelMap = ['ppm' => 'PPM', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
    @endphp

    @php
        // Today's jobs grouped by work status: what's in hand first, finished work last
        $statusGroups = [
            'in_progress' => ['Ongoing', 'ongoing', 'play-circle'],
            'on_hold'     => ['On Hold', 'hold', 'pause-circle'],
            'pending'     => ['Not Started', 'todo', 'clock'],
            'completed'   => ['Completed', 'done', 'check-circle'],
        ];
        $grouped = $todaysSchedule->groupBy(fn ($job) => array_key_exists($job->status, $statusGroups) ? $job->status : 'pending');
    @endphp

    @if($todaysSchedule->isEmpty())
        <p class="text-center text-muted small py-3 mb-0">Nothing scheduled for today.</p>
    @endif

    @foreach($statusGroups as $statusKey => [$groupLabel, $statusClass, $groupIcon])
        @continue(! $grouped->has($statusKey))

        <div class="th-group-head th-group-{{ $statusClass }}">
            <x-ui-icon :name="$groupIcon" size="16" stroke="2.2" />
            {{ $groupLabel }}
            <span class="th-group-count">{{ $grouped[$statusKey]->count() }}</span>
        </div>

        @foreach($grouped[$statusKey] as $job)
            <a href="{{ route('technician.activities.show', $job->id) }}" class="th-schedule-item {{ $statusKey === 'completed' ? 'is-done' : '' }}">
                <div class="th-schedule-icon" style="background:{{ $typeColorMap[$job->job_type] ?? '#8792a2' }};">
                    <x-ui-icon :name="$typeIconMap[$job->job_type] ?? 'clipboard'" size="20" stroke="2" />
                </div>
                <div>
                    <div class="title">
                        {{ optional($job->unit)->identification_no ?? '—' }}
                        @if(optional($job->unit)->unit_number)
                            <span class="text-muted fw-normal" style="font-size:12px;">({{ $job->unit->unit_number }})</span>
                        @endif
                        <span class="th-status th-status-{{ $statusClass }}">{{ $groupLabel }}</span>
                    </div>
                    <div class="sub">
                        {{ $typeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }}
                        @if(optional($job->contract)->project_name) · {{ $job->contract->project_name }} @endif
                    </div>
                </div>
                <div class="time">
                    @if($job->check_out_time)
                        {{ $job->check_out_time->format('h:i A') }}
                    @elseif($job->check_in_time)
                        {{ $job->check_in_time->format('h:i A') }}
                    @endif
                </div>
            </a>
        @endforeach
    @endforeach
</div>

<a href="{{ route('technician.activities.select') }}" class="th-fab" aria-label="New Activity">
    <x-ui-icon name="plus" />
</a>

@endsection