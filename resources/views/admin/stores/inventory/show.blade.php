@extends('layouts.vertical', ['subtitle' => $item->part_name])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'Item Detail'])
    @include('admin.stores._styles')

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    @php $out = (float) $item->stock_qty <= 0; @endphp

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card st-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="st-sub">{{ $item->item_code }}</div>
                            <h5 class="mb-0">{{ $item->part_name }}</h5>
                        </div>
                        @unless ($item->is_active)<span class="st-soft" style="--c:#8792a2;">Inactive</span>@endunless
                    </div>

                    <div class="st-stat my-3" style="--c:{{ $out ? '#D64545' : ($item->isLow() ? '#E08A00' : '#2E9E5B') }};">
                        <div class="ic"><x-ui-icon name="package" size="20" /></div>
                        <div>
                            <div class="lbl">In stock {{ $out ? '— out of stock' : ($item->isLow() ? '— low' : '') }}</div>
                            <div class="num">{{ qty($item->stock_qty) }} {{ $item->unit }}</div>
                            <div class="st-sub">Value {{ money($item->stockValue()) }}</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6"><div class="st-field-label">Category</div><div class="st-field-value">{{ $item->category ?? '—' }}</div></div>
                        <div class="col-6"><div class="st-field-label">Brand</div><div class="st-field-value">{{ $item->brand ?? '—' }}</div></div>
                        <div class="col-6"><div class="st-field-label">Part No</div><div class="st-field-value">{{ $item->part_number ?? '—' }}</div></div>
                        <div class="col-6"><div class="st-field-label">Unit Cost</div><div class="st-field-value">{{ money($item->unit_cost) }}</div></div>
                        <div class="col-6"><div class="st-field-label">Minimum Stock</div><div class="st-field-value">{{ $item->min_stock > 0 ? qty($item->min_stock) . ' ' . $item->unit : '—' }}</div></div>
                        <div class="col-6"><div class="st-field-label">Location</div><div class="st-field-value">{{ $item->store_location ?? '—' }}</div></div>
                        <div class="col-6"><div class="st-field-label">Total Issued</div><div class="st-field-value">{{ qty($issuedTotal) }} {{ $item->unit }}</div></div>
                        @if ($item->remarks)
                            <div class="col-12"><div class="st-field-label">Remarks</div><div class="st-field-value fw-normal">{{ $item->remarks }}</div></div>
                        @endif
                    </div>

                    @if ($canManage)
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-success receive-btn" data-url="{{ route('admin.stores.inventory.receive', $item) }}"
                                data-name="{{ $item->part_name }}" data-unit="{{ $item->unit }}" data-cost="{{ $item->unit_cost }}">+ Receive stock</button>
                            <button type="button" class="btn btn-outline-warning adjust-btn" data-url="{{ route('admin.stores.inventory.adjust', $item) }}"
                                data-name="{{ $item->part_name }}" data-unit="{{ $item->unit }}" data-stock="{{ qty($item->stock_qty) }}">Correct stock count</button>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.stores.inventory.edit', $item) }}" class="btn btn-outline-primary flex-fill">Edit</a>
                                <button type="button" class="btn btn-outline-danger flex-fill" id="deleteItemBtn">Delete</button>
                            </div>
                        </div>
                    @endif
                    <a href="{{ route('admin.stores.inventory.index') }}" class="btn btn-link w-100 mt-2">← Back to inventory</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card st-card mb-3">
                <div class="card-body">
                    <h6 class="mb-3">Stock history</h6>
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead>
                                <tr><th>Date</th><th>Movement</th><th class="text-end">Qty</th><th class="text-end">Balance</th><th>Reference / Project</th><th>By</th><th>Note</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($transactions as $tx)
                                    @php $c = ['receive' => '#2E9E5B', 'issue' => '#D64545', 'adjust' => '#E08A00'][$tx->type] ?? '#8792a2'; @endphp
                                    <tr>
                                        <td class="text-nowrap">{{ $tx->created_at->format('d M Y, h:i A') }}</td>
                                        <td><span class="st-soft" style="--c:{{ $c }};">{{ \App\Models\StockTransaction::TYPES[$tx->type] ?? $tx->type }}</span></td>
                                        <td class="text-end text-nowrap {{ $tx->quantity >= 0 ? 'st-qty-in' : 'st-qty-out' }}">{{ $tx->quantity > 0 ? '+' : '' }}{{ qty($tx->quantity) }}</td>
                                        <td class="text-end fw-semibold">{{ qty($tx->balance_after) }}</td>
                                        <td>
                                            @if ($tx->materialRequest)
                                                <a href="{{ route('admin.stores.requests.show', $tx->material_request_id) }}">{{ $tx->materialRequest->mrn_no }}</a>
                                                <div class="st-sub">{{ optional($tx->contract)->project_name }}</div>
                                            @else
                                                {{ $tx->reference ?? '—' }}
                                            @endif
                                        </td>
                                        <td>{{ optional($tx->user)->name ?? '—' }}</td>
                                        <td class="small">{{ $tx->note }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-4">No stock movements yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-2">{{ $transactions->links() }}</div>
                </div>
            </div>

            <div class="card st-card">
                <div class="card-body">
                    <h6 class="mb-3">Recent material requests for this part</h6>
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead><tr><th>Ref</th><th>Project</th><th>Requested by</th><th class="text-end">Qty</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($requests as $r)
                                    <tr>
                                        <td><a href="{{ route('admin.stores.requests.show', $r) }}">{{ $r->reference() }}</a></td>
                                        <td>{{ optional($r->contract)->project_name ?? '—' }}</td>
                                        <td>{{ optional($r->requester)->name ?? '—' }}</td>
                                        <td class="text-end">{{ qty($r->quantity_issued ?? $r->quantity_requested) }}</td>
                                        <td><span class="st-pill" style="background:{{ $r->statusColor() }};">{{ $r->statusLabel() }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No requests yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.stores._stock-scripts')
    @if ($canManage)
        <script>
            document.getElementById('deleteItemBtn')?.addEventListener('click', function() {
                Swal.fire({
                    title: @json('Delete ' . $item->part_name . '?'),
                    text: 'An item with stock history is deactivated instead, so its records stay.',
                    icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete'
                }).then(r => {
                    if (!r.isConfirmed) return;
                    fetch('{{ route('admin.stores.inventory.destroy', $item) }}', {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    }).then(res => res.json()).then(data => {
                        Swal.fire(data.success ? 'Done' : 'Not deleted', data.message, data.success ? 'success' : 'error')
                            .then(() => { if (data.success) location.href = '{{ route('admin.stores.inventory.index') }}'; });
                    });
                });
            });
        </script>
    @endif
@endsection
