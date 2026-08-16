@extends('layouts.vertical', ['subtitle' => 'Job Detail'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Timesheets', 'subtitle' => 'Job Detail'])

    <style>
        .jt-field-label { font-size: 12px; color: #8792a2; text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px; }
        .jt-field-value { font-size: 14px; color: #16233b; font-weight: 500; }
        .jt-approval-tag { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px; }
        .jt-approval-tag.approved { background: rgba(46,158,91,0.10); color: #2E9E5B; }
        .jt-approval-tag.pending { background: rgba(240,162,2,0.10); color: #F0A202; }
        .jt-type-pill { display: inline-block; padding: 4px 14px; border-radius: 6px; font-size: 12px; font-weight: 700; color: #fff; }
    </style>

    @php
        $workTypeLabelMap = ['ppm' => 'Maintenance', 'breakdown' => 'Breakdown', 'repair' => 'Repair', 'casual_job' => 'Casual Job'];
        $workTypeColorMap = ['ppm' => '#2E9E5B', 'breakdown' => '#D64545', 'repair' => '#F0730A', 'casual_job' => '#9B59B6'];
        $workTypeColor = $workTypeColorMap[$job->job_type] ?? '#8792a2';
        $attendance = $job->attendance;
        $normalHours = $attendance ? max(0, ($attendance->total_hours ?? 0) - ($attendance->ot_hours ?? 0)) : null;
    @endphp

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            {{ optional($job->contract)->project_name ?? 'Unknown project' }}
                            {{ $job->job_code ? ' — '.$job->job_code : '' }}
                        </h5>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="jt-type-pill" style="background:{{ $workTypeColor }};">{{ $workTypeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }}</span>
                        <span class="badge" style="background:{{ $job->status === 'completed' ? '#2E9E5B' : '#17A2B8' }}; color:#fff;">
                            {{ ucfirst(str_replace('_',' ', $job->status)) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="jt-field-label">Unit</p>
                            <p class="jt-field-value">{{ optional($job->unit)->identification_no ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="jt-field-label">Work Type</p>
                            <p class="jt-field-value">{{ $workTypeLabelMap[$job->job_type] ?? ucfirst($job->job_type) }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="jt-field-label">Technician</p>
                            <p class="jt-field-value">{{ optional($job->technician)->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="jt-field-label">Assigned By</p>
                            <p class="jt-field-value">{{ optional($job->assignedBy)->name ?? 'System' }}</p>
                        </div>
                    </div>

                    <hr>

                    {{-- Type-specific fields --}}
                    @if($job->job_type === 'breakdown')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="jt-field-label">Breakdown Cause</p>
                                <p class="jt-field-value">{{ $job->breakdown_cause ?? '—' }}</p>
                            </div>
                        </div>
                    @endif

                    @if($job->job_type === 'repair')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="jt-field-label">Warranty / Contract</p>
                                <p class="jt-field-value">{{ $job->warranty_contract ?? '—' }}</p>
                            </div>
                        </div>
                    @endif

                    @if($job->job_type === 'casual_job')
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p class="jt-field-label">Repair Job No</p>
                                <p class="jt-field-value">{{ $job->repair_job_no ?? '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">Customer</p>
                                <p class="jt-field-value">{{ $job->customer_name ?? '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">Quotation Date</p>
                                <p class="jt-field-value">{{ $job->quotation_date?->format('d M Y') ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p class="jt-field-label">Quotation Value</p>
                                <p class="jt-field-value">{{ $job->quotation_value ? number_format($job->quotation_value, 2) : '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">PO Status</p>
                                <p class="jt-field-value">{{ $job->po_status ?? '—' }}</p>
                            </div>
                            @if($job->po_status === 'Received')
                                <div class="col-md-4">
                                    <p class="jt-field-label">PO Number</p>
                                    <p class="jt-field-value">{{ $job->po_number ?? '—' }} <span class="text-muted small">({{ $job->po_received_date?->format('d M Y') }})</span></p>
                                </div>
                            @endif
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="jt-field-label">Material Status</p>
                                <p class="jt-field-value">{{ $job->material_status ?? '—' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="jt-field-label">Site Status</p>
                                <p class="jt-field-value">{{ $job->site_status ?? '—' }}</p>
                            </div>
                        </div>

                        @if($job->scope_of_work)
                            <p class="jt-field-label">Scope of Work</p>
                            <p class="mb-3">{{ $job->scope_of_work }}</p>
                        @endif

                        @if($job->quotation_attachment)
                            <p class="jt-field-label mb-1">Quotation Attachment</p>
                            <a href="{{ storage_asset($job->quotation_attachment) }}" target="_blank" class="btn btn-sm btn-outline-secondary mb-3">
                                <iconify-icon icon="solar:file-text-outline"></iconify-icon> View Attachment
                            </a>
                        @endif
                    @endif

                    <p class="jt-field-label">Issue Description</p>
                    <p class="mb-3">{{ $job->description ?? '—' }}</p>

                    @if($job->job_type === 'breakdown' && $job->immediate_action_taken)
                        <p class="jt-field-label">Immediate Action Taken</p>
                        <p class="mb-3">{{ $job->immediate_action_taken }}</p>
                    @endif

                    <p class="jt-field-label">Work Performed</p>
                    <p class="mb-3">{{ $job->work_notes ?? '—' }}</p>

                    @if(!empty($job->parts_used))
                        <p class="jt-field-label">Parts Used</p>
                        <p class="mb-3">
                            @foreach($job->parts_used as $part)
                                <span class="badge badge-soft-secondary me-1">{{ $part }}</span>
                            @endforeach
                        </p>
                    @endif

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="jt-field-label mb-1">Check-in</p>
                            @if($job->check_in_time)
                                <p class="mb-1">{{ $job->check_in_time->format('h:i A, d M Y') }}</p>
                                @if($job->check_in_lat)
                                    <a href="https://www.google.com/maps?q={{ $job->check_in_lat }},{{ $job->check_in_lng }}" target="_blank" class="small">
                                        <iconify-icon icon="solar:map-point-outline"></iconify-icon> View on map
                                    </a>
                                @endif
                            @else
                                <p class="text-muted mb-0">Not started yet</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <p class="jt-field-label mb-1">Check-out</p>
                            @if($job->check_out_time)
                                <p class="mb-1">{{ $job->check_out_time->format('h:i A, d M Y') }}</p>
                                @if($job->check_out_lat)
                                    <a href="https://www.google.com/maps?q={{ $job->check_out_lat }},{{ $job->check_out_lng }}" target="_blank" class="small">
                                        <iconify-icon icon="solar:map-point-outline"></iconify-icon> View on map
                                    </a>
                                @endif
                            @else
                                <p class="text-muted mb-0">Not completed yet</p>
                            @endif
                        </div>
                    </div>

                    @if($job->check_in_time && $job->check_out_time)
                        <div class="alert alert-light d-flex align-items-center gap-2 mb-0">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                            Duration: {{ $job->check_in_time->diffForHumans($job->check_out_time, true) }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Timesheet details for this day --}}
            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Timesheet — Day of {{ $job->check_in_time?->format('d M Y') }}</h6></div>
                <div class="card-body">
                    @if($attendance)
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <p class="jt-field-label">Work Shift</p>
                                <p class="jt-field-value">{{ optional($attendance->shift)->name ?? '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">Normal Time</p>
                                <p class="jt-field-value">{{ $normalHours !== null ? number_format($normalHours, 2).'h' : '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">Overtime</p>
                                <p class="jt-field-value" style="color: {{ $attendance->ot_hours > 0 ? '#D64545' : '#16233b' }};">
                                    {{ number_format($attendance->ot_hours ?? 0, 2) }}h
                                </p>
                            </div>
                        </div>

                        <p class="jt-field-label mb-2">Approval Chain</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="jt-approval-tag {{ $attendance->supervisor_approved_at ? 'approved' : 'pending' }}">
                                <iconify-icon icon="{{ $attendance->supervisor_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                Supervisor {{ $attendance->supervisor_approved_at ? '— Approved' : '— Pending' }}
                            </span>
                            <span class="jt-approval-tag {{ $attendance->engineer_approved_at ? 'approved' : 'pending' }}">
                                <iconify-icon icon="{{ $attendance->engineer_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                Engineer {{ $attendance->engineer_approved_at ? '— Approved' : '— Pending' }}
                            </span>
                            <span class="jt-approval-tag {{ $attendance->manager_approved_at ? 'approved' : 'pending' }}">
                                <iconify-icon icon="{{ $attendance->manager_approved_at ? 'solar:check-circle-bold' : 'solar:clock-circle-outline' }}"></iconify-icon>
                                Manager {{ $attendance->manager_approved_at ? '— Approved' : '— Pending' }}
                            </span>
                        </div>
                    @else
                        <p class="text-muted small mb-0">No attendance record found for this technician on this day.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            @if($job->start_photo)
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Start Photo</h6></div>
                    <div class="card-body">
                        <img src="{{ storage_asset($job->start_photo) }}" class="img-fluid rounded" alt="Start photo">
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Completion Photo</h6></div>
                <div class="card-body">
                    @if($job->completion_photo)
                        <img src="{{ storage_asset($job->completion_photo) }}" class="img-fluid rounded" alt="Completion photo">
                    @else
                        <p class="text-muted small mb-0">No photo uploaded yet.</p>
                    @endif
                </div>
            </div>

            @if($job->signature_path)
                <div class="card">
                    <div class="card-header"><h6 class="card-title mb-0">Signature</h6></div>
                    <div class="card-body">
                        <img src="{{ storage_asset($job->signature_path) }}" class="img-fluid rounded" style="background:#f5f7fa;">
                    </div>
                </div>
            @endif

            <a href="{{ route('admin.tracking.index') }}" class="btn btn-secondary w-100">Back to Timesheets</a>
        </div>
    </div>
@endsection