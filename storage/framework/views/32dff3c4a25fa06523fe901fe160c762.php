<div class="app-sidebar">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="<?php echo e(route('dashboard')); ?>" class="logo-dark">
            <img src="/images/switch-smarter.png" class="logo-sm" alt="logo sm">
            <img src="/images/switch-smarter.png" class="logo-lg" alt="logo dark" style="width: 150px; height: 75px;">
        </a>

        <a href="<?php echo e(route('dashboard')); ?>" class="logo-light">
            <img src="/images/switch-smarter.png" class="logo-sm" alt="logo sm">
            <img src="/images/switch-smarter.png" class="logo-lg" alt="logo light" style="width: 150px; height: 75px;">
        </a>
    </div>

    <div class="scrollbar" data-simplebar>

        <ul class="navbar-nav" id="navbar-nav">

            <li class="menu-title">Menu...</li>

            

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.users.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:user-circle-outline"></iconify-icon>
                    </span>
                    <span class="nav-text">Admin</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.team.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:users-group-rounded-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Team</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.contracts.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:file-text-outline"></iconify-icon>
                    </span>
                    <span class="nav-text">Master list</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.scheduling.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:calendar-mark-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Scheduling</span>
                </a>
            </li>



            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.monthly-maintenance.index')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:calendar-mark-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Preventive Maintenance</span>
                </a>
            </li>


            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.job-types.breakdown')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:danger-triangle-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Breakdown</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.job-types.repair')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:settings-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Repair</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.job-types.casual-job')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:wallet-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Casual Job</span>
                </a>
            </li>



            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarTimesheets" data-bs-toggle="collapse" role="button"
                    aria-expanded="false" aria-controls="sidebarTimesheets">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Times & Approval</span>
                </a>
                <div class="collapse" id="sidebarTimesheets">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="<?php echo e(route('admin.tracking.index')); ?>">Sheets</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="<?php echo e(route('admin.timesheet-approval.index')); ?>">Approval</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?php echo e(route('admin.profile.edit')); ?>">
                    <span class="nav-icon">
                        <iconify-icon icon="solar:widget-2-outline"></iconify-icon>
                    </span>
                    <span class="nav-text"> Profile </span>

                </a>
            </li>

            
        </ul>
    </div>
</div>
<?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>