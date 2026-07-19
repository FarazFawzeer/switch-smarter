@extends('layouts.vertical', ['subtitle' => 'New Project'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Contracts', 'subtitle' => 'New Project'])

    <div id="message"></div>

    <form id="createContractForm" action="{{ route('admin.contracts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                        <input type="text" name="project_name" class="form-control" placeholder="Ex: Liberty Plaza" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Ex: Colombo 03" required>
                    </div>
                </div>

                <div class="row">
                  
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contract Start Date</label>
                        <input type="date" name="contract_start_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Elevators / Units</label>
                        <input type="number" id="number_of_elevators" name="number_of_elevators" class="form-control"
                            min="1" max="50" value="1" required>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Contract Document <span class="text-muted">(optional)</span></label>
                        <input type="file" name="contract_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>
        </div>

        {{-- SECTION 2: Elevator/Unit Details (dynamic, generated based on Number of Elevators) --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <iconify-icon icon="solar:elevator-outline" style="margin-right:6px;"></iconify-icon>
                    Elevator / Unit Details
                </h5>
                <p class="card-subtitle">Fill in the details for each unit. This updates automatically based on the number entered above.</p>
            </div>
            <div class="card-body" id="elevatorUnitsContainer">
                {{-- JS injects one block per unit here --}}
            </div>
        </div>

        {{-- SECTION 3: Engineer Assignment --}}
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
                <input type="text" name="route_no" class="form-control" placeholder="Ex: RT-05">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Engineer</label>
                <select name="assigned_engineer_id" class="form-select" required>
                    <option value="">Select Engineer</option>
                    @foreach($engineers as $engineer)
                        <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Project</button>
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
        </div>
    </template>

    <script>
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

                container.appendChild(block);
            }
        }

        countInput.addEventListener('input', function() {
            const count = Math.min(Math.max(parseInt(this.value) || 1, 1), 50);
            renderElevatorUnits(count);
        });

        renderElevatorUnits(1);

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
@endsection