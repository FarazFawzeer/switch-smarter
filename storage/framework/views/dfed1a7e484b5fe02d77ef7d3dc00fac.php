

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'New Project'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div id="message"></div>

    <form id="createContractForm" action="<?php echo e(route('admin.contracts.store')); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        
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
                        <input type="text" name="project_name" class="form-control" placeholder="Ex: Liberty Plaza" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Type</label>
                        <select id="project_type" name="project_type" class="form-select">
                            <option value="">Select type (optional)</option>
                            <option value="Residential">Residential</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Industrial">Industrial</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Hotel">Hotel</option>
                            <option value="Mixed Use">Mixed Use</option>
                            <option value="Other">Other</option>
                        </select>
                        <input type="text" id="project_type_other" name="project_type_other" class="form-control mt-2"
                            placeholder="Please specify" style="display:none;">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Ex: Colombo 03" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Elevators / Units</label>
                        <input type="number" id="number_of_elevators" name="number_of_elevators" class="form-control"
                            min="0" max="200" value="1" required>
                        <div class="form-text">For large contracts (50+ units), save with a few units now and use
                            <strong>Bulk Import</strong> on the edit page afterward.</div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Contract Document <span class="text-muted">(optional)</span></label>
                        <input type="file" name="contract_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
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
                <p class="card-subtitle">Fill in the details for each unit. This updates automatically based on the number entered above.</p>
            </div>
            <div class="card-body" id="elevatorUnitsContainer">
                
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:user-check-outline" style="margin-right:6px;"></iconify-icon>
                    Route & Engineer Assignment
                </h5>
                <p class="card-subtitle">Supervisor and technician assignment happens later, in Contract Scheduling.</p>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Route</label>
                        <div class="d-flex gap-2 mb-2">
                            <select id="route_id" name="route_id" class="form-select">
                                <option value="">No route yet / assign later</option>
                                <?php $__currentLoopData = $routes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $route): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($route->id); ?>" data-engineer="<?php echo e(optional($route->users->first())->name ?? ''); ?>">
                                        <?php echo e($route->route_no); ?><?php echo e($route->description ? ' — '.$route->description : ''); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#newRouteModal">
                                <iconify-icon icon="solar:add-circle-outline"></iconify-icon> New
                            </button>
                        </div>

                        <input type="text" name="new_route_no" class="form-control" placeholder="Or type a new route, e.g. RT-06" style="display:none;">

                        <div id="routeEngineerDisplay" class="form-text" style="display:none;">
                            <iconify-icon icon="solar:user-check-outline"></iconify-icon> Engineer: <strong id="routeEngineerName"></strong>
                        </div>
                        <div class="form-text" id="noRouteEngineerText" style="display:none;">
                            No engineer is assigned to this route yet.
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Engineer</label>
                        <select name="assigned_engineer_id" id="assigned_engineer_id" class="form-select" required>
                            <option value="">Select Engineer</option>
                            <?php $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($engineer->id); ?>"><?php echo e($engineer->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <div class="form-text">Auto-fills based on the route's assigned engineer — you can still change it manually.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="<?php echo e(route('admin.contracts.index')); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Project</button>
        </div>
    </form>

    
    <div class="modal fade" id="newRouteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Route</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="newRouteMessage"></div>
                    <div class="mb-3">
                        <label class="form-label">Route Number</label>
                        <input type="text" id="modal_route_no" class="form-control" placeholder="Ex: RT-06" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <input type="text" id="modal_route_description" class="form-control" placeholder="Ex: Nugegoda / Maharagama zone">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Assign Engineer <span class="text-muted">(optional)</span></label>
                        <select id="modal_route_engineer" class="form-select">
                            <option value="">Assign later</option>
                            <?php $__currentLoopData = $engineers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $engineer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($engineer->id); ?>"><?php echo e($engineer->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveNewRouteBtn" class="btn btn-primary">Create Route</button>
                </div>
            </div>
        </div>
    </div>

    
    <template id="customFieldTemplate">
        <div class="d-flex gap-2 mb-2 custom-field-row">
            <input type="text" class="form-control custom-field-label" placeholder="Field name (e.g. Building Manager)">
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
                    <input type="text" name="elevators[__INDEX__][identification_no]" class="form-control" placeholder="Ex: LP-A-01" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Unit Type</label>
                    <select name="elevators[__INDEX__][unit_type]" class="form-select" required>
                        <option value="Elevator" selected>Elevator</option>
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
                    <input type="text" name="elevators[__INDEX__][speed]" class="form-control" placeholder="Ex: 1.5 m/s">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="text" name="elevators[__INDEX__][capacity]" class="form-control" placeholder="Ex: 1000kg / 13 persons">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Brand</label>
                    <input type="text" name="elevators[__INDEX__][brand]" class="form-control" placeholder="Ex: KONE">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Model</label>
                    <input type="text" name="elevators[__INDEX__][model]" class="form-control" placeholder="Ex: MonoSpace 500">
                </div>
            </div>

            <div class="unit-custom-fields"></div>
            <button type="button" class="btn btn-sm btn-outline-primary add-unit-field-btn">
                <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> Add Extra Field for This Unit
            </button>
        </div>
    </template>

    <script>
        // ===== Project Type "Other" toggle =====
        document.getElementById('project_type').addEventListener('change', function() {
            document.getElementById('project_type_other').style.display = this.value === 'Other' ? 'block' : 'none';
        });

        // ===== Reusable: add a custom field row (label/value pair) into any container =====
        function addCustomFieldRow(container, labelName, valueName) {
            const template = document.getElementById('customFieldTemplate');
            const wrapper = document.createElement('div');
            wrapper.innerHTML = template.innerHTML;
            const row = wrapper.firstElementChild;

            row.querySelector('.custom-field-label').name = labelName;
            row.querySelector('.custom-field-value').name = valueName;

            row.querySelector('.remove-field-btn').addEventListener('click', () => row.remove());
            container.appendChild(row);
        }

        document.getElementById('addContractFieldBtn').addEventListener('click', function() {
            addCustomFieldRow(document.getElementById('contractCustomFields'), 'custom_field_labels[]', 'custom_field_values[]');
        });

        // ===== Elevator units (dynamic count + per-unit custom fields) =====
        const container = document.getElementById('elevatorUnitsContainer');
        const template = document.getElementById('elevatorUnitTemplate');
        const countInput = document.getElementById('number_of_elevators');

        function renderElevatorUnits(count) {
            const existingValues = [];
            container.querySelectorAll('.elevator-unit-block').forEach(block => {
                const values = {};
                block.querySelectorAll('input, select').forEach(field => {
                    const key = field.name.match(/\[(\w+)\]$/)?.[1];
                    if (key) values[key] = field.value;
                });
                existingValues.push(values);
            });

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
                        if (key && existingValues[i][key]) field.value = existingValues[i][key];
                    });
                }

                block.querySelector('.add-unit-field-btn').addEventListener('click', function() {
                    const unitFieldsContainer = block.querySelector('.unit-custom-fields');
                    addCustomFieldRow(unitFieldsContainer, `elevators[${i}][custom_field_labels][]`, `elevators[${i}][custom_field_values][]`);
                });

                container.appendChild(block);
            }
        }

        countInput.addEventListener('input', function() {
            const count = Math.min(Math.max(parseInt(this.value) || 0, 0), 200);
            renderElevatorUnits(count);
        });

        renderElevatorUnits(1);

        // ===== Route → Engineer auto-display =====
        const routeSelect = document.getElementById('route_id');
        const engineerSelect = document.getElementById('assigned_engineer_id');
        const routeEngineerDisplay = document.getElementById('routeEngineerDisplay');
        const routeEngineerName = document.getElementById('routeEngineerName');
        const noRouteEngineerText = document.getElementById('noRouteEngineerText');

        function updateEngineerFromRoute() {
            const selectedOption = routeSelect.options[routeSelect.selectedIndex];
            const engineerName = selectedOption?.dataset?.engineer || '';

            routeEngineerDisplay.style.display = 'none';
            noRouteEngineerText.style.display = 'none';

            if (routeSelect.value === '') {
                return;
            }

            if (engineerName) {
                routeEngineerName.textContent = engineerName;
                routeEngineerDisplay.style.display = 'block';

                for (const opt of engineerSelect.options) {
                    if (opt.text === engineerName) {
                        engineerSelect.value = opt.value;
                        break;
                    }
                }
            } else {
                noRouteEngineerText.style.display = 'block';
            }
        }

        routeSelect.addEventListener('change', updateEngineerFromRoute);

        // ===== New Route modal =====
        document.getElementById('saveNewRouteBtn').addEventListener('click', function() {
            const routeNo = document.getElementById('modal_route_no').value.trim();
            const description = document.getElementById('modal_route_description').value.trim();
            const engineerId = document.getElementById('modal_route_engineer').value;
            const msgBox = document.getElementById('newRouteMessage');

            if (! routeNo) {
                msgBox.innerHTML = '<div class="alert alert-danger">Route number is required.</div>';
                return;
            }

            const btn = this;
            btn.disabled = true;
            btn.innerText = 'Creating...';

            fetch("<?php echo e(route('admin.routes.quick-store')); ?>", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({ route_no: routeNo, description: description, engineer_id: engineerId })
                })
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerText = 'Create Route';

                    if (data.success) {
                        const option = document.createElement('option');
                        option.value = data.route.id;
                        option.text = data.route.route_no + (data.route.description ? ' — ' + data.route.description : '');
                        option.dataset.engineer = data.route.engineer_name || '';
                        routeSelect.appendChild(option);
                        routeSelect.value = data.route.id;
                        updateEngineerFromRoute();

                        document.getElementById('modal_route_no').value = '';
                        document.getElementById('modal_route_description').value = '';
                        document.getElementById('modal_route_engineer').value = '';
                        msgBox.innerHTML = '';
                        bootstrap.Modal.getInstance(document.getElementById('newRouteModal')).hide();
                    } else {
                        msgBox.innerHTML = `<div class="alert alert-danger">${Object.values(data.errors).flat().join('<br>')}</div>`;
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerText = 'Create Route';
                    msgBox.innerHTML = '<div class="alert alert-danger">Something went wrong. Please try again.</div>';
                });
        });

        // ===== Form submit =====
        document.getElementById('createContractForm').addEventListener('submit', function(e) {
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
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.vertical', ['subtitle' => 'New Project'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH F:\Personal Projects\Elevator\switch-smarter\resources\views/admin/contracts/create.blade.php ENDPATH**/ ?>