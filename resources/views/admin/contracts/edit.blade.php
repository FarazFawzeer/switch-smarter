@extends('layouts.vertical', ['subtitle' => 'Edit Project'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'Edit Project'])

    <div id="message"></div>

    <form id="editContractForm" action="{{ route('admin.contracts.update', $contract->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        {{-- SECTION 1: Project Information --}}
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
                        <input type="text" name="project_name" class="form-control" value="{{ $contract->project_name }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Type</label>
                        @php
                            $knownTypes = ['Residential', 'Commercial', 'Industrial', 'Hospital', 'Hotel', 'Mixed Use'];
                            $isOtherType = $contract->project_type && ! in_array($contract->project_type, $knownTypes);
                        @endphp
                        <select id="project_type" name="project_type" class="form-select">
                            <option value="">Select type (optional)</option>
                            @foreach($knownTypes as $type)
                                <option value="{{ $type }}" {{ $contract->project_type === $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                            <option value="Other" {{ $isOtherType ? 'selected' : '' }}>Other</option>
                        </select>
                        <input type="text" id="project_type_other" name="project_type_other" class="form-control mt-2"
                            placeholder="Please specify" value="{{ $isOtherType ? $contract->project_type : '' }}"
                            style="{{ $isOtherType ? '' : 'display:none;' }}">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $contract->location }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control"
                            value="{{ $contract->contract_start_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control"
                            value="{{ $contract->contract_end_date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Elevators / Units</label>
                        <input type="number" id="number_of_elevators" name="number_of_elevators" class="form-control"
                            min="0" max="200" value="{{ $contract->elevatorUnits->count() }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" {{ $contract->status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="expired" {{ $contract->status == 'expired' ? 'selected' : '' }}>Expired</option>
                            <option value="cancelled" {{ $contract->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contract Document <span class="text-muted">(optional)</span></label>
                        <input type="file" name="contract_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                        @if($contract->contract_document)
                            <div class="form-text">
                                Current: <a href="{{ storage_asset($contract->contract_document) }}" target="_blank">View existing document</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div id="contractCustomFields"></div>
                <button type="button" id="addContractFieldBtn" class="btn btn-sm btn-outline-primary">
                    <iconify-icon icon="solar:add-circle-outline" style="margin-right:4px;"></iconify-icon> Add Extra Field
                </button>
            </div>
        </div>

        {{-- SECTION 2: Elevator/Unit Details --}}
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

        {{-- SECTION 3: Route & Engineer Assignment --}}
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
                        <div class="d-flex gap-2 mb-2">
                            <select id="route_id" name="route_id" class="form-select">
                                <option value="">No route yet / assign later</option>
                                @foreach($routes as $route)
                                    <option value="{{ $route->id }}" data-engineer="{{ optional($route->users->first())->name ?? '' }}"
                                        {{ $contract->route_id == $route->id ? 'selected' : '' }}>
                                        {{ $route->route_no }}{{ $route->description ? ' — '.$route->description : '' }}
                                    </option>
                                @endforeach
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
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}" {{ $contract->assigned_engineer_id == $engineer->id ? 'selected' : '' }}>
                                    {{ $engineer->name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">Auto-fills based on the route's assigned engineer — you can still change it manually.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Project</button>
        </div>
    </form>

    {{-- BULK IMPORT: separate mini-form, adds units without touching the ones above --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <iconify-icon icon="solar:upload-outline" style="margin-right:6px;"></iconify-icon>
                Bulk Import Elevator Units
            </h5>
            <p class="card-subtitle">For contracts with many units (50+), upload a spreadsheet instead of entering each one manually. This adds to the units already saved above — it does not replace them.</p>
        </div>
        <div class="card-body">
            <div id="importMessage"></div>
            <p class="small text-muted">Expected columns (first row as headers): <code>identification_no, unit_number, unit_type, elevator_type, speed, capacity, brand, model</code></p>
            <form id="importUnitsForm" action="{{ route('admin.contracts.units.import', $contract->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="d-flex gap-2">
                    <input type="file" name="units_file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <button type="submit" class="btn btn-primary text-nowrap">Upload &amp; Import</button>
                </div>
            </form>
        </div>
    </div>

    {{-- New Route modal --}}
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
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                            @endforeach
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

    {{-- Template for one custom field row --}}
    <template id="customFieldTemplate">
        <div class="d-flex gap-2 mb-2 custom-field-row">
            <input type="text" class="form-control custom-field-label" placeholder="Field name (e.g. Building Manager)">
            <input type="text" class="form-control custom-field-value" placeholder="Value">
            <button type="button" class="btn btn-outline-danger remove-field-btn">
                <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon>
            </button>
        </div>
    </template>

    {{-- Template for one elevator unit block --}}
    <template id="elevatorUnitTemplate">
        <div class="border rounded p-3 mb-3 elevator-unit-block">
            <h6 class="mb-3 text-primary unit-index-label">Unit __INDEX_DISPLAY__</h6>
            <div class="row">
                {{-- keeps the link to the saved unit, so editing never re-creates it --}}
                <input type="hidden" name="elevators[__INDEX__][id]">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Elevator Identification No</label>
                    <input type="text" name="elevators[__INDEX__][identification_no]" class="form-control" placeholder="Ex: LP-A-01" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Unit Number</label>
                    <input type="text" name="elevators[__INDEX__][unit_number]" class="form-control" placeholder="Ex: L1, L2, SL3">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Unit Type</label>
                    <select name="elevators[__INDEX__][unit_type]" class="form-select" required>
                        <option value="Elevator">Elevator</option>
                        <option value="Escalator">Escalator</option>
                        <option value="Dumbwaiter">Dumbwaiter</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
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
        const existingUnits = @json($contract->elevatorUnits);
        const existingContractFields = @json($contract->custom_fields ?? []);

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

        const contractFieldsContainer = document.getElementById('contractCustomFields');
        existingContractFields.forEach(f => {
            addCustomFieldRow(contractFieldsContainer, 'custom_field_labels[]', 'custom_field_values[]', f.label, f.value);
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
                        id: unit.id,
                        identification_no: unit.identification_no,
                        unit_number: unit.unit_number || '',
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
                        addCustomFieldRow(unitFieldsContainer, `elevators[${i}][custom_field_labels][]`, `elevators[${i}][custom_field_values][]`, f.label, f.value);
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

        renderElevatorUnits(existingUnits.length > 0 ? existingUnits.length : 1, existingUnits);

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
            } else {
                noRouteEngineerText.style.display = 'block';
            }
        }

        routeSelect.addEventListener('change', updateEngineerFromRoute);
        updateEngineerFromRoute(); // run once on load — pre-existing route selection shows engineer immediately

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

            fetch("{{ route('admin.routes.quick-store') }}", {
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

        // ===== Main form submit =====
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
                        setTimeout(() => { window.location.href = data.redirect; }, 1000);
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

        // ===== Bulk import form submit =====
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
                        setTimeout(() => { window.location.href = data.redirect; }, 1200);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('importMessage').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection