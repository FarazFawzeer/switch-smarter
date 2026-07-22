

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => $team->name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $roleColor = match($team->type) {
            'Manager' => 'primary', 'Engineer' => 'info', 'Supervisor' => 'warning', 'Technician' => 'success', default => 'secondary',
        };
    ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo e(storage_asset($team->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                        alt="<?php echo e($team->name); ?>" class="rounded-circle mb-3" style="width: 96px; height: 96px; object-fit: cover;">
                    <h5 class="mb-1"><?php echo e($team->name); ?></h5>
                    <span class="badge badge-soft-<?php echo e($roleColor); ?> mb-3"><?php echo e($team->type); ?></span>
                    <p class="text-muted small mb-0">
                        <iconify-icon icon="solar:letter-outline"></iconify-icon> <?php echo e($team->email); ?>

                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Reports To</h6></div>
                <div class="card-body">
                    <?php if($team->type === 'Supervisor' && $team->engineer): ?>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?php echo e(storage_asset($team->engineer->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                                class="avatar-sm rounded-circle">
                            <div>
                                <p class="mb-0 fw-semibold"><?php echo e($team->engineer->name); ?></p>
                                <span class="small text-muted">Engineer</span>
                            </div>
                        </div>
                    <?php elseif($team->type === 'Technician' && $team->supervisor): ?>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?php echo e(storage_asset($team->supervisor->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                                class="avatar-sm rounded-circle">
                            <div>
                                <p class="mb-0 fw-semibold"><?php echo e($team->supervisor->name); ?></p>
                                <span class="small text-muted">Supervisor</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-muted small mb-0">This is a top-level role — does not report to anyone in the system.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h6 class="card-title mb-0">Routes</h6></div>
                <div class="card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $team->routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <span class="badge badge-soft-secondary me-1 mb-1"><?php echo e($route->route_no); ?></span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted small mb-0">No routes assigned yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?php echo e(route('admin.team.index')); ?>" class="btn btn-secondary w-100">Back to Team List</a>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <?php if($team->type === 'Engineer'): ?>
                            Supervisors Under <?php echo e($team->name); ?>

                        <?php elseif($team->type === 'Supervisor'): ?>
                            Technicians Under <?php echo e($team->name); ?>

                        <?php else: ?>
                            Organization
                        <?php endif; ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if($team->type === 'Engineer'): ?>
                        <?php $__empty_1 = true; $__currentLoopData = $reportees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="d-flex justify-content-between align-items-center py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo e(storage_asset($supervisor->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="<?php echo e(route('admin.team.show', $supervisor->id)); ?>" class="fw-semibold text-dark"><?php echo e($supervisor->name); ?></a>
                                        <div class="small text-muted">
                                            Supervisor
                                            <?php $__currentLoopData = $supervisor->routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span class="badge badge-soft-secondary ms-1"><?php echo e($route->route_no); ?></span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    </div>
                                </div>
                                <span class="badge badge-soft-secondary"><?php echo e($supervisor->technicians_count); ?> technician(s)</span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted small mb-0">No supervisors assigned to this engineer yet.</p>
                        <?php endif; ?>

                    <?php elseif($team->type === 'Supervisor'): ?>
                        <?php $__empty_1 = true; $__currentLoopData = $reportees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $technician): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="d-flex justify-content-between align-items-center py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo e(storage_asset($technician->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                                        class="avatar-sm rounded-circle">
                                    <div>
                                        <a href="<?php echo e(route('admin.team.show', $technician->id)); ?>" class="fw-semibold text-dark"><?php echo e($technician->name); ?></a>
                                        <div class="small text-muted">
                                            <?php echo e($technician->email); ?>

                                            <?php if($technician->routes->first()): ?>
                                                <span class="badge badge-soft-secondary ms-1"><?php echo e($technician->routes->first()->route_no); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted small mb-0">No technicians assigned to this supervisor yet.</p>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="text-muted small mb-0">
                            <?php echo e($team->type === 'Technician' ? 'Technicians do not manage other team members.' : 'This role oversees the whole organization.'); ?>

                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => $team->name], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/team/show.blade.php ENDPATH**/ ?>