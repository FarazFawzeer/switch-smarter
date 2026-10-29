@extends('layouts.technician', ['subtitle' => 'Jobs'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Jobs', 'subtitle' => 'This Month'])

    @php
        $tabs = [
            ''            => ['All', '#071A3D', $statusCounts->sum()],
            'pending'     => ['Pending', '#F0A202', $statusCounts['pending'] ?? 0],
            'in_progress' => ['In Progress', '#2E86DE', $statusCounts['in_progress'] ?? 0],
            'completed'   => ['Completed', '#2E9E5B', $statusCounts['completed'] ?? 0],
            'on_hold'     => ['On Hold', '#8792a2', $statusCounts['on_hold'] ?? 0],
        ];
    @endphp

    <style>
        .unit-search-box { position: relative; margin-bottom: 12px; }
        .unit-search-box iconify-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8792a2; font-size: 18px; }
        .unit-search-box input { padding-left: 38px; }
        #jobListContainer { min-height: 200px; }
        #jobListContainer.loading { opacity: 0.5; pointer-events: none; transition: opacity .15s ease; }

        .status-tabs { display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px; margin-bottom: 12px; scrollbar-width: none; }
        .status-tabs::-webkit-scrollbar { display: none; }
        .status-tab {
            flex-shrink: 0; display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 20px; font-size: 12.5px; font-weight: 700;
            background: #fff; color: #56606f; border: 1px solid #e7e9ee; cursor: pointer;
        }
        .status-tab .count { background: #F5F7FA; color: #56606f; border-radius: 10px; padding: 0 7px; font-size: 11px; }
        .status-tab.active { color: #fff; border-color: var(--tab-color); background: var(--tab-color); }
        .status-tab.active .count { background: rgba(255,255,255,0.25); color: #fff; }
    </style>

    <div class="status-tabs" id="statusTabs">
        @foreach ($tabs as $value => [$label, $color, $count])
            <button type="button" class="status-tab {{ $status === $value ? 'active' : '' }}"
                style="--tab-color: {{ $color }};" data-status="{{ $value }}">
                {{ $label }} <span class="count">{{ $count }}</span>
            </button>
        @endforeach
    </div>

    <div class="unit-search-box">
        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
        <input type="text" id="unitSearchInput" class="form-control" placeholder="Search by unit ID or job no (e.g. LP-A-01, BD-2026-00003)">
    </div>

    <div id="jobListContainer"></div>

    @push('scripts')
    <script>
        const container = document.getElementById('jobListContainer');
        const searchInput = document.getElementById('unitSearchInput');
        let currentStatus = @json($status);
        let searchTimeout;

        function loadJobs(page = 1) {
            container.classList.add('loading');
            const params = new URLSearchParams({ unit_search: searchInput.value, status: currentStatus, page: page });

            fetch(`{{ route('technician.jobs.list') }}?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    container.innerHTML = html;
                    container.classList.remove('loading');
                    attachPaginationHandlers();
                })
                .catch(() => container.classList.remove('loading'));
        }

        function attachPaginationHandlers() {
            container.querySelectorAll('.pagination a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    loadJobs(url.searchParams.get('page') || 1);
                    window.scrollTo({ top: container.offsetTop - 20, behavior: 'smooth' });
                });
            });
        }

        document.querySelectorAll('.status-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                currentStatus = this.dataset.status;

                // keep the selected tab in the address bar, so Back returns to it
                const url = new URL(window.location);
                currentStatus ? url.searchParams.set('status', currentStatus) : url.searchParams.delete('status');
                history.replaceState(null, '', url);

                loadJobs(1);
            });
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadJobs(1), 500);
        });

        loadJobs(1);
    </script>
    @endpush
@endsection
