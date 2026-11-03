{{-- Manual Timesheet Adjustment modal — used by Timesheets and Timesheet Approval.
     Buttons need class .adjust-time-btn with data-url, data-in, data-out, data-who. --}}
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
                                <label class="form-label small mb-1">Check-out <span class="text-muted">(fill in if the technician forgot to log out)</span></label>
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

    });
</script>
