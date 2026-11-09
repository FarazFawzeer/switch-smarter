@extends('layouts.vertical', ['subtitle' => 'Idle / Travel Time'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Timesheets', 'subtitle' => 'Idle / Travel Time'])

    @php $fmt = fn($m) => \App\Services\IdleTimeCalculator::format((int) $m); @endphp

    <style>
        .it-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .it-stat { border-radius: 12px; background: var(--ss-surface-2, #F5F7FA); border: 1px solid var(--ss-border, #e7e9ee); padding: 12px 16px; }
        .it-stat .lbl { font-size: 12px; color: var(--ss-text-2, #56606f); font-weight: 600; }
        .it-stat .num { font-size: 22px; font-weight: 800; color: var(--ss-text, #16233b); }
        .it-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        .it-table thead th { background: var(--ss-surface-2, #F5F7FA); color: var(--ss-text-2, #56606f); font-weight: 600; padding: 10px 12px; border-bottom: 1px solid var(--ss-border, #e7e9ee); white-space: nowrap; }
        .it-table tbody td { padding: 11px 12px; border-bottom: 1px solid var(--ss-border, #f0f1f4); vertical-align: top; }
        .it-gap { display: inline-block; font-size: 11px; font-weight: 600; background: rgba(224,138,0,0.10); color: #B26D00; border-radius: 6px; padding: 2px 8px; margin: 1px 4px 2px 0; white-space: nowrap; }
        .it-job { display: inline-block; font-size: 11px; background: var(--ss-surface-2, #F5F7FA); border: 1px solid var(--ss-border, #e7e9ee); border-radius: 6px; padding: 2px 7px; margin: 1px 4px 2px 0; white-space: nowrap; }
        .it-pill { display: inline-block; font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 6px; }
        .it-leave { background: rgba(122,111,208,0.14); color: #7A6FD0; }
        .it-work { background: rgba(46,158,91,0.14); color: #2E9E5B; }
        .it-idle { background: rgba(224,138,0,0.14); color: #B26D00; }
    </style>

    <form method="GET" action="{{ route('admin.idle-time.index') }}" id="filterForm">
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">
    </form>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['date' => $date->copy()->subDay()->toDateString()]) }}"><x-ui-icon name="chevron-left" size="14" /></a>
        <input type="date" class="form-control form-control-sm" style="width: 160px;" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}"
            onchange="const f=document.getElementById('filterForm'); f.date.value=this.value; f.submit();">
        @if (! $date->isToday())
            <a class="btn btn-sm btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['date' => $date->copy()->addDay()->toDateString()]) }}"><x-ui-icon name="chevron-right" size="14" /></a>
            <a class="btn btn-sm btn-link" href="{{ route('admin.idle-time.index') }}">Today</a>
        @endif
        <span class="text-muted small ms-2">{{ $date->format('l, d M Y') }}</span>
    </div>

    <x-active-filters :filters="['technician' => 'Technician']" :keep="['date']" />

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3"><div class="it-stat"><div class="lbl">Technicians worked</div><div class="num">{{ $totals['technicians'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="it-stat"><div class="lbl">Working time</div><div class="num">{{ $fmt($totals['worked']) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="it-stat"><div class="lbl">Idle / travel time</div><div class="num" style="color:#B26D00;">{{ $fmt($totals['idle']) }}</div></div></div>
        <div class="col-6 col-md-3"><div class="it-stat"><div class="lbl">On leave</div><div class="num" style="color:#7A6FD0;">{{ $totals['on_leave'] }}</div></div></div>
    </div>

    <div class="alert alert-light border small mb-3">
        Idle / travel time is the time <strong>between</strong> one job and the next. Example: job 8:00–10:00 AM, next job 12:00–2:00 PM → <strong>2h</strong> idle / travel.
        Time before the first job and after the last job is not counted. Technicians on approved leave are never counted as working or idle.
    </div>

    <div class="card it-card">
        <div class="table-responsive">
            <table class="it-table">
                <thead>
                    <tr>
                        <th><x-th-filter label="Technician" name="technician" /></th>
                        <th class="text-center">Jobs</th>
                        <th>First start</th>
                        <th>Last finish</th>
                        <th>Working</th>
                        <th>Idle / Travel</th>
                        <th>Between jobs</th>
                        <th>Jobs that day</th>
                        @if ($date->isToday())<th>Now</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ optional($row['technician'])->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ optional($row['technician'])->employee_id }}</div>
                            </td>
                            <td class="text-center">{{ $row['jobs'] }}</td>
                            <td class="text-nowrap">{{ $row['first_start']?->format('h:i A') ?? '—' }}</td>
                            <td class="text-nowrap">{{ $row['last_end']?->format('h:i A') ?? ($row['jobs'] ? 'On job' : '—') }}</td>
                            <td class="text-nowrap">{{ $row['jobs'] ? $fmt($row['worked_min']) : '—' }}</td>
                            <td class="text-nowrap fw-bold" style="color: {{ $row['idle_min'] >= 60 ? '#B26D00' : 'inherit' }};">{{ $row['jobs'] ? $fmt($row['idle_min']) : '—' }}</td>
                            <td>
                                @forelse ($row['gaps'] as $gap)
                                    <span class="it-gap">{{ $gap['from']->format('h:i A') }} – {{ $gap['to']->format('h:i A') }} · {{ $fmt($gap['minutes']) }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td>
                                @if ($row['on_leave'] && ! $row['jobs'])
                                    <span class="it-pill it-leave">On approved leave</span>
                                @endif
                                @foreach ($row['job_list'] as $job)
                                    <span class="it-job" title="{{ optional($job->contract)->project_name }}">
                                        {{ $job->job_code ?? strtoupper($job->job_type) }} · {{ $job->check_in_time->format('h:i') }}–{{ $job->check_out_time?->format('h:i A') ?? 'now' }}
                                    </span>
                                @endforeach
                            </td>
                            @if ($date->isToday())
                                <td class="text-nowrap">
                                    @if ($row['on_leave'])
                                        <span class="it-pill it-leave">On leave</span>
                                    @elseif (($row['now']['type'] ?? null) === 'working')
                                        <span class="it-pill it-work">{{ $row['now']['label'] }}</span>
                                    @elseif ($row['now'])
                                        <span class="it-pill it-idle">Idle since {{ optional($row['now']['since'])->format('h:i A') }}</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">No technician work recorded on this day.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
