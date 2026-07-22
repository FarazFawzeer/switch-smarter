

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Team'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        .tm-stat-card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .tm-stat-icon {
            width: 44px; height: 44px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
        }
        .tm-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .tm-section-label { font-size: 13px; font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase; color: #8792a2; }
        .tm-row { padding: 12px 4px; }
        .tm-row + .tm-row { border-top: 1px solid #eef0f4; }
        .tm-name { font-weight: 600; color: #16233b; }
        .tm-name:hover { color: #2E5AAC; }
        .tm-sub { font-size: 12px; color: #8792a2; }
        .tm-accordion-btn {
            border: none !important; background: #fff !important; box-shadow: none !important;
            border-radius: 12px !important; padding: 14px 16px;
        }
        .tm-accordion-item { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); margin-bottom: 10px; overflow: hidden; }
        .tm-accordion-item .accordion-body { background: #FAFBFD; border-top: 1px solid #eef0f4; }
        .tm-pill { background: #F5F7FA; border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; color: #6b7280; display: inline-block; }
        .tm-icon-btn {
            width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center;
            border: 1px solid #e7e9ee; color: #6b7280; transition: all .15s ease;
        }
        .tm-icon-btn.danger:hover { background: #fdeeee; color: #d64545; border-color: #f6d7d7; }
        .supervisor-toggle .supervisor-chevron { transition: transform 0.15s ease; font-size: 14px; color: #8792a2; }
        .supervisor-toggle[aria-expanded="true"] .supervisor-chevron { transform: rotate(90deg); }
    </style>

    
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(46,90,172,0.10); color: #2E5AAC;">
                        <iconify-icon icon="solar:user-check-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;"><?php echo e($roleCounts['Manager']); ?></div>
                        <div class="small text-muted">Manager</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(23,162,184,0.10); color: #17A2B8;">
                        <iconify-icon icon="solar:settings-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;"><?php echo e($roleCounts['Engineer']); ?></div>
                        <div class="small text-muted">Engineers</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(240,162,2,0.10); color: #F0A202;">
                        <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;"><?php echo e($roleCounts['Supervisor']); ?></div>
                        <div class="small text-muted">Supervisors</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card tm-stat-card">
                <div class="card-body d-flex align-items-center gap-2">
                    <div class="tm-stat-icon" style="background: rgba(46,158,91,0.10); color: #2E9E5B;">
                        <iconify-icon icon="solar:user-id-outline"></iconify-icon>
                    </div>
                    <div>
                        <div class="fw-bold" style="font-size:20px; color:#16233b;"><?php echo e($roleCounts['Technician']); ?></div>
                        <div class="small text-muted">Technicians</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <form method="GET" action="<?php echo e(route('admin.team.index')); ?>" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 220px;"
                placeholder="Search by name or email..." value="<?php echo e(request('search')); ?>">
        </div>
        <div class="col-auto">
            <select name="type" class="form-select filter-auto" style="width: 160px;">
                <option value="">All Roles</option>
                <option value="Manager" <?php echo e(request('type') == 'Manager' ? 'selected' : ''); ?>>Manager</option>
                <option value="Engineer" <?php echo e(request('type') == 'Engineer' ? 'selected' : ''); ?>>Engineer</option>
                <option value="Supervisor" <?php echo e(request('type') == 'Supervisor' ? 'selected' : ''); ?>>Supervisor</option>
                <option value="Technician" <?php echo e(request('type') == 'Technician' ? 'selected' : ''); ?>>Technician</option>
            </select>
        </div>
        <?php if($isFiltering): ?>
            <div class="col-auto">
                <a href="<?php echo e(route('admin.team.index')); ?>" class="btn btn-outline-secondary" title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        <?php endif; ?>
        <div class="col-auto">
            <a href="<?php echo e(route('admin.team.create')); ?>" class="btn btn-primary">
                <iconify-icon icon="solar:user-plus-outline" style="margin-right:4px;"></iconify-icon> Add Team Member
            </a>
        </div>
    </form>

    <?php if($isFiltering): ?>
        
        <div class="card tm-card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Routes</th>
                                <th>Reports To</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $team; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $roleColor = match($member->type) {
                                        'Manager' => ['#2E5AAC','rgba(46,90,172,0.10)'], 'Engineer' => ['#17A2B8','rgba(23,162,184,0.10)'],
                                        'Supervisor' => ['#F0A202','rgba(240,162,2,0.10)'], 'Technician' => ['#2E9E5B','rgba(46,158,91,0.10)'],
                                        default => ['#8792a2','rgba(135,146,162,0.10)'],
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?php echo e(storage_asset($member->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>"
                                                class="avatar-sm rounded-circle">
                                            <a href="<?php echo e(route('admin.team.show', $member->id)); ?>" class="tm-name"><?php echo e($member->name); ?></a>
                                        </div>
                                    </td>
                                    <td class="text-muted"><?php echo e($member->email); ?></td>
                                    <td><span class="badge" style="background:<?php echo e($roleColor[1]); ?>; color:<?php echo e($roleColor[0]); ?>; font-weight:600;"><?php echo e($member->type); ?></span></td>
                                    <td>
                                        <?php $__empty_2 = true; $__currentLoopData = $member->routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                            <span class="tm-pill me-1"><?php echo e($route->route_no); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted">
                                        <?php if($member->type === 'Supervisor'): ?>
                                            <?php echo e(optional($member->engineer)->name ?? '—'); ?>

                                        <?php elseif($member->type === 'Technician'): ?>
                                            <?php echo e(optional($member->supervisor)->name ?? '—'); ?>

                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="tm-icon-btn danger border-0 bg-transparent delete-team"
                                            data-id="<?php echo e($member->id); ?>" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline" style="font-size: 16px;"></iconify-icon>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No matching team members found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
            <?php echo e($team->links()); ?>

        </div>

    <?php else: ?>
        

        <?php if($managers->count()): ?>
            <div class="card tm-card mb-3">
                <div class="card-body">
                    <p class="tm-section-label mb-2">Management</p>
                    <?php $__currentLoopData = $managers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $manager): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="tm-row d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?php echo e(storage_asset($manager->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>" class="avatar-sm rounded-circle">
                                <div>
                                    <a href="<?php echo e(route('admin.team.show', $manager->id)); ?>" class="tm-name d-block"><?php echo e($manager->name); ?></a>
                                    <span class="tm-sub"><?php echo e($manager->email); ?></span>
                                </div>
                            </div>
                            <span class="tm-pill">Oversees organization</span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center justify-content-between mb-2 mt-4">
            <p class="tm-section-label mb-0">Engineering Teams</p>
            <span class="small text-muted">Click to expand each level</span>
        </div>

        <div class="accordion" id="engineerAccordion">
            <?php $__empty_1 = true; $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="accordion-item tm-accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed tm-accordion-btn" type="button" data-bs-toggle="collapse"
                            data-bs-target="#engineer-<?php echo e($engineer->id); ?>">
                            <div class="d-flex align-items-center justify-content-between w-100 me-2">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo e(storage_asset($engineer->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>" class="avatar-sm rounded-circle">
                                    <div>
                                        <span class="tm-name"><?php echo e($engineer->name); ?></span>
                                        <span class="badge ms-2" style="background: rgba(23,162,184,0.10); color:#17A2B8; font-weight:600;">Engineer</span>
                                        <?php $__currentLoopData = $engineer->routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <span class="tm-pill ms-1"><?php echo e($route->route_no); ?></span>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                                <span class="tm-pill me-3"><?php echo e($engineer->supervisors_count); ?> supervisor(s)</span>
                            </div>
                        </button>
                    </h2>
                    <div id="engineer-<?php echo e($engineer->id); ?>" class="accordion-collapse collapse" data-bs-parent="#engineerAccordion">
                        <div class="accordion-body">
                            <div class="d-flex justify-content-end mb-2">
                                <a href="<?php echo e(route('admin.team.show', $engineer->id)); ?>" class="small">View full profile →</a>
                            </div>

                            <?php $__empty_2 = true; $__currentLoopData = $engineer->supervisors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $supervisor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <div class="border rounded mb-2" style="background:#fff;">
                                    <div class="d-flex justify-content-between align-items-center p-2 supervisor-toggle"
                                        data-bs-toggle="collapse" data-bs-target="#supervisor-<?php echo e($supervisor->id); ?>" style="cursor:pointer;">
                                        <div class="d-flex align-items-center gap-2">
                                            <iconify-icon icon="solar:alt-arrow-right-outline" class="supervisor-chevron"></iconify-icon>
                                            <img src="<?php echo e(storage_asset($supervisor->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>" class="avatar-sm rounded-circle">
                                            <div>
                                                <a href="<?php echo e(route('admin.team.show', $supervisor->id)); ?>" class="tm-name d-block" onclick="event.stopPropagation()"><?php echo e($supervisor->name); ?></a>
                                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                                    <span class="badge" style="background: rgba(240,162,2,0.10); color:#F0A202; font-weight:600;">Supervisor</span>
                                                    <?php $__currentLoopData = $supervisor->routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <span class="tm-pill"><?php echo e($route->route_no); ?></span>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="tm-pill"><?php echo e($supervisor->technicians_count); ?> technician(s)</span>
                                    </div>

                                    <div id="supervisor-<?php echo e($supervisor->id); ?>" class="collapse">
                                        <div class="px-3 pb-3">
                                            <?php $__empty_3 = true; $__currentLoopData = $supervisor->technicians; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $technician): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_3 = false; ?>
                                                <div class="tm-row d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?php echo e(storage_asset($technician->image_path) ?? asset('/images/users/avatar-6.jpg')); ?>" class="avatar-sm rounded-circle">
                                                        <div>
                                                            <a href="<?php echo e(route('admin.team.show', $technician->id)); ?>" class="tm-name d-block"><?php echo e($technician->name); ?></a>
                                                            <span class="tm-sub"><?php echo e($technician->email); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge" style="background: rgba(46,158,91,0.10); color:#2E9E5B; font-weight:600;">Technician</span>
                                                        <?php if($technician->routes->first()): ?>
                                                            <span class="tm-pill"><?php echo e($technician->routes->first()->route_no); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_3): ?>
                                                <p class="text-muted small mb-0 py-2">No technicians assigned to this supervisor yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <p class="text-muted small mb-0 py-2">No supervisors assigned to this engineer yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="card tm-card">
                    <div class="card-body text-center text-muted py-5">
                        <iconify-icon icon="solar:users-group-rounded-outline" style="font-size: 36px; opacity: 0.4;"></iconify-icon>
                        <p class="mb-2 mt-2">No engineers added yet.</p>
                        <a href="<?php echo e(route('admin.team.create')); ?>" class="btn btn-primary btn-sm">Add your first one</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if($unassignedTechnicianCount > 0): ?>
            <div class="alert d-flex align-items-center gap-2 mt-3 mb-0" style="background: rgba(240,162,2,0.08); border: 1px solid rgba(240,162,2,0.25); color: #8a6116; border-radius: 10px;">
                <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px; color:#F0A202;"></iconify-icon>
                <span><?php echo e($unassignedTechnicianCount); ?> technician(s) are not yet assigned to a supervisor.</span>
                <a href="<?php echo e(route('admin.team.index')); ?>?type=Technician" class="ms-1 fw-semibold">View them</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });

        document.querySelectorAll('.supervisor-toggle').forEach(el => {
            const targetId = el.getAttribute('data-bs-target');
            const target = document.querySelector(targetId);
            target.addEventListener('shown.bs.collapse', () => el.setAttribute('aria-expanded', 'true'));
            target.addEventListener('hidden.bs.collapse', () => el.setAttribute('aria-expanded', 'false'));
        });

        document.querySelectorAll('.delete-team').forEach(button => {
            button.addEventListener('click', function() {
                let memberId = this.dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("<?php echo e(url('admin/team')); ?>/" + memberId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Error!', data.message || 'Something went wrong!', 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error!', 'Something went wrong!', 'error');
                            });
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Team'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/team/index.blade.php ENDPATH**/ ?>