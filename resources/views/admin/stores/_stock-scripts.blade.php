{{-- Receive stock / stock correction dialogs (buttons: .receive-btn, .adjust-btn) --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const esc = v => String(v ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
        const post = (url, payload) => fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        }).then(async r => {
            const data = await r.json().catch(() => ({}));
            if (!r.ok || !data.success) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                throw new Error(first || data.message || 'Could not save — please try again.');
            }
            return data;
        });

        document.querySelectorAll('.receive-btn').forEach(btn => btn.addEventListener('click', function() {
            const d = this.dataset;
            Swal.fire({
                title: 'Receive stock',
                html: `<p class="small text-muted mb-3">${esc(d.name)}</p>
                    <div class="text-start">
                        <label class="form-label small mb-1">Quantity received (${esc(d.unit)})</label>
                        <input type="number" id="rcQty" class="form-control mb-2" min="0" step="0.01">
                        <label class="form-label small mb-1">Unit cost <span class="text-muted">(leave as is if unchanged)</span></label>
                        <input type="number" id="rcCost" class="form-control mb-2" min="0" step="0.01" value="${esc(d.cost)}">
                        <label class="form-label small mb-1">Reference <span class="text-muted">(GRN / PO / invoice no.)</span></label>
                        <input type="text" id="rcRef" class="form-control mb-2" maxlength="100">
                        <label class="form-label small mb-1">Note</label>
                        <input type="text" id="rcNote" class="form-control" maxlength="500">
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Add to stock',
                confirmButtonColor: '#2E9E5B',
                preConfirm: () => {
                    const quantity = document.getElementById('rcQty').value;
                    if (!quantity || Number(quantity) <= 0) { Swal.showValidationMessage('Enter the quantity received.'); return false; }
                    return post(d.url, {
                        quantity,
                        unit_cost: document.getElementById('rcCost').value || null,
                        reference: document.getElementById('rcRef').value.trim() || null,
                        note: document.getElementById('rcNote').value.trim() || null,
                    }).catch(e => { Swal.showValidationMessage(e.message); return false; });
                }
            }).then(r => { if (r.isConfirmed && r.value) Swal.fire('Stock updated', r.value.message, 'success').then(() => location.reload()); });
        }));

        document.querySelectorAll('.adjust-btn').forEach(btn => btn.addEventListener('click', function() {
            const d = this.dataset;
            Swal.fire({
                title: 'Correct stock count',
                html: `<p class="small text-muted mb-3">${esc(d.name)} — system stock <strong>${esc(d.stock)} ${esc(d.unit)}</strong></p>
                    <div class="text-start">
                        <label class="form-label small mb-1">Actual quantity in the store (${esc(d.unit)})</label>
                        <input type="number" id="adjQty" class="form-control mb-2" min="0" step="0.01" value="${esc(d.stock)}">
                        <label class="form-label small mb-1">Reason</label>
                        <input type="text" id="adjNote" class="form-control" maxlength="500" placeholder="e.g. Stock count 09 Nov — 1 damaged">
                    </div>`,
                showCancelButton: true,
                confirmButtonText: 'Save correction',
                preConfirm: () => {
                    const new_balance = document.getElementById('adjQty').value;
                    const note = document.getElementById('adjNote').value.trim();
                    if (new_balance === '' || Number(new_balance) < 0) { Swal.showValidationMessage('Enter the counted quantity.'); return false; }
                    if (!note) { Swal.showValidationMessage('Please give the reason.'); return false; }
                    return post(d.url, { new_balance, note }).catch(e => { Swal.showValidationMessage(e.message); return false; });
                }
            }).then(r => { if (r.isConfirmed && r.value) Swal.fire('Saved', r.value.message, 'success').then(() => location.reload()); });
        }));
    });
</script>
