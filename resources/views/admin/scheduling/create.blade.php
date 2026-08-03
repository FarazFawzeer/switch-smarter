@extends('layouts.vertical', ['subtitle' => 'Schedule PPM'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Scheduling', 'subtitle' => 'Schedule PPM'])

    <div id="message"></div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">{{ $contract->project_name }}</h5>
            <p class="card-subtitle">{{ $contract->location }} — Contract No: {{ $contract->contract_number }}</p>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <span class="text-muted small">Contract Start</span>
                    <p class="mb-0">{{ $contract->contract_start_date->format('d M Y') }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Contract End</span>
                    <p class="mb-0">{{ $contract->contract_end_date->format('d M Y') }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Engineer</span>
                    <p class="mb-0">{{ optional($contract->engineer)->name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small">Route</span>
                    <p class="mb-0">{{ optional($contract->route)->route_no ?? 'Not assigned' }}</p>
                </div>
            </div>

            @if (!$contract->route)
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px;"></iconify-icon>
                    <div>This project has no route assigned. Add one from the project's Edit page first — a route is what
                        links this project to a supervisor and technician.</div>
                </div>
            @elseif($supervisors->isEmpty() && $technicians->isEmpty())
                <div class="alert alert-warning d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:danger-triangle-outline" style="font-size: 20px;"></iconify-icon>
                    <div>No supervisor or technician is assigned to route <strong>{{ $contract->route->route_no }}</strong>
                        yet. Add them in Team management, or continue and assign them later.</div>
                </div>
            @endif

            <form id="scheduleForm" action="{{ route('admin.scheduling.store', $contract->id) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Supervisor</label>
                        <select name="assigned_supervisor_id" class="form-select">
                            <option value="">Not assigned yet</option>
                            @foreach ($supervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Only supervisors covering route
                            {{ optional($contract->route)->route_no ?? '—' }} are shown.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Technician</label>
                        <select name="assigned_technician_id" class="form-select">
                            <option value="">Not assigned yet</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">This technician will be assigned to every monthly visit.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">PPM Start Date</label>
                        <input type="date" name="ppm_start_date" class="form-control"
                            min="{{ $contract->contract_start_date->copy()->addDay()->format('Y-m-d') }}"
                            max="{{ $contract->contract_end_date->format('Y-m-d') }}" required>
                        <div class="form-text">
                            Must be after the contract start date ({{ $contract->contract_start_date->format('d M Y') }}).
                            The first visit happens on this date, then repeats every month on the same day until the
                            contract ends.
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label">Elevator Units to Schedule</label>
                        <div class="form-text mb-2">All units are selected by default. Uncheck any you don't want included
                            in this schedule.</div>

                        @if ($units->isEmpty())
                            <div class="alert alert-warning py-2 px-3 mb-0">
                                This contract has no elevator units recorded yet. Add units from the contract's Edit page
                                before scheduling.
                            </div>
                        @else
                            <div class="border rounded p-2">
                                @foreach ($units as $unit)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="unit_ids[]"
                                            value="{{ $unit->id }}" id="unit-{{ $unit->id }}" checked>
                                        <label class="form-check-label" for="unit-{{ $unit->id }}">
                                            <strong>{{ $unit->identification_no }}</strong>
                                            <span class="text-muted"> —
                                                {{ $unit->unit_type }}{{ $unit->brand ? ' · ' . $unit->brand : '' }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Generate Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
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
                        }, 1200);
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
