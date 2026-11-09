{{-- Manual Timesheet Adjustment modal — used by Timesheets and Timesheet Approval.
     Buttons need class .adjust-time-btn with data-url, data-in, data-out, data-who.
     Also the office "Mark as Completed" modal: buttons with class .mark-complete-btn and
     data-url, data-who, data-start (display text), data-in (datetime-local), data-needs-unit (1/0). --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
            // ===== Manual Timesheet Adjustment =====
            document.querySelectorAll('.adjust-time-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const url = this.dataset.url;
                    Swal.fire({
                        title: 'Adjust timesheet',
                        html: `<p class="small text-muted mb-3">${this.dataset.who.replace(/</g, '&lt;')}</p>
                            <div class="text-start">
                                <label class="form-label small mb-1">Check-in</label>
                                <input type="datetime-local" id="adjIn" class="form-control mb-2" value="${this.dataset.in}">
                                <label class="form-label small mb-1">Check-out <span class="text-muted">(fill in if the technician forgot to log out — an unfinished job becomes Completed)</span></label>
                                <input type="datetime-local" id="adjOut" class="form-control mb-2" value="${this.dataset.out || ''}">
                                <label class="form-label small mb-1">Reason</label>
                                <input type="text" id="adjReason" class="form-control" maxlength="255" placeholder="e.g. Forgot to log out at 5:15 PM">
                            </div>`,
                        showCancelButton: true,
                        confirmButtonText: 'Save correction',
                        preConfirm: () => {
                            const payload = {
                                check_in_time: document.getElementById('adjIn').value,
                                check_out_time: document.getElementById('adjOut').value || null,
                                reason: document.getElementById('adjReason').value.trim(),
                            };
                            if (!payload.check_in_time) { Swal.showValidationMessage('Check-in time is required.'); return false; }
                            if (!payload.reason) { Swal.showValidationMessage('Please give a reason.'); return false; }
                            return fetch(url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify(payload)
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (!data.success) { Swal.showValidationMessage(data.message); return false; }
                                    return data;
                                });
                        }
                    }).then(result => {
                        if (result.isConfirmed && result.value) {
                            Swal.fire('Saved', result.value.message, 'success').then(() => location.reload());
                        }
                    });
                });
            });

            // ===== Office "Mark as Completed" =====
            const pad = n => String(n).padStart(2, '0');
            const nowLocal = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`; };
            const esc = v => String(v || '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

            document.querySelectorAll('.mark-complete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const url = this.dataset.url;
                    const needsUnit = this.dataset.needsUnit === '1';
                    Swal.fire({
                        title: 'Mark as completed',
                        html: `<p class="small text-muted mb-1">${esc(this.dataset.who)}</p>
                            <p class="small mb-3">Started: <strong>${esc(this.dataset.start)}</strong></p>
                            <div class="text-start">
                                <label class="form-label small mb-1">Work finished at</label>
                                <input type="datetime-local" id="mcEnd" class="form-control mb-2" value="${nowLocal()}" max="${nowLocal()}">
                                ${needsUnit ? `
                                <label class="form-label small mb-1">Elevator left</label>
                                <select id="mcUnit" class="form-select mb-2">
                                    <option value="">Select…</option>
                                    <option value="working">Working</option>
                                    <option value="shutdown">Shut down</option>
                                </select>
                                <input type="text" id="mcShutdown" class="form-control mb-2 d-none" maxlength="500" placeholder="Shutdown reason">` : ''}
                                <label class="form-label small mb-1">Note</label>
                                <input type="text" id="mcReason" class="form-control" maxlength="255" placeholder="e.g. Work done, technician forgot to complete in the app">
                            </div>`,
                        showCancelButton: true,
                        confirmButtonText: 'Mark completed',
                        confirmButtonColor: '#2E9E5B',
                        didOpen: () => {
                            const unit = document.getElementById('mcUnit');
                            if (unit) unit.addEventListener('change', () => document.getElementById('mcShutdown').classList.toggle('d-none', unit.value !== 'shutdown'));
                        },
                        preConfirm: () => {
                            const payload = {
                                check_out_time: document.getElementById('mcEnd').value,
                                reason: document.getElementById('mcReason').value.trim(),
                            };
                            if (needsUnit) {
                                payload.unit_status_after = document.getElementById('mcUnit').value;
                                payload.shutdown_reason = document.getElementById('mcShutdown').value.trim();
                                if (!payload.unit_status_after) { Swal.showValidationMessage('Please choose how the elevator was left.'); return false; }
                                if (payload.unit_status_after === 'shutdown' && !payload.shutdown_reason) { Swal.showValidationMessage('Please give the shutdown reason.'); return false; }
                            }
                            if (!payload.check_out_time) { Swal.showValidationMessage('Please enter the finish time.'); return false; }
                            if (!payload.reason) { Swal.showValidationMessage('Please add a short note.'); return false; }
                            return fetch(url, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify(payload)
                                })
                                .then(r => r.json())
                                .then(data => {
                                    if (!data.success) { Swal.showValidationMessage(data.message || 'Could not complete the job.'); return false; }
                                    return data;
                                })
                                .catch(() => { Swal.showValidationMessage('No connection — please try again.'); return false; });
                        }
                    }).then(result => {
                        if (result.isConfirmed && result.value) {
                            Swal.fire('Completed', result.value.message, 'success').then(() => location.reload());
                        }
                    });
                });
            });

    });
</script>
