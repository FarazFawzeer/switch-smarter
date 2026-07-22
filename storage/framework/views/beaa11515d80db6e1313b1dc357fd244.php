<?php $__env->startSection('content'); ?>

    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Switch Smarter', 'subtitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <style>
        /* ===== Brand color variables — update these once the logo is shared ===== */
        :root {
            --brand-primary: #2E5AAC;
            --brand-primary-soft: rgba(46, 90, 172, 0.10);
            --brand-accent: #17A2B8;
            --brand-navy: #0F2A43;
            --brand-success: #2E9E5B;
            --brand-warning: #F0A202;
            --brand-danger: #D64545;
        }

        .ss-stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--brand-primary-soft);
            color: var(--brand-primary);
            font-size: 26px;
            flex-shrink: 0;
        }

        .ss-stat-value {
            font-size: 26px;
            font-weight: 600;
            color: var(--brand-navy);
            margin: 0;
        }

        .ss-stat-label {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }

        .ss-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .ss-section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--brand-navy);
        }

        .ss-progress-thin {
            height: 6px;
            border-radius: 4px;
        }
    </style>

    <div class="row g-3 mb-1">
        <div class="col-md-6 col-xl-3">
            <div class="card ss-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="ss-stat-icon">
                        <iconify-icon icon="solar:buildings-outline"></iconify-icon>
                    </div>
                    <div>
                        <p class="ss-stat-label">Total Projects</p>
                        <p class="ss-stat-value"><?php echo e($totalContracts); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card ss-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="ss-stat-icon" style="background: rgba(46,158,91,0.10); color: var(--brand-success);">
                        <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <p class="ss-stat-label">Active Contracts</p>
                        <p class="ss-stat-value"><?php echo e($activeContracts); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card ss-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="ss-stat-icon" style="background: rgba(240,162,2,0.10); color: var(--brand-warning);">
                        <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                    </div>
                    <div>
                        <p class="ss-stat-label">Expiring Soon (90 days)</p>
                        <p class="ss-stat-value"><?php echo e($expiringSoon->count()); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="card ss-card h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="ss-stat-icon" style="background: rgba(23,162,184,0.10); color: var(--brand-accent);">
                 <iconify-icon icon="solar:transfer-vertical-outline"></iconify-icon>
                    </div>
                    <div>
                        <p class="ss-stat-label">Total Elevator Units</p>
                        <p class="ss-stat-value"><?php echo e($totalElevatorUnits); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        
        <div class="col-lg-4">
            <div class="card ss-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <p class="ss-section-title mb-0">Contract Status</p>
                </div>
                <div class="card-body">
                    <div id="statusDonutChart"></div>
                    <div class="d-flex justify-content-between mt-2 px-2">
                        <div class="text-center">
                            <p class="mb-0 fw-semibold" style="color: var(--brand-success);"><?php echo e($activeContracts); ?></p>
                            <p class="mb-0 small text-muted">Active</p>
                        </div>
                        <div class="text-center">
                            <p class="mb-0 fw-semibold" style="color: var(--brand-warning);"><?php echo e($expiredContracts); ?></p>
                            <p class="mb-0 small text-muted">Expired</p>
                        </div>
                        <div class="text-center">
                            <p class="mb-0 fw-semibold" style="color: var(--brand-danger);"><?php echo e($cancelledContracts); ?></p>
                            <p class="mb-0 small text-muted">Cancelled</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card ss-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <p class="ss-section-title mb-0">Unit Types</p>
                </div>
                <div class="card-body">
                    <div id="unitTypeChart"></div>
                </div>
            </div>
        </div>

        
        <div class="col-lg-4">
            <div class="card ss-card h-100">
                <div class="card-header bg-transparent border-0 pb-0">
                    <p class="ss-section-title mb-0">
                        <?php echo e($isAdmin ? 'Projects by Engineer' : 'Your Workload'); ?>

                    </p>
                </div>
                <div class="card-body">
                    <?php if($isAdmin && $contractsByEngineer->count()): ?>
                        <?php $__currentLoopData = $contractsByEngineer; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small"><?php echo e($row['name']); ?></span>
                                    <span class="small fw-semibold"><?php echo e($row['total']); ?></span>
                                </div>
                                <div class="progress ss-progress-thin">
                                    <div class="progress-bar"
                                        style="width: <?php echo e($totalContracts ? round(($row['total'] / $totalContracts) * 100) : 0); ?>%; background-color: var(--brand-primary);">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php elseif(!$isAdmin): ?>
                        <div class="text-center text-muted py-4">
                            <iconify-icon icon="solar:widget-outline" style="font-size: 32px;"></iconify-icon>
                            <p class="mb-0 mt-2 small">Overview of your assigned projects</p>
                            <p class="mb-0 fw-semibold" style="font-size: 22px; color: var(--brand-navy);">
                                <?php echo e($totalContracts); ?> projects</p>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">No data yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        
        <div class="col-lg-5">
            <div class="card ss-card h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <p class="ss-section-title mb-0">Expiring Soon</p>
                    <a href="<?php echo e(route('admin.contracts.index')); ?>" class="small">View all</a>
                </div>
                <div class="card-body pt-0">
                    <?php $__empty_1 = true; $__currentLoopData = $expiringSoon; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div
                            class="d-flex justify-content-between align-items-center py-2 <?php echo e(!$loop->last ? 'border-bottom' : ''); ?>">
                            <div>
                                <a href="<?php echo e(route('admin.contracts.show', $contract->id)); ?>"
                                    class="fw-semibold text-dark d-block"><?php echo e($contract->project_name); ?></a>
                                <span class="small text-muted"><?php echo e($contract->location); ?></span>
                            </div>
                            <span class="badge" style="background: rgba(240,162,2,0.12); color: var(--brand-warning);">
                                <?php echo e(Carbon\Carbon::today()->diffInDays($contract->contract_end_date)); ?> days left
                            </span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center text-muted py-4">Nothing expiring in the next 90 days.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        
        <div class="col-lg-7">
            <div class="card ss-card h-100">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                    <p class="ss-section-title mb-0">Recently Added Projects</p>
                    <a href="<?php echo e(route('admin.contracts.create')); ?>" class="btn btn-sm"
                        style="background: var(--brand-primary); color: #fff;">
                        <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> New Project
                    </a>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Project</th>
                                    <th>Engineer</th>
                                    <th>Units</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $recentContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
                                        $statusColor = match ($contract->status) {
                                            'active' => 'success',
                                            'expired' => 'warning',
                                            'cancelled' => 'danger',
                                            default => 'secondary',
                                        };
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e(route('admin.contracts.show', $contract->id)); ?>"
                                                class="text-dark fw-semibold">
                                                <?php echo e($contract->project_name); ?>

                                            </a>
                                            <div class="small text-muted"><?php echo e($contract->location); ?></div>
                                        </td>
                                        <td><?php echo e(optional($contract->engineer)->name ?? '—'); ?></td>
                                        <td><?php echo e($contract->elevator_units_count); ?></td>
                                        <td><span
                                                class="badge badge-soft-<?php echo e($statusColor); ?>"><?php echo e(ucfirst($contract->status)); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">No projects yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const brandPrimary = '#2E5AAC';
            const brandSuccess = '#2E9E5B';
            const brandWarning = '#F0A202';
            const brandDanger = '#D64545';
            const brandAccent = '#17A2B8';

            // Contract status donut
            if (document.getElementById('statusDonutChart')) {
                new ApexCharts(document.getElementById('statusDonutChart'), {
                    chart: {
                        type: 'donut',
                        height: 220
                    },
                    series: [<?php echo e($activeContracts); ?>, <?php echo e($expiredContracts); ?>, <?php echo e($cancelledContracts); ?>],
                    labels: ['Active', 'Expired', 'Cancelled'],
                    colors: [brandSuccess, brandWarning, brandDanger],
                    legend: {
                        show: false
                    },
                    dataLabels: {
                        enabled: false
                    },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '70%'
                            }
                        }
                    },
                }).render();
            }

            // Unit type breakdown
            // Unit type breakdown
            if (document.getElementById('unitTypeChart')) {
                const elevatorCount = <?php echo e((int) ($unitTypeBreakdown['Elevator'] ?? 0)); ?>;
                const escalatorCount = <?php echo e((int) ($unitTypeBreakdown['Escalator'] ?? 0)); ?>;
                const dumbwaiterCount = <?php echo e((int) ($unitTypeBreakdown['Dumbwaiter'] ?? 0)); ?>;

                new ApexCharts(document.getElementById('unitTypeChart'), {
                    chart: {
                        type: 'bar',
                        height: 220,
                        toolbar: {
                            show: false
                        }
                    },
                    series: [{
                        name: 'Units',
                        data: [elevatorCount, escalatorCount, dumbwaiterCount]
                    }],
                    xaxis: {
                        categories: ['Elevator', 'Escalator', 'Dumbwaiter']
                    },
                    colors: [brandAccent],
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: '45%'
                        }
                    },
                    dataLabels: {
                        enabled: false
                    },
                    grid: {
                        borderColor: '#eee'
                    },
                    noData: {
                        text: 'No elevator units recorded yet',
                        style: {
                            color: '#8792a2',
                            fontSize: '13px'
                        }
                    },
                }).render();
            }
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/index.blade.php ENDPATH**/ ?>