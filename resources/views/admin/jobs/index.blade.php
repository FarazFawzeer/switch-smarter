@extends('layouts.vertical', ['subtitle' => 'Job View'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Jobs', 'subtitle' => 'View'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">PPM & Repair Jobs</h5>
            <p class="card-subtitle">Scheduled maintenance and repair work across all sites.</p>
        </div>

        <div class="card-body">

            <form method="GET" action="{{ route('admin.jobs.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
                <div class="col-auto">
                    <input type="text" name="search" id="searchInput" class="form-control" style="width: 220px;"
                        placeholder="Search by site name..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <select name="job_type" class="form-select filter-auto" style="width: 160px;">
                        <option value="">All Types</option>
                        <option value="ppm" {{ request('job_type') == 'ppm' ? 'selected' : '' }}>PPM</option>
                        <option value="repair" {{ request('job_type') == 'repair' ? 'selected' : '' }}>Repair</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select filter-auto" style="width: 160px;">
                        <option value="">All Statuses</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                @if(request()->hasAny(['search', 'job_type', 'status']))
                    <div class="col-auto d-flex align-items-center">
                        <a href="{{ route('admin.jobs.index') }}" class="text-secondary" title="Clear filters">
                            <iconify-icon icon="solar:close-circle-outline" style="font-size: 22px;"></iconify-icon>
                        </a>
                    </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Site</th>
                            <th scope="col">Type</th>
                            <th scope="col">Scheduled</th>
                            <th scope="col">Technician</th>
                            <th scope="col">Priority</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $job)
                            <tr id="job-{{ $job->id }}">
                                <td>{{ optional($job->site)->site_name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $job->job_type === 'ppm' ? 'badge-soft-info' : 'badge-soft-primary' }}">
                                        {{ strtoupper($job->job_type) }}
                                    </span>
                                </td>
                                <td>{{ $job->scheduled_date?->format('d M Y') ?? '—' }}</td>
                                <td>{{ optional($job->technician)->name ?? 'Unassigned' }}</td>
                                <td>
                                    @if($job->priority)
                                        @php
                                            $priorityClass = match($job->priority) {
                                                'critical' => 'badge-soft-danger',
                                                'high' => 'badge-soft-warning',
                                                'medium' => 'badge-soft-info',
                                                'low' => 'badge-soft-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $priorityClass }}">{{ ucfirst($job->priority) }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($job->status) {
                                            'completed' => 'badge-soft-success',
                                            'in_progress' => 'badge-soft-info',
                                            'overdue' => 'badge-soft-danger',
                                            'cancelled' => 'badge-soft-secondary',
                                            default => 'badge-soft-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $job->status)) }}</span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('admin.jobs.edit', $job->id) }}" class="text-primary" title="Edit">
                                            <iconify-icon icon="solar:pen-2-outline" style="font-size: 20px;"></iconify-icon>
                                        </a>
                                        <button type="button" class="btn btn-link p-0 border-0 text-danger delete-job"
                                            data-id="{{ $job->id }}" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline" style="font-size: 20px;"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No jobs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-end mt-3">
                    {{ $jobs->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.filter-auto').forEach(select => {
            select.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });

        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 600);
        });

        document.querySelectorAll('.delete-job').forEach(button => {
            button.addEventListener('click', function() {
                let jobId = this.dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will permanently delete this job.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('admin/jobs') }}/" + jobId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('job-' + jobId).remove();
                                    Swal.fire('Deleted!', data.message, 'success');
                                } else {
                                    Swal.fire('Error!', data.message || 'Something went wrong!', 'error');
                                }
                            })
                            .catch(error => {
                                Swal.fire('Error!', 'Something went wrong!', 'error');
                            });
                    }
                });
            });
        });
    </script>
@endsection