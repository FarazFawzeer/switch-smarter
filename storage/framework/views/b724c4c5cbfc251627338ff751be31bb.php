

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'PPM Schedule'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="card-title mb-0"><?php echo e($contract->project_name); ?></h5>
                <p class="card-subtitle"><?php echo e($contract->location); ?> — Contract No: <?php echo e($contract->contract_number); ?></p>
            </div>
            <span class="badge badge-soft-success">Scheduled</span>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <span class="text-muted small">Engineer</span>
                    <p class="mb-0"><?php echo e(optional($contract->engineer)->name ?? '—'); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Supervisor</span>
                    <p class="mb-0"><?php echo e(optional($contract->supervisor)->name ?? '—'); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Route</span>
                    <p class="mb-0"><?php echo e(optional($contract->route)->route_no ?? '—'); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">PPM Start Date</span>
                    <p class="mb-0"><?php echo e($contract->ppm_start_date->format('d M Y')); ?></p>
                </div>
            </div>
            <?php if($contract->renewals->count()): ?>
                <div class="alert alert-info d-flex align-items-center gap-2 mb-4">
                    <iconify-icon icon="solar:refresh-outline" style="font-size: 18px;"></iconify-icon>
                    This contract has been renewed <?php echo e($contract->renewals->count()); ?> time(s).
                    Latest renewal: <?php echo e($contract->renewals->first()->new_start_date->format('d M Y')); ?> –
                    <?php echo e($contract->renewals->first()->new_end_date->format('d M Y')); ?>.
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Scheduled Date</th>
                            <th>Technician</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $contract->ppmJobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($index + 1); ?></td>
                                <td><?php echo e($job->scheduled_date->format('d M Y')); ?></td>
                                <td><?php echo e(optional($job->technician)->name ?? 'Unassigned'); ?></td>
                                <td>
                                    <?php
                                        $statusClass = match ($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => 'badge-soft-warning',
                                        };
                                    ?>
                                    <span
                                        class="badge <?php echo e($statusClass); ?>"><?php echo e(ucfirst(str_replace('_', ' ', $job->status))); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>

            <a href="<?php echo e(route('admin.scheduling.index')); ?>" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'PPM Schedule'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/scheduling/show.blade.php ENDPATH**/ ?>