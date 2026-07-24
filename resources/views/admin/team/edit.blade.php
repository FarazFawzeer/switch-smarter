@extends('layouts.vertical', ['subtitle' => 'Edit Team Member'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Team', 'subtitle' => 'Edit'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Edit {{ $team->name }}</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="editTeamForm" action="{{ route('admin.team.update', $team->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $team->name }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ $team->email }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="{{ $team->employee_id }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_no" class="form-label">Contact No <span class="text-muted">(optional)</span></label>
                        <input type="text" id="contact_no" name="contact_no" class="form-control" value="{{ $team->contact_no }}">
                    </div>
                </div>

             
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Role</label>
                        <select id="type" name="type" class="form-select" required>
                            <option value="Manager" {{ $team->type == 'Manager' ? 'selected' : '' }}>Manager</option>
                            <option value="Engineer" {{ $team->type == 'Engineer' ? 'selected' : '' }}>Engineer</option>
                            <option value="Supervisor" {{ $team->type == 'Supervisor' ? 'selected' : '' }}>Supervisor</option>
                            <option value="Technician" {{ $team->type == 'Technician' ? 'selected' : '' }}>Technician</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="image_path" class="form-label">Profile Image <span class="text-muted">(leave blank to keep current)</span></label>
                        <input type="file" id="image_path" name="image_path" class="form-control" accept="image/*">
                        @if($team->image_path)
                            <div class="form-text">Current: <a href="{{ storage_asset($team->image_path) }}" target="_blank">View photo</a></div>
                        @endif
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3" id="engineerField" style="display: {{ $team->type === 'Supervisor' ? 'block' : 'none' }};">
                        <label for="engineer_id" class="form-label">Reports to (Engineer)</label>
                        <select id="engineer_id" name="engineer_id" class="form-select">
                            <option value="">Select Engineer</option>
                            @foreach($engineers as $engineer)
                                <option value="{{ $engineer->id }}" {{ $team->engineer_id == $engineer->id ? 'selected' : '' }}>{{ $engineer->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3" id="supervisorField" style="display: {{ $team->type === 'Technician' ? 'block' : 'none' }};">
                        <label for="supervisor_id" class="form-label">Reports to (Supervisor)</label>
                        <select id="supervisor_id" name="supervisor_id" class="form-select">
                            <option value="">Select Supervisor</option>
                            @foreach($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}" {{ $team->supervisor_id == $supervisor->id ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Routes: checkboxes for Engineer/Supervisor, radio for Technician --}}
                @php $teamRouteIds = $team->routes->pluck('id')->toArray(); @endphp
                <div class="row" id="routesSection" style="display: {{ in_array($team->type, ['Engineer','Supervisor','Technician']) ? 'block' : 'none' }};">
                    <div class="col-12 mb-3">
                        <label class="form-label">Routes</label>
                        <div class="form-text mb-2" id="routesHelp">
                            {{ $team->type === 'Technician' ? 'Select the single route this technician is assigned to.' : 'Select one or more routes this ' . strtolower($team->type) . ' covers.' }}
                        </div>

                        @if($routes->isNotEmpty())
                            <div id="routesCheckboxes" class="border rounded p-2 mb-2">
                                @foreach($routes as $route)
                                    <div class="form-check">
                                        <input class="form-check-input route-checkbox"
                                            type="{{ $team->type === 'Technician' ? 'radio' : 'checkbox' }}"
                                            name="routes[]" value="{{ $route->id }}" id="route-{{ $route->id }}"
                                            {{ in_array($route->id, $teamRouteIds) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="route-{{ $route->id }}">
                                            <strong>{{ $route->route_no }}</strong>
                                            @if($route->description)
                                                <span class="text-muted"> — {{ $route->description }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="new_route_no" class="form-control" style="max-width: 220px;" placeholder="Ex: RT-06 (only if it's new)">
                            <span class="small text-muted">Leave blank if the route already exists above</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.team.show', $team->id) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Team Member</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('type').addEventListener('change', function() {
            const engineerField = document.getElementById('engineerField');
            const supervisorField = document.getElementById('supervisorField');
            const routesSection = document.getElementById('routesSection');
            const routesHelp = document.getElementById('routesHelp');
            const checkboxes = document.querySelectorAll('.route-checkbox');

            engineerField.style.display = 'none';
            supervisorField.style.display = 'none';

            if (this.value === 'Supervisor') {
                engineerField.style.display = 'block';
            } else if (this.value === 'Technician') {
                supervisorField.style.display = 'block';
            }

            if (this.value === 'Engineer' || this.value === 'Supervisor') {
                routesSection.style.display = 'block';
                routesHelp.textContent = 'Select one or more routes this ' + this.value.toLowerCase() + ' covers.';
                checkboxes.forEach(cb => cb.type = 'checkbox');
            } else if (this.value === 'Technician') {
                routesSection.style.display = 'block';
                routesHelp.textContent = 'Select the single route this technician is assigned to.';
                checkboxes.forEach(cb => cb.type = 'radio');
            } else {
                routesSection.style.display = 'none';
            }
        });

        document.getElementById('editTeamForm').addEventListener('submit', function(e) {
            e.preventDefault();
            let form = this;
            let formData = new FormData(form);
            formData.append('_method', 'PUT');

            fetch(form.action, {
                    method: "POST",
                    body: formData,
                    headers: { "X-CSRF-TOKEN": document.querySelector('input[name="_token"]').value }
                })
                .then(response => response.json())
                .then(data => {
                    let messageBox = document.getElementById('message');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => { window.location.href = data.redirect; }, 1200);
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