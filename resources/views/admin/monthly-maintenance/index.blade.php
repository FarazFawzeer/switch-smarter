@extends('layouts.vertical', ['subtitle' => 'Monthly Maintenance'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'Monthly Maintenance'])

    <style>
        .mm-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .mm-stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .mm-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .mm-table thead th {
            background: #F5F7FA;
            color: #56606f;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee;
            white-space: nowrap;
        }

        .mm-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f1f4;
            vertical-align: middle;
        }

        .mm-table tbody tr:hover {
            background: #FAFBFD;
        }

        .mm-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        .mm-month-select {
            font-weight: 700;
            font-size: 14px;
        }
    </style>

    {{-- Month/Year selector --}}
    <div class="card mm-card mb-3">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.monthly-maintenance.index') }}" id="monthForm"
                class="d-flex align-items-center gap-2 flex-wrap">
                <iconify-icon icon="solar:calendar-mark-outline" style="font-size: 20px; color:#2E5AAC;"></iconify-icon>
                <select name="month" class="form-select form-select-sm mm-month-select filter-auto" style="width: 150px;">
                    @foreach ($monthOptions as $num => $name)
                        <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}
                        </option>
                    @endforeach
                </select>
                <select name="year" class="form-select form-select-sm mm-month-select filter-auto" style="width: 110px;">
                    @foreach ($yearOptions as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
                <span class="text-muted small">Showing maintenance scheduled for this month across all contracts.</span>
            </form>
        </div>
    </div>

    {{-- Overall summary --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(46,90,172,0.10); color:#2E5AAC;">
                        <iconify-icon icon="solar:clipboard-list-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->total ?? 0 }}</div>
                        <div class="small text-muted">Total PPMs</div>
                        @if (!is_null($summary->not_scheduled))
                            <div class="text-muted" style="font-size:11px;"
                                title="Units in the master list (active contracts) vs. units with a visit this month">
                                {{ $summary->scheduled ?? 0 }} scheduled · {{ $summary->not_scheduled }} not scheduled
                            </div>
                        @else
                            <div class="text-muted" style="font-size:11px;">units assigned this month</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(46,158,91,0.10); color:#2E9E5B;">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->completed ?? 0 }}</div>
                        <div class="small text-muted">Completed</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(23,162,184,0.10); color:#17A2B8;">
                        <iconify-icon icon="solar:hourglass-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->in_progress ?? 0 }}</div>
                        <div class="small text-muted">In Progress</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(240,162,2,0.10); color:#F0A202;">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->pending ?? 0 }}</div>
                        <div class="small text-muted">Pending</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(214,69,69,0.10); color:#D64545;">
                        <iconify-icon icon="solar:danger-triangle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->overdue ?? 0 }}</div>
                        <div class="small text-muted">Overdue</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card mm-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="mm-stat-icon" style="background: rgba(46,90,172,0.10); color:#2E5AAC;">
                        <iconify-icon icon="solar:pie-chart-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">
                            {{ ($summary->total ?? 0) > 0 ? round((($summary->completed ?? 0) / $summary->total) * 100) : 0 }}%
                        </div>
                        <div class="small text-muted">Completion</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mm-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.monthly-maintenance.index') }}" id="filterForm"
                class="row g-2 align-items-end">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">

                <div class="col-auto">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-sm"
                        style="width: 200px;" placeholder="Project or unit ID..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Engineer</label>
                    <select name="engineer_id" class="form-select form-select-sm filter-auto" style="width: 170px;">
                        <option value="">All Engineers</option>
                        @foreach ($engineers as $engineer)
                            <option value="{{ $engineer->id }}"
                                {{ request('engineer_id') == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Technician</label>
                    <select name="technician_id" class="form-select form-select-sm filter-auto" style="width: 190px;">
                        <option value="">All Technicians</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}"
                                {{ request('technician_id') == $technician->id ? 'selected' : '' }}>
                                {{ $technician->name }}{{ $technician->employee_id ? ' (' . $technician->employee_id . ')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Route</label>
                    <select name="route_id" class="form-select form-select-sm filter-auto" style="width: 150px;">
                        <option value="">All Routes</option>
                        @foreach ($routes as $route)
                            <option value="{{ $route->id }}"
                                {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm filter-auto" style="width: 150px;">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                        </option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress
                        </option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                @if (request()->hasAny(['search', 'engineer_id', 'technician_id', 'route_id', 'status']))
                    <div class="col-auto">
                        <a href="{{ route('admin.monthly-maintenance.index', ['month' => $month, 'year' => $year]) }}"
                            class="btn btn-outline-secondary btn-sm" title="Clear filters">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Job list --}}
    <div class="card mm-card">
        <div class="table-responsive">
            <table class="mm-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Project</th>
                        <th>Unit</th>
                        <th>Route</th>
                        <th>Engineer</th>
                        <th>Technician</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        @php
                            $isOverdue =
                                in_array($job->status, ['pending', 'overdue']) && $job->scheduled_date->isPast();
                            $statusMap = [
                                'completed' => ['Completed', '#2E9E5B'],
                                'in_progress' => ['In Progress', '#17A2B8'],
                                'pending' => ['Pending', '#F0A202'],
                            ];
                            [$label, $color] = $isOverdue
                                ? ['Overdue', '#D64545']
                                : $statusMap[$job->status] ?? [ucfirst($job->status), '#8792a2'];
                        @endphp
                        <tr>
                            <td>{{ $jobs->firstItem() + $loop->index }}</td>
                            <td>
                                <a href="{{ route('admin.contracts.show', $job->contract_id) }}"
                                    class="fw-semibold text-dark text-decoration-none">
                                    {{ optional($job->contract)->project_name ?? '—' }}
                                </a>
                                <div class="text-muted" style="font-size:12px;">{{ optional($job->contract)->location }}
                                </div>
                            </td>
                            <td class="fw-semibold">
                                {{ optional($job->unit)->identification_no ?? '—' }}
                                @if (optional($job->unit)->unit_number)
                                    <span class="text-muted fw-normal">({{ $job->unit->unit_number }})</span>
                                @endif
                            </td>
                            <td>{{ optional(optional($job->contract)->route)->route_no ?? '—' }}</td>
                            <td>{{ optional(optional($job->contract)->engineer)->name ?? '—' }}</td>
                            <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                            <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                            <td><span class="mm-pill" style="background:{{ $color }};">{{ $label }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.scheduling.show', $job->contract_id) }}?unit_id={{ $job->elevator_unit_id }}"
                                    class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:calendar-mark-outline"
                                    style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                <p class="mb-0 mt-2">No PPM visits found for this month with the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">
        {{ $jobs->links() }}
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                // Month/Year selectors submit their own dedicated form; everything else submits the filter form
                const isMonthOrYear = select.name === 'month' || select.name === 'year';
                document.getElementById(isMonthOrYear ? 'monthForm' : 'filterForm').submit();
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
@endsection
