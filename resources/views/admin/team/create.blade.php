@extends('layouts.vertical', ['subtitle' => 'Team Create'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Create'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">New Team Member</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="createTeamForm" action="{{ route('admin.team.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}"
                            placeholder="Ex: Kasun Perera" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}"
                            placeholder="Ex: kasun@company.com" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control"
                            value="{{ old('employee_id') }}" placeholder="Ex: EMP-0042" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_no" class="form-label">Contact No <span
                                class="text-muted">(optional)</span></label>
                        <input type="text" id="contact_no" name="contact_no" class="form-control"
                            value="{{ old('contact_no') }}" placeholder="Ex: 077-1234567">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password"
                            required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirmation" class="form-label">Re-enter Password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            placeholder="Re-enter Password" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Role</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="">Select Role</option>
                            <option value="Manager">Manager</option>
                            <option value="Engineer">Engineer</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Technician">Technician</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="image_path" class="form-label">Profile Image</label>
                        <input type="file" id="image_path" name="image_path" class="form-control" accept="image/*">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="engineerField" style="display:none;">
                        <label for="engineer_id" class="form-label">Reports to (Engineer)</label>
                        <select id="engineer_id" name="engineer_id" class="form-select">
                            <option value="">Select Engineer</option>
                            @foreach ($engineers as $engineer)
                                <option value="{{ $engineer->id }}">{{ $engineer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="shiftField" style="display:none;">
    <label for="shift_id" class="form-label">Shift</label>
    <select id="shift_id" name="shift_id" class="form-select">
        <option value="">Select Shift</option>
        @foreach($shifts as $shift)
            <option value="{{ $shift->id }}">
                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('h:i A') }})
            </option>
        @endforeach
    </select>
    <div class="form-text">Used to calculate overtime automatically based on check-out time.</div>
</div>

                    <div class="col-md-6 mb-3" id="supervisorField" style="display:none;">
                        <label for="supervisor_id" class="form-label">Reports to (Supervisor)</label>
                        <select id="supervisor_id" name="supervisor_id" class="form-select">
                            <option value="">Select Supervisor</option>
                            @foreach ($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Routes: checkboxes for Engineer/Supervisor (multiple), radio for Technician (single) --}}
                <div class="row" id="routesSection" style="display:none;">
                    <div class="col-12 mb-3">
                        <label class="form-label" id="routesLabel">Routes</label>
                        <div class="form-text mb-2" id="routesHelp"></div>

                        @if ($routes->isNotEmpty())
                            <div id="routesCheckboxes" class="border rounded p-2 mb-2">
                                @foreach ($routes as $route)
                                    <div class="form-check">
                                        <input class="form-check-input route-checkbox" type="checkbox" name="routes[]"
                                            value="{{ $route->id }}" id="route-{{ $route->id }}">
                                        <label class="form-check-label" for="route-{{ $route->id }}">
                                            <strong>{{ $route->route_no }}</strong>
                                            @if ($route->description)
                                                <span class="text-muted"> — {{ $route->description }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="new_route_no" class="form-control" style="max-width: 220px;"
                                placeholder="Ex: RT-06 (only if it's new)">
                            <span class="small text-muted">Leave blank if the route already exists above</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Create Team Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
     document.getElementById('type').addEventListener('change', function() {
    const engineerField = document.getElementById('engineerField');
    const supervisorField = document.getElementById('supervisorField');
    const shiftField = document.getElementById('shiftField');
    const routesSection = document.getElementById('routesSection');
    const routesHelp = document.getElementById('routesHelp');
    const checkboxes = document.querySelectorAll('.route-checkbox');

    engineerField.style.display = 'none';
    supervisorField.style.display = 'none';
    shiftField.style.display = 'none';
    document.getElementById('engineer_id').value = '';
    document.getElementById('supervisor_id').value = '';

    checkboxes.forEach(cb => {
        cb.checked = false;
        cb.type = 'checkbox';
    });

    if (this.value === 'Supervisor') {
        engineerField.style.display = 'block';
    } else if (this.value === 'Technician') {
        supervisorField.style.display = 'block';
        shiftField.style.display = 'block';
    }

    if (this.value === 'Engineer' || this.value === 'Supervisor') {
        routesSection.style.display = 'block';
        routesHelp.textContent = 'Select one or more routes this ' + this.value.toLowerCase() + ' covers.';
    } else if (this.value === 'Technician') {
        routesSection.style.display = 'block';
        routesHelp.textContent = 'Select the single route this technician is assigned to.';
        checkboxes.forEach(cb => { cb.type = 'radio'; });
    } else {
        routesSection.style.display = 'none';
    }
});
        document.getElementById('createTeamForm').addEventListener('submit', function(e) {
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
                        form.reset();
                        document.getElementById('engineerField').style.display = 'none';
                        document.getElementById('supervisorField').style.display = 'none';
                        document.getElementById('routesSection').style.display = 'none';
                        setTimeout(() => {
                            messageBox.innerHTML = "";
                        }, 3000);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML =
                        `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection
