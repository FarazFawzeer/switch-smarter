@extends('layouts.vertical', ['subtitle' => 'PPM Schedule'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Schedule'])

    <style>
        .sh-info-label { font-size: 12px; color: #8792a2; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px; }
        .sh-info-value { font-size: 14px; color: #16233b; font-weight: 500; }
        .sh-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }

        .unit-status-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; }
        .unit-overdue-note { font-size: 12px; color: #D64545; font-weight: 600; }

        .sched-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .sched-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap;
        }
        .sched-table tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .sched-table tbody tr:hover { background: #FAFBFD; }
        .sched-table tbody tr.table-active { background: #EAF0FB; }
        .sched-moved-note { font-size: 11px; color: #8792a2; }

        /* Row action buttons — fixed-width slots so buttons line up from row to row */
        .act-group { display: inline-flex; align-items: center; gap: 6px; }
        .act-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 5px;
            height: 30px; padding: 0 10px; border-radius: 8px; font-size: 12px; font-weight: 600;
            border: 1px solid #e7e9ee; background: #fff; color: #56606f; text-decoration: none;
            white-space: nowrap; transition: all .15s ease; cursor: pointer;
        }
        .act-btn iconify-icon { font-size: 15px; }
        .act-btn.primary { color: #2E5AAC; border-color: #cfe0f6; }
        .act-btn.primary:hover { background: #eaf0fb; border-color: #2E5AAC; color: #2E5AAC; }
        .act-btn.danger { color: #D64545; border-color: #f6d7d7; }
        .act-btn.danger:hover { background: #fdeeee; border-color: #D64545; color: #D64545; }
        .act-btn.muted:hover { background: #F5F7FA; color: #16233b; }
        .act-btn.is-active { background: #2E5AAC; border-color: #2E5AAC; color: #fff; }
        .w-visits { width: 78px; }
        .w-unschedule { width: 108px; }
        .w-reschedule { width: 108px; }
        .w-undo { width: 72px; }
    </style>

    {{-- Project overview --}}
    <div class="card sh-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
                <p class="card-subtitle mb-0">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($unscheduledCount > 0)
                    <a href="{{ route('admin.scheduling.create', $contract->id) }}" class="btn btn-sm btn-primary">
                        <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon>
                        Schedule {{ $unscheduledCount }} Remaining Unit{{ $unscheduledCount > 1 ? 's' : '' }}
                    </a>
                @endif
                <span class="badge badge-soft-success">{{ $unscheduledCount > 0 ? 'Partially Scheduled' : 'Fully Scheduled' }}</span>
            </div>
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
                    <p class="sh-info-label">First PPM Start Date</p>
                    <p class="sh-info-value">{{ optional($contract->ppm_start_date)->format('d M Y') ?? '—' }}</p>
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

    {{-- Unit status overview --}}
    <p class="text-muted small mb-2">This month's status by unit — search or filter, then click a row to narrow the schedule below.</p>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card sh-card mb-0">
                <div class="card-body py-2 text-center">
                    <div class="fw-bold fs-20" style="color:#2E9E5B;">{{ $unitStatusCounts['completed'] }}</div>
                    <div class="small text-muted">Completed</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sh-card mb-0">
                <div class="card-body py-2 text-center">
                    <div class="fw-bold fs-20" style="color:#F0A202;">{{ $unitStatusCounts['pending'] }}</div>
                    <div class="small text-muted">Pending</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sh-card mb-0">
                <div class="card-body py-2 text-center">
                    <div class="fw-bold fs-20" style="color:#D64545;">{{ $unitStatusCounts['overdue'] }}</div>
                    <div class="small text-muted">Overdue</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card sh-card mb-0">
                <div class="card-body py-2 text-center">
                    <div class="fw-bold fs-20" style="color:#8792a2;">{{ $unitStatusCounts['unscheduled'] + $unitStatusCounts['none'] }}</div>
                    <div class="small text-muted">Unscheduled / No visit</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card sh-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.scheduling.show', $contract->id) }}" id="unitFilterForm" class="row g-2 mb-3">
                @if(request('unit_id'))
                    <input type="hidden" name="unit_id" value="{{ request('unit_id') }}">
                @endif
                <div class="col-md-6">
                    <input type="text" name="unit_search" id="unitSearchInput" class="form-control form-control-sm"
                        placeholder="Search by unit ID (e.g. LP-A-01)..." value="{{ request('unit_search') }}">
                </div>
                <div class="col-md-4">
                    <select name="unit_status" id="unitStatusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="completed" {{ request('unit_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="pending" {{ request('unit_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="overdue" {{ request('unit_status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="unscheduled" {{ request('unit_status') == 'unscheduled' ? 'selected' : '' }}>Not scheduled yet</option>
                        <option value="none" {{ request('unit_status') == 'none' ? 'selected' : '' }}>No visit this month</option>
                    </select>
                </div>
                <div class="col-md-2">
                    @if(request()->hasAny(['unit_search', 'unit_status']))
                        <a href="{{ route('admin.scheduling.show', $contract->id) }}" class="btn btn-outline-secondary btn-sm w-100">
                            Clear
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="sched-table mb-0">
                    <thead>
                        <tr>
                            <th>Unit</th>
                            <th>Type</th>
                            <th>This Month</th>
                            <th>Overdue</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unitSummaries as $summary)
                            @php
                                $accentMap = ['completed' => '#2E9E5B', 'pending' => '#F0A202', 'overdue' => '#D64545', 'none' => '#c3c9d1', 'unscheduled' => '#8792a2'];
                                $labelMap = ['completed' => 'Completed', 'pending' => 'Pending', 'overdue' => 'Overdue', 'none' => 'No visit this month', 'unscheduled' => 'Not scheduled yet'];
                                $isActiveFilter = request('unit_id') == $summary->unit->id;
                            @endphp
                            <tr class="{{ $isActiveFilter ? 'table-active' : '' }}">
                                <td class="fw-semibold">
                                    {{ $summary->unit->identification_no }}
                                    @if($summary->unit->unit_number)
                                        <span class="text-muted fw-normal">({{ $summary->unit->unit_number }})</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $summary->unit->unit_type }}{{ $summary->unit->brand ? ' · '.$summary->unit->brand : '' }}</td>
                                <td>
                                    <span class="unit-status-pill" style="background:{{ $accentMap[$summary->status] }};">
                                        {{ $labelMap[$summary->status] }}
                                    </span>
                                </td>
                                <td>
                                    @if($summary->overdueDays !== null)
                                        <span class="unit-overdue-note">{{ $summary->overdueDays }} day{{ $summary->overdueDays == 1 ? '' : 's' }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="act-group">
                                        <a href="{{ route('admin.scheduling.show', array_merge(request()->except('unit_id'), ['contract' => $contract->id, 'unit_id' => $summary->unit->id])) }}"
                                            class="act-btn primary w-visits {{ $isActiveFilter ? 'is-active' : '' }}"
                                            title="Show this unit's visits below">
                                            <iconify-icon icon="solar:filter-outline"></iconify-icon> Visits
                                        </a>
                                        @if($summary->hasEverBeenScheduled)
                                            <button type="button" class="act-btn danger w-unschedule unschedule-unit-btn"
                                                data-url="{{ route('admin.scheduling.units.unschedule', [$contract->id, $summary->unit->id]) }}"
                                                data-unit="{{ $summary->unit->identification_no }}"
                                                title="Remove this unit's upcoming visits so it can be scheduled again">
                                                <iconify-icon icon="solar:undo-left-outline"></iconify-icon> Unschedule
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No units match this search.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-2">
                {{ $paginatedUnits->links() }}
            </div>
        </div>
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
                                {{ $unit->identification_no }}{{ $unit->unit_number ? ' (' . $unit->unit_number . ')' : '' }}
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
    @if(request('from') || request('to'))
        Showing {{ $visibleJobs->count() }} of {{ $totalJobsCount }} total scheduled visits (custom date range).
    @elseif(request('unit_id'))
        Showing {{ now()->format('F Y') }}'s visit for this unit ({{ $visibleJobs->count() }} record{{ $visibleJobs->count() == 1 ? '' : 's' }}).
        Use the date range above to view its full history.
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
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($visibleJobs as $index => $job)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-semibold">
                                    {{ optional($job->unit)->identification_no ?? '—' }}
                                    @if(optional($job->unit)->unit_number)
                                        <span class="text-muted fw-normal">({{ $job->unit->unit_number }})</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $job->scheduled_date->format('d M Y') }}
                                    @if($job->reschedules->isNotEmpty())
                                        <div class="sched-moved-note">
                                            Moved from {{ $job->reschedules->first()->old_date->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>
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
                                <td class="text-center">
                                    <div class="act-group">
                                        @if(! $job->hasStarted())
                                            <button type="button" class="act-btn primary w-reschedule reschedule-btn"
                                                data-url="{{ route('admin.scheduling.jobs.reschedule', $job->id) }}"
                                                data-date="{{ $job->scheduled_date->format('Y-m-d') }}"
                                                data-unit="{{ optional($job->unit)->identification_no }}"
                                                title="Move this visit to another date">
                                                <iconify-icon icon="solar:calendar-date-outline"></iconify-icon> Reschedule
                                            </button>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif

                                        @if(! $job->hasStarted() && $job->reschedules->isNotEmpty())
                                            <button type="button" class="act-btn muted w-undo undo-reschedule-btn"
                                                data-url="{{ route('admin.scheduling.jobs.undo-reschedule', $job->id) }}"
                                                data-date="{{ $job->reschedules->first()->old_date->format('d M Y') }}"
                                                title="Move back to {{ $job->reschedules->first()->old_date->format('d M Y') }}">
                                                <iconify-icon icon="solar:undo-left-outline"></iconify-icon> Undo
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
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

    {{-- Scheduling history: every "Schedule PPM" submit, each can be undone --}}
    @if($batches->isNotEmpty())
        <div class="card sh-card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <iconify-icon icon="solar:history-outline" style="margin-right:6px;"></iconify-icon>
                    Scheduling History
                </h6>
                <p class="card-subtitle mb-0 small">Scheduled the wrong units, date or technician? Undo removes every visit from that submit that hasn't been started.</p>
            </div>
            <div class="table-responsive">
                <table class="sched-table">
                    <thead>
                        <tr>
                            <th>Scheduled On</th>
                            <th>By</th>
                            <th>Technician</th>
                            <th>Units</th>
                            <th>Visits</th>
                            <th>First Visit</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $batch)
                            @php $pendingVisits = $batch->visits - $batch->started; @endphp
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($batch->created_at)->format('d M Y h:i A') }}</td>
                                <td>{{ $batchPeople[$batch->assigned_by] ?? '—' }}</td>
                                <td>{{ $batchPeople[$batch->technician_id] ?? '—' }}</td>
                                <td>{{ $batch->units }}</td>
                                <td>
                                    {{ $batch->visits }}
                                    @if($batch->started > 0)
                                        <span class="text-muted small">({{ $batch->started }} started)</span>
                                    @endif
                                </td>
                                <td>{{ \Carbon\Carbon::parse($batch->first_visit)->format('d M Y') }}</td>
                                <td class="text-center">
                                    @if($pendingVisits > 0)
                                        <button type="button" class="act-btn danger undo-batch-btn"
                                            data-url="{{ route('admin.scheduling.batches.undo', [$contract->id, $batch->schedule_batch]) }}"
                                            data-pending="{{ $pendingVisits }}" data-started="{{ $batch->started }}">
                                            <iconify-icon icon="solar:undo-left-outline"></iconify-icon> Undo
                                        </button>
                                    @else
                                        <span class="text-muted small">All started</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('jobFilterForm').submit();
            });
        });

        document.getElementById('unitStatusFilter').addEventListener('change', function() {
            document.getElementById('unitFilterForm').submit();
        });

        function postAction(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(body || {})
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire('Done', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Not changed', data.message || Object.values(data.errors || {}).flat().join('<br>'), 'error');
                }
            });
        }

        // ===== Reschedule one visit =====
        document.querySelectorAll('.reschedule-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                Swal.fire({
                    title: 'Reschedule visit',
                    html: `<p class="small text-muted mb-2">Unit ${this.dataset.unit || ''} — choose the new visit date.</p>
                           <input type="date" id="swalNewDate" class="form-control mb-2" value="${this.dataset.date}"
                               min="{{ $contract->contract_start_date->format('Y-m-d') }}" max="{{ $contract->contract_end_date->format('Y-m-d') }}">
                           <input type="text" id="swalReason" class="form-control" maxlength="255" placeholder="Reason (optional, e.g. customer requested)">`,
                    showCancelButton: true,
                    confirmButtonText: 'Move visit',
                    preConfirm: () => {
                        const date = document.getElementById('swalNewDate').value;
                        if (!date) { Swal.showValidationMessage('Please pick a date.'); return false; }
                        return { scheduled_date: date, reason: document.getElementById('swalReason').value };
                    }
                }).then(result => {
                    if (result.isConfirmed) postAction(url, result.value);
                });
            });
        });

        // ===== Undo the last reschedule =====
        document.querySelectorAll('.undo-reschedule-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                Swal.fire({
                    title: 'Undo reschedule?',
                    text: `The visit will move back to ${this.dataset.date}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, move it back'
                }).then(result => { if (result.isConfirmed) postAction(url); });
            });
        });

        // ===== Undo a whole scheduling submit =====
        document.querySelectorAll('.undo-batch-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                const started = parseInt(this.dataset.started);
                Swal.fire({
                    title: 'Undo this schedule?',
                    text: `${this.dataset.pending} upcoming visit(s) will be removed.` +
                        (started > 0 ? ` ${started} visit(s) already started will be kept.` : '') +
                        ' The units can then be scheduled again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, undo'
                }).then(result => { if (result.isConfirmed) postAction(url); });
            });
        });

        // ===== Unschedule one unit =====
        document.querySelectorAll('.unschedule-unit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                Swal.fire({
                    title: `Unschedule ${this.dataset.unit}?`,
                    text: 'All upcoming visits for this unit will be removed (started/completed visits are kept). You can then schedule it again.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, unschedule'
                }).then(result => { if (result.isConfirmed) postAction(url); });
            });
        });

        let unitSearchTimeout;
        document.getElementById('unitSearchInput').addEventListener('input', function() {
            clearTimeout(unitSearchTimeout);
            unitSearchTimeout = setTimeout(() => {
                document.getElementById('unitFilterForm').submit();
            }, 600);
        });
    </script>
@endsection