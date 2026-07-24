

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

            
      
<form method="GET" action="<?php echo e(route('admin.scheduling.show', $contract->id)); ?>" id="jobFilterForm" class="row g-2 mb-3 align-items-end">
    
    
    
    <div class="col-auto">
        <label class="form-label small mb-1">From date</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?php echo e(request('from')); ?>">
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">To date</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?php echo e(request('to')); ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary btn-sm">Apply</button>
    </div>
    <?php if($isFiltering): ?>
        <div class="col-auto">
            <a href="<?php echo e(route('admin.scheduling.show', $contract->id)); ?>" class="btn btn-outline-secondary btn-sm">
                <iconify-icon icon="solar:close-circle-outline"></iconify-icon> Reset to Default
            </a>
        </div>
    <?php endif; ?>
</form>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <p class="text-muted small mb-0">
                    <?php if($isFiltering): ?>
                        Showing <?php echo e($visibleJobs->count()); ?> of <?php echo e($totalJobsCount); ?> total scheduled visits (filtered).
                    <?php else: ?>
                        Showing visits through <?php echo e(now()->format('F Y')); ?>

                        (<?php echo e($visibleJobs->count()); ?> of <?php echo e($totalJobsCount); ?> total scheduled visits).
                    <?php endif; ?>
                </p>
            </div>

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
                        <?php $__empty_1 = true; $__currentLoopData = $visibleJobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
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
                                            default => $job->scheduled_date->isPast() ? 'badge-soft-danger' : 'badge-soft-warning',
                                        };
                                        $statusLabel = $job->status === 'pending' && $job->scheduled_date->isPast()
                                            ? 'Overdue'
                                            : ucfirst(str_replace('_', ' ', $job->status));
                                    ?>
                                    <span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    <?php echo e($isFiltering ? 'No visits found for this filter.' : 'No visits scheduled up to this month yet.'); ?>

                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <a href="<?php echo e(route('admin.scheduling.index')); ?>" class="btn btn-secondary mt-3">Back to List</a>
        </div>
    </div>

    <script>
      // If the user picks month/year, clear the from/to fields (mutually exclusive filter modes)
document.querySelector('select[name="month"]').addEventListener('change', clearDateRange);
document.querySelector('select[name="year"]').addEventListener('change', clearDateRange);

function clearDateRange() {
    document.querySelector('input[name="from"]').value = '';
    document.querySelector('input[name="to"]').value = '';
}

// If the user types from/to, clear the month/year dropdowns
['from', 'to'].forEach(name => {
    document.querySelector(`input[name="${name}"]`).addEventListener('input', function() {
        if (this.value) {
            document.querySelector('select[name="month"]').value = '';
            document.querySelector('select[name="year"]').value = '';
        }
    });
});
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'PPM Schedule'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/scheduling/show.blade.php ENDPATH**/ ?>