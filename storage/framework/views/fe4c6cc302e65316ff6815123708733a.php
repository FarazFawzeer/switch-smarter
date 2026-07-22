

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Create'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Team Member</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createTeamForm" action="<?php echo e(route('admin.team.store')); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo e(old('name')); ?>"
                            placeholder="Ex: Kasun Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo e(old('email')); ?>"
                            placeholder="Ex: kasun@company.com" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Re-enter Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="Re-enter Password" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Role</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="Manager">Manager</option>
                            <option value="Engineer">Engineer</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Technician">Technician</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="image_path" class="form-label">Profile Image</label>
                        <input type="file" id="image_path" name="image_path" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="engineerField" style="display:none;">
                        <label for="engineer_id" class="form-label">Reports to (Engineer)</label>
                        <select id="engineer_id" name="engineer_id" class="form-select">
                            <option value="">Select Engineer</option>
                            <?php $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($engineer->id); ?>"><?php echo e($engineer->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="supervisorField" style="display:none;">
                        <label for="supervisor_id" class="form-label">Reports to (Supervisor)</label>
                        <select id="supervisor_id" name="supervisor_id" class="form-select">
                            <option value="">Select Supervisor</option>
                            <?php $__currentLoopData = $supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($supervisor->id); ?>"><?php echo e($supervisor->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>

                
                <div class="row" id="routesSection" style="display:none;">
                    <div class="col-12 mb-3">
                        <label class="form-label" id="routesLabel">Routes</label>
                        <div class="form-text mb-2" id="routesHelp"></div>

                        <?php if($routes->isNotEmpty()): ?>
                            <div id="routesCheckboxes" class="border rounded p-2 mb-2">
                                <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="form-check">
                                        <input class="form-check-input route-checkbox" type="checkbox" name="routes[]"
                                            value="<?php echo e($route->id); ?>" id="route-<?php echo e($route->id); ?>">
                                        <label class="form-check-label" for="route-<?php echo e($route->id); ?>">
                                            <strong><?php echo e($route->route_no); ?></strong>
                                            <?php if($route->description): ?>
                                                <span class="text-muted"> — <?php echo e($route->description); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="new_route_no" class="form-control" style="max-width: 220px;"
                                placeholder="Ex: RT-06 (only if it's new)">
                            <span class="small text-muted">Leave blank if the route already exists above</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Team Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('type').addEventListener('change', function() {
            const engineerField = document.getElementById('engineerField');
            const supervisorField = document.getElementById('supervisorField');
            const routesSection = document.getElementById('routesSection');
            const routesHelp = document.getElementById('routesHelp');
            const checkboxes = document.querySelectorAll('.route-checkbox');

            engineerField.style.display = 'none';
            supervisorField.style.display = 'none';
            document.getElementById('engineer_id').value = '';
            document.getElementById('supervisor_id').value = '';

            checkboxes.forEach(cb => {
                cb.checked = false;
                cb.type = 'checkbox'; // reset back to checkbox by default
            });

            if (this.value === 'Supervisor') {
                engineerField.style.display = 'block';
            } else if (this.value === 'Technician') {
                supervisorField.style.display = 'block';
            }

            if (this.value === 'Engineer' || this.value === 'Supervisor') {
                routesSection.style.display = 'block';
                routesHelp.textContent = 'Select one or more routes this ' + this.value.toLowerCase() + ' covers.';
            } else if (this.value === 'Technician') {
                routesSection.style.display = 'block';
                routesHelp.textContent = 'Select the single route this technician is assigned to.';
                // Turn checkboxes into radio buttons so only one can be selected
                checkboxes.forEach(cb => {
                    cb.type = 'radio';
                });
            } else {
                routesSection.style.display = 'none';
            }
        });

        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
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
                        form.reset();
                        document.getElementById('engineerField').style.display = 'none';
                        document.getElementById('supervisorField').style.display = 'none';
                        document.getElementById('routesSection').style.display = 'none';
                        setTimeout(() => {
                            messageBox.innerHTML = "";
                        }, 3000);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML =
                        `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Team Create'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/team/create.blade.php ENDPATH**/ ?>