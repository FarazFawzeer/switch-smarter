@extends('layouts.vertical', ['subtitle' => 'Holidays'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Times & Approval', 'subtitle' => 'Holidays'])

    <style>
        .hd-card { border: none; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); }
        .hd-table { border-collapse: separate; border-spacing: 0; width: 100%; font-size: 13px; }
        .hd-table thead th { background: #F5F7FA; color: #56606f; font-weight: 600; padding: 10px 14px; border-bottom: 1px solid #e7e9ee; }
        .hd-table tbody td { padding: 10px 14px; border-bottom: 1px solid #f0f1f4; vertical-align: middle; }
        .hd-weekly { display: inline-block; background: rgba(23,162,184,0.12); color: #138496; font-weight: 700; font-size: 12px; padding: 3px 10px; border-radius: 6px; margin-right: 4px; }
    </style>

    <div class="alert alert-light border small d-flex gap-2 align-items-start">
        <iconify-icon icon="solar:info-circle-outline" style="font-size:18px; flex-shrink:0;"></iconify-icon>
        <div>
            Work done on a holiday is shown as <strong>HOT</strong> in timesheets instead of Normal Time / Over Time.
            The weekly day off is always a holiday:
            @foreach ($weeklyOff as $day)
                <span class="hd-weekly">{{ $day }}</span>
            @endforeach
            — add public holidays below. Eid dates depend on the moon sighting, so update them once officially announced.
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card hd-card">
                <div class="card-header"><h6 class="card-title mb-0">Add Holiday</h6></div>
                <div class="card-body">
                    <div id="holidayMessage"></div>
                    <form id="holidayForm">
                        <div class="mb-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" maxlength="150" required
                                placeholder="Ex: Eid al-Fitr (Day 1)">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Add Holiday</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card hd-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">Holidays in {{ $year }}</h6>
                    <form method="GET" action="{{ route('admin.holidays.index') }}">
                        <select name="year" class="form-select form-select-sm" style="width:110px;" onchange="this.form.submit()">
                            @foreach ($years as $y)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="hd-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Holiday</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($holidays as $holiday)
                                <tr>
                                    <td class="fw-semibold">{{ $holiday->date->format('d M Y') }}</td>
                                    <td>{{ $holiday->date->format('l') }}</td>
                                    <td>{{ $holiday->name }}</td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-holiday"
                                            data-url="{{ route('admin.holidays.destroy', $holiday->id) }}"
                                            data-name="{{ $holiday->name }}">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No holidays added for {{ $year }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('holidayForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const msg = document.getElementById('holidayMessage');

            fetch("{{ route('admin.holidays.store') }}", {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(this)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Added', data.message, 'success').then(() => location.reload());
                    } else {
                        const div = document.createElement('div');
                        div.className = 'alert alert-danger py-2';
                        div.textContent = Object.values(data.errors || {}).flat().join(' ');
                        msg.replaceChildren(div);
                    }
                });
        });

        document.querySelectorAll('.delete-holiday').forEach(btn => {
            btn.addEventListener('click', function() {
                const url = this.dataset.url;
                Swal.fire({
                    title: 'Remove holiday?',
                    text: `${this.dataset.name} will be treated as a normal working day.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, remove'
                }).then(result => {
                    if (!result.isConfirmed) return;
                    fetch(url, {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        })
                        .then(r => r.json())
                        .then(data => Swal.fire('Removed', data.message, 'success').then(() => location.reload()));
                });
            });
        });
    </script>
@endsection
