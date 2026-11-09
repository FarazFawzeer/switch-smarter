@extends('layouts.vertical', ['subtitle' => $mr->reference()])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'Material Request'])
    @include('admin.stores._styles')

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    @php
        $item = $mr->item;
        $stock = (float) optional($item)->stock_qty;
        $maxIssue = min((float) $mr->quantity_requested, $stock);
    @endphp

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card st-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div class="st-sub">Material Request</div>
                            <h4 class="mb-0">{{ $mr->reference() }}</h4>
                            @unless ($mr->mrn_no)<div class="st-sub">MRN number is given on approval</div>@endunless
                        </div>
                        <span class="st-pill" style="background:{{ $mr->statusColor() }}; font-size:12px;">{{ $mr->statusLabel() }}</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6"><div class="st-field-label">Project</div><div class="st-field-value">{{ optional($mr->contract)->project_name ?? '—' }}<div class="st-sub fw-normal">{{ optional($mr->contract)->location }}</div></div></div>
                        <div class="col-md-6"><div class="st-field-label">Unit</div><div class="st-field-value">
                            {{ optional($mr->unit)->identification_no ?? '—' }}{{ optional($mr->unit)->unit_number ? ' (' . $mr->unit->unit_number . ')' : '' }}
                            <div class="st-sub fw-normal">{{ collect([optional($mr->unit)->unit_type, optional($mr->unit)->brand, optional($mr->unit)->model])->filter()->implode(' · ') }}</div>
                        </div></div>
                        <div class="col-md-6"><div class="st-field-label">Part</div><div class="st-field-value">
                            @if ($item)
                                <a href="{{ route('admin.stores.inventory.show', $item) }}" class="text-decoration-none">{{ $item->part_name }}</a>
                                <div class="st-sub fw-normal">{{ collect([$item->item_code, $item->brand, $item->part_number ? 'P/N ' . $item->part_number : null])->filter()->implode(' · ') }}</div>
                            @else — @endif
                        </div></div>
                        <div class="col-md-3"><div class="st-field-label">Requested</div><div class="st-field-value">{{ qty($mr->quantity_requested) }} {{ optional($item)->unit }}</div></div>
                        <div class="col-md-3"><div class="st-field-label">In stock now</div><div class="st-field-value {{ $stock < (float) $mr->quantity_requested && in_array($mr->status, ['pending', 'approved']) ? 'text-danger' : '' }}">{{ qty($stock) }} {{ optional($item)->unit }}</div></div>
                        @if ($mr->status === 'issued')
                            <div class="col-md-3"><div class="st-field-label">Issued</div><div class="st-field-value">{{ qty($mr->quantity_issued) }} {{ optional($item)->unit }}</div></div>
                            <div class="col-md-3"><div class="st-field-label">Unit cost</div><div class="st-field-value">{{ money($mr->unit_cost) }}</div></div>
                            <div class="col-md-6"><div class="st-field-label">Charged to project</div><div class="st-field-value" style="color:#2E9E5B;">{{ money($mr->total_cost) }}</div></div>
                        @endif
                        <div class="col-12"><div class="st-field-label">Description</div><div class="st-field-value fw-normal" style="white-space: pre-line;">{{ $mr->description }}</div></div>
                        @if ($mr->photo_path)
                            <div class="col-md-6">
                                <div class="st-field-label">Photo</div>
                                <a href="{{ storage_asset($mr->photo_path) }}" target="_blank" rel="noopener"><img src="{{ storage_asset($mr->photo_path) }}" class="img-fluid rounded border" style="max-height: 260px;" alt="Part photo"></a>
                            </div>
                        @endif
                    </div>

                    {{-- actions --}}
                    @if ($canApprove)
                        <hr>
                        <h6>Approval</h6>
                        @if ($stock < (float) $mr->quantity_requested)
                            <div class="alert alert-warning py-2 small">Only {{ qty($stock) }} {{ optional($item)->unit }} in stock — the storekeeper can issue up to that, or wait for new stock.</div>
                        @endif
                        <textarea id="approvalRemarks" class="form-control mb-2" rows="2" maxlength="500" placeholder="Remarks (required to reject)"></textarea>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success" id="approveBtn"><x-ui-icon name="check" size="15" stroke="2.4" /> Approve</button>
                            <button type="button" class="btn btn-outline-danger" id="rejectBtn"><x-ui-icon name="x" size="15" stroke="2.4" /> Reject</button>
                        </div>
                    @endif

                    @if ($canIssue)
                        <hr>
                        <h6>Issue from store</h6>
                        @if ($stock <= 0)
                            <div class="alert alert-danger py-2 small mb-0">Out of stock — receive stock for this item first, then issue.</div>
                        @else
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Quantity handed over ({{ optional($item)->unit }})</label>
                                    <input type="number" id="issueQty" class="form-control" min="0" step="0.01" max="{{ $maxIssue }}" value="{{ qty($maxIssue) }}">
                                    <div class="form-text">Max {{ qty($maxIssue) }} (requested {{ qty($mr->quantity_requested) }}, in stock {{ qty($stock) }})</div>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label small mb-1">Remarks</label>
                                    <input type="text" id="issueRemarks" class="form-control" maxlength="500" placeholder="e.g. Handed to Kavinda (technician) at store">
                                    <div class="form-text">&nbsp;</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-primary mt-1" id="issueBtn"><x-ui-icon name="package" size="15" /> Issue &amp; update stock</button>
                        @endif
                    @endif

                    @if ($canCancel)
                        <hr>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="cancelBtn">Cancel my request</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card st-card">
                <div class="card-body">
                    <h6 class="mb-3">What happened</h6>
                    <ul class="st-timeline">
                        <li>
                            <span class="dot" style="--c:#2E6BD8;"><x-ui-icon name="file-text" size="11" stroke="2.4" /></span>
                            <div class="t">Requested</div>
                            <div class="m">{{ optional($mr->requester)->name }} ({{ optional($mr->requester)->type }})</div>
                            <div class="m">{{ $mr->created_at->format('d M Y, h:i A') }}</div>
                        </li>

                        @if (in_array($mr->status, ['approved', 'issued']))
                            <li>
                                <span class="dot" style="--c:#2E9E5B;"><x-ui-icon name="check" size="11" stroke="3" /></span>
                                <div class="t">Approved — {{ $mr->mrn_no }}</div>
                                <div class="m">{{ optional($mr->approver)->name }} ({{ optional($mr->approver)->type }})</div>
                                <div class="m">{{ $mr->approved_at?->format('d M Y, h:i A') }}</div>
                                @if ($mr->approval_remarks)<div class="m fst-italic">“{{ $mr->approval_remarks }}”</div>@endif
                            </li>
                        @elseif ($mr->status === 'rejected')
                            <li>
                                <span class="dot" style="--c:#D64545;"><x-ui-icon name="x" size="11" stroke="3" /></span>
                                <div class="t">Rejected</div>
                                <div class="m">{{ optional($mr->approver)->name }} ({{ optional($mr->approver)->type }})</div>
                                <div class="m">{{ $mr->approved_at?->format('d M Y, h:i A') }}</div>
                                @if ($mr->approval_remarks)<div class="m fst-italic">“{{ $mr->approval_remarks }}”</div>@endif
                            </li>
                        @elseif ($mr->status === 'cancelled')
                            <li>
                                <span class="dot" style="--c:#8792a2;"><x-ui-icon name="x" size="11" stroke="3" /></span>
                                <div class="t">Cancelled by the requester</div>
                                <div class="m">{{ $mr->updated_at->format('d M Y, h:i A') }}</div>
                            </li>
                        @else
                            <li><span class="dot todo"></span><div class="t text-muted">Waiting for Manager / Admin approval</div></li>
                        @endif

                        @if ($mr->status === 'issued')
                            <li>
                                <span class="dot" style="--c:#7A6FD0;"><x-ui-icon name="package" size="11" stroke="2.4" /></span>
                                <div class="t">Issued from store</div>
                                <div class="m">{{ qty($mr->quantity_issued) }} {{ optional($item)->unit }} · {{ money($mr->total_cost) }}</div>
                                <div class="m">{{ optional($mr->issuer)->name }} ({{ optional($mr->issuer)->type }})</div>
                                <div class="m">{{ $mr->issued_at?->format('d M Y, h:i A') }}</div>
                                @foreach ($mr->transactions as $tx)
                                    <div class="m">Stock {{ qty((float) $tx->balance_after - (float) $tx->quantity) }} → {{ qty($tx->balance_after) }}</div>
                                @endforeach
                                @if ($mr->issue_remarks)<div class="m fst-italic">“{{ $mr->issue_remarks }}”</div>@endif
                            </li>
                        @elseif ($mr->status === 'approved')
                            <li><span class="dot todo"></span><div class="t text-muted">Waiting for the storekeeper to issue</div></li>
                        @endif
                    </ul>
                    <a href="{{ route('admin.stores.requests.index') }}" class="btn btn-link w-100">← All material requests</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const post = (url, payload) => fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify(payload || {})
            }).then(async r => {
                const data = await r.json().catch(() => ({}));
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                return { ok: r.ok && data.success, message: first || data.message || 'Could not save — please try again.' };
            }).catch(() => ({ ok: false, message: 'No connection — please try again.' }));

            const act = (btn, url, payload, confirmText) => {
                Swal.fire({ title: confirmText, icon: 'question', showCancelButton: true, confirmButtonText: 'Yes' }).then(r => {
                    if (!r.isConfirmed) return;
                    btn.disabled = true;
                    post(url, payload).then(res => {
                        Swal.fire(res.ok ? 'Done' : 'Not saved', res.message, res.ok ? 'success' : 'error').then(() => res.ok ? location.reload() : (btn.disabled = false));
                    });
                });
            };

            const remarks = () => document.getElementById('approvalRemarks')?.value.trim() || '';
            document.getElementById('approveBtn')?.addEventListener('click', function() {
                act(this, @json(route('admin.stores.requests.approve', $mr)), { remarks: remarks() }, 'Approve this request?');
            });
            document.getElementById('rejectBtn')?.addEventListener('click', function() {
                if (!remarks()) { Swal.fire('Reason needed', 'Write the reason for rejecting in the remarks box.', 'warning'); return; }
                act(this, @json(route('admin.stores.requests.reject', $mr)), { remarks: remarks() }, 'Reject this request?');
            });
            document.getElementById('issueBtn')?.addEventListener('click', function() {
                const q = document.getElementById('issueQty').value;
                if (!q || Number(q) <= 0) { Swal.fire('Quantity needed', 'Enter the quantity handed over.', 'warning'); return; }
                act(this, @json(route('admin.stores.requests.issue', $mr)), { quantity: q, remarks: document.getElementById('issueRemarks').value.trim() || null },
                    `Issue ${q} {{ optional($item)->unit }} and deduct it from stock?`);
            });
            document.getElementById('cancelBtn')?.addEventListener('click', function() {
                act(this, @json(route('admin.stores.requests.cancel', $mr)), {}, 'Cancel this request?');
            });
        });
    </script>
@endsection
