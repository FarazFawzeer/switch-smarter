

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', [
        'title' => 'Contracts',
        'subtitle' => $contract->project_name,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        .sh-info-label {
            font-size: 12px;
            color: #8792a2;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 2px;
        }

        .sh-info-value {
            font-size: 14px;
            color: #16233b;
            font-weight: 500;
        }

        .sh-status-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
        }

        .sh-unit-extra {
            font-size: 12px;
            color: #6b7280;
        }

        .sh-unit-extra strong {
            color: #16233b;
        }
    </style>

    <?php
        $statusMap = [
            'active' => '#2E9E5B',
            'expired' => '#F0A202',
            'cancelled' => '#D64545',
        ];
        $statusColor = $statusMap[$contract->status] ?? '#8792a2';

        $typeColorMap = [
            'Residential' => '#2E5AAC',
            'Commercial' => '#17A2B8',
            'Industrial' => '#6b7280',
            'Hospital' => '#D64545',
            'Hotel' => '#9B59B6',
            'Mixed Use' => '#F0A202',
        ];
        $typeColor = $typeColorMap[$contract->project_type] ?? '#56606f';

        // Pair up custom fields two-per-row so they blend into the same grid as the fixed fields
        $customFieldPairs = collect($contract->custom_fields ?? [])->chunk(2);
    ?>

    <div class="row">
        <div class="col-md-8">
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo e($contract->project_name); ?></h5>
                    <div class="d-flex gap-2">
                        <?php if($contract->project_type): ?>
                            <span class="sh-status-pill"
                                style="background: <?php echo e($typeColor); ?>;"><?php echo e($contract->project_type); ?></span>
                        <?php endif; ?>
                        <span class="sh-status-pill"
                            style="background: <?php echo e($statusColor); ?>;"><?php echo e(ucfirst($contract->status)); ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="sh-info-label">Location</p>
                            <p class="sh-info-value"><?php echo e($contract->location); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="sh-info-label">Contract Number</p>
                            <p class="sh-info-value"><?php echo e($contract->contract_number ?? '—'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="sh-info-label">Start Date</p>
                            <p class="sh-info-value"><?php echo e($contract->contract_start_date->format('d M Y')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="sh-info-label">End Date</p>
                            <p class="sh-info-value"><?php echo e($contract->contract_end_date->format('d M Y')); ?></p>
                        </div>
                    </div>

                    
                    <?php $__currentLoopData = $customFieldPairs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pair): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="row mb-3">
                            <?php $__currentLoopData = $pair; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="col-md-6">
                                    <p class="sh-info-label"><?php echo e($field['label']); ?></p>
                                    <p class="sh-info-value"><?php echo e($field['value'] ?: '—'); ?></p>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                    <?php if($contract->contract_document): ?>
                        <a href="<?php echo e(storage_asset($contract->contract_document)); ?>" target="_blank"
                            class="btn btn-sm btn-outline-secondary mt-1">
                            <iconify-icon icon="solar:file-text-outline"></iconify-icon> View Contract Document
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Elevator / Unit Details (<?php echo e($contract->elevatorUnits->count()); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php
                        // Collect every distinct custom field label used across all units, in first-seen order
                        $unitCustomLabels = collect();
                        foreach ($contract->elevatorUnits as $unit) {
                            foreach ($unit->custom_fields ?? [] as $field) {
                                if (!$unitCustomLabels->contains($field['label'])) {
                                    $unitCustomLabels->push($field['label']);
                                }
                            }
                        }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID No</th>
                                    <th>Unit Type</th>
                                    <th>Elevator Type</th>
                                    <th>Speed</th>
                                    <th>Capacity</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <?php $__currentLoopData = $unitCustomLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <th><?php echo e($label); ?></th>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $contract->elevatorUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $unitFieldMap = collect($unit->custom_fields ?? [])->pluck('value', 'label');
                                    ?>
                                    <tr>
                                        <td><?php echo e($unit->identification_no); ?></td>
                                        <td><?php echo e($unit->unit_type); ?></td>
                                        <td><?php echo e($unit->elevator_type ?? '—'); ?></td>
                                        <td><?php echo e($unit->speed ?? '—'); ?></td>
                                        <td><?php echo e($unit->capacity ?? '—'); ?></td>
                                        <td><?php echo e($unit->brand ?? '—'); ?></td>
                                        <td><?php echo e($unit->model ?? '—'); ?></td>
                                        <?php $__currentLoopData = $unitCustomLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <td><?php echo e($unitFieldMap->get($label, '—')); ?></td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="<?php echo e(7 + $unitCustomLabels->count()); ?>"
                                            class="text-center text-muted py-3">No units recorded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
            <?php if($contract->renewals->count()): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Renewal History</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Previous Term</th>
                                    <th>Renewed To</th>
                                    <th>Renewed By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $contract->renewals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $renewal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($renewal->previous_start_date->format('d M Y')); ?> –
                                            <?php echo e($renewal->previous_end_date->format('d M Y')); ?></td>
                                        <td><?php echo e($renewal->new_start_date->format('d M Y')); ?> –
                                            <?php echo e($renewal->new_end_date->format('d M Y')); ?></td>
                                        <td><?php echo e(optional($renewal->renewedBy)->name ?? '—'); ?></td>
                                        <td><?php echo e($renewal->created_at->format('d M Y')); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-4">
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Route & Engineer Assignment</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <p class="sh-info-label">Route</p>
                        <p class="sh-info-value">
                            <?php if($contract->route): ?>
                                <span class="badge badge-soft-secondary"><?php echo e($contract->route->route_no); ?></span>
                                <?php if($contract->route->description): ?>
                                    <span class="text-muted small d-block mt-1"><?php echo e($contract->route->description); ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Not assigned yet</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="mb-0">
                        <p class="sh-info-label">Engineer</p>
                        <p class="sh-info-value"><?php echo e(optional($contract->engineer)->name ?? '—'); ?></p>
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">PPM Scheduling</h5>
                </div>
                <div class="card-body">
                    <?php if($contract->is_scheduled): ?>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <iconify-icon icon="solar:check-circle-bold"
                                style="color:#2E9E5B; font-size:20px;"></iconify-icon>
                            <span class="fw-semibold">Scheduled</span>
                        </div>
                        <p class="sh-info-label mb-0">PPM Start Date</p>
                        <p class="sh-info-value"><?php echo e(optional($contract->ppm_start_date)->format('d M Y') ?? '—'); ?></p>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:clock-circle-outline"
                                style="color:#F0A202; font-size:20px;"></iconify-icon>
                            <span class="text-muted">Not scheduled yet</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo e(route('admin.contracts.edit', $contract->id)); ?>" class="btn btn-primary flex-fill">Edit
                    Project</a>
                <?php if($contract->status === 'expired'): ?>
                    <a href="<?php echo e(route('admin.contracts.renew.form', $contract->id)); ?>" class="btn btn-warning flex-fill">
                        <iconify-icon icon="solar:refresh-outline" style="margin-right:4px;"></iconify-icon> Renew Contract
                    </a>
                <?php endif; ?>
                <a href="<?php echo e(route('admin.contracts.index')); ?>" class="btn btn-secondary flex-fill">Back to List</a>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => $contract->project_name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/contracts/show.blade.php ENDPATH**/ ?>