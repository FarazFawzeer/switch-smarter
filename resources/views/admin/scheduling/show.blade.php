@extends('layouts.vertical', ['subtitle' => 'PPM Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Schedule'])

    <style>
        .sh-info-label { font-size: 12px; color: #8792a2; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px; }
        .sh-info-value { font-size: 14px; color: #16233b; font-weight: 500; }
        .sh-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }

        .unit-card {
            border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06);
            padding: 14px 16px; cursor: pointer; transition: box-shadow .15s ease, transform .15s ease;
            border-left: 4px solid var(--unit-accent, #ccc);
        }
        .unit-card:hover { box-shadow: 0 6px 16px rgba(15,42,67,0.10); transform: translateY(-2px); }
        .unit-card.active-filter { outline: 2px solid #2E5AAC; outline-offset: 1px; }
        .unit-id { font-weight: 700; font-size: 14px; color: #16233b; }
        .unit-type { font-size: 11px; color: #8792a2; }
        .unit-status-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; margin-top: 6px; }
        .unit-status-pill.completed { background: #2E9E5B; }
        .unit-status-pill.pending { background: #F0A202; }
        .unit-status-pill.overdue { background: #D64545; }
        .unit-status-pill.none { background: #c3c9d1; }
        .unit-overdue-note { font-size: 11px; color: #D64545; font-weight: 600; margin-top: 4px; }

        .sched-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .sched-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap;
        }
        .sched-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .sched-table tbody tr:hover { background: #FAFBFD; }
    </style>

    {{-- Project overview --}}
    <div class="card sh-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                <p class="card-subtitle mb-0">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
            </div>
            <span class="badge badge-soft-success">Scheduled</span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p class="sh-info-label">Engineer</p>
                    <p class="sh-info-value">{{ optional($contract->engineer)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <p class="sh-info-label">Supervisor</p>
                    <p class="sh-info-value">{{ optional($contract->supervisor)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <p class="sh-info-label">Route</p>
                    <p class="sh-info-value">{{ optional($contract->route)->route_no ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <p class="sh-info-label">PPM Start Date</p>
                    <p class="sh-info-value">{{ $contract->ppm_start_date->format('d M Y') }}</p>
                </div>
            </div>

            @if ($contract->renewals->count())
                <div class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0">
                    <iconify-icon icon="solar:refresh-outline" style="font-size: 18px;"></iconify-icon>
                    This contract has been renewed {{ $contract->renewals->count() }} time(s).
                    Latest renewal: {{ $contract->renewals->first()->new_start_date->format('d M Y') }} –
                    {{ $contract->renewals->first()->new_end_date->format('d M Y') }}.
                </div>
            @endif
        </div>
    </div>

    {{-- Per-unit quick-glance cards (this month's status) --}}
    <p class="text-muted small mb-2">This month's status by unit — click a unit to filter the schedule below.</p>
    <div class="row mb-3">
        @foreach($unitSummaries as $summary)
            @php
                $accentMap = ['completed' => '#2E9E5B', 'pending' => '#F0A202', 'overdue' => '#D64545', 'none' => '#c3c9d1'];
                $accent = $accentMap[$summary->status];
                $isActiveFilter = request('unit_id') == $summary->unit->id;
            @endphp
            <div class="col-md-3 col-sm-6 mb-3">
                <a href="{{ route('admin.scheduling.show', ['contract' => $contract->id, 'unit_id' => $summary->unit->id]) }}"
                    class="text-decoration-none">
                    <div class="unit-card {{ $isActiveFilter ? 'active-filter' : '' }}" style="--unit-accent: {{ $accent }};">
                        <div class="unit-id">{{ $summary->unit->identification_no }}</div>
                        <div class="unit-type">{{ $summary->unit->unit_type }}{{ $summary->unit->brand ? ' · '.$summary->unit->brand : '' }}</div>
                        <span class="unit-status-pill {{ $summary->status }}">
                            {{ $summary->status === 'none' ? 'No visit this month' : ucfirst($summary->status) }}
                        </span>
                        @if($summary->overdueDays !== null)
                            <div class="unit-overdue-note">{{ $summary->overdueDays }} day{{ $summary->overdueDays == 1 ? '' : 's' }} overdue</div>
                        @endif
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    {{-- Full schedule with filters --}}
    <div class="card sh-card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.scheduling.show', $contract->id) }}" id="jobFilterForm" class="row g-2 mb-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Unit</label>
                    <select name="unit_id" class="form-select form-select-sm filter-auto" style="width: 180px;">
                        <option value="">All Units</option>
                        @foreach($contract->elevatorUnits as $unit)
                            <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->identification_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">From date</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To date</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>
                @if($isFiltering)
                    <div class="col-auto">
                        <a href="{{ route('admin.scheduling.show', $contract->id) }}" class="btn btn-outline-secondary btn-sm">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Reset to Default
                        </a>
                    </div>
                @endif
            </form>

            <p class="text-muted small mb-2">
                @if($isFiltering)
                    Showing {{ $visibleJobs->count() }} of {{ $totalJobsCount }} total scheduled visits (filtered).
                @else
                    Showing visits through {{ now()->format('F Y') }}
                    ({{ $visibleJobs->count() }} of {{ $totalJobsCount }} total scheduled visits across all units).
                @endif
            </p>

            <div class="table-responsive">
                <table class="sched-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Unit</th>
                            <th>Scheduled Date</th>
                            <th>Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visibleJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ optional($job->unit)->identification_no ?? '—' }}</td>
                                <td>{{ $job->scheduled_date->format('d M Y') }}</td>
                                <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                                <td>
                                    @php
                                        $statusClass = match ($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => $job->scheduled_date->isPast() ? 'badge-soft-danger' : 'badge-soft-warning',
                                        };
                                        $statusLabel = in_array($job->status, ['pending', 'overdue']) && $job->scheduled_date->isPast()
                                            ? 'Overdue'
                                            : ucfirst(str_replace('_', ' ', $job->status));
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    {{ $isFiltering ? 'No visits found for this filter.' : 'No visits scheduled up to this month yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('jobFilterForm').submit();
            });
        });
    </script>
@endsection