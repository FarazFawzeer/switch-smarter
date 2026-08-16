@extends('layouts.vertical', ['subtitle' => 'New Casual Job'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Casual Jobs', 'subtitle' => 'New Casual Job'])

    <div id="message"></div>

    <form id="casualJobForm" action="{{ route('admin.job-types.casual-job.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" style="color:#9B59B6;">
                    <iconify-icon icon="solar:case-round-bold" style="margin-right:6px;"></iconify-icon>
                    Assignment
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Project Name</label>
                        <select id="contract_id" name="contract_id" class="form-select" required>
                            <option value="">Select Project</option>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->project_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Unit</label>
                        <select id="elevator_unit_id" name="elevator_unit_id" class="form-select" required disabled>
                            <option value="">Select a project first</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Assign Technician</label>
                        <select id="assigned_technician_id" name="assigned_technician_id" class="form-select" required disabled>
                            <option value="">Select a project first</option>
                        </select>
                        <div class="form-text">Technicians are scoped to the project's route.</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Customer <span class="text-muted">(optional)</span></label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Customer / requester name">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" style="color:#9B59B6;">Quotation & PO Details</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Repair Job No</label>
                        <input type="text" name="repair_job_no" class="form-control" placeholder="Ex: RJ-2026-0125" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quotation Date</label>
                        <input type="date" name="quotation_date" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quotation Value</label>
                        <input type="number" name="quotation_value" step="0.01" min="0" class="form-control" placeholder="0.00" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">PO Status</label>
                        <select id="po_status" name="po_status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Pending">Pending</option>
                            <option value="Received">Received</option>
                            <option value="Not Required">Not Required</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="poReceivedDateField" style="display:none;">
                        <label class="form-label">PO Received Date</label>
                        <input type="date" name="po_received_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3" id="poNumberField" style="display:none;">
                        <label class="form-label">PO Number</label>
                        <input type="text" name="po_number" class="form-control" placeholder="Ex: PO-88213">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Scope of Work</label>
                    <textarea name="scope_of_work" class="form-control" rows="4" placeholder="Describe the work requested by the customer..." required></textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label">Quotation Attachment <span class="text-muted">(optional — image or PDF)</span></label>
                    <input type="file" name="quotation_attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0" style="color:#9B59B6;">Status Tracking</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Material Status</label>
                        <select name="material_status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Pending">Pending</option>
                            <option value="Ordered">Ordered</option>
                            <option value="Received">Received</option>
                            <option value="Not Required">Not Required</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Site Status</label>
                        <select name="site_status" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Not Started">Not Started</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mb-4">
            <a href="{{ route('admin.job-types.casual-job') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn text-white" style="background:#9B59B6;">Create Casual Job</button>
        </div>
    </form>

    <script>
        const contractSelect = document.getElementById('contract_id');
        const unitSelect = document.getElementById('elevator_unit_id');
        const technicianSelect = document.getElementById('assigned_technician_id');
        const poStatusSelect = document.getElementById('po_status');

        contractSelect.addEventListener('change', function() {
            const contractId = this.value;

            unitSelect.innerHTML = '<option value="">Loading...</option>';
            technicianSelect.innerHTML = '<option value="">Loading...</option>';
            unitSelect.disabled = true;
            technicianSelect.disabled = true;

            if (!contractId) {
                unitSelect.innerHTML = '<option value="">Select a project first</option>';
                technicianSelect.innerHTML = '<option value="">Select a project first</option>';
                return;
            }

            fetch(`/admin/casual-job/${contractId}/units`)
                .then(r => r.json())
                .then(data => {
                    unitSelect.innerHTML = '<option value="">Select Unit</option>';
                    data.units.forEach(unit => {
                        unitSelect.innerHTML += `<option value="${unit.id}">${unit.identification_no} — ${unit.unit_type}</option>`;
                    });
                    unitSelect.disabled = false;
                });

            fetch(`/admin/casual-job/${contractId}/technicians`)
                .then(r => r.json())
                .then(data => {
                    technicianSelect.innerHTML = '<option value="">Select Technician</option>';
                    if (data.technicians.length === 0) {
                        technicianSelect.innerHTML = '<option value="">No technician on this route</option>';
                        return;
                    }
                    data.technicians.forEach(t => {
                        technicianSelect.innerHTML += `<option value="${t.id}">${t.name}</option>`;
                    });
                    technicianSelect.disabled = false;
                });
        });

        poStatusSelect.addEventListener('change', function() {
            const isReceived = this.value === 'Received';
            document.getElementById('poReceivedDateField').style.display = isReceived ? 'block' : 'none';
            document.getElementById('poNumberField').style.display = isReceived ? 'block' : 'none';
        });

        document.getElementById('casualJobForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const formData = new FormData(form);

            fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value }
                })
                .then(r => r.json())
                .then(data => {
                    const messageBox = document.getElementById('message');
                    if (data.success) {
                        messageBox.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                        setTimeout(() => window.location.href = data.redirect, 1200);
                    } else {
                        messageBox.innerHTML = `<div class="alert alert-danger">${Object.values(data.errors).flat().join('<br>')}</div>`;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                });
        });
    </script>
@endsection