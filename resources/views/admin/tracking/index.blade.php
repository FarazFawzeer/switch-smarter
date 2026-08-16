@extends('layouts.vertical', ['subtitle' => 'Timesheet'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Timesheets', 'subtitle' => 'Timesheet Dashboard'])

    <style>
        .ts-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .ts-stat-value {
            font-size: 22px;
            font-weight: 800;
            color: #16233b;
        }

        .ts-stat-label {
            font-size: 12px;
            color: #8792a2;
        }

        .ts-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .ts-table thead th {
            background: #F5F7FA;
            color: #56606f;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee;
            white-space: nowrap;
        }

        .ts-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f1f4;
            vertical-align: middle;
        }

        .ts-table tbody tr:hover {
            background: #FAFBFD;
        }

        .ts-pill {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #fff;
        }

        .ts-approval-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .ts-approval-tag.approved {
            background: rgba(46, 158, 91, 0.10);
            color: #2E9E5B;
        }

        .ts-approval-tag.pending {
            background: rgba(240, 162, 2, 0.10);
            color: #F0A202;
        }

        .ts-location-link {
            color: #2E5AAC;
            text-decoration: none;
            font-size: 12px;
        }
    </style>

    {{-- Summary cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-2">
            <div class="card ts-card">
                <div class="card-body py-3">
                    <div class="ts-stat-value">{{ $summary['total_technicians'] }}</div>
                    <div class="ts-stat-label">Total Technicians</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card ts-card">
                <div class="card-body py-3">
                    <div class="ts-stat-value">{{ number_format($summary['total_hours'], 1) }}h</div>
                    <div class="ts-stat-label">Total Hours</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card ts-card">
                <div class="card-body py-3">
                    <div class="ts-stat-value">{{ number_format($summary['regular_hours'], 1) }}h</div>
                    <div class="ts-stat-label">Regular Hours</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card ts-card">
                <div class="card-body py-3">
                    <div class="ts-stat-value" style="color:#D64545;">{{ number_format($summary['overtime_hours'], 1) }}h
                    </div>
                    <div class="ts-stat-label">Overtime</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card ts-card">
                <div class="card-body py-3">
                    <div class="ts-stat-value" style="color:#2E9E5B;">{{ $summary['compliance'] }}%</div>
                    <div class="ts-stat-label">Compliance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2 d-flex align-items-center">
            <a href="{{ route('admin.tracking.export', request()->query()) }}" class="btn btn-primary w-100">
                <iconify-icon icon="solar:download-outline" style="margin-right:4px;"></iconify-icon> Export
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card ts-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.tracking.index') }}" id="filterForm"
                class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" id="searchInput" class="form-control form-control-sm"
                        style="width: 190px;" placeholder="Project, unit, or technician..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Technician</label>
                    <select name="technician_id" class="form-select form-select-sm filter-auto" style="width: 170px;">
                        <option value="">All Technicians</option>
                        @foreach ($technicians as $technician)
                            <option value="{{ $technician->id }}"
                                {{ request('technician_id') == $technician->id ? 'selected' : '' }}>
                                {{ $technician->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Work Type</label>
                    <select name="job_type" class="form-select form-select-sm filter-auto" style="width: 150px;">
                        <option value="">All Types</option>
                        <option value="ppm" {{ request('job_type') == 'ppm' ? 'selected' : '' }}>Maintenance</option>
                        <option value="breakdown" {{ request('job_type') == 'breakdown' ? 'selected' : '' }}>Breakdown
                        </option>
                        <option value="repair" {{ request('job_type') == 'repair' ? 'selected' : '' }}>Repair</option>
                        <option value="casual_job" {{ request('job_type') == 'casual_job' ? 'selected' : '' }}>Casual Job
                        </option>
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
                        <option value="on_hold" {{ request('status') == 'on_hold' ? 'selected' : '' }}>On Hold</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" class="form-control form-control-sm"
                        value="{{ request('from') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>
                @if (request()->hasAny(['search', 'technician_id', 'job_type', 'status', 'from', 'to']))
                    <div class="col-auto">
                        <a href="{{ route('admin.tracking.index') }}" class="btn btn-outline-secondary btn-sm">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Working Hours Chart (shown when a technician is selected) --}}
    @if ($chartData)
        <div class="card ts-card mb-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Working Hours — {{ $chartData['technician_name'] }}</h6>
            </div>
            <div class="card-body">
                <div id="workingHoursChart"></div>
            </div>
        </div>
    @else
        <div class="alert alert-light border small mb-3">
            <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
            Select a technician above to see their daily working-hours chart (Normal Time / Overtime / Days Off).
        </div>
    @endif

    {{-- Table --}}
    <div class="card ts-card">
        <div class="table-responsive">
            <table class="ts-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Technician</th>
                        <th>Project / Unit</th>
                        <th>Date Commenced</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Work Shift</th>
                        <th>Work Type</th>
                        <th>Normal Time</th>
                        <th>Overtime</th>
                        <th>Supervisor</th>
                        <th>Engineer</th>
                        <th>Manager</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($jobs as $job)
                        @php
                            $attendance = $job->attendance;
                            $normalHours = $attendance
                                ? max(0, ($attendance->total_hours ?? 0) - ($attendance->ot_hours ?? 0))
                                : null;
                            $workTypeColorMap = [
                                'ppm' => '#2E9E5B',
                                'breakdown' => '#D64545',
                                'repair' => '#F0730A',
                                'casual_job' => '#9B59B6',
                            ];
                            $workTypeColor = $workTypeColorMap[$job->job_type] ?? '#8792a2';
                            $workTypeLabelMap = [
                                'ppm' => 'Maintenance',
                                'breakdown' => 'Breakdown',
                                'repair' => 'Repair',
                                'casual_job' => 'Casual Job',
                            ];
                            $statusColorMap = [
                                'completed' => '#2E9E5B',
                                'in_progress' => '#17A2B8',
                                'on_hold' => '#8792a2',
                                'pending' => '#F0A202',
                            ];
                            $statusColor = $statusColorMap[$job->status] ?? '#8792a2';
                        @endphp
                        <tr>
                            <td>{{ $jobs->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ optional($job->technician)->name ?? '—' }}</td>
                            <td>
                                <a href="{{ route('admin.tracking.show', $job->id) }}"
                                    class="text-dark text-decoration-none fw-semibold">
                                    {{ optional($job->contract)->project_name ?? '—' }}
                                </a>
                                <div class="text-muted" style="font-size:11px;">
                                    {{ optional($job->unit)->identification_no ?? '—' }}</div>
                            </td>
                            <td>{{ $job->check_in_time->format('d M Y') }}</td>
                            <td>
                                {{ $job->check_in_time->format('h:i A') }}
                                @if ($job->check_in_lat && $job->check_in_lng)
                                    <br><a
                                        href="https://www.google.com/maps?q={{ $job->check_in_lat }},{{ $job->check_in_lng }}"
                                        target="_blank" class="ts-location-link">
                                        <iconify-icon icon="solar:map-point-outline"
                                            style="font-size:12px;"></iconify-icon> View
                                    </a>
                                @endif
                            </td>
                            <td>
                                @if ($job->check_out_time)
                                    {{ $job->check_out_time->format('h:i A') }}
                                @else
                                    <span class="text-muted">Not yet</span>
                                @endif
                            </td>
                            <td>{{ optional(optional($attendance)->shift)->name ?? '—' }}</td>
                            <td>
                                <span class="ts-pill"
                                    style="background:{{ $workTypeColor }};">{{ $workTypeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }}</span>
                            </td>
                            <td>{{ $normalHours !== null ? number_format($normalHours, 2) . 'h' : '—' }}</td>
                            <td>
                                @if ($attendance && $attendance->ot_hours > 0)
                                    <span class="ts-pill"
                                        style="background:#D64545;">{{ number_format($attendance->ot_hours, 2) }}h</span>
                                @else
                                    <span class="text-muted">0h</span>
                                @endif
                            </td>
                            <td>
                                <span
                                    class="ts-approval-tag {{ $attendance?->supervisor_approved_at ? 'approved' : 'pending' }}">
                                    <iconify-icon
                                        icon="{{ $attendance?->supervisor_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                    {{ $attendance?->supervisor_approved_at ? 'Approved' : 'Pending' }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="ts-approval-tag {{ $attendance?->engineer_approved_at ? 'approved' : 'pending' }}">
                                    <iconify-icon
                                        icon="{{ $attendance?->engineer_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                    {{ $attendance?->engineer_approved_at ? 'Approved' : 'Pending' }}
                                </span>
                            </td>
                            <td>
                                <span
                                    class="ts-approval-tag {{ $attendance?->manager_approved_at ? 'approved' : 'pending' }}">
                                    <iconify-icon
                                        icon="{{ $attendance?->manager_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                    {{ $attendance?->manager_approved_at ? 'Approved' : 'Pending' }}
                                </span>
                            </td>
                            <td><span class="ts-pill"
                                    style="background:{{ $statusColor }};">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.tracking.show', $job->id) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <iconify-icon icon="solar:eye-outline" style="font-size:14px;"></iconify-icon> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:clock-circle-outline"
                                    style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                <p class="mb-0 mt-2">No timesheet activity found for these filters.</p>
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

    @if ($chartData)
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const labels = @json($chartData['labels']);
                const normalTime = @json($chartData['normal_time']);
                const overTime = @json($chartData['over_time']);
                const hot = @json($chartData['hot']);

                new ApexCharts(document.getElementById('workingHoursChart'), {
                    chart: {
                        type: 'bar',
                        height: 320,
                        toolbar: {
                            show: false
                        },
                        stacked: false
                    },
                    series: [{
                            name: 'HOT (Day Off)',
                            data: hot
                        },
                        {
                            name: 'Normal Time',
                            data: normalTime
                        },
                        {
                            name: 'Over Time',
                            data: overTime
                        },
                    ],
                    colors: ['#17A2B8', '#F0A202', '#9B59B6'],
                    plotOptions: {
                        bar: {
                            columnWidth: '55%',
                            borderRadius: 2
                        }
                    },
                    dataLabels: {
                        enabled: true,
                        formatter: (val) => val > 0 ? val : '',
                        style: {
                            fontSize: '10px'
                        }
                    },
                    xaxis: {
                        categories: labels,
                        labels: {
                            rotate: -45,
                            style: {
                                fontSize: '10px'
                            }
                        }
                    },
                    yaxis: {
                        title: {
                            text: 'Hours'
                        },
                        max: 10,
                    },
                    legend: {
                        position: 'bottom'
                    },
                    grid: {
                        borderColor: '#f0f1f4'
                    },
                }).render();
            });
        </script>
    @endif
@endsection
