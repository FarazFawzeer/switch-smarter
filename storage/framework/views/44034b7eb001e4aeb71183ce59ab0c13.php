

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Edit Project'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div id="message"></div>

    <form id="editContractForm" action="<?php echo e(route('admin.contracts.update', $contract->id)); ?>" method="POST"
        enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:buildings-outline" style="margin-right:6px;"></iconify-icon>
                    Project Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Name</label>
                        <input type="text" name="project_name" class="form-control" value="<?php echo e($contract->project_name); ?>"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Type</label>
                        <?php
                            $knownTypes = ['Residential', 'Commercial', 'Industrial', 'Hospital', 'Hotel', 'Mixed Use'];
                            $isOtherType = $contract->project_type && !in_array($contract->project_type, $knownTypes);
                        ?>
                        <select id="project_type" name="project_type" class="form-select">
                            <option value="">Select type (optional)</option>
                            <?php $__currentLoopData = $knownTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type); ?>"
                                    <?php echo e($contract->project_type === $type ? 'selected' : ''); ?>><?php echo e($type); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <option value="Other" <?php echo e($isOtherType ? 'selected' : ''); ?>>Other</option>
                        </select>
                        <input type="text" id="project_type_other" name="project_type_other" class="form-control mt-2"
                            placeholder="Please specify" value="<?php echo e($isOtherType ? $contract->project_type : ''); ?>"
                            style="<?php echo e($isOtherType ? '' : 'display:none;'); ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?php echo e($contract->location); ?>"
                            required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control"
                            value="<?php echo e($contract->contract_start_date->format('Y-m-d')); ?>" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control"
                            value="<?php echo e($contract->contract_end_date->format('Y-m-d')); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Elevators / Units</label>
                        <input type="number" id="number_of_elevators" name="number_of_elevators" class="form-control"
                            min="0" max="200" value="<?php echo e($contract->elevatorUnits->count()); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" <?php echo e($contract->status == 'active' ? 'selected' : ''); ?>>Active</option>
                            <option value="expired" <?php echo e($contract->status == 'expired' ? 'selected' : ''); ?>>Expired</option>
                            <option value="cancelled" <?php echo e($contract->status == 'cancelled' ? 'selected' : ''); ?>>Cancelled
                            </option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contract Document <span class="text-muted">(optional)</span></label>
                        <input type="file" name="contract_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        <?php if($contract->contract_document): ?>
                            <div class="form-text">
                                Current: <a href="<?php echo e(storage_asset($contract->contract_document)); ?>" target="_blank">View
                                    existing document</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="contractCustomFields"></div>
                <button type="button" id="addContractFieldBtn" class="btn btn-sm btn-outline-primary">
                    <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> Add Extra Field
                </button>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:widget-5-outline" style="margin-right:6px;"></iconify-icon>
                    Elevator / Unit Details
                </h5>
                <p class="card-subtitle">Editing these fields and saving will replace the unit list below entirely.</p>
            </div>
            <div class="card-body" id="elevatorUnitsContainer"></div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:user-check-outline" style="margin-right:6px;"></iconify-icon>
                    Route & Engineer Assignment
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Route</label>
                        <select name="route_id" class="form-select mb-2">
                            <option value="">No route yet / assign later</option>
                            <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($route->id); ?>"
                                    <?php echo e($contract->route_id == $route->id ? 'selected' : ''); ?>>
                                    <?php echo e($route->route_no); ?><?php echo e($route->description ? ' — ' . $route->description : ''); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <input type="text" name="new_route_no" class="form-control"
                            placeholder="Or type a new route, e.g. RT-06">
                        <div class="form-text">Only fill this in if the route doesn't already exist in the list above.
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Engineer</label>
                        <select name="assigned_engineer_id" class="form-select" required>
                            <option value="">Select Engineer</option>
                            <?php $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($engineer->id); ?>"
                                    <?php echo e($contract->assigned_engineer_id == $engineer->id ? 'selected' : ''); ?>>
                                    <?php echo e($engineer->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="<?php echo e(route('admin.contracts.show', $contract->id)); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Project</button>
        </div>
    </form>

    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <iconify-icon icon="solar:upload-outline" style="margin-right:6px;"></iconify-icon>
                Bulk Import Elevator Units
            </h5>
            <p class="card-subtitle">For contracts with many units (50+), upload a spreadsheet instead of entering each one
                manually. This adds to the units already saved above — it does not replace them.</p>
        </div>
        <div class="card-body">
            <div id="importMessage"></div>
            <p class="small text-muted">Expected columns (first row as headers): <code>identification_no, unit_type,
                    elevator_type, speed, capacity, brand, model</code></p>
            <form id="importUnitsForm" action="<?php echo e(route('admin.contracts.units.import', $contract->id)); ?>"
                method="POST" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <div class="d-flex gap-2">
                    <input type="file" name="units_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <button type="submit" class="btn btn-primary text-nowrap">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>

    
    <template id="customFieldTemplate">
        <div class="d-flex gap-2 mb-2 custom-field-row">
            <input type="text" class="form-control custom-field-label"
                placeholder="Field name (e.g. Building Manager)">
            <input type="text" class="form-control custom-field-value" placeholder="Value">
            <button type="button" class="btn btn-outline-danger remove-field-btn">
                <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon>
            </button>
        </div>
    </template>

    
    <template id="elevatorUnitTemplate">
        <div class="border rounded p-3 mb-3 elevator-unit-block">
            <h6 class="mb-3 text-primary unit-index-label">Unit __INDEX_DISPLAY__</h6>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Elevator Identification No</label>
                    <input type="text" name="elevators[__INDEX__][identification_no]" class="form-control"
                        placeholder="Ex: LP-A-01" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Unit Type</label>
                    <select name="elevators[__INDEX__][unit_type]" class="form-select" required>
                        <option value="Elevator">Elevator</option>
                        <option value="Escalator">Escalator</option>
                        <option value="Dumbwaiter">Dumbwaiter</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Elevator Type</label>
                    <select name="elevators[__INDEX__][elevator_type]" class="form-select">
                        <option value="">Select type</option>
                        <option value="Passenger">Passenger</option>
                        <option value="Service">Service</option>
                        <option value="Freight">Freight</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Speed</label>
                    <input type="text" name="elevators[__INDEX__][speed]" class="form-control"
                        placeholder="Ex: 1.5 m/s">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="text" name="elevators[__INDEX__][capacity]" class="form-control"
                        placeholder="Ex: 1000kg / 13 persons">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" name="elevators[__INDEX__][brand]" class="form-control"
                        placeholder="Ex: KONE">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Model</label>
                    <input type="text" name="elevators[__INDEX__][model]" class="form-control"
                        placeholder="Ex: MonoSpace 500">
                </div>
            </div>

            <div class="unit-custom-fields"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-unit-field-btn">
                <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> Add Extra Field
                for This Unit
            </button>
        </div>
    </template>

    <script>
        const existingUnits = <?php echo json_encode($contract->elevatorUnits, 15, 512) ?>;
        const existingContractFields = <?php echo json_encode($contract->custom_fields ?? [], 15, 512) ?>;

        document.getElementById('project_type').addEventListener('change', function() {
            document.getElementById('project_type_other').style.display = this.value === 'Other' ? 'block' : 'none';
        });

        function addCustomFieldRow(container, labelName, valueName, prefillLabel = '', prefillValue = '') {
            const template = document.getElementById('customFieldTemplate');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML;
            const row = wrapper.firstElementChild;

            const labelInput = row.querySelector('.custom-field-label');
            const valueInput = row.querySelector('.custom-field-value');
            labelInput.name = labelName;
            valueInput.name = valueName;
            labelInput.value = prefillLabel;
            valueInput.value = prefillValue;

            row.querySelector('.remove-field-btn').addEventListener('click', () => row.remove());
            container.appendChild(row);
        }

        // Pre-fill contract-level custom fields
        const contractFieldsContainer = document.getElementById('contractCustomFields');
        existingContractFields.forEach(f => {
            addCustomFieldRow(contractFieldsContainer, 'custom_field_labels[]', 'custom_field_values[]', f.label, f
                .value);
        });

        document.getElementById('addContractFieldBtn').addEventListener('click', function() {
            addCustomFieldRow(contractFieldsContainer, 'custom_field_labels[]', 'custom_field_values[]');
        });

        const container = document.getElementById('elevatorUnitsContainer');
        const template = document.getElementById('elevatorUnitTemplate');
        const countInput = document.getElementById('number_of_elevators');

        function renderElevatorUnits(count, prefillData = null) {
            const existingValues = [];

            if (prefillData) {
                prefillData.forEach(unit => {
                    existingValues.push({
                        identification_no: unit.identification_no,
                        unit_type: unit.unit_type,
                        elevator_type: unit.elevator_type || '',
                        speed: unit.speed || '',
                        capacity: unit.capacity || '',
                        brand: unit.brand || '',
                        model: unit.model || '',
                        _customFields: unit.custom_fields || [],
                    });
                });
            } else {
                container.querySelectorAll('.elevator-unit-block').forEach(block => {
                    const values = {};
                    block.querySelectorAll('input, select').forEach(field => {
                        const key = field.name.match(/\[(\w+)\]$/)?.[1];
                        if (key) values[key] = field.value;
                    });
                    existingValues.push(values);
                });
            }

            container.innerHTML = '';
            for (let i = 0; i < count; i++) {
                let html = template.innerHTML
                    .replaceAll('__INDEX__', i)
                    .replaceAll('__INDEX_DISPLAY__', i + 1);
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const block = wrapper.firstElementChild;

                if (existingValues[i]) {
                    block.querySelectorAll('input, select').forEach(field => {
                        const key = field.name.match(/\[(\w+)\]$/)?.[1];
                        if (key && existingValues[i][key] !== undefined && existingValues[i][key] !== '') {
                            field.value = existingValues[i][key];
                        }
                    });

                    const unitFieldsContainer = block.querySelector('.unit-custom-fields');
                    (existingValues[i]._customFields || []).forEach(f => {
                        addCustomFieldRow(unitFieldsContainer, `elevators[${i}][custom_field_labels][]`,
                            `elevators[${i}][custom_field_values][]`, f.label, f.value);
                    });
                }

                block.querySelector('.add-unit-field-btn').addEventListener('click', function() {
                    const unitFieldsContainer = block.querySelector('.unit-custom-fields');
                    addCustomFieldRow(unitFieldsContainer, `elevators[${i}][custom_field_labels][]`,
                        `elevators[${i}][custom_field_values][]`);
                });

                container.appendChild(block);
            }
        }

        countInput.addEventListener('input', function() {
            const count = Math.min(Math.max(parseInt(this.value) || 0, 0), 200);
            renderElevatorUnits(count);
        });

        renderElevatorUnits(existingUnits.length > 0 ? existingUnits.length : 1, existingUnits);

        document.getElementById('editContractForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);
            formData.append('_method', 'PUT');

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
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML =
                        `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });

        document.getElementById('importUnitsForm').addEventListener('submit', function(e) {
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
                    let messageBox = document.getElementById('importMessage');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1200);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('importMessage').innerHTML =
                        `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.vertical', ['subtitle' => 'Edit Project'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/contracts/edit.blade.php ENDPATH**/ ?>