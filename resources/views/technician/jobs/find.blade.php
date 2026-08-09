@extends('layouts.technician', ['subtitle' => 'Find Unit'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Find Unit', 'subtitle' => 'Search by Unit ID'])

    <style>
        .find-search-box { position: relative; margin-bottom: 16px; }
        .find-search-box iconify-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8792a2; font-size: 22px; }
        .find-search-box input {
            padding-left: 44px; height: 56px; font-size: 17px; border-radius: 14px;
        }
        .find-result-card {
            display: block; text-decoration: none; border-left: 4px solid #2E5AAC;
        }
        .find-result-card:hover { box-shadow: 0 6px 16px rgba(15,42,67,0.10); }
        .find-empty-state { text-align: center; padding: 40px 20px; color: #8792a2; }
    </style>

    <p class="text-muted small mb-3">Type the unit ID printed on the elevator to jump straight to that job.</p>

    <div class="find-search-box">
        <iconify-icon icon="solar:magnifer-outline"></iconify-icon>
        <input type="text" id="findUnitInput" class="form-control" placeholder="Enter unit ID..." autofocus>
    </div>

    <div id="findResultsContainer">
        <div class="find-empty-state">
            <iconify-icon icon="solar:qr-code-outline" style="font-size: 44px; opacity: 0.35;"></iconify-icon>
            <p class="mt-2 mb-0">Start typing to find your job</p>
        </div>
    </div>

    @push('scripts')
    <script>
        const findInput = document.getElementById('findUnitInput');
        const resultsContainer = document.getElementById('findResultsContainer');
        let searchTimeout;

        function renderEmptyState(message) {
            resultsContainer.innerHTML = `
                <div class="find-empty-state">
                    <iconify-icon icon="solar:qr-code-outline" style="font-size: 44px; opacity: 0.35;"></iconify-icon>
                    <p class="mt-2 mb-0">${message}</p>
                </div>`;
        }

        function renderResults(results) {
            if (results.length === 0) {
                renderEmptyState('No matching job found for this unit this month.');
                return;
            }

            const statusMap = {
                completed: ['#2E9E5B', 'Completed'],
                in_progress: ['#17A2B8', 'In Progress'],
                pending: ['#F0A202', 'Pending'],
            };

            let html = '';
            results.forEach(job => {
                const [color, label] = statusMap[job.status] || ['#8792a2', job.status];
                html += `
                    <a href="${job.url}" class="card mb-2 find-result-card" style="border-left-color:${color};">
                        <div class="card-body py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1 fw-bold text-dark">${job.unit_id}</p>
                                <p class="mb-0 small text-muted">${job.project}</p>
                            </div>
                            <span class="badge" style="background:${color}; color:#fff;">${label}</span>
                        </div>
                    </a>`;
            });
            resultsContainer.innerHTML = html;

            // If there's exactly one match, jump straight to the job start/complete form automatically
            if (results.length === 1) {
                window.location.href = results[0].url;
            }
        }

        findInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();

            if (value === '') {
                renderEmptyState('Start typing to find your job');
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('technician.jobs.search-unit') }}?unit_search=${encodeURIComponent(value)}`)
                    .then(response => response.json())
                    .then(data => renderResults(data.results))
                    .catch(() => renderEmptyState('Something went wrong. Please try again.'));
            }, 400);
        });
    </script>
    @endpush
@endsection