

<?php $__env->startSection('content'); ?>

<style>
    /* Profile card — dark navy, matches topbar/bottomnav */
    .th-profile-card {
        background: linear-gradient(135deg, #071A3D, #0F2A5C);
        border-radius: 16px; padding: 18px; box-shadow: 0 4px 14px rgba(7,26,61,0.25);
        display: flex; align-items: center; gap: 14px; margin-bottom: 16px;
    }
    .th-profile-card img {
        width: 58px; height: 58px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.25);
    }
    .th-profile-name { color: #fff; font-weight: 700; font-size: 16px; }
    .th-profile-role { color: #AEBBD0; font-size: 13px; }
    .th-online-dot { width: 8px; height: 8px; border-radius: 50%; background: #2E9E5B; display: inline-block; margin-right: 5px; box-shadow: 0 0 0 3px rgba(46,158,91,0.2); }
    .th-online-text { color: #6FE3A0; font-size: 12px; font-weight: 600; }

    .th-section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .th-section-title span:first-child { font-weight: 700; color: #16233b; font-size: 15px; }
    .th-section-title a { font-size: 12px; color: #071A3D; text-decoration: none; font-weight: 600; }

    .th-overview-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 1px 4px rgba(15,42,67,0.06); margin-bottom: 16px; }
    .th-overview-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
    .th-stat-tile { border-radius: 12px; padding: 14px 6px; text-align: center; color: #fff; }
    .th-stat-tile .num { font-size: 22px; font-weight: 800; line-height: 1; }
    .th-stat-tile .lbl { font-size: 10.5px; margin-top: 5px; opacity: 0.95; font-weight: 600; }

    .th-schedule-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 1px 4px rgba(15,42,67,0.06); margin-bottom: 24px; }
    .th-schedule-item {
        display: flex; align-items: center; gap: 12px; padding: 11px 4px; text-decoration: none; color: inherit;
    }
    .th-schedule-item + .th-schedule-item { border-top: 1px solid #f0f1f4; }
    .th-schedule-icon {
        width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 19px; color: #fff; flex-shrink: 0;
    }
    .th-schedule-item .title { font-weight: 700; color: #16233b; font-size: 14px; }
    .th-schedule-item .sub { font-size: 12px; color: #8792a2; }
    .th-schedule-item .time { font-size: 12px; color: #8792a2; margin-left: auto; white-space: nowrap; font-weight: 600; }

    /* Compact floating action button — bottom right, above the bottom nav */
    .th-fab {
        position: fixed; bottom: 82px; right: 20px; z-index: 999;
        width: 56px; height: 56px; border-radius: 50%;
        background: linear-gradient(135deg, #2E5AAC, #1F4590); color: #fff;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 6px 16px rgba(46,90,172,0.4);
        text-decoration: none; font-size: 26px;
    }
    .th-fab:hover { color: #fff; }
</style>


<div class="th-profile-card">
    <img src="<?php echo e(Auth::user()->image_path ? asset(Auth::user()->image_path) : asset('images/users/avatar-17.jpg')); ?>" alt="">
    <div>
        <div class="th-profile-name"><?php echo e(Auth::user()->name); ?></div>
        <div class="th-profile-role">Technician</div>
        <div class="th-online-text"><span class="th-online-dot"></span>Online</div>
    </div>
</div>


<div class="th-overview-card">
    <div class="th-section-title">
        <span>Job Overview</span>
        <a href="<?php echo e(route('technician.jobs.browse')); ?>">View All</a>
    </div>
    <div class="th-overview-grid">
        <div class="th-stat-tile" style="background:#F0A202;">
            <div class="num"><?php echo e($pendingCount); ?></div>
            <div class="lbl">Pending</div>
        </div>
        <div class="th-stat-tile" style="background:#2E86DE;">
            <div class="num"><?php echo e($inProgressCount); ?></div>
            <div class="lbl">In Progress</div>
        </div>
        <div class="th-stat-tile" style="background:#2E9E5B;">
            <div class="num"><?php echo e($completedCount); ?></div>
            <div class="lbl">Completed</div>
        </div>
        <div class="th-stat-tile" style="background:#8792a2;">
            <div class="num"><?php echo e($onHoldCount); ?></div>
            <div class="lbl">On Hold</div>
        </div>
    </div>
</div>


<div class="th-schedule-card">
    <div class="th-section-title">
        <span>Today's Schedule</span>
        <a href="<?php echo e(route('technician.schedule')); ?>">View All</a>
    </div>

    <?php
        $typeColorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $typeIconMap = ['ppm' => 'solar:calendar-mark-bold', 'casual_job' => 'solar:case-round-bold', 'repair' => 'mdi:wrench', 'breakdown' => 'solar:danger-triangle-bold'];
        $typeLabelMap = ['ppm' => 'PPM', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
    ?>

    <?php $__empty_1 = true; $__currentLoopData = $todaysSchedule; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <a href="<?php echo e(route('technician.activities.show', $job->id)); ?>" class="th-schedule-item">
            <div class="th-schedule-icon" style="background:<?php echo e($typeColorMap[$job->job_type] ?? '#8792a2'); ?>;">
                <iconify-icon icon="<?php echo e($typeIconMap[$job->job_type] ?? 'solar:clipboard-list-outline'); ?>"></iconify-icon>
            </div>
            <div>
                <div class="title"><?php echo e(optional($job->unit)->identification_no ?? '—'); ?></div>
                <div class="sub"><?php echo e($typeLabelMap[$job->job_type] ?? ucfirst($job->job_type)); ?></div>
            </div>
            <div class="time"><?php echo e($job->scheduled_date->format('h:i A')); ?></div>
        </a>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p class="text-center text-muted small py-3 mb-0">Nothing scheduled for today.</p>
    <?php endif; ?>
</div>

<a href="<?php echo e(route('technician.activities.select')); ?>" class="th-fab" aria-label="New Activity">
    <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
</a>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.technician', ['subtitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/technician/jobs/index.blade.php ENDPATH**/ ?>