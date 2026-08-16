<style>
    .jt-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
    .jt-stat-icon { width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0; }
    .jt-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
    .jt-table thead th { background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #e7e9ee; white-space: nowrap; }
    .jt-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
    .jt-table tbody tr:hover { background: #FAFBFD; }
    .jt-pill { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; }
    .jt-month-select { font-weight: 700; font-size: 14px; }
</style>

{{-- Month/Year selector --}}
<div class="card jt-card mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route($routeName) }}" id="monthForm" class="d-flex align-items-center gap-2 flex-wrap">
            <iconify-icon icon="solar:calendar-mark-outline" style="font-size: 20px; color:#2E5AAC;"></iconify-icon>
            <select name="month" class="form-select form-select-sm jt-month-select filter-auto" style="width: 150px;">
                @foreach($monthOptions as $num => $name)
                    <option value="{{ $num }}" {{ $month == $num ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
            <select name="year" class="form-select form-select-sm jt-month-select filter-auto" style="width: 110px;">
                @foreach($yearOptions as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <span class="text-muted small">Showing {{ strtolower($label) }} records reported this month.</span>
        </form>
    </div>
</div>

{{-- Summary --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-2">
        <div class="card jt-card">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="jt-stat-icon" style="background: rgba(46,90,172,0.10); color:#2E5AAC;">
                    <iconify-icon icon="solar:clipboard-list-outline"></iconify-icon>
                </div>
                <div>
                    <div class="fw-bold fs-20">{{ $summary->total ?? 0 }}</div>
                    <div class="small text-muted">Total</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="card jt-card">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="jt-stat-icon" style="background: rgba(46,158,91,0.10); color:#2E9E5B;">
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
        <div class="card jt-card">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="jt-stat-icon" style="background: rgba(23,162,184,0.10); color:#17A2B8;">
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
        <div class="card jt-card">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="jt-stat-icon" style="background: rgba(240,162,2,0.10); color:#F0A202;">
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
        <div class="card jt-card">
            <div class="card-body d-flex align-items-center gap-2">
                <div class="jt-stat-icon" style="background: rgba(214,69,69,0.10); color:#D64545;">
                    <iconify-icon icon="solar:danger-triangle-outline"></iconify-icon>
                </div>
                <div>
                    <div class="fw-bold fs-20">{{ $summary->overdue ?? 0 }}</div>
                    <div class="small text-muted">Overdue</div>
                </div>
            </div>
        </div>
    </div>
    @if($jobType === 'casual_job')
        <div class="col-6 col-md-2">
            <div class="card jt-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="jt-stat-icon" style="background: rgba(46,90,172,0.10); color:#2E5AAC;">
                        <iconify-icon icon="solar:dollar-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ number_format($summary->total_cost ?? 0, 2) }}</div>
                        <div class="small text-muted">Total Cost</div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="col-6 col-md-2">
            <div class="card jt-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="jt-stat-icon" style="background: rgba(135,146,162,0.10); color:#8792a2;">
                        <iconify-icon icon="solar:pause-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold fs-20">{{ $summary->on_hold ?? 0 }}</div>
                        <div class="small text-muted">On Hold</div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Filters --}}
<div class="card jt-card mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route($routeName) }}" id="filterForm" class="row g-2 align-items-end">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="col-auto">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="search" id="searchInput" class="form-control form-control-sm" style="width: 200px;"
                    placeholder="Project, unit ID, or job code..." value="{{ request('search') }}">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Engineer</label>
                <select name="engineer_id" class="form-select form-select-sm filter-auto" style="width: 170px;">
                    <option value="">All Engineers</option>
                    @foreach($engineers as $engineer)
                        <option value="{{ $engineer->id }}" {{ request('engineer_id') == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Technician</label>
                <select name="technician_id" class="form-select form-select-sm filter-auto" style="width: 190px;">
                    <option value="">All Technicians</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}" {{ request('technician_id') == $technician->id ? 'selected' : '' }}>
                            {{ $technician->name }}{{ $technician->employee_id ? ' ('.$technician->employee_id.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Route</label>
                <select name="route_id" class="form-select form-select-sm filter-auto" style="width: 150px;">
                    <option value="">All Routes</option>
                    @foreach($routes as $route)
                        <option value="{{ $route->id }}" {{ request('route_id') == $route->id ? 'selected' : '' }}>{{ $route->route_no }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm filter-auto" style="width: 150px;">
                    <option value="">All Statuses</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                    <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                </select>
            </div>
            @if(request()->hasAny(['search', 'engineer_id', 'technician_id', 'route_id', 'status']))
                <div class="col-auto">
                    <a href="{{ route($routeName, ['month' => $month, 'year' => $year]) }}" class="btn btn-outline-secondary btn-sm" title="Clear filters">
                        <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>

{{-- Job list --}}
<div class="card jt-card">
    <div class="table-responsive">
        <table class="jt-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Unit</th>
                    <th>Route</th>
                    <th>Engineer</th>
                    <th>Technician</th>
                    <th>Reported Date</th>
                    @if($jobType === 'casual_job')
                        <th>Cost</th>
                    @endif
                    <th>{{ $label }} Details</th>
                    <th>Status</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    @php
                        $isOverdue = in_array($job->status, ['pending', 'overdue']) && $job->scheduled_date->isPast();
                        $statusMap = [
                            'completed' => ['Completed', '#2E9E5B'],
                            'in_progress' => ['In Progress', '#17A2B8'],
                            'pending' => ['Pending', '#F0A202'],
                            'on_hold' => ['On Hold', '#8792a2'],
                        ];
                        [$statusLabel, $statusColor] = $isOverdue ? ['Overdue', '#D64545'] : ($statusMap[$job->status] ?? [ucfirst($job->status), '#8792a2']);
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.contracts.show', $job->contract_id) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ optional($job->contract)->project_name ?? '—' }}
                            </a>
                            <div class="text-muted" style="font-size:12px;">{{ optional($job->contract)->location }}</div>
                        </td>
                        <td class="fw-semibold">{{ optional($job->unit)->identification_no ?? '—' }}</td>
                        <td>{{ optional(optional($job->contract)->route)->route_no ?? '—' }}</td>
                        <td>{{ optional(optional($job->contract)->engineer)->name ?? '—' }}</td>
                        <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                        <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                        @if($jobType === 'casual_job')
                            <td>{{ $job->cost ? number_format($job->cost, 2) : '—' }}</td>
                        @endif
                        <td style="max-width: 220px;">
                            <span class="text-truncate d-inline-block" style="max-width: 220px;" title="{{ $job->description }}">
                                {{ $job->description ?? '—' }}
                            </span>
                        </td>
                        <td><span class="jt-pill" style="background:{{ $statusColor }};">{{ $statusLabel }}</span></td>
                        <td class="text-center">
                            <a href="{{ route('admin.job-types.show', $job->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $jobType === 'casual_job' ? 10 : 9 }}" class="text-center text-muted py-5">
                            <iconify-icon icon="solar:clipboard-list-outline" style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                            <p class="mb-0 mt-2">No {{ strtolower($label) }} records found for this month with the current filters.</p>
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