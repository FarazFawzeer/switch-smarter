@extends('layouts.vertical', ['subtitle' => 'Job Edit'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Jobs', 'subtitle' => 'Edit'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Edit Job</h5>
        </div>

        <div class="card-body">
            <div id="message"></div>

            <form id="editJobForm" action="{{ route('admin.jobs.update', $job->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="job_type" class="form-label">Job Type</label>
                        <select id="job_type" name="job_type" class="form-select" required>
                            <option value="ppm" {{ $job->job_type == 'ppm' ? 'selected' : '' }}>PPM (Scheduled Maintenance)</option>
                            <option value="repair" {{ $job->job_type == 'repair' ? 'selected' : '' }}>Repair</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="site_id" class="form-label">Site</label>
                        <select id="site_id" name="site_id" class="form-select" required>
                            @foreach($sites as $site)
                                <option value="{{ $site->id }}" {{ $job->site_id == $site->id ? 'selected' : '' }}>
                                    {{ $site->site_name }} ({{ optional($site->contract)->project_name ?? 'No contract' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="scheduled_date" class="form-label">Scheduled Date</label>
                        <input type="date" id="scheduled_date" name="scheduled_date" class="form-control"
                            value="{{ $job->scheduled_date?->format('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="assigned_technician_id" class="form-label">Assigned Technician</label>
                        <select id="assigned_technician_id" name="assigned_technician_id" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach($technicians as $technician)
                                <option value="{{ $technician->id }}" {{ $job->assigned_technician_id == $technician->id ? 'selected' : '' }}>
                                    {{ $technician->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="priorityField" style="{{ $job->job_type === 'repair' ? '' : 'display:none;' }}">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select">
                            @foreach(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $value => $label)
                                <option value="{{ $value }}" {{ $job->priority == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select" required>
                            @foreach(['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $value => $label)
                                <option value="{{ $value }}" {{ $job->status == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description / Notes</label>
                        <textarea id="description" name="description" class="form-control" rows="3">{{ $job->description }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.jobs.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Job</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('job_type').addEventListener('change', function() {
            const priorityField = document.getElementById('priorityField');
            priorityField.style.display = this.value === 'repair' ? 'block' : 'none';
        });

        document.getElementById('editJobForm').addEventListener('submit', function(e) {
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
                            window.location.href = "{{ route('admin.jobs.index') }}";
                        }, 1200);
                    } else {
                        let errors = Object.values(data.errors).flat().join('<br>');
                        messageBox.innerHTML = `<div class="alert alert-danger">${errors}</div>`;
                    }
                })
                .catch(error => {
                    document.getElementById('message').innerHTML = `<div class="alert alert-danger">Error: ${error}</div>`;
                });
        });
    </script>
@endsection