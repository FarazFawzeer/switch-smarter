@extends('layouts.technician', ['subtitle' => 'Select Unit'])

@section('content')
    @php
        $colorMap = ['ppm' => '#1E9E74', 'casual_job' => '#8E5BD6', 'repair' => '#E8730A', 'breakdown' => '#D64545'];
        $labelMap = ['ppm' => 'Preventive Maintenance', 'casual_job' => 'Casual Job', 'repair' => 'Repair', 'breakdown' => 'Breakdown'];
        $iconMap = ['ppm' => 'calendar', 'casual_job' => 'briefcase', 'repair' => 'wrench', 'breakdown' => 'alert'];
        $color = $colorMap[$type];
        $acceptsJobNo = in_array($type, ['breakdown', 'repair'], true);
    @endphp

    <style>
        .as-head { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
        .as-back { width: 40px; height: 40px; border-radius: 12px; background: #fff; display: flex; align-items: center; justify-content: center; color: #16233b; box-shadow: 0 1px 3px rgba(15,42,67,.08); }
        .as-type { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; background: {{ $color }}; }
        .as-title { font-weight: 700; color: #16233b; font-size: 17px; line-height: 1.2; }
        .as-sub { font-size: 12px; color: #8792a2; }

        .as-search { position: relative; margin-bottom: 8px; }
        .as-search .ui-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #8792a2; }
        .as-search input { height: 50px; padding-left: 44px; border-radius: 14px; border: 1.5px solid #e2e6ec; font-size: 15px; box-shadow: 0 1px 3px rgba(15,42,67,.05); }
        .as-search input:focus { border-color: {{ $color }}; box-shadow: 0 0 0 3px {{ $color }}22; }
        .as-hint { font-size: 12px; color: #8792a2; margin-bottom: 14px; }

        .as-result { display: flex; align-items: center; gap: 12px; background: #fff; border-radius: 14px; padding: 12px 14px; margin-bottom: 10px;
            box-shadow: 0 1px 3px rgba(15,42,67,.07); border: 1px solid #eef0f4; cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
        .as-result:active { transform: scale(.985); }
        .as-result-ic { width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
        .as-result-ic.existing { background: {{ $color }}1A; color: {{ $color }}; }
        .as-result-ic.new { background: #F1F4F8; color: #56606f; }
        .as-result-body { flex: 1; min-width: 0; }
        .as-result-title { font-weight: 700; color: #16233b; font-size: 14.5px; }
        .as-result-project { font-size: 12.5px; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .as-result-note { font-size: 11.5px; font-weight: 600; margin-top: 3px; }
        .as-result-note.join { color: #B57900; }
        .as-result-note.warn { color: #D64545; }
        .as-result-note.ok { color: {{ $color }}; }
        .as-chev { color: #c3c9d1; flex-shrink: 0; }

        .as-state { text-align: center; color: #8792a2; padding: 28px 12px; font-size: 13.5px; }
        .as-state .ui-icon { display: block; margin: 0 auto 8px; opacity: .6; }
        .as-error { background: #FDECEC; color: #B23030; border-radius: 12px; padding: 12px 14px; font-size: 13px; font-weight: 600; }
        .as-spinner { width: 22px; height: 22px; border: 3px solid #e2e6ec; border-top-color: {{ $color }}; border-radius: 50%; margin: 0 auto 8px; animation: asSpin .8s linear infinite; }
        @keyframes asSpin { to { transform: rotate(360deg); } }
    </style>

    <div class="as-head">
        <a href="{{ route('technician.activities.select') }}" class="as-back" aria-label="Back"><x-ui-icon name="arrow-left" size="20" /></a>
        <div class="as-type"><x-ui-icon :name="$iconMap[$type]" size="20" stroke="2" /></div>
        <div>
            <div class="as-title">{{ $labelMap[$type] }}</div>
            <div class="as-sub">Find the unit to {{ $type === 'ppm' ? 'open its visit' : 'start or continue the job' }}</div>
        </div>
    </div>

    <div class="as-search">
        <x-ui-icon name="search" size="20" />
        <input type="search" id="unitSearchInput" class="form-control" autocomplete="off" inputmode="search" autofocus
            placeholder="{{ $acceptsJobNo ? 'Unit ID, unit no or job no' : 'Unit ID or unit no' }}">
    </div>
    <div class="as-hint">
        @if ($type === 'ppm')
            Shows this month's visit for each of your units, plus units with no PPM scheduled this month.
        @elseif ($type === 'casual_job')
            Shows casual jobs assigned to you, and open casual jobs you can join.
        @else
            Any unit can be reported — scheduled or not. Type a job number (e.g. BD-2026-00003) to reopen an existing job or a saved draft.
        @endif
    </div>

    <div id="unitResults">
        <div class="as-state">
            <x-ui-icon name="search" size="28" />
            Start typing to search.
        </div>
    </div>

    @push('scripts')
    <script>
        const unitSearchInput = document.getElementById('unitSearchInput');
        const unitResults = document.getElementById('unitResults');
        const icons = {
            existing: `@include('technician.activities._svg', ['name' => 'clipboard'])`,
            unit: `@include('technician.activities._svg', ['name' => 'layers'])`,
            chev: `@include('technician.activities._svg', ['name' => 'chevron-right'])`,
            search: `@include('technician.activities._svg', ['name' => 'search', 'size' => 28])`,
        };
        let searchTimeout, requestId = 0;

        function showState(html) { unitResults.innerHTML = `<div class="as-state">${html}</div>`; }
        function showError(message) { unitResults.innerHTML = `<div class="as-error">${message}</div>`; }

        // Turn a failed response into a message the technician understands (instead of showing nothing)
        async function readJson(response) {
            if (response.status === 401 || response.status === 419) throw new Error('Your session has expired. Please log in again.');
            if (response.redirected || !(response.headers.get('content-type') || '').includes('application/json')) {
                throw new Error('Your session has expired. Please log in again.');
            }
            const data = await response.json();
            if (!response.ok && !data.errors) throw new Error(data.message || 'Something went wrong. Please try again.');
            return data;
        }

        function render(results) {
            if (!results.length) {
                showState(`${icons.search}No matching units found.<br><span style="font-size:12px;">Check the unit ID — you can type part of it (e.g. the last digits).</span>`);
                return;
            }
            unitResults.innerHTML = '';
            results.forEach(item => {
                const card = document.createElement('div');
                card.className = 'as-result';
                const noteClass = /JOIN/.test(item.note || '') ? 'join' : (/SHUT DOWN|contract/.test(item.note || '') ? 'warn' : 'ok');

                card.innerHTML = `
                    <div class="as-result-ic ${item.mode === 'existing' ? 'existing' : 'new'}">${item.mode === 'existing' ? icons.existing : icons.unit}</div>
                    <div class="as-result-body">
                        <div class="as-result-title"></div>
                        <div class="as-result-project"></div>
                        ${item.note ? '<div class="as-result-note ' + noteClass + '"></div>' : ''}
                    </div>
                    <span class="as-chev">${icons.chev}</span>`;
                card.querySelector('.as-result-title').textContent = item.unit_id;
                card.querySelector('.as-result-project').textContent = item.project;
                if (item.note) card.querySelector('.as-result-note').textContent = item.note;

                card.addEventListener('click', () => item.mode === 'existing' ? (window.location.href = item.url) : createActivity(item.unit_id_pk, card));
                unitResults.appendChild(card);
            });
        }

        unitSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const value = this.value.trim();
            if (value === '') { showState(`${icons.search}Start typing to search.`); return; }

            searchTimeout = setTimeout(() => {
                const myRequest = ++requestId;
                showState('<div class="as-spinner"></div>Searching…');

                fetch(`{{ route('technician.activities.search-unit', $type) }}?unit_search=${encodeURIComponent(value)}`, {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(readJson)
                    .then(data => { if (myRequest === requestId) render(data.results || []); })
                    .catch(err => { if (myRequest === requestId) showError(err.message || 'Could not search. Check your connection and try again.'); });
            }, 350);
        });

        function createActivity(unitId, card) {
            card.style.opacity = '.6';
            card.style.pointerEvents = 'none';

            fetch("{{ route('technician.activities.create', $type) }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ elevator_unit_id: unitId })
                })
                .then(readJson)
                .then(data => {
                    if (data.success) { window.location.href = data.redirect; return; }
                    throw new Error(Object.values(data.errors || {}).flat().join(' ') || 'Could not open the job. Please try again.');
                })
                .catch(err => {
                    card.style.opacity = '';
                    card.style.pointerEvents = '';
                    alert(err.message);
                });
        }
    </script>
    @endpush
@endsection
