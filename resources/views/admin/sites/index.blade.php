@extends('layouts.vertical', ['subtitle' => 'Site View'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Sites', 'subtitle' => 'View'])

    <div class="card">
        <div class="card-header">
            <h5 class="card-title">Sites</h5>
            <p class="card-subtitle">All elevator sites linked to your contracts.</p>
        </div>

        <div class="card-body">

            {{-- Search & Filter (auto-applies) --}}
            <form method="GET" action="{{ route('admin.sites.index') }}" id="filterForm" class="row g-2 mb-3 justify-content-end align-items-end">
                <div class="col-auto">
                    <input type="text" name="search" id="searchInput" class="form-control" style="width: 240px;"
                        placeholder="Search by site name or address..." value="{{ request('search') }}">
                </div>
                <div class="col-auto">
                    <select name="contract_id" class="form-select filter-auto" style="width: 220px;">
                        <option value="">All Contracts</option>
                        @foreach($contracts as $contract)
                            <option value="{{ $contract->id }}" {{ request('contract_id') == $contract->id ? 'selected' : '' }}>
                                {{ $contract->project_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(request()->hasAny(['search', 'contract_id']))
                    <div class="col-auto d-flex align-items-center">
                        <a href="{{ route('admin.sites.index') }}" class="text-secondary" title="Clear filters">
                            <iconify-icon icon="solar:close-circle-outline" style="font-size: 22px;"></iconify-icon>
                        </a>
                    </div>
                @endif
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-centered">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Site Name</th>
                            <th scope="col">Contract</th>
                            <th scope="col">Address</th>
                            <th scope="col">Coordinates</th>
                            <th scope="col">Radius</th>
                            <th scope="col">Elevators</th>
                            <th scope="col" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sites as $site)
                            <tr id="site-{{ $site->id }}">
                                <td>{{ $site->site_name }}</td>
                                <td>{{ optional($site->contract)->project_name ?? '—' }}</td>
                                <td>{{ $site->address ?? '—' }}</td>
                                <td>
                                    <a href="https://www.google.com/maps?q={{ $site->latitude }},{{ $site->longitude }}"
                                        target="_blank" class="text-secondary">
                                        {{ $site->latitude }}, {{ $site->longitude }}
                                    </a>
                                </td>
                                <td>{{ $site->radius_meters }}m</td>
                                <td>{{ $site->elevator_count }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('admin.sites.edit', $site->id) }}"
                                            class="text-primary" title="Edit">
                                            <iconify-icon icon="solar:pen-2-outline" style="font-size: 20px;"></iconify-icon>
                                        </a>
                                        <button type="button" class="btn btn-link p-0 border-0 text-danger delete-site"
                                            data-id="{{ $site->id }}" title="Delete">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline" style="font-size: 20px;"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No sites found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="d-flex justify-content-end mt-3">
                    {{ $sites->links() }}
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

        document.querySelectorAll('.delete-site').forEach(button => {
            button.addEventListener('click', function() {
                let siteId = this.dataset.id;

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will permanently delete the site.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch("{{ url('admin/sites') }}/" + siteId, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                    'Accept': 'application/json'
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    document.getElementById('site-' + siteId).remove();
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