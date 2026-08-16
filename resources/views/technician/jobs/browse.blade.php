@extends('layouts.technician', ['subtitle' => 'Jobs'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Jobs', 'subtitle' => 'This Month'])

    <style>
        .unit-search-box { position: relative; margin-bottom: 14px; }
        .unit-search-box iconify-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #8792a2; font-size: 18px; }
        .unit-search-box input { padding-left: 38px; }
        #jobListContainer { min-height: 200px; }
        #jobListContainer.loading { opacity: 0.5; pointer-events: none; transition: opacity .15s ease; }
    </style>

    <div class="unit-search-box">
        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
        <input type="text" id="unitSearchInput" class="form-control" placeholder="Search by unit ID (e.g. LP-A-01)">
    </div>

    <div id="jobListContainer"></div>

    @push('scripts')
    <script>
        const container = document.getElementById('jobListContainer');
        const searchInput = document.getElementById('unitSearchInput');
        let searchTimeout;

        function loadJobs(page = 1) {
            container.classList.add('loading');
            const params = new URLSearchParams({ unit_search: searchInput.value, page: page });

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

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => loadJobs(1), 500);
        });

        loadJobs(1);
    </script>
    @endpush
@endsection