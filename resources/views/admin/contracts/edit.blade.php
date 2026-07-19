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
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="{{ $contract->location }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contract Number</label>
                        <input type="text" class="form-control" value="{{ $contract->contract_number }}" disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control"
                            value="{{ $contract->contract_start_date->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control"
                            value="{{ $contract->contract_end_date->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Elevators / Units</label>
                        <input type="number" id="number_of_elevators" name="number_of_elevators" class="form-control"
                            min="1" max="50" value="{{ $contract->elevatorUnits->count() }}" required>
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
            </div>
        </div>

        {{-- SECTION 2: Elevator/Unit Details --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:elevator-outline" style="margin-right:6px;"></iconify-icon>
                    Elevator / Unit Details
                </h5>
                <p class="card-subtitle">Fill in the details for each unit. This updates automatically based on the number entered above.</p>
            </div>
            <div class="card-body" id="elevatorUnitsContainer">
                {{-- JS populates existing units here on page load --}}
            </div>
        </div>

        {{-- SECTION 3: Route & Engineer Assignment --}}
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
                        <label class="form-label">Route No <span class="text-muted">(optional)</span></label>
                        <input type="text" name="route_no" class="form-control" value="{{ $contract->route_no }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Engineer</label>
                        <select name="assigned_engineer_id" class="form-select" required>
                            <option value="">Select Engineer</option>
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}" {{ $contract->assigned_engineer_id == $engineer->id ? 'selected' : '' }}>
                                    {{ $engineer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.contracts.show', $contract->id) }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Update Project</button>
        </div>
    </form>

    {{-- Template for one elevator unit block — cloned by JS --}}
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
        </div>
    </template>

    <script>
        // Existing units passed from the backend, used to pre-fill the form on load
        const existingUnits = @json($contract->elevatorUnits);

        const container = document.getElementById('elevatorUnitsContainer');
        const template = document.getElementById('elevatorUnitTemplate');
        const countInput = document.getElementById('number_of_elevators');

        function renderElevatorUnits(count, prefillData = null) {
            const existingValues = [];

            if (prefillData) {
                // First render on page load — use data passed from the server
                prefillData.forEach(unit => {
                    existingValues.push({
                        identification_no: unit.identification_no,
                        unit_type: unit.unit_type,
                        elevator_type: unit.elevator_type || '',
                        speed: unit.speed || '',
                        capacity: unit.capacity || '',
                        brand: unit.brand || '',
                        model: unit.model || '',
                    });
                });
            } else {
                // Subsequent re-renders (count changed) — preserve whatever's currently typed
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
                }

                container.appendChild(block);
            }
        }

        countInput.addEventListener('input', function() {
            const count = Math.min(Math.max(parseInt(this.value) || 1, 1), 50);
            renderElevatorUnits(count);
        });

        // Initial render — pre-fill with the contract's existing units
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
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection