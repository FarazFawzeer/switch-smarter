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

            @if ($scheduledUnits->count() > 0)
                <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
                    <iconify-icon icon="solar:check-circle-outline" style="font-size: 20px;"></iconify-icon>
                    <div>
                        {{ $scheduledUnits->count() }} unit(s) already scheduled:
                        <strong>{{ $scheduledUnits->pluck('identification_no')->join(', ') }}</strong>
                        — <a href="{{ route('admin.scheduling.show', $contract->id) }}">View existing schedule</a>
                    </div>
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
                        <label class="form-label">Technician <span class="text-danger">*</span></label>
                        <select name="assigned_technician_id" class="form-select" required>
                            <option value="">Select technician</option>
                            @foreach ($technicians as $technician)
                                <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Required — this technician will be assigned to every monthly visit for the
                            selected units.</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">PPM Start Date <span class="text-danger">*</span></label>
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

                        @if ($units->isEmpty())
                            <div class="alert alert-success py-2 px-3 mb-0">
                                <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
                                All units on this contract have already been scheduled.
                                <a href="{{ route('admin.scheduling.show', $contract->id) }}">View schedule</a>
                            </div>
                        @else
                            <div class="form-text mb-2">All unscheduled units are selected by default. Search to narrow the
                                list, or use Select All / Deselect All below.</div>

                            <div class="row g-2 mb-2 align-items-center">
                                <div class="col-md-6">
                                    <input type="text" id="unitSearchBox" class="form-control form-control-sm"
                                        placeholder="Search by unit ID (e.g. LP-A-01)...">
                                </div>
                                <div class="col-md-6 d-flex align-items-center gap-2 justify-content-md-end">
                                    <button type="button" id="selectAllUnitsBtn"
                                        class="btn btn-sm btn-outline-primary">Select All</button>
                                    <button type="button" id="deselectAllUnitsBtn"
                                        class="btn btn-sm btn-outline-secondary">Deselect All</button>
                                    <span class="text-muted small text-nowrap" id="unitSelectedCount">{{ $units->count() }}
                                        of {{ $units->count() }} selected</span>
                                </div>
                            </div>

                            <div class="border rounded p-2" style="max-height: 340px; overflow-y: auto;">
                                @foreach ($units as $unit)
                                    <div class="form-check unit-checkbox-row"
                                        data-unit-search="{{ strtolower($unit->identification_no) }}">
                                        <input class="form-check-input unit-checkbox" type="checkbox" name="unit_ids[]"
                                            value="{{ $unit->id }}" id="unit-{{ $unit->id }}" checked>
                                        <label class="form-check-label" for="unit-{{ $unit->id }}">
                                            <strong>{{ $unit->identification_no }}</strong>
                                            <span class="text-muted"> —
                                                {{ $unit->unit_type }}{{ $unit->brand ? ' · ' . $unit->brand : '' }}</span>
                                        </label>
                                    </div>
                                @endforeach
                                <p id="noUnitsMatchSearch" class="text-center text-muted py-2 mb-0" style="display:none;">No
                                    units match this search.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('admin.scheduling.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" {{ $units->isEmpty() ? 'disabled' : '' }}>Generate
                        Schedule</button>
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
    </script>

    <script>
        // ===== Unit checklist: search, select all / deselect all, live count =====
        const unitSearchBox = document.getElementById('unitSearchBox');
        const unitCheckboxRows = document.querySelectorAll('.unit-checkbox-row');
        const unitCheckboxes = document.querySelectorAll('.unit-checkbox');
        const noUnitsMatchSearch = document.getElementById('noUnitsMatchSearch');
        const unitSelectedCount = document.getElementById('unitSelectedCount');
        const selectAllUnitsBtn = document.getElementById('selectAllUnitsBtn');
        const deselectAllUnitsBtn = document.getElementById('deselectAllUnitsBtn');

        function updateSelectedCount() {
            const total = unitCheckboxes.length;
            const selected = document.querySelectorAll('.unit-checkbox:checked').length;
            unitSelectedCount.textContent = `${selected} of ${total} selected`;
        }

        function filterUnitChecklist() {
            const search = unitSearchBox.value.toLowerCase().trim();
            let visibleCount = 0;

            unitCheckboxRows.forEach(row => {
                const matches = row.dataset.unitSearch.includes(search);
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            noUnitsMatchSearch.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        if (unitSearchBox) {
            unitSearchBox.addEventListener('input', filterUnitChecklist);

            unitCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            // "Select All" only affects units currently visible (i.e. matching the search)
            selectAllUnitsBtn.addEventListener('click', function() {
                unitCheckboxRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        row.querySelector('.unit-checkbox').checked = true;
                    }
                });
                updateSelectedCount();
            });

            // "Deselect All" also only affects currently visible units
            deselectAllUnitsBtn.addEventListener('click', function() {
                unitCheckboxRows.forEach(row => {
                    if (row.style.display !== 'none') {
                        row.querySelector('.unit-checkbox').checked = false;
                    }
                });
                updateSelectedCount();
            });

            updateSelectedCount();
        }
    </script>
@endsection
