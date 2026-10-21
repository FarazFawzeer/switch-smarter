<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Projects'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php
        $typeColorMap = [
            'Residential' => '#2E5AAC',
            'Commercial' => '#17A2B8',
            'Industrial' => '#6b7280',
            'Hospital' => '#D64545',
            'Hotel' => '#9B59B6',
            'Mixed Use' => '#F0A202',
        ];
        if (! function_exists('typeColor')) {
            function typeColor($type, $map)
            {
                return $map[$type] ?? '#56606f';
            }
        }
        if (! function_exists('sortLink')) {
            function sortLink($column, $label, $sort, $dir)
            {
                $newDir = $sort === $column && $dir === 'asc' ? 'desc' : 'asc';
                $icon = $sort === $column ? ($dir === 'asc' ? '↑' : '↓') : '<span style="opacity:.35">↕</span>';
                $url = e(request()->fullUrlWithQuery(['sort' => $column, 'dir' => $newDir]));
                return "<a href=\"{$url}\">{$label} {$icon}</a>";
            }
        }
    ?>

    <style>
        .ct-stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
        }

        .ct-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }

        .ct-project-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(15, 42, 67, 0.06);
            transition: box-shadow .15s ease, transform .15s ease;
        }

        .ct-project-card:hover {
            box-shadow: 0 6px 16px rgba(15, 42, 67, 0.10);
            transform: translateY(-2px);
        }

        .ct-project-title {
            font-size: 16px;
            font-weight: 600;
            color: #16233b;
            margin: 0;
        }

        .ct-meta-row {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
        }

        .ct-progress-track {
            height: 5px;
            border-radius: 4px;
            background: #eef0f4;
            overflow: hidden;
        }

        .ct-progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .ct-info-pill {
            background: #F5F7FA;
            border-radius: 8px;
            padding: 6px 10px;
            text-align: center;
            flex: 1;
        }

        .ct-info-pill .val {
            font-size: 15px;
            font-weight: 600;
            color: #16233b;
            line-height: 1.2;
        }

        .ct-info-pill .lbl {
            font-size: 11px;
            color: #8792a2;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .ct-icon-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e7e9ee;
            color: #6b7280;
            transition: all .15s ease;
        }

        .ct-icon-btn:hover {
            background: #F5F7FA;
        }

        .ct-icon-btn.danger:hover {
            background: #fdeeee;
            color: #d64545;
            border-color: #f6d7d7;
        }

        .ct-icon-btn.primary:hover {
            background: #eaf0fb;
            color: #2E5AAC;
            border-color: #cfe0f6;
        }

        .ct-view-toggle {
            border: 1px solid #e7e9ee;
            border-radius: 8px;
            overflow: hidden;
        }

        .ct-view-toggle a {
            padding: 6px 14px;
            font-size: 13px;
            color: #6b7280;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .ct-view-toggle a.active {
            background: #2E5AAC;
            color: #fff;
        }

        .ct-list-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            font-size: 13px;
        }

        .ct-list-table thead th {
            background: #F5F7FA;
            color: #56606f;
            font-weight: 600;
            padding: 10px 12px;
            border-bottom: 1px solid #e7e9ee;
            white-space: nowrap;
        }

        .ct-list-table thead th a {
            color: inherit;
            text-decoration: none;
        }

        .ct-list-table thead th a:hover {
            color: #2E5AAC;
        }

        .ct-list-table tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #f0f1f4;
            vertical-align: middle;
        }

        .ct-list-table tbody tr:hover {
            background: #FAFBFD;
        }

        .ct-status-pill {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #fff;
            min-width: 90px;
            text-align: center;
        }

        .ct-filter-select {
            font-size: 12px;
            padding: 3px 6px;
            border-radius: 6px;
            border: 1px solid #e7e9ee;
            background: #fff;
            max-width: 140px;
        }

        /* =========================
           TOOLBAR ACTIONS (right side: Bulk Upload + New Project)
        ========================= */
        .ct-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .ct-toolbar-actions .btn {
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 600;
            white-space: nowrap;
            line-height: 1;
        }

        .ct-toolbar-actions .btn iconify-icon {
            font-size: 16px;
        }

        .ct-toolbar-actions .btn-outline-primary {
            border: 1px solid #cfe0f6;
            color: #2E5AAC;
            background: #fff;
        }

        .ct-toolbar-actions .btn-outline-primary:hover {
            background: #eaf0fb;
            border-color: #2E5AAC;
            color: #2E5AAC;
        }

        .ct-toolbar-actions .btn-primary {
            background: #2E5AAC;
            border: 1px solid #2E5AAC;
        }

        .ct-toolbar-actions .btn-primary:hover {
            background: #1F4590;
            border-color: #1F4590;
        }

        .ct-bulk-bar {
            display: none;
            align-items: center;
            gap: 10px;
            background: #EAF0FB;
            border: 1px solid #cfe0f6;
            border-radius: 10px;
            padding: 8px 14px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .ct-bulk-bar.show {
            display: flex;
        }

        @media (max-width: 767.98px) {
            .ct-toolbar-actions {
                width: 100%;
                justify-content: stretch;
            }

            .ct-toolbar-actions .btn {
                flex: 1;
            }
        }
    </style>

    
    <?php if($view === 'grid'): ?>
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card ct-stat-card">
                    <div class="card-body d-flex align-items-center gap-2">
                        <div class="ct-stat-icon" style="background: rgba(46,90,172,0.10); color: #2E5AAC;">
                            <iconify-icon icon="solar:buildings-outline"></iconify-icon>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:20px; color:#16233b;"><?php echo e($contracts->total()); ?></div>
                            <div class="small text-muted">Total Projects</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card ct-stat-card">
                    <div class="card-body d-flex align-items-center gap-2">
                        <div class="ct-stat-icon" style="background: rgba(46,158,91,0.10); color: #2E9E5B;">
                            <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:20px; color:#16233b;">
                                <?php echo e($contracts->where('status', 'active')->count()); ?></div>
                            <div class="small text-muted">Active</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card ct-stat-card">
                    <div class="card-body d-flex align-items-center gap-2">
                        <div class="ct-stat-icon" style="background: rgba(240,162,2,0.10); color: #F0A202;">
                            <iconify-icon icon="solar:clock-circle-outline"></iconify-icon>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:20px; color:#16233b;">
                                <?php echo e($contracts->where('status', 'expired')->count()); ?></div>
                            <div class="small text-muted">Expired</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card ct-stat-card">
                    <div class="card-body d-flex align-items-center gap-2">
                        <div class="ct-stat-icon" style="background: rgba(214,69,69,0.10); color: #D64545;">
                            <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:20px; color:#16233b;">
                                <?php echo e($contracts->where('status', 'cancelled')->count()); ?></div>
                            <div class="small text-muted">Cancelled</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    
    <form method="GET" action="<?php echo e(route('admin.contracts.index')); ?>" id="filterForm" class="row g-2 mb-2 align-items-end">
        <div class="col-auto">
            <div class="ct-view-toggle d-flex">
                <a href="<?php echo e(request()->fullUrlWithQuery(['view' => 'grid'])); ?>"
                    class="<?php echo e($view === 'grid' ? 'active' : ''); ?>">
                    <iconify-icon icon="solar:widget-outline"></iconify-icon> Grid
                </a>
                <a href="<?php echo e(request()->fullUrlWithQuery(['view' => 'list'])); ?>"
                    class="<?php echo e($view === 'list' ? 'active' : ''); ?>">
                    <iconify-icon icon="solar:list-outline"></iconify-icon> List
                </a>
            </div>
        </div>
        <input type="hidden" name="view" value="<?php echo e($view); ?>">
        <div class="col-auto">
            <input type="text" name="search" id="searchInput" class="form-control" style="width: 220px;"
                placeholder="Search by project, location, unit ID..." value="<?php echo e(request('search')); ?>" form="filterForm">
        </div>
        <?php if($view === 'grid'): ?>
            <div class="col-auto">
                <select name="status" class="form-select filter-auto" style="width: 150px;" form="filterForm">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>Active</option>
                    <option value="expired" <?php echo e(request('status') == 'expired' ? 'selected' : ''); ?>>Expired</option>
                    <option value="cancelled" <?php echo e(request('status') == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                </select>
            </div>
        <?php endif; ?>
        <?php if(request()->hasAny(['search', 'status', 'project_type', 'engineer_id', 'route_id', 'unit_type', 'elevator_type'])): ?>
            <div class="col-auto">
                <a href="<?php echo e(route('admin.contracts.index')); ?>?view=<?php echo e($view); ?>" class="btn btn-outline-secondary"
                    title="Clear filters">
                    <iconify-icon icon="solar:close-circle-outline"></iconify-icon>
                </a>
            </div>
        <?php endif; ?>
    </form>

    
    <div class="d-flex justify-content-end mb-3">
        <div class="ct-toolbar-actions">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#manageRoutesModal">
                <iconify-icon icon="solar:routing-2-outline"></iconify-icon> Manage Routes
            </button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#masterListUploadModal">
                <iconify-icon icon="solar:upload-outline"></iconify-icon> Bulk Upload
            </button>
            <a href="<?php echo e(route('admin.contracts.create')); ?>" class="btn btn-primary">
                <iconify-icon icon="solar:add-circle-outline"></iconify-icon> New Project
            </a>
        </div>
    </div>

    <?php if($view === 'list'): ?>
        
        <div class="ct-bulk-bar" id="bulkBar">
            <strong><span id="bulkCount">0</span> row(s) selected</strong>
            <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteUnits">
                <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon> Delete selected units
            </button>
            <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteProjects">
                <iconify-icon icon="solar:buildings-outline"></iconify-icon> Delete whole projects
            </button>
            <button type="button" class="btn btn-sm btn-link text-muted" id="bulkClear">Clear selection</button>
        </div>

        <div class="card ct-stat-card">
            <div class="table-responsive">
                <table class="ct-list-table">
                    <thead>
                        <tr>
                            <th style="width:34px;"><input type="checkbox" class="form-check-input" id="selectAllRows"
                                    title="Select all rows on this page"></th>
                            <th>No</th>
                            <th><?php echo sortLink('identification_no', 'Unit ID', $sort, $dir); ?></th>
                            <th><?php echo sortLink('unit_number', 'Unit No', $sort, $dir); ?></th>
                            <th><?php echo sortLink('project_name', 'Project', $sort, $dir); ?></th>
                            <th>
                                <?php echo sortLink('project_type', 'Type', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="project_type" form="filterForm">
                                        <option value="">All</option>
                                        <?php $__currentLoopData = $projectTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type); ?>"
                                                <?php echo e(request('project_type') == $type ? 'selected' : ''); ?>>
                                                <?php echo e($type); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select></div>
                            </th>
                            <th><?php echo sortLink('location', 'Location', $sort, $dir); ?></th>
                            <th><?php echo sortLink('contract_number', 'Contract No', $sort, $dir); ?></th>
                            <th>
                                <?php echo sortLink('route_no', 'Route', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="route_id" form="filterForm">
                                        <option value="">All</option>
                                        <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($route->id); ?>"
                                                <?php echo e(request('route_id') == $route->id ? 'selected' : ''); ?>>
                                                <?php echo e($route->route_no); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select></div>
                            </th>
                            <th>
                                <?php echo sortLink('engineer_name', 'Engineer', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="engineer_id" form="filterForm">
                                        <option value="">All</option>
                                        <?php $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($engineer->id); ?>"
                                                <?php echo e(request('engineer_id') == $engineer->id ? 'selected' : ''); ?>>
                                                <?php echo e($engineer->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select></div>
                            </th>
                            <th><?php echo sortLink('supervisor_name', 'Supervisor', $sort, $dir); ?></th>
                            <th><?php echo sortLink('technician_name', 'Technician', $sort, $dir); ?></th>
                            <th><?php echo sortLink('contract_start_date', 'Start', $sort, $dir); ?></th>
                            <th><?php echo sortLink('contract_end_date', 'End', $sort, $dir); ?></th>
                            <th>
                                <?php echo sortLink('contract_status', 'Contract Status', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="status" form="filterForm">
                                        <option value="">All</option>
                                        <option value="active" <?php echo e(request('status') == 'active' ? 'selected' : ''); ?>>
                                            Active</option>
                                        <option value="expired" <?php echo e(request('status') == 'expired' ? 'selected' : ''); ?>>
                                            Expired</option>
                                        <option value="cancelled"
                                            <?php echo e(request('status') == 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                                    </select></div>
                            </th>
                            <th>
                                <?php echo sortLink('unit_type', 'Unit Type', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="unit_type" form="filterForm">
                                        <option value="">All</option>
                                        <?php $__currentLoopData = $unitTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type); ?>"
                                                <?php echo e(request('unit_type') == $type ? 'selected' : ''); ?>><?php echo e($type); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select></div>
                            </th>
                            <th>
                                <?php echo sortLink('elevator_type', 'Elevator Type', $sort, $dir); ?>

                                <div><select class="ct-filter-select filter-auto" name="elevator_type" form="filterForm">
                                        <option value="">All</option>
                                        <?php $__currentLoopData = $elevatorTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($type); ?>"
                                                <?php echo e(request('elevator_type') == $type ? 'selected' : ''); ?>>
                                                <?php echo e($type); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select></div>
                            </th>
                            <th><?php echo sortLink('speed', 'Speed', $sort, $dir); ?></th>
                            <th><?php echo sortLink('capacity', 'Capacity', $sort, $dir); ?></th>
                            <th><?php echo sortLink('brand', 'Brand', $sort, $dir); ?></th>
                            <th><?php echo sortLink('model', 'Model', $sort, $dir); ?></th>
                            <th><?php echo sortLink('created_at', 'Log Created', $sort, $dir); ?></th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $statusColor = match ($unit->contract_status) {
                                    'active' => '#2E9E5B',
                                    'expired' => '#F0A202',
                                    'cancelled' => '#D64545',
                                    default => '#8792a2',
                                };
                            ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input row-select" value="<?php echo e($unit->id); ?>"></td>
                                <td><?php echo e($units->firstItem() + $loop->index); ?></td>
                                <td class="fw-semibold"><?php echo e($unit->identification_no); ?></td>
                                <td><?php echo e($unit->unit_number ?? '—'); ?></td>
                                <td>
                                    <a href="<?php echo e(route('admin.contracts.show', $unit->contract_id)); ?>"
                                        class="fw-semibold text-dark">
                                        <?php echo e($unit->project_name); ?>

                                    </a>
                                </td>
                                <td>
                                    <?php if($unit->project_type): ?>
                                        <?php $tColor = typeColor($unit->project_type, $typeColorMap); ?>
                                        <span class="ct-status-pill"
                                            style="background: <?php echo e($tColor); ?>;"><?php echo e($unit->project_type); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($unit->location); ?></td>
                                <td><?php echo e($unit->contract_number); ?></td>
                                <td><?php echo e($unit->route_no ?? '—'); ?></td>
                                <td><?php echo e($unit->engineer_name ?? '—'); ?></td>
                                <td><?php echo e($unit->supervisor_name ?? '—'); ?></td>
                                <td><?php echo e($unit->technician_name ?? '—'); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($unit->contract_start_date)->format('d M Y')); ?></td>
                                <td><?php echo e(\Carbon\Carbon::parse($unit->contract_end_date)->format('d M Y')); ?></td>
                                <td><span class="ct-status-pill"
                                        style="background:<?php echo e($statusColor); ?>;"><?php echo e(ucfirst($unit->contract_status)); ?></span>
                                </td>
                                <td><?php echo e($unit->unit_type); ?></td>
                                <td><?php echo e($unit->elevator_type ?? '—'); ?></td>
                                <td><?php echo e($unit->speed ?? '—'); ?></td>
                                <td><?php echo e($unit->capacity ?? '—'); ?></td>
                                <td><?php echo e($unit->brand ?? '—'); ?></td>
                                <td><?php echo e($unit->model ?? '—'); ?></td>
                                <td class="text-nowrap" title="<?php echo e($unit->created_at?->format('d M Y h:i A')); ?>">
                                    <?php echo e($unit->created_at?->format('d M Y') ?? '—'); ?>

                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="<?php echo e(route('admin.contracts.show', $unit->contract_id)); ?>"
                                            class="ct-icon-btn" title="View Contract">
                                            <iconify-icon icon="solar:eye-outline"
                                                style="font-size: 15px;"></iconify-icon>
                                        </a>
                                        <a href="<?php echo e(route('admin.contracts.edit', $unit->contract_id)); ?>"
                                            class="ct-icon-btn primary" title="Edit Contract">
                                            <iconify-icon icon="solar:pen-2-outline"
                                                style="font-size: 15px;"></iconify-icon>
                                        </a>
                                        <button type="button"
                                            class="ct-icon-btn danger border-0 bg-transparent delete-contract"
                                            data-id="<?php echo e($unit->contract_id); ?>" title="Delete Project">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline"
                                                style="font-size: 15px;"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="23" class="text-center text-muted py-5">
                                    <iconify-icon icon="solar:widget-5-outline"
                                        style="font-size: 32px; opacity: 0.4;"></iconify-icon>
                                    <p class="mb-0 mt-2">No units match these filters.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-2">
            <?php echo e($units->links()); ?>

        </div>
    <?php else: ?>
        
        <div class="row">
            <?php $__empty_1 = true; $__currentLoopData = $contracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $statusMap = [
                        'active' => ['color' => '#2E9E5B', 'bg' => 'rgba(46,158,91,0.10)', 'label' => 'Active'],
                        'expired' => ['color' => '#F0A202', 'bg' => 'rgba(240,162,2,0.10)', 'label' => 'Expired'],
                        'cancelled' => ['color' => '#D64545', 'bg' => 'rgba(214,69,69,0.10)', 'label' => 'Cancelled'],
                    ];
                    $s = $statusMap[$contract->status] ?? [
                        'color' => '#8792a2',
                        'bg' => 'rgba(135,146,162,0.10)',
                        'label' => ucfirst($contract->status),
                    ];
                    $progress = $contract->progressPercent();
                    $daysLeft = $contract->daysRemaining();
                ?>
                <div class="col-md-4 mb-4">
                    <div class="card ct-project-card h-100" style="--ct-accent: <?php echo e($s['color']); ?>;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <p class="ct-project-title"><?php echo e($contract->project_name); ?></p>
                                <span class="badge"
                                    style="background: <?php echo e($s['bg']); ?>; color: <?php echo e($s['color']); ?>; font-weight: 600;">
                                    <?php echo e($s['label']); ?>

                                </span>
                            </div>

                            <div class="ct-meta-row mb-1">
                                <iconify-icon icon="solar:map-point-outline"></iconify-icon> <?php echo e($contract->location); ?>

                            </div>
                            <div class="ct-meta-row mb-3">
                                <iconify-icon icon="solar:hashtag-outline"></iconify-icon>
                                <?php echo e($contract->contract_number); ?>

                            </div>

                            <div class="d-flex justify-content-between mb-1" style="font-size: 12px; color:#8792a2;">
                                <span><?php echo e($contract->contract_start_date->format('d M Y')); ?></span>
                                <span><?php echo e($contract->contract_end_date->format('d M Y')); ?></span>
                            </div>
                            <div class="ct-progress-track mb-1">
                                <div class="ct-progress-fill"
                                    style="width: <?php echo e($progress); ?>%; background-color: <?php echo e($s['color']); ?>;"></div>
                            </div>
                            <p class="small mb-3" style="color: <?php echo e($s['color']); ?>;">
                                <?php if($contract->status === 'active' && $daysLeft > 0): ?>
                                    <iconify-icon icon="solar:hourglass-outline" style="font-size:13px;"></iconify-icon>
                                    <?php echo e($daysLeft); ?> days remaining
                                <?php elseif($contract->status === 'active'): ?>
                                    Ending very soon
                                <?php else: ?>
                                    <?php echo e($s['label']); ?>

                                <?php endif; ?>
                            </p>

                            <div class="d-flex gap-2 mb-3">
                                <div class="ct-info-pill">
                                    <div class="val"><?php echo e($contract->elevatorUnits->count()); ?></div>
                                    <div class="lbl">Units</div>
                                </div>
                                <div class="ct-info-pill">
                                    <div class="val" style="font-size:13px;">
                                        <?php echo e(optional($contract->engineer)->name ?? '—'); ?></div>
                                    <div class="lbl">Engineer</div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="<?php echo e(route('admin.contracts.show', $contract->id)); ?>" class="btn btn-sm"
                                    style="background: <?php echo e($s['bg']); ?>; color: <?php echo e($s['color']); ?>; font-weight: 600; border: none;">
                                    View Details
                                </a>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e(route('admin.contracts.edit', $contract->id)); ?>"
                                        class="ct-icon-btn primary" title="Edit">
                                        <iconify-icon icon="solar:pen-2-outline" style="font-size: 16px;"></iconify-icon>
                                    </a>
                                    <button type="button"
                                        class="ct-icon-btn danger border-0 bg-transparent delete-contract"
                                        data-id="<?php echo e($contract->id); ?>" title="Delete">
                                        <iconify-icon icon="solar:trash-bin-minimalistic-outline"
                                            style="font-size: 16px;"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12">
                    <div class="card ct-stat-card">
                        <div class="card-body text-center text-muted py-5">
                            <iconify-icon icon="solar:buildings-outline"
                                style="font-size: 40px; opacity: 0.4;"></iconify-icon>
                            <p class="mb-2 mt-2">No projects yet.</p>
                            <a href="<?php echo e(route('admin.contracts.create')); ?>" class="btn btn-primary btn-sm">Create your
                                first project</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-end">
            <?php echo e($contracts->links()); ?>

        </div>
    <?php endif; ?>

    <div class="modal fade" id="manageRoutesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Routes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Remove a route that was added by mistake. A route that is still linked to a project
                        cannot be deleted — move those projects to another route first.
                    </p>
                    <div id="routesMessage"></div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Route</th>
                                    <th>Description</th>
                                    <th>Team</th>
                                    <th class="text-center">Projects</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="routesTableBody">
                                <tr><td colspan="5" class="text-center text-muted py-3">Loading…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="masterListUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Upload — New Projects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="masterListUploadMessage"></div>

                    <p class="text-muted small">
                        This adds <strong>new</strong> projects and units only — it will not change any existing project.
                        Download the template, fill it in (repeat the project columns on every unit row that belongs to the
                        same project), then upload it below.
                    </p>

                    <a href="<?php echo e(route('admin.contracts.master-list.template')); ?>"
                        class="btn btn-sm btn-outline-secondary mb-3">
                        <iconify-icon icon="solar:download-outline" style="margin-right:4px;"></iconify-icon> Download
                        Template
                    </a>

                    <form id="masterListUploadForm" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label">Excel File</label>
                            <input type="file" name="master_list_file" class="form-control" accept=".xlsx,.xls,.csv"
                                required>
                        </div>
                    </form>

                    <div id="masterListRowErrors" class="small text-danger" style="max-height:180px; overflow-y:auto;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="masterListUploadBtn" class="btn btn-primary">Upload</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('masterListUploadBtn').addEventListener('click', function() {
            const form = document.getElementById('masterListUploadForm');
            const formData = new FormData(form);
            const msgBox = document.getElementById('masterListUploadMessage');
            const rowErrorsBox = document.getElementById('masterListRowErrors');
            const btn = this;

            if (!form.querySelector('input[type="file"]').files.length) {
                msgBox.innerHTML = '<div class="alert alert-danger">Please choose a file.</div>';
                return;
            }

            btn.disabled = true;
            btn.innerText = 'Uploading...';
            msgBox.innerHTML = '';
            rowErrorsBox.innerHTML = '';

            fetch("<?php echo e(route('admin.contracts.master-list.import')); ?>", {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = 'Upload';

                    if (data.success) {
                        msgBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        if (data.row_errors && data.row_errors.length) {
                            rowErrorsBox.innerHTML = '<strong>Skipped rows:</strong><br>' + data.row_errors
                                .join('<br>');
                        }
                        setTimeout(() => window.location.reload(), 1200);
                    } else {
                        const errors = data.errors?.master_list_file || Object.values(data.errors || {}).flat();
                        msgBox.innerHTML = `<div class="alert alert-danger">Upload failed.</div>`;
                        rowErrorsBox.innerHTML = Array.isArray(errors) ? errors.join('<br>') : String(errors);
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerText = 'Upload';
                    msgBox.innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>

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

        document.querySelectorAll('.delete-contract').forEach(button => {
            button.addEventListener('click', function() {
                let contractId = this.dataset.id;
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will permanently delete the project and all its units.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("<?php echo e(url('admin/contracts')); ?>/" + contractId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Deleted!', data.message, 'success').then(() =>
                                        location.reload());
                                } else {
                                    Swal.fire('Cannot delete', data.message, 'error');
                                }
                            });
                    }
                });
            });
        });

        // ===== Master list: select rows + bulk delete =====
        const rowSelects = Array.from(document.querySelectorAll('.row-select'));
        const selectAllRows = document.getElementById('selectAllRows');
        const bulkBar = document.getElementById('bulkBar');

        function selectedUnitIds() {
            return rowSelects.filter(c => c.checked).map(c => parseInt(c.value));
        }

        function refreshBulkBar() {
            if (!bulkBar) return;
            const count = selectedUnitIds().length;
            document.getElementById('bulkCount').textContent = count;
            bulkBar.classList.toggle('show', count > 0);
            if (selectAllRows) {
                selectAllRows.checked = rowSelects.length > 0 && count === rowSelects.length;
                selectAllRows.indeterminate = count > 0 && count < rowSelects.length;
            }
        }

        selectAllRows?.addEventListener('change', function() {
            rowSelects.forEach(c => c.checked = this.checked);
            refreshBulkBar();
        });
        rowSelects.forEach(c => c.addEventListener('change', refreshBulkBar));
        document.getElementById('bulkClear')?.addEventListener('click', function() {
            rowSelects.forEach(c => c.checked = false);
            refreshBulkBar();
        });

        function bulkDelete(mode) {
            const ids = selectedUnitIds();
            if (!ids.length) return;

            const text = mode === 'projects'
                ? 'This permanently deletes every project the selected rows belong to, with ALL of their units and scheduled visits. Projects with recorded maintenance work are skipped.'
                : `This permanently deletes ${ids.length} selected unit(s) and their upcoming visits. Units with recorded maintenance work are skipped.`;

            Swal.fire({
                title: mode === 'projects' ? 'Delete whole projects?' : 'Delete selected units?',
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch("<?php echo e(route('admin.contracts.bulk-destroy')); ?>", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>",
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ mode: mode, unit_ids: ids })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const skipped = (data.skipped || []).length
                                ? '<br><br><small>Skipped:<br>' + data.skipped.map(s => s.replace(/</g, '&lt;')).join('<br>') + '</small>'
                                : '';
                            Swal.fire({ title: 'Done', html: data.message + skipped, icon: 'success' })
                                .then(() => location.reload());
                        } else {
                            Swal.fire('Error!', Object.values(data.errors || {}).flat().join('<br>') || 'Delete failed.', 'error');
                        }
                    });
            });
        }

        document.getElementById('bulkDeleteUnits')?.addEventListener('click', () => bulkDelete('units'));
        document.getElementById('bulkDeleteProjects')?.addEventListener('click', () => bulkDelete('projects'));

        // ===== Manage Routes =====
        const routesBody = document.getElementById('routesTableBody');
        const routesMessage = document.getElementById('routesMessage');
        let routesChanged = false;

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        function loadRoutes() {
            fetch("<?php echo e(route('admin.routes.index')); ?>", { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    if (!data.routes.length) {
                        routesBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No routes yet.</td></tr>';
                        return;
                    }
                    routesBody.innerHTML = data.routes.map(route => `
                        <tr>
                            <td class="fw-semibold">${escapeHtml(route.route_no)}</td>
                            <td>${escapeHtml(route.description) || '<span class="text-muted">—</span>'}</td>
                            <td class="small">${escapeHtml(route.people) || '<span class="text-muted">—</span>'}</td>
                            <td class="text-center">${route.contracts_count}</td>
                            <td class="text-end">
                                ${route.contracts_count > 0
                                    ? '<span class="small text-muted" title="Linked to projects">In use</span>'
                                    : `<button type="button" class="btn btn-sm btn-outline-danger delete-route" data-id="${route.id}" data-name="${escapeHtml(route.route_no)}">Delete</button>`}
                            </td>
                        </tr>`).join('');

                    routesBody.querySelectorAll('.delete-route').forEach(btn => {
                        btn.addEventListener('click', function() {
                            if (!confirm(`Delete route ${this.dataset.name}?`)) return;
                            fetch("<?php echo e(url('admin/routes')); ?>/" + this.dataset.id, {
                                    method: 'DELETE',
                                    headers: { 'X-CSRF-TOKEN': "<?php echo e(csrf_token()); ?>", 'Accept': 'application/json' }
                                })
                                .then(r => r.json())
                                .then(res => {
                                    routesChanged = routesChanged || res.success;
                                    routesMessage.innerHTML = `<div class="alert alert-${res.success ? 'success' : 'danger'} py-2">${escapeHtml(res.message)}</div>`;
                                    loadRoutes();
                                });
                        });
                    });
                });
        }

        document.getElementById('manageRoutesModal').addEventListener('show.bs.modal', function() {
            routesMessage.innerHTML = '';
            loadRoutes();
        });
        document.getElementById('manageRoutesModal').addEventListener('hidden.bs.modal', function() {
            if (routesChanged) location.reload(); // refresh the route filter list
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Projects'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/contracts/index.blade.php ENDPATH**/ ?>