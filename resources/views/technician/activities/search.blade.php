@extends('layouts.technician', ['subtitle' => 'Select Unit'])

@section('content')
    @php
        $colorMap = ['ppm' => '#2E9E5B', 'casual_job' => '#9B59B6', 'repair' => '#F0730A', 'breakdown' => '#D64545'];
        $labelMap = ['ppm' => 'Preventive Maintenance', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
        $color = $colorMap[$type];
    @endphp

    <style>
        .act-header { background: {{ $color }}; color: #fff; padding: 14px 16px; border-radius: 14px; display: flex; align-items: center; gap: 10px; margin-bottom: 16px; }
        .act-header a { color: #fff; font-size: 20px; }
        .unit-result-card { border-left: 4px solid {{ $color }}; cursor: pointer; }
    </style>

    <div class="act-header">
        <a href="{{ route('technician.activities.select') }}"><iconify-icon icon="solar:arrow-left-outline"></iconify-icon></a>
        <span class="fw-bold">{{ $labelMap[$type] }}</span>
    </div>

    <div class="position-relative mb-3">
        <input type="text" id="unitSearchInput" class="form-control" placeholder="Type the unit ID..." autofocus>
    </div>

    <div id="unitResults"></div>

    <p class="text-muted small mt-2">
        @if($type === 'ppm')
            Shows this month's visit for each of your units, plus units with no PPM scheduled this month.
        @elseif($type === 'casual_job')
            Showing units with an existing {{ strtolower($labelMap[$type]) }} assigned to you.
        @else
            Search any unit — this will create a new {{ strtolower($labelMap[$type]) }} report.
        @endif
    </p>

    @push('scripts')
    <script>
        const unitSearchInput = document.getElementById('unitSearchInput');
        const unitResults = document.getElementById('unitResults');
        let searchTimeout;

        unitSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            if (value === '') { unitResults.innerHTML = ''; return; }

            searchTimeout = setTimeout(() => {
                fetch(`{{ route('technician.activities.search-unit', $type) }}?unit_search=${encodeURIComponent(value)}`)
                    .then(r => r.json())
                    .then(data => {
                        unitResults.innerHTML = '';
                        if (data.results.length === 0) {
                            unitResults.innerHTML = '<p class="text-center text-muted py-3">No matching units found.</p>';
                            return;
                        }
                        data.results.forEach(item => {
                            const card = document.createElement('div');
                            card.className = 'card mb-2 unit-result-card';
                            const body = document.createElement('div');
                            body.className = 'card-body py-3';
                            const title = document.createElement('div');
                            title.className = 'fw-bold';
                            title.textContent = item.unit_id;
                            const project = document.createElement('div');
                            project.className = 'small text-muted';
                            project.textContent = item.project;
                            body.append(title, project);
                            if (item.note) {
                                const note = document.createElement('div');
                                note.className = 'small fw-semibold mt-1';
                                note.style.color = item.mode === 'new' ? '#B57900' : '{{ $color }}';
                                note.textContent = item.note;
                                body.appendChild(note);
                            }
                            card.appendChild(body);

                            if (item.mode === 'existing') {
                                card.addEventListener('click', () => window.location.href = item.url);
                            } else {
                                card.addEventListener('click', () => createActivity(item.unit_id_pk));
                            }
                            unitResults.appendChild(card);
                        });
                    });
            }, 400);
        });

        function createActivity(unitId) {
            fetch("{{ route('technician.activities.create', $type) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ elevator_unit_id: unitId })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { window.location.href = data.redirect; }
                    else { alert(Object.values(data.errors || {}).flat().join('\n') || 'Something went wrong. Please try again.'); }
                });
        }
    </script>
    @endpush
@endsection