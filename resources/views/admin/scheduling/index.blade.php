@extends('layouts.vertical', ['subtitle' => 'PPM Scheduling'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Scheduling'])

    <style>
        .sc-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .sc-intro {
            background: #F5F7FA;
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #56606f;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .sc-list-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .sc-list-table thead th {
            background: #F5F7FA;
            color: #56606f;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee;
            white-space: nowrap;
        }

        .sc-list-table thead th a {
            color: inherit;
            text-decoration: none;
        }

        .sc-list-table thead th a:hover {
            color: #2E5AAC;
        }

        .sc-list-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #f0f1f4;
            vertical-align: middle;
        }

        .sc-list-table tbody tr:hover {
            background: #FAFBFD;
        }

        .sc-chain-empty {
            color: #c3c9d1;
        }

        .sc-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            min-width: 90px;
            text-align: center;
        }

        .sc-pill.scheduled {
            background: #2E9E5B;
            color: #fff;
        }

        .sc-pill.pending {
            background: #F0A202;
            color: #fff;
        }

        .sc-pill.completed {
            background: #2E9E5B;
            color: #fff;
        }

        .sc-pill.overdue {
            background: #D64545;
            color: #fff;
        }

        .sc-pill.none {
            background: #eef0f4;
            color: #8792a2;
        }

        .sc-overdue-text {
            color: #D64545;
            font-weight: 700;
            font-size: 12px;
        }

        .sc-badge-renewed {
            background: rgba(23, 162, 184, 0.10);
            color: #17A2B8;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 5px;
            margin-left: 6px;
        }

        .sc-filter-select {
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid #e7e9ee;
            background: #fff;
        }
    </style>

    <div class="sc-intro">
        <iconify-icon icon="solar:info-circle-outline" style="font-size: 18px; flex-shrink:0;"></iconify-icon>
        <div>
            Click <strong>Schedule PPM</strong> to assign a supervisor and technician for a project's route and set the
            first maintenance visit date.
            The visit then repeats every month automatically until the contract ends. The <strong>PPM Status</strong> column
            shows this month's visit progress.
        </div>
    </div>

    <form method="GET" action="{{ route('admin.scheduling.index') }}" id="filterForm"
        class="row g-2 mb-3 justify-content-end align-items-end">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 200px;"
                placeholder="Search by project or location..." value="{{ request('search') }}">
        </div>
        <div class="col-auto">
            <select name="engineer_id" class="form-select filter-auto" style="width: 160px;">
                <option value="">All Engineers</option>
                @foreach ($engineers as $engineer)
                    <option value="{{ $engineer->id }}" {{ request('engineer_id') == $engineer->id ? 'selected' : '' }}>
                        {{ $engineer->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <select name="ppm_status" class="form-select filter-auto" style="width: 160px;">
                <option value="">All PPM Statuses</option>
                <option value="pending" {{ request('ppm_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ request('ppm_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="overdue" {{ request('ppm_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
            </select>
        </div>
        <div class="col-auto">
            <select name="scheduled" class="form-select filter-auto" style="width: 150px;">
                <option value="">All Contracts</option>
                <option value="yes" {{ request('scheduled') == 'yes' ? 'selected' : '' }}>Scheduled</option>
                <option value="no" {{ request('scheduled') == 'no' ? 'selected' : '' }}>Not Scheduled</option>
            </select>
        </div>
        @if (request()->hasAny(['search', 'engineer_id', 'scheduled', 'ppm_status']))
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
                        <th>Project</th>
                        <th>Engineer</th>
                        <th>Route</th>
                        <th>Supervisor</th>
                        <th>Technician</th>
                        <th>PPM Schedule Date</th>
                        <th>PPM Status</th>
                        <th>Overdue</th>
                        <th>Contract Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contracts as $contract)
                        @php
                            $currentJob = $contract->ppmJobs->first();

                            $ppmStatusLabel = 'None';
                            $ppmStatusClass = 'none';
                            $overdueDays = null;

                            if ($currentJob) {
                                $scheduledDate = $currentJob->scheduled_date->copy()->startOfDay();
                                $today = now()->copy()->startOfDay();

                                if ($currentJob->status === 'completed') {
                                    $ppmStatusLabel = 'Completed';
                                    $ppmStatusClass = 'completed';
                                } elseif (
                                    in_array($currentJob->status, ['pending', 'overdue']) &&
                                    $scheduledDate->lt($today)
                                ) {
                                    $ppmStatusLabel = 'Overdue';
                                    $ppmStatusClass = 'overdue';
                                    $overdueDays = (int) $scheduledDate->diffInDays($today);
                                } else {
                                    $ppmStatusLabel = 'Pending';
                                    $ppmStatusClass = 'pending';
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.contracts.show', $contract->id) }}"
                                    class="fw-semibold text-dark text-decoration-none">
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
                                    <span class="sc-chain-empty">Not set</span>
                                @endif
                            </td>
                            <td>{{ optional($contract->supervisor)->name ?? '—' }}</td>
                            <td>{{ optional($contract->technician)->name ?? '—' }}</td>
                            <td>
                                @if ($currentJob)
                                    {{ $currentJob->scheduled_date->format('d M Y') }}
                                @else
                                    <span class="sc-chain-empty">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="sc-pill {{ $ppmStatusClass }}">{{ $ppmStatusLabel }}</span>
                            </td>
                            <td>
                                @if ($overdueDays !== null)
                                    <span class="sc-overdue-text">{{ $overdueDays }}
                                        day{{ $overdueDays == 1 ? '' : 's' }} overdue</span>
                                @else
                                    <span class="sc-chain-empty">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($contract->is_scheduled)
                                    <span class="sc-pill scheduled">Scheduled</span>
                                @else
                                    <span class="sc-pill pending">Not Scheduled</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($contract->is_scheduled)
                                    <a href="{{ route('admin.scheduling.show', $contract->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                @else
                                    <a href="{{ route('admin.scheduling.create', $contract->id) }}"
                                        class="btn btn-sm btn-primary">
                                        Schedule
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-5">
                                <iconify-icon icon="solar:calendar-mark-outline"
                                    style="font-size: 32px; opacity: 0.4;"></iconify-icon>
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
@endsection
