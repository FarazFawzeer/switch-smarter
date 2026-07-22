

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'Schedule PPM'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div id="message"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><?php echo e($contract->project_name); ?></h5>
            <p class="card-subtitle"><?php echo e($contract->location); ?> — Contract No: <?php echo e($contract->contract_number); ?></p>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <span class="text-muted small">Contract Start</span>
                    <p class="mb-0"><?php echo e($contract->contract_start_date->format('d M Y')); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Contract End</span>
                    <p class="mb-0"><?php echo e($contract->contract_end_date->format('d M Y')); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Engineer</span>
                    <p class="mb-0"><?php echo e(optional($contract->engineer)->name ?? '—'); ?></p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Route</span>
                    <p class="mb-0"><?php echo e(optional($contract->route)->route_no ?? 'Not assigned'); ?></p>
                </div>
            </div>

            <?php if(!$contract->route): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px;"></iconify-icon>
                    <div>This project has no route assigned. Add one from the project's Edit page first — a route is what links this project to a supervisor and technician.</div>
                </div>
            <?php elseif($supervisors->isEmpty() && $technicians->isEmpty()): ?>
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px;"></iconify-icon>
                    <div>No supervisor or technician is assigned to route <strong><?php echo e($contract->route->route_no); ?></strong> yet. Add them in Team management, or continue and assign them later.</div>
                </div>
            <?php endif; ?>

            <form id="scheduleForm" action="<?php echo e(route('admin.scheduling.store', $contract->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supervisor</label>
                        <select name="assigned_supervisor_id" class="form-select">
                            <option value="">Not assigned yet</option>
                            <?php $__currentLoopData = $supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($supervisor->id); ?>"><?php echo e($supervisor->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">Only supervisors covering route <?php echo e(optional($contract->route)->route_no ?? '—'); ?> are shown.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Technician</label>
                        <select name="assigned_technician_id" class="form-select">
                            <option value="">Not assigned yet</option>
                            <?php $__currentLoopData = $technicians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $technician): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($technician->id); ?>"><?php echo e($technician->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">This technician will be assigned to every monthly visit.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">PPM Start Date</label>
                        <input type="date" name="ppm_start_date" class="form-control"
                            min="<?php echo e($contract->contract_start_date->copy()->addDay()->format('Y-m-d')); ?>"
                            max="<?php echo e($contract->contract_end_date->format('Y-m-d')); ?>" required>
                        <div class="form-text">
                            Must be after the contract start date (<?php echo e($contract->contract_start_date->format('d M Y')); ?>).
                            The first visit happens on this date, then repeats every month on the same day until the contract ends.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?php echo e(route('admin.scheduling.index')); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Generate Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    let messageBox = document.getElementById('message');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => { window.location.href = data.redirect; }, 1200);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Schedule PPM'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/scheduling/create.blade.php ENDPATH**/ ?>