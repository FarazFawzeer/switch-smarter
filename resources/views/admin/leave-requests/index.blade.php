@extends('layouts.vertical', ['subtitle' => 'Leave Requests'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Times & Approval', 'subtitle' => 'Leave Requests'])

    @php
        $tabs = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'];
        $statusStyle = ['pending' => '#F0A202', 'approved' => '#2E9E5B', 'rejected' => '#D64545', 'cancelled' => '#8792a2'];
    @endphp

    <style>
        .lr-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .lr-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .lr-table thead th { background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 12px; border-bottom: 1px solid #e7e9ee; white-space: nowrap; }
        .lr-table tbody td { padding: 11px 12px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .lr-pill { display: inline-block; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #fff; }
    </style>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach ($tabs as $value => $label)
            <a href="{{ route('admin.leave-requests.index', ['status' => $value]) }}"
                class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
                @if ($value === 'pending' && $pendingCount > 0)
                    <span class="badge bg-danger ms-1">{{ $pendingCount }}</span>
                @endif
            </a>
        @endforeach
    </div>

    @unless ($canReview)
        <div class="alert alert-light border small">You can see your team's leave requests. Approval is done by each technician's supervisor.</div>
    @endunless

    <div class="card lr-card">
        <div class="table-responsive">
            <table class="lr-table">
                <thead>
                    <tr>
                        <th>Technician</th>
                        <th>Supervisor</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $leave)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ optional($leave->technician)->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ optional($leave->technician)->employee_id }}</div>
                            </td>
                            <td>{{ optional(optional($leave->technician)->supervisor)->name ?? '—' }}</td>
                            <td>{{ $leave->leave_type }}</td>
                            <td class="text-nowrap">
                                {{ $leave->start_date->format('d M Y') }}
                                @if ($leave->days > 1)
                                    – {{ $leave->end_date->format('d M Y') }}
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $leave->days }}</td>
                            <td style="max-width: 260px;">{{ $leave->reason }}</td>
                            <td class="text-nowrap text-muted">{{ $leave->created_at->format('d M, h:i A') }}</td>
                            <td>
                                <span class="lr-pill" style="background: {{ $statusStyle[$leave->status] ?? '#8792a2' }};">{{ ucfirst($leave->status) }}</span>
                                @if ($leave->reviewer)
                                    <div class="text-muted" style="font-size:11px;">by {{ $leave->reviewer->name }}{{ $leave->review_note ? ': ' . $leave->review_note : '' }}</div>
                                @endif
                            </td>
                            <td class="text-center text-nowrap">
                                @if ($canReview && $leave->status === 'pending')
                                    <button type="button" class="btn btn-sm btn-success review-btn" data-decision="approve"
                                        data-url="{{ route('admin.leave-requests.approve', $leave->id) }}"
                                        data-name="{{ optional($leave->technician)->name }}">Approve</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger review-btn" data-decision="reject"
                                        data-url="{{ route('admin.leave-requests.reject', $leave->id) }}"
                                        data-name="{{ optional($leave->technician)->name }}">Reject</button>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-5">No {{ $status === 'all' ? '' : $status }} leave requests.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-2">{{ $requests->links() }}</div>

    <script>
        document.querySelectorAll('.review-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const approve = this.dataset.decision === 'approve';
                const url = this.dataset.url;
                Swal.fire({
                    title: approve ? `Approve leave for ${this.dataset.name}?` : `Reject leave for ${this.dataset.name}?`,
                    input: 'text',
                    inputPlaceholder: approve ? 'Note (optional)' : 'Reason for rejecting',
                    inputValidator: (value) => (!approve && !value) ? 'A reason is required to reject.' : undefined,
                    showCancelButton: true,
                    confirmButtonColor: approve ? '#2E9E5B' : '#D64545',
                    confirmButtonText: approve ? 'Approve' : 'Reject'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                            body: JSON.stringify({ note: result.value || null })
                        })
                        .then(r => r.json())
                        .then(data => Swal.fire(data.success ? 'Done' : 'Not changed', data.message, data.success ? 'success' : 'error')
                            .then(() => location.reload()));
                });
            });
        });
    </script>
@endsection
