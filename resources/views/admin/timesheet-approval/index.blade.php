@extends('layouts.vertical', ['subtitle' => 'Timesheet Approval'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Timesheets', 'subtitle' => 'Approval'])

    <style>
        .ap-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .ap-chain { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; }
        .ap-chain-step { display: flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; background: #F5F7FA; color: #8792a2; }
        .ap-chain-step.active { background: #2E5AAC; color: #fff; }
        .ap-chain-arrow { color: #c3c9d1; }

        .ap-admin-banner {
            background: #FFF8E6; border: 1px solid #F0D68A; border-radius: 10px; padding: 10px 16px;
            font-size: 12px; color: #8a6116; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
        }

        .ap-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .ap-table thead th {
            background: #F5F7FA; color: #56606f; font-weight: 700; padding: 10px 14px;
            border-bottom: 1px solid #e7e9ee; white-space: nowrap; text-transform: uppercase; font-size: 11px; letter-spacing: 0.03em;
        }
        .ap-table tbody td { padding: 10px 14px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .ap-table tbody tr:hover { background: #FAFBFD; }

        .ap-pill { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; color: #fff; min-width: 100px; text-align: center; }
        .ap-shift-morning { background: #2E5AAC; }
        .ap-shift-evening { background: #9B59B6; }
        .ap-shift-night { background: #1a1f36; }
        .ap-shift-default { background: #8792a2; }

        .ap-status-pending { background: #F0A202; }
        .ap-status-supervisor_approved { background: #17A2B8; }
        .ap-status-engineer_approved { background: #2E5AAC; }
        .ap-status-manager_approved { background: #2E9E5B; }
        .ap-status-rejected { background: #D64545; }

        .ap-ot-pill { background: rgba(214,69,69,0.10); color: #D64545; font-weight: 700; font-size: 11px; padding: 3px 10px; border-radius: 6px; }
        .ap-employee-cell { display: flex; align-items: center; gap: 10px; }
        .ap-employee-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
        .ap-employee-toggle { cursor: pointer; }
        .ap-employee-toggle:hover .fw-semibold { color: #2E5AAC; text-decoration: underline; }
        .ap-chevron { transition: transform .15s ease; color: #8792a2; }
        .ap-row-open .ap-chevron { transform: rotate(90deg); }
        .ap-detail-row > td { background: #FAFBFD; padding: 0 14px 14px 56px !important; }
        .ap-detail-table { width: 100%; font-size: 12px; border-collapse: collapse; }
        .ap-detail-table th { color: #8792a2; font-weight: 600; padding: 8px 10px; border-bottom: 1px solid #e7e9ee; white-space: nowrap; }
        .ap-detail-table td { padding: 7px 10px; border-bottom: 1px solid #f0f1f4; }
        .ap-bulk-bar { display: flex; align-items: center; gap: 10px; }
    </style>

    {{-- Admin testing banner --}}
    @if($isAdminOverride)
        <div class="ap-admin-banner">
            <iconify-icon icon="solar:shield-user-outline" style="font-size:16px;"></iconify-icon>
            You're viewing this as <strong>{{ $user->type }}</strong> — the final approval authority in this system (no separate Manager role exists). Use "View as" below to test each stage of the chain.
        </div>
    @endif

    {{-- Approval chain visual --}}
    <div class="ap-chain">
        <div class="ap-chain-step {{ $stage === 'supervisor' ? 'active' : '' }}">
            <iconify-icon icon="solar:user-check-outline"></iconify-icon> Supervisor
        </div>
        <span class="ap-chain-arrow">→</span>
        <div class="ap-chain-step {{ $stage === 'engineer' ? 'active' : '' }}">
            <iconify-icon icon="solar:settings-outline"></iconify-icon> Engineer
        </div>
        <span class="ap-chain-arrow">→</span>
        <div class="ap-chain-step {{ $stage === 'manager' ? 'active' : '' }}">
            <iconify-icon icon="solar:crown-outline"></iconify-icon> {{ $isAdminOverride ? $user->type : 'Final Approval' }}
        </div>
    </div>

    {{-- Selection + filter form --}}
    <div class="card ap-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.timesheet-approval.index') }}" id="selectionForm" class="row g-2 align-items-end">

                @if($isAdminOverride)
                    <div class="col-auto">
                        <label class="form-label small mb-1">View as</label>
                        <select name="view_as" class="form-select form-select-sm filter-auto" style="width: 150px;">
                            <option value="supervisor" {{ $stage === 'supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="engineer" {{ $stage === 'engineer' ? 'selected' : '' }}>Engineer</option>
                            <option value="manager" {{ $stage === 'manager' ? 'selected' : '' }}>{{ $user->type }} (Final)</option>
                        </select>
                    </div>
                @endif

                @if($stage === 'engineer')
                    <div class="col-auto">
                        <label class="form-label small mb-1">Supervisor</label>
                        <select name="supervisor_id" class="form-select form-select-sm filter-auto" style="width: 190px;">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ request('supervisor_id') == $supervisor->id ? 'selected' : '' }}>
                                    {{ $supervisor->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif($stage === 'supervisor' && !$isAdminOverride)
                    <div class="col-auto">
                        <label class="form-label small mb-1">Engineer</label>
                        <input type="text" class="form-control form-control-sm" style="width: 190px;"
                            value="{{ optional($engineers->first())->name ?? 'Not assigned' }}" disabled>
                    </div>
                @endif

                <div class="col-auto">
                    <label class="form-label small mb-1">Technician</label>
                    <select name="technician_id" class="form-select form-select-sm filter-auto" style="width: 210px;"
                        {{ $stage === 'engineer' && !$isAdminOverride && $technicians->isEmpty() ? 'disabled' : '' }}>
                        <option value="">All Technicians</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}" {{ request('technician_id') == $technician->id ? 'selected' : '' }}>
                                {{ $technician->name }}{{ $technician->employee_id ? ' ('.$technician->employee_id.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($isAdminOverride)
                    <div class="col-auto">
                        <label class="form-label small mb-1">Month</label>
                        <select name="month_filter" class="form-select form-select-sm filter-auto" style="width: 150px;">
                            <option value="current" {{ request('month_filter', 'current') == 'current' ? 'selected' : '' }}>Current Month</option>
                            <option value="previous" {{ request('month_filter') == 'previous' ? 'selected' : '' }}>Previous Month</option>
                            <option value="both" {{ request('month_filter') == 'both' ? 'selected' : '' }}>Both</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-1">Status</label>
                        <select name="status_filter" class="form-select form-select-sm filter-auto" style="width: 180px;">
                            <option value="">Stage default ({{ ucfirst($stage) }})</option>
                            <option value="pending" {{ request('status_filter') == 'pending' ? 'selected' : '' }}>Awaiting Supervisor</option>
                            <option value="supervisor_approved" {{ request('status_filter') == 'supervisor_approved' ? 'selected' : '' }}>Awaiting Engineer</option>
                            <option value="engineer_approved" {{ request('status_filter') == 'engineer_approved' ? 'selected' : '' }}>Awaiting {{ $user->type }}</option>
                            <option value="manager_approved" {{ request('status_filter') == 'manager_approved' ? 'selected' : '' }}>Fully Approved</option>
                            <option value="rejected" {{ request('status_filter') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Timesheet table --}}
    <div class="card ap-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <iconify-icon icon="solar:calendar-mark-outline" style="margin-right:6px;"></iconify-icon>
                Daily Timesheet
                @if(!request('technician_id'))
                    <span class="text-muted small fw-normal">— all technicians in view</span>
                @endif
            </h6>
            <div class="ap-bulk-bar">
                <span class="text-muted small">{{ $records->count() }} record(s)</span>
                <button type="button" id="bulkApproveBtn" class="btn btn-sm btn-success" disabled>
                    <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                    Approve selected (<span id="selectedCount">0</span>)
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width:36px;">
                            <input type="checkbox" class="form-check-input" id="selectAll" title="Select all days you can approve">
                        </th>
                        <th>No</th>
                        <th>Employee</th>
                        <th>Shift</th>
                        <th>Date</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Hours</th>
                        <th>OT</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                        @php
                            $shiftClassMap = ['Morning' => 'ap-shift-morning', 'Evening' => 'ap-shift-evening', 'Night' => 'ap-shift-night'];
                            $shiftName = optional($record->shift)->name;
                            $shiftClass = $shiftClassMap[$shiftName] ?? 'ap-shift-default';

                            $statusLabelMap = [
                                'pending' => 'Awaiting Supervisor',
                                'supervisor_approved' => 'Awaiting Engineer',
                                'engineer_approved' => 'Awaiting ' . $user->type,
                                'manager_approved' => 'Fully Approved',
                                'rejected' => 'Rejected',
                            ];
                            $statusLabel = $statusLabelMap[$record->approval_status] ?? 'Unknown';

                            $expectedStatus = ['supervisor' => 'pending', 'engineer' => 'supervisor_approved', 'manager' => 'engineer_approved'][$stage] ?? null;
                            $canAct = $record->approval_status === $expectedStatus;
                        @endphp
                        <tr id="ap-row-{{ $record->id }}">
                            <td>
                                @if($canAct)
                                    <input type="checkbox" class="form-check-input row-check" value="{{ $record->id }}">
                                @endif
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div class="ap-employee-cell ap-employee-toggle" data-target="ap-detail-{{ $record->id }}"
                                    title="Show every unit worked on this day">
                                    <iconify-icon icon="solar:alt-arrow-right-outline" class="ap-chevron"></iconify-icon>
                                    <img src="{{ storage_asset(optional($record->technician)->image_path) ?? asset('/images/users/avatar-6.jpg') }}"
                                        class="ap-employee-avatar" alt="">
                                    <div>
                                        <div class="fw-semibold">{{ optional($record->technician)->name ?? '—' }}</div>
                                        @if(optional($record->technician)->employee_id)
                                            <div class="text-muted" style="font-size:11px;">{{ $record->technician->employee_id }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($shiftName)
                                    <span class="ap-pill {{ $shiftClass }}">{{ $shiftName }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $record->work_date->format('d M Y') }}</td>
                            <td>{{ $record->check_in_time?->format('h:i A') ?? '—' }}</td>
                            <td>{{ $record->check_out_time?->format('h:i A') ?? '—' }}</td>
                            <td class="fw-semibold">{{ $record->total_hours ?? '—' }}</td>
                            <td>
                                @if($record->ot_hours > 0)
                                    <span class="ap-ot-pill">+{{ $record->ot_hours }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="ap-pill ap-status-{{ $record->approval_status }}">{{ $statusLabel }}</span>
                                @if($record->approval_status === 'rejected' && $record->rejection_reason)
                                    <div class="small text-danger mt-1">{{ $record->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($canAct)
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-sm btn-success approve-btn" data-id="{{ $record->id }}">
                                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon> Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger reject-btn" data-id="{{ $record->id }}">
                                            Reject
                                        </button>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="ap-detail-row d-none" id="ap-detail-{{ $record->id }}">
                            <td colspan="11">
                                @if(($record->day_jobs ?? collect())->isEmpty())
                                    <p class="text-muted small mb-0 pt-2">No unit activity was recorded for this day.</p>
                                @else
                                    <table class="ap-detail-table">
                                        <thead>
                                            <tr>
                                                <th>Unit</th>
                                                <th>Project</th>
                                                <th>Work Type</th>
                                                <th>Start</th>
                                                <th>Start Location</th>
                                                <th>End</th>
                                                <th>Normal</th>
                                                <th>OT</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($record->day_jobs as $dayJob)
                                                <tr>
                                                    <td class="fw-semibold">
                                                        {{ optional($dayJob->unit)->identification_no ?? '—' }}
                                                        @if(optional($dayJob->unit)->unit_number)
                                                            <span class="text-muted fw-normal">({{ $dayJob->unit->unit_number }})</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ optional($dayJob->contract)->project_name ?? '—' }}</td>
                                                    <td>{{ ['ppm' => 'Maintenance', 'breakdown' => 'Breakdown', 'repair' => 'Repair', 'casual_job' => 'Casual Job'][$dayJob->job_type] ?? ucfirst($dayJob->job_type) }}</td>
                                                    <td>{{ $dayJob->check_in_time->format('h:i A') }}</td>
                                                    <td>
                                                        @if($dayJob->check_in_lat && $dayJob->check_in_lng)
                                                            <a href="https://www.google.com/maps?q={{ $dayJob->check_in_lat }},{{ $dayJob->check_in_lng }}" target="_blank">View map</a>
                                                        @else
                                                            —
                                                        @endif
                                                    </td>
                                                    <td>{{ $dayJob->check_out_time?->format('h:i A') ?? 'Not finished' }}</td>
                                                    <td>{{ $dayJob->hours ? number_format($dayJob->hours['normal'], 2) . 'h' : '—' }}</td>
                                                    <td>{{ $dayJob->hours && $dayJob->hours['ot'] > 0 ? number_format($dayJob->hours['ot'], 2) . 'h' : '—' }}</td>
                                                    <td>{{ ucfirst(str_replace('_', ' ', $dayJob->status)) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-5">
                                No timesheet records found for this selection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('selectionForm').submit();
            });
        });

        // ===== Expand a technician row to see every unit's times for that day =====
        document.querySelectorAll('.ap-employee-toggle').forEach(cell => {
            cell.addEventListener('click', function() {
                const detail = document.getElementById(this.dataset.target);
                detail.classList.toggle('d-none');
                this.closest('tr').classList.toggle('ap-row-open', !detail.classList.contains('d-none'));
            });
        });

        // ===== Select all / selected approval =====
        const rowChecks = Array.from(document.querySelectorAll('.row-check'));
        const selectAll = document.getElementById('selectAll');
        const bulkBtn = document.getElementById('bulkApproveBtn');
        const selectedCount = document.getElementById('selectedCount');

        function refreshSelection() {
            const checked = rowChecks.filter(c => c.checked).length;
            selectedCount.textContent = checked;
            bulkBtn.disabled = checked === 0;
            selectAll.checked = rowChecks.length > 0 && checked === rowChecks.length;
            selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
        }

        selectAll.disabled = rowChecks.length === 0;
        selectAll.addEventListener('change', function() {
            rowChecks.forEach(c => c.checked = this.checked);
            refreshSelection();
        });
        rowChecks.forEach(c => c.addEventListener('change', refreshSelection));

        bulkBtn.addEventListener('click', function() {
            const ids = rowChecks.filter(c => c.checked).map(c => parseInt(c.value));
            if (!ids.length) return;

            Swal.fire({
                title: `Approve ${ids.length} day(s)?`,
                text: 'All selected timesheet days will move to the next approval stage.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2E9E5B',
                confirmButtonText: 'Yes, approve all'
            }).then((result) => {
                if (!result.isConfirmed) return;
                bulkBtn.disabled = true;

                fetch("{{ route('admin.timesheet-approval.bulk-approve') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ ids })
                    })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire(data.success ? 'Approved!' : 'Nothing approved', data.message, data.success ? 'success' : 'warning')
                            .then(() => location.reload());
                    });
            });
        });

        document.querySelectorAll('.approve-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;

                Swal.fire({
                    title: 'Approve this day?',
                    text: "This will move the timesheet to the next approval stage.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2E9E5B',
                    confirmButtonText: 'Yes, approve'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/timesheet-approval/${id}/approve`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Approved!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Error!', data.message, 'error');
                                }
                            });
                    }
                });
            });
        });

        document.querySelectorAll('.reject-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;

                Swal.fire({
                    title: 'Reject this day?',
                    input: 'textarea',
                    inputLabel: 'Reason for rejection',
                    inputPlaceholder: 'Explain what needs to be corrected...',
                    showCancelButton: true,
                    confirmButtonColor: '#D64545',
                    confirmButtonText: 'Reject',
                    inputValidator: (value) => {
                        if (!value) return 'A reason is required.';
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(`/admin/timesheet-approval/${id}/reject`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ reason: result.value })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Rejected', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Error!', data.message, 'error');
                                }
                            });
                    }
                });
            });
        });
    </script>
@endsection