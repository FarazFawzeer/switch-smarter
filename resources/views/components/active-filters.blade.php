@props([
    'filters' => [],   // [query param => human label]  (selects can pass [param => [label, options]])
    'keep' => [],      // params that are NOT filters and must survive "Clear all" (e.g. view, month, year)
])
{{-- Chips above a table showing which column filters are on, each removable, plus "Clear all". --}}
@php
    $chips = [];
    foreach ($filters as $param => $meta) {
        $raw = request($param);
        if ($raw === null || $raw === '') continue;
        [$label, $options] = is_array($meta) ? $meta : [$meta, []];
        $chips[] = [
            'label' => $label,
            'value' => $options[$raw] ?? $raw,
            'url'   => request()->fullUrlWithQuery([$param => null, 'page' => null]),
        ];
    }
    $clearAllUrl = url()->current() . (count(array_filter(request()->only($keep))) ? '?' . http_build_query(array_filter(request()->only($keep))) : '');
@endphp
@if ($chips)
    <div class="af-bar">
        <span class="af-title"><x-ui-icon name="filter" size="13" stroke="2.2" /> Filtered by</span>
        @foreach ($chips as $chip)
            <a href="{{ $chip['url'] }}" class="af-chip" title="Remove this filter">
                <span>{{ $chip['label'] }}: <strong>{{ \Illuminate\Support\Str::limit($chip['value'], 40) }}</strong></span>
                <x-ui-icon name="x" size="12" stroke="2.4" />
            </a>
        @endforeach
        <a href="{{ $clearAllUrl }}" class="af-clear">Clear all</a>
    </div>
@endif
