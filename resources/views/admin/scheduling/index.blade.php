@extends('layouts.vertical', ['subtitle' => 'PPM Scheduling'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Scheduling'])

    <style>
        .sc-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .sc-intro { background: #F5F7FA; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #56606f; display: flex; align-items: flex-start; gap: 10px; }
        .sc-list-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .sc-list-table thead th { background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #e7e9ee; white-space: nowrap; }
        .sc-list-table tbody td { padding: 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .sc-list-table tbody tr:hover { background: #FAFBFD; }
        .sc-pill { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; min-width: 90px; text-align: center; }
        .sc-pill.scheduled { background: #2E9E5B; color: #fff; }
        .sc-pill.pending { background: #F0A202; color: #fff; }
        .sc-badge-renewed { background: rgba(23,162,184,0.10); color: #17A2B8; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 5px; margin-left: 6px; }

        /* Row actions: equal-height buttons, centred in the cell */
        .sc-act-group { display: inline-flex; align-items: center; gap: 6px; }
        .sc-act-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            height: 32px; border-radius: 8px; font-size: 12.5px; font-weight: 600;
            text-decoration: none; white-space: nowrap; transition: all .15s ease;
        }
        .sc-act-btn iconify-icon { font-size: 15px; }
        .sc-act-btn.view { width: 78px; color: #2E5AAC; background: #fff; border: 1px solid #cfe0f6; }
        .sc-act-btn.view:hover { background: #eaf0fb; border-color: #2E5AAC; }
        .sc-act-btn.schedule { width: 106px; color: #fff; background: #2E5AAC; border: 1px solid #2E5AAC; }
        .sc-act-btn.schedule:hover { background: #1F4590; border-color: #1F4590; color: #fff; }

        .sc-view-toggle { border: 1px solid #e7e9ee; border-radius: 8px; overflow: hidden; }
        .sc-view-toggle a { padding: 6px 14px; font-size: 13px; color: #6b7280; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .sc-view-toggle a.active { background: #2E5AAC; color: #fff; }

        /* Calendar */
        .sc-cal-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); overflow: hidden; }
        .sc-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); }
        .sc-cal-head { background: #F5F7FA; color: #56606f; font-weight: 700; font-size: 12px; text-align: center; padding: 10px 0; }
        .sc-cal-day { min-height: 110px; border-right: 1px solid #f0f1f4; border-bottom: 1px solid #f0f1f4; padding: 6px; position: relative; }
        .sc-cal-day.out-month { background: #FAFBFD; }
        .sc-cal-day.out-month .sc-cal-daynum { color: #c3c9d1; }
        .sc-cal-daynum { font-size: 12px; color: #56606f; font-weight: 600; margin-bottom: 4px; display: inline-block; }
        .sc-cal-daynum.today { background: #2E5AAC; color: #fff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
        .sc-cal-pill {
            display: block; font-size: 10px; font-weight: 600; padding: 2px 6px; border-radius: 4px;
            margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none;
        }
        .sc-cal-more { font-size: 10px; color: #8792a2; padding-left: 4px; }
    </style>

    <div class="sc-intro">
        <iconify-icon icon="solar:info-circle-outline" style="font-size: 18px; flex-shrink:0;"></iconify-icon>
        <div>
            Click <strong>Schedule PPM</strong> to assign a supervisor and technician for a project's route and set the first maintenance visit date.
            The visit then repeats every month automatically until the contract ends.
        </div>
    </div>

    {{-- View toggle + month selector (calendar) --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        @if($view === 'calendar')
            <form method="GET" action="{{ route('admin.scheduling.index') }}" id="calMonthForm" class="d-flex align-items-center gap-2">
                <input type="hidden" name="view" value="calendar">
                <select name="month" class="form-select form-select-sm filter-auto" style="width: 140px;">
                    @foreach($monthOptions as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="year" class="form-select form-select-sm filter-auto" style="width: 100px;">
                    @foreach($yearOptions as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </form>
        @else
            <div></div>
        @endif

        <div class="d-flex align-items-center gap-2">
            <div class="sc-view-toggle d-flex">
                <a href="{{ route('admin.scheduling.index', ['view' => 'calendar']) }}" class="{{ $view === 'calendar' ? 'active' : '' }}">
                    <iconify-icon icon="solar:calendar-outline"></iconify-icon> Calendar View
                </a>
                <a href="{{ route('admin.scheduling.index', ['view' => 'list']) }}" class="{{ $view === 'list' ? 'active' : '' }}">
                    <iconify-icon icon="solar:list-outline"></iconify-icon> List View
                </a>
            </div>

            @if($view === 'calendar')
                <select name="route_id" form="calMonthForm" class="form-select form-select-sm filter-auto" style="width: 150px;">
                    <option value="">All Routes</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_no }}</option>
                    @endforeach
                </select>
                <select name="supervisor_id" form="calMonthForm" class="form-select form-select-sm filter-auto" style="width: 170px;">
                    <option value="">All Supervisors</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}" {{ request('supervisor_id') == $supervisor->id ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                    @endforeach
                </select>
                <select name="technician_id" form="calMonthForm" class="form-select form-select-sm filter-auto" style="width: 170px;">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" {{ request('technician_id') == $technician->id ? 'selected' : '' }}>{{ $technician->name }}</option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @if($view === 'calendar')
        {{-- ===== CALENDAR VIEW ===== --}}
        @php
            $pillColors = ['#DCEFDA', '#DCEEF7', '#F7E9D9', '#F3DCEF', '#DCEEF0', '#EFEBDC', '#E3DCF7'];
            $pillTextColors = ['#2E7D4F', '#1F6FA6', '#B5730A', '#8E3D8E', '#1F7A82', '#8A7A1F', '#5A3FA0'];
        @endphp
        <div class="card sc-cal-card">
            <div class="sc-cal-grid">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dayName)
                    <div class="sc-cal-head">{{ $dayName }}</div>
                @endforeach
            </div>

            @foreach($calendarWeeks as $week)
                <div class="sc-cal-grid">
                    @foreach($week as $day)
                        <div class="sc-cal-day {{ $day['inMonth'] ? '' : 'out-month' }}">
                            <span class="sc-cal-daynum {{ $day['isToday'] ? 'today' : '' }}">{{ $day['date']->day }}</span>

                            @foreach($day['jobs']->take(3) as $i => $job)
                                @php
                                    $colorIndex = $job->contract_id % count($pillColors);
                                @endphp
                                <a href="{{ route('admin.scheduling.show', $job->contract_id) }}?unit_id={{ $job->elevator_unit_id }}"
                                    class="sc-cal-pill" style="background:{{ $pillColors[$colorIndex] }}; color:{{ $pillTextColors[$colorIndex] }};"
                                    title="{{ optional($job->unit)->identification_no }}{{ optional($job->unit)->unit_number ? ' (' . $job->unit->unit_number . ')' : '' }} — {{ optional($job->contract)->project_name }} — {{ optional($job->technician)->name ?? 'Unassigned' }}">
                                    {{ optional($job->unit)->identification_no }}
                                </a>
                            @endforeach

                            @if($day['jobs']->count() > 3)
                                <span class="sc-cal-more">+{{ $day['jobs']->count() - 3 }} more</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <script>
            document.querySelectorAll('.filter-auto').forEach(select => {
                select.addEventListener('change', function() {
                    document.getElementById('calMonthForm').submit();
                });
            });
        </script>

    @else
        {{-- ===== LIST VIEW (unchanged) ===== --}}
        <form method="GET" action="{{ route('admin.scheduling.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
            <input type="hidden" name="view" value="list">
            <div class="col-auto">
                <input type="text" name="search" id="searchInput" class="form-control" style="width: 200px;"
                    placeholder="Search by project or location..." value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <select name="engineer_id" class="form-select filter-auto" style="width: 160px;">
                    <option value="">All Engineers</option>
                    @foreach($engineers as $engineer)
                        <option value="{{ $engineer->id }}" {{ request('engineer_id') == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="supervisor_id" class="form-select filter-auto" style="width: 170px;">
                    <option value="">All Supervisors</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}" {{ request('supervisor_id') == $supervisor->id ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="technician_id" class="form-select filter-auto" style="width: 170px;">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" {{ request('technician_id') == $technician->id ? 'selected' : '' }}>{{ $technician->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="scheduled" class="form-select filter-auto" style="width: 170px;">
                    <option value="">All Contracts</option>
                    <option value="yes" {{ request('scheduled') == 'yes' ? 'selected' : '' }}>Fully Scheduled</option>
                    <option value="partial" {{ request('scheduled') == 'partial' ? 'selected' : '' }}>Partially Scheduled</option>
                    <option value="no" {{ request('scheduled') == 'no' ? 'selected' : '' }}>Not Scheduled</option>
                </select>
            </div>
            @if(request()->hasAny(['search', 'engineer_id', 'supervisor_id', 'technician_id', 'scheduled']))
                <div class="col-auto">
                    <a href="{{ route('admin.scheduling.index') }}" class="btn btn-outline-secondary" title="Clear filters">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                    </a>
                </div>
            @endif
        </form>

        <div class="card sc-card">
            <div class="table-responsive">
                <table class="sc-list-table">
                    <thead>
                        <tr>
                            <th>S/N</th>
                            <th>Project</th>
                            <th>Engineer</th>
                            <th>Route</th>
                            <th>Supervisor</th>
                            <th>Technician</th>
                            <th>Units</th>
                            <th>Contract Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contracts as $contract)
                            @php
                                $statusLabelMap = [
                                    'not_scheduled' => ['Not Scheduled', 'pending'],
                                    'partial'       => ['Partially Scheduled', 'pending'],
                                    'complete'      => ['Scheduled', 'scheduled'],
                                    'no_units'      => ['No Units', 'none'],
                                ];
                                [$contractStatusLabel, $contractStatusClass] = $statusLabelMap[$contract->scheduling_status] ?? ['Unknown', 'none'];
                            @endphp
                            <tr>
                                <td>{{ $contracts->firstItem() + $loop->index }}</td>
                                <td>
                                    <a href="{{ route('admin.contracts.show', $contract->id) }}" class="fw-semibold text-dark text-decoration-none">
                                        {{ $contract->project_name }}
                                    </a>
                                    <div class="text-muted" style="font-size:12px;">
                                        {{ $contract->location }}
                                        @if ($contract->renewals->count())
                                            <span class="sc-badge-renewed">Renewed {{ $contract->renewals->count() }}x</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ optional($contract->engineer)->name ?? '—' }}</td>
                                <td>
                                    @if ($contract->route)
                                        <span class="badge badge-soft-secondary">{{ $contract->route->route_no }}</span>
                                    @else
                                        <span class="text-muted">Not set</span>
                                    @endif
                                </td>
                                <td>{{ optional($contract->supervisor)->name ?? '—' }}</td>
                                <td>{{ $contract->technician_names ?: (optional($contract->technician)->name ?? '—') }}</td>
                                <td>{{ $contract->scheduled_units_count ?? 0 }} / {{ $contract->total_units_count ?? 0 }}</td>
                                <td>
                                    <span class="sc-pill {{ $contractStatusClass }}">{{ $contractStatusLabel }}</span>
                                </td>
                                @php
                                    $canView = in_array($contract->scheduling_status, ['complete', 'partial']);
                                    $canSchedule = in_array($contract->scheduling_status, ['partial', 'not_scheduled']);
                                @endphp
                                <td class="text-center">
                                    <div class="sc-act-group">
                                        @if ($canView)
                                            <a href="{{ route('admin.scheduling.show', $contract->id) }}" class="sc-act-btn view"
                                                title="Open the PPM schedule">
                                                <iconify-icon icon="solar:eye-outline"></iconify-icon> View
                                            </a>
                                        @endif

                                        @if ($canSchedule)
                                            <a href="{{ route('admin.scheduling.create', $contract->id) }}" class="sc-act-btn schedule"
                                                title="{{ $contract->scheduling_status === 'partial' ? 'Schedule the remaining units' : 'Schedule PPM for this project' }}">
                                                <iconify-icon icon="solar:calendar-add-outline"></iconify-icon> Schedule
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-5">
                                    <iconify-icon icon="solar:calendar-mark-outline" style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                    <p class="mb-0 mt-2">No active projects found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-2">
            {{ $contracts->links() }}
        </div>

        <script>
            document.querySelectorAll('.filter-auto').forEach(select => {
                select.addEventListener('change', function() {
                    document.getElementById('filterForm').submit();
                });
            });

            let searchTimeout;
            document.getElementById('searchInput').addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 600);
            });
        </script>
    @endif
@endsection