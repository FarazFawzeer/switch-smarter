@extends('layouts.technician', ['subtitle' => 'Leave'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave', 'subtitle' => 'Requests'])

    @php
        $statusStyle = [
            'pending'   => ['Awaiting Approval', '#F0A202'],
            'approved'  => ['Approved', '#2E9E5B'],
            'rejected'  => ['Rejected', '#D64545'],
            'cancelled' => ['Cancelled', '#8792a2'],
        ];
    @endphp

    <style>
        .lv-card { background: #fff; border-radius: 16px; padding: 16px; box-shadow: 0 1px 4px rgba(15,42,67,0.06); margin-bottom: 16px; }
        .lv-title { font-weight: 700; color: #16233b; font-size: 15px; margin-bottom: 10px; }
        .lv-item { border-left: 4px solid var(--lv-color); border-radius: 10px; background: #FAFBFD; padding: 10px 12px; margin-bottom: 8px; }
        .lv-pill { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; color: #fff; }
    </style>

    <div class="lv-card">
        <div class="lv-title">New Leave Request</div>
        <p class="small text-muted mb-3">
            Sent to your supervisor{{ $supervisor ? ' (' . $supervisor->name . ')' : '' }} for approval.
        </p>
        <div id="leaveMessage"></div>
        <form id="leaveForm">
            <label class="form-label small fw-semibold mb-1">Leave Type</label>
            <select name="leave_type" class="form-select mb-2" required>
                @foreach (\App\Models\LeaveRequest::TYPES as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>
            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label small fw-semibold mb-1">From</label>
                    <input type="date" name="start_date" class="form-control" required value="{{ now()->toDateString() }}">
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold mb-1">To</label>
                    <input type="date" name="end_date" class="form-control" required value="{{ now()->toDateString() }}">
                </div>
            </div>
            <label class="form-label small fw-semibold mb-1 mt-2">Reason</label>
            <textarea name="reason" class="form-control mb-3" rows="3" maxlength="1000" required placeholder="Why do you need leave?"></textarea>
            <button type="submit" class="btn w-100 fw-bold text-white py-2" style="background:#071A3D;">Send Request</button>
        </form>
    </div>

    <div class="lv-card">
        <div class="lv-title">My Requests</div>
        @forelse ($requests as $leave)
            @php [$label, $color] = $statusStyle[$leave->status] ?? [ucfirst($leave->status), '#8792a2']; @endphp
            <div class="lv-item" style="--lv-color: {{ $color }};">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">{{ $leave->leave_type }}</div>
                        <div class="small text-muted">
                            {{ $leave->start_date->format('d M') }}{{ $leave->days > 1 ? ' – ' . $leave->end_date->format('d M Y') : ' ' . $leave->start_date->format('Y') }}
                            · {{ $leave->days }} day{{ $leave->days > 1 ? 's' : '' }}
                        </div>
                    </div>
                    <span class="lv-pill" style="background: {{ $color }};">{{ $label }}</span>
                </div>
                <div class="small mt-1">{{ $leave->reason }}</div>
                @if ($leave->review_note || $leave->reviewer)
                    <div class="small mt-1" style="color: {{ $color }};">
                        {{ $leave->reviewer ? $leave->reviewer->name . ': ' : '' }}{{ $leave->review_note }}
                    </div>
                @endif
                @if ($leave->status === 'pending')
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 mt-1 cancel-leave-btn"
                        data-url="{{ route('technician.leave.cancel', $leave->id) }}">Cancel request</button>
                @endif
            </div>
        @empty
            <p class="text-center text-muted small py-3 mb-0">No leave requests yet.</p>
        @endforelse
    </div>

    @push('scripts')
    <script>
        document.getElementById('leaveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const msg = document.getElementById('leaveMessage');
            fetch("{{ route('technician.leave.store') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(this)
                })
                .then(r => r.json())
                .then(data => {
                    const div = document.createElement('div');
                    div.className = 'alert py-2 ' + (data.success ? 'alert-success' : 'alert-danger');
                    div.textContent = data.success ? data.message : Object.values(data.errors || {}).flat().join(' ');
                    msg.replaceChildren(div);
                    if (data.success) setTimeout(() => location.reload(), 1000);
                });
        });

        document.querySelectorAll('.cancel-leave-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if (!confirm('Cancel this leave request?')) return;
                fetch(this.dataset.url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(() => location.reload());
            });
        });
    </script>
    @endpush
@endsection
