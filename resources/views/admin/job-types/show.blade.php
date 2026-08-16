@extends('layouts.vertical', ['subtitle' => $label . ' Detail'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Job Types', 'subtitle' => $label . ' Detail'])

    @php
        $colorMap = ['breakdown' => '#D64545', 'repair' => '#F0730A', 'casual_job' => '#9B59B6'];
        $color = $colorMap[$job->job_type] ?? '#8792a2';
    @endphp

    <style>
        .jt-detail-header {
            background: {{ $color }};
            color: #fff;
            border-radius: 14px 14px 0 0;
            padding: 16px 20px;
        }

        .jt-detail-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
            overflow: hidden;
        }

        .jt-field-label {
            font-size: 12px;
            color: #8792a2;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }

        .jt-field-value {
            font-size: 14px;
            color: #16233b;
            font-weight: 500;
        }

        .jt-status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }
    </style>

    @php
        $statusMap = [
            'completed' => ['Completed', '#2E9E5B'],
            'in_progress' => ['In Progress', '#17A2B8'],
            'pending' => ['Pending', '#F0A202'],
            'on_hold' => ['On Hold', '#8792a2'],
        ];
        $isOverdue = in_array($job->status, ['pending', 'overdue']) && $job->scheduled_date->isPast();
        [$statusLabel, $statusColor] = $isOverdue
            ? ['Overdue', '#D64545']
            : $statusMap[$job->status] ?? [ucfirst($job->status), '#8792a2'];
    @endphp

    <div class="row">
        <div class="col-md-8">
            <div class="card jt-detail-card">
                <div class="jt-detail-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">{{ $label }}{{ $job->job_code ? ' — ' . $job->job_code : '' }}</h5>
                        <p class="mb-0 small" style="opacity:0.9;">{{ optional($job->contract)->project_name ?? '—' }}</p>
                    </div>
                    <span class="jt-status-pill" style="background: rgba(255,255,255,0.25);">{{ $statusLabel }}</span>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p class="jt-field-label">Unit</p>
                            <p class="jt-field-value">{{ optional($job->unit)->identification_no ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="jt-field-label">Location</p>
                            <p class="jt-field-value">{{ optional($job->contract)->location ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="jt-field-label">Reported Date</p>
                            <p class="jt-field-value">{{ $job->scheduled_date->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <p class="jt-field-label">Route</p>
                            <p class="jt-field-value">{{ optional(optional($job->contract)->route)->route_no ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="jt-field-label">Engineer</p>
                            <p class="jt-field-value">{{ optional(optional($job->contract)->engineer)->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="jt-field-label">Technician</p>
                            <p class="jt-field-value">
                                {{ optional($job->technician)->name ?? 'Unassigned' }}
                                @if (optional($job->technician)->employee_id)
                                    <span class="text-muted small">({{ $job->technician->employee_id }})</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <hr>

                    @if ($job->job_type === 'breakdown')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="jt-field-label">Breakdown Cause</p>
                                <p class="jt-field-value">{{ $job->breakdown_cause ?? '—' }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($job->job_type === 'repair')
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="jt-field-label">Warranty / Contract</p>
                                <p class="jt-field-value">{{ $job->warranty_contract ?? '—' }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($job->job_type === 'casual_job')
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
                                <p class="jt-field-value">
                                    {{ $job->quotation_value ? number_format($job->quotation_value, 2) : '—' }}</p>
                            </div>
                            <div class="col-md-4">
                                <p class="jt-field-label">PO Status</p>
                                <p class="jt-field-value">{{ $job->po_status ?? '—' }}</p>
                            </div>
                            @if ($job->po_status === 'Received')
                                <div class="col-md-4">
                                    <p class="jt-field-label">PO Number</p>
                                    <p class="jt-field-value">{{ $job->po_number ?? '—' }} <span
                                            class="text-muted small">({{ $job->po_received_date?->format('d M Y') }})</span>
                                    </p>
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

                        @if ($job->scope_of_work)
                            <p class="jt-field-label">Scope of Work</p>
                            <p class="mb-3">{{ $job->scope_of_work }}</p>
                        @endif

                        @if ($job->quotation_attachment)
                            <p class="jt-field-label mb-1">Quotation Attachment</p>
                            <a href="{{ storage_asset($job->quotation_attachment) }}" target="_blank"
                                class="btn btn-sm btn-outline-secondary mb-3">
                                <iconify-icon icon="solar:file-text-outline"></iconify-icon> View Attachment
                            </a>
                        @endif
                    @endif

                    <p class="jt-field-label">Issue Description</p>
                    <p class="mb-3">{{ $job->description ?? '—' }}</p>

                    @if ($job->job_type === 'breakdown' && $job->immediate_action_taken)
                        <p class="jt-field-label">Immediate Action Taken</p>
                        <p class="mb-3">{{ $job->immediate_action_taken }}</p>
                    @endif

                    <p class="jt-field-label">Work Performed</p>
                    <p class="mb-3">{{ $job->work_notes ?? '—' }}</p>

                    @if (!empty($job->parts_used))
                        <p class="jt-field-label">Parts Used</p>
                        <p class="mb-3">
                            @foreach ($job->parts_used as $part)
                                <span class="badge badge-soft-secondary me-1">{{ $part }}</span>
                            @endforeach
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Timeline</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="jt-field-label mb-0">Started</p>
                        <p class="jt-field-value">{{ $job->check_in_time?->format('d M Y, h:i A') ?? 'Not started yet' }}
                        </p>
                    </div>
                    <div class="mb-3">
                        <p class="jt-field-label mb-0">Completed</p>
                        <p class="jt-field-value">
                            {{ $job->check_out_time?->format('d M Y, h:i A') ?? 'Not completed yet' }}</p>
                    </div>
                    @if ($job->check_in_time && $job->check_out_time)
                        <div class="alert alert-light border small mb-0">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                            Duration: {{ $job->check_in_time->diffForHumans($job->check_out_time, true) }}
                        </div>
                    @endif
                </div>
            </div>

            @if ($job->completion_photo)
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Photo</h6>
                    </div>
                    <div class="card-body">
                        <img src="{{ storage_asset($job->completion_photo) }}" class="img-fluid rounded">
                    </div>
                </div>
            @endif

            @if ($job->signature_path)
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Signature</h6>
                    </div>
                    <div class="card-body">
                        <img src="{{ storage_asset($job->signature_path) }}" class="img-fluid rounded"
                            style="background:#f5f7fa;">
                    </div>
                </div>
            @endif

            <a href="{{ route($backRoute) }}" class="btn btn-secondary w-100">
                <iconify-icon icon="solar:arrow-left-outline"></iconify-icon> Back to {{ $label }} List
            </a>
        </div>
    </div>
@endsection
