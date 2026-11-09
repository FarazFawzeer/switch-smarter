@extends('layouts.vertical', ['subtitle' => 'New Material Request'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'New Material Request'])
    @include('admin.stores._styles')

    <div class="row justify-content-center">
        <div class="col-xl-8">
            <div class="card st-card">
                <div class="card-body">
                    <h5 class="mb-1">Material Request (MRN)</h5>
                    <p class="text-muted small mb-3">Sent to the Manager / Admin for approval. Once approved, the storekeeper issues the part and the cost is charged to the project.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">
                            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.stores.requests.store') }}" enctype="multipart/form-data" id="mrForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Project <span class="text-danger">*</span></label>
                                <select name="contract_id" id="contractSelect" class="form-select" required>
                                    <option value="">Select project…</option>
                                    @foreach ($contracts as $c)
                                        <option value="{{ $c->id }}" {{ old('contract_id') == $c->id ? 'selected' : '' }}>{{ $c->project_name }}{{ $c->location ? ' — ' . $c->location : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Unit <span class="text-danger">*</span></label>
                                <select name="elevator_unit_id" id="unitSelect" class="form-select" required disabled>
                                    <option value="">Choose the project first</option>
                                </select>
                                <div class="form-text" id="unitInfo"></div>
                            </div>

                            <div class="col-12 mb-3 position-relative">
                                <label class="form-label">Part / Item <span class="text-danger">*</span></label>
                                <input type="hidden" name="inventory_item_id" id="itemId" value="{{ old('inventory_item_id', $item?->id) }}">
                                <div id="pickedBox" class="st-picked d-none">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="st-name" id="pickedName"></div>
                                            <div class="st-sub" id="pickedMeta"></div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold" id="pickedStock"></div>
                                            <button type="button" class="btn btn-link btn-sm p-0" id="changePart">Change</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="searchBox">
                                    <input type="search" id="partSearch" class="form-control" placeholder="Search part name, item code, part no or brand…" autocomplete="off">
                                    <div id="partResults" class="st-part-results d-none"></div>
                                    <div class="form-text">Only items in the store list can be requested. Stock shown is what is available now.</div>
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="quantity_requested" id="qtyInput" class="form-control" min="0" step="0.01" required value="{{ old('quantity_requested') }}">
                                    <span class="input-group-text" id="qtyUnit">Nos</span>
                                </div>
                                <div class="form-text text-danger d-none" id="qtyWarn"></div>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Photo <span class="text-muted small">(optional — the damaged part)</span></label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="form-label">Description / reason <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control" rows="3" maxlength="2000" required placeholder="e.g. Door roller worn out on landing 3, door dragging">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="{{ route('admin.stores.requests.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">Send for approval</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $presetItem = $item ? [
            'id' => $item->id, 'code' => $item->item_code, 'name' => $item->part_name, 'brand' => $item->brand,
            'part_number' => $item->part_number, 'unit' => $item->unit, 'stock' => (float) $item->stock_qty,
            'stock_label' => qty($item->stock_qty) . ' ' . $item->unit, 'low' => $item->isLow(),
        ] : null;
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const unitsUrl = @json(route('admin.stores.requests.units', ['contract' => '__ID__']));
            const searchUrl = @json(route('admin.stores.inventory.search'));
            const oldUnit = @json(old('elevator_unit_id'));
            const preset = @json($presetItem);
            const esc = v => String(v ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            const contractSelect = document.getElementById('contractSelect');
            const unitSelect = document.getElementById('unitSelect');
            const unitInfo = document.getElementById('unitInfo');
            let units = [];

            function loadUnits(selected) {
                unitSelect.innerHTML = '<option value="">Loading…</option>';
                unitSelect.disabled = true;
                unitInfo.textContent = '';
                if (!contractSelect.value) { unitSelect.innerHTML = '<option value="">Choose the project first</option>'; return; }
                fetch(unitsUrl.replace('__ID__', contractSelect.value), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        units = data.units || [];
                        unitSelect.innerHTML = '<option value="">Select unit…</option>' + units.map(u =>
                            `<option value="${u.id}" ${String(u.id) === String(selected) ? 'selected' : ''}>${esc(u.identification_no)}${u.unit_number ? ' (' + esc(u.unit_number) + ')' : ''}</option>`).join('');
                        unitSelect.disabled = false;
                        showUnit();
                    })
                    .catch(() => { unitSelect.innerHTML = '<option value="">Could not load units</option>'; });
            }
            function showUnit() {
                const u = units.find(x => String(x.id) === unitSelect.value);
                unitInfo.textContent = u ? [u.unit_type, u.brand, u.model].filter(Boolean).join(' · ') : '';
            }
            contractSelect.addEventListener('change', () => loadUnits(null));
            unitSelect.addEventListener('change', showUnit);
            if (contractSelect.value) loadUnits(oldUnit);

            // ----- part search -----
            const itemId = document.getElementById('itemId');
            const search = document.getElementById('partSearch');
            const results = document.getElementById('partResults');
            const qtyInput = document.getElementById('qtyInput');
            const qtyWarn = document.getElementById('qtyWarn');
            let picked = null, timer = null;

            function pick(item) {
                picked = item;
                itemId.value = item ? item.id : '';
                document.getElementById('pickedBox').classList.toggle('d-none', !item);
                document.getElementById('searchBox').classList.toggle('d-none', !!item);
                results.classList.add('d-none');
                if (item) {
                    document.getElementById('pickedName').textContent = item.name;
                    document.getElementById('pickedMeta').textContent = [item.code, item.brand, item.part_number ? 'P/N ' + item.part_number : null].filter(Boolean).join(' · ');
                    const stock = document.getElementById('pickedStock');
                    stock.textContent = 'In stock: ' + item.stock_label;
                    stock.className = 'fw-bold ' + (item.stock <= 0 ? 'text-danger' : (item.low ? 'text-warning' : 'text-success'));
                    document.getElementById('qtyUnit').textContent = item.unit;
                }
                checkQty();
            }
            function checkQty() {
                const q = Number(qtyInput.value);
                const show = picked && q > 0 && q > picked.stock;
                qtyWarn.classList.toggle('d-none', !show);
                if (show) qtyWarn.textContent = `Only ${picked.stock_label} in stock now — you can still request; the storekeeper issues what is available.`;
            }
            function runSearch() {
                fetch(searchUrl + '?q=' + encodeURIComponent(search.value.trim()), { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(data => {
                        const items = data.items || [];
                        results.innerHTML = items.length ? items.map((it, i) => `
                            <button type="button" data-i="${i}">
                                <span><strong>${esc(it.name)}</strong><br><small class="text-muted">${esc([it.code, it.brand, it.part_number].filter(Boolean).join(' · '))}</small></span>
                                <span class="text-nowrap fw-bold ${it.stock <= 0 ? 'text-danger' : (it.low ? 'text-warning' : 'text-success')}">${esc(it.stock_label)}</span>
                            </button>`).join('') : '<div class="p-3 text-muted small">No matching item in the store list.</div>';
                        results.querySelectorAll('button[data-i]').forEach(b => b.addEventListener('click', () => pick(items[b.dataset.i])));
                        results.classList.remove('d-none');
                    });
            }
            search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(runSearch, 250); });
            search.addEventListener('focus', runSearch);
            document.addEventListener('click', e => { if (!e.target.closest('#searchBox')) results.classList.add('d-none'); });
            document.getElementById('changePart').addEventListener('click', () => { pick(null); search.focus(); });
            qtyInput.addEventListener('input', checkQty);
            if (preset) pick(preset);

            document.getElementById('mrForm').addEventListener('submit', function(e) {
                if (!itemId.value) { e.preventDefault(); Swal.fire('Choose a part', 'Search and pick the part from the store list.', 'warning'); return; }
                document.getElementById('submitBtn').disabled = true;
            });
        });
    </script>
@endsection
