@props([
    'label',
    'name' => null,          // query parameter this column filters on (null = no filter)
    'type' => 'text',        // text | select | date | daterange
    'options' => [],         // select: [value => label]
    'sort' => null,          // sort key (null = not sortable)
    'form' => 'filterForm',  // id of the page's GET form
    'placeholder' => null,
    'from' => null,          // daterange: query parameter names
    'to' => null,
])
{{--
    Table heading with its own filter: the label (click to sort) and a small icon that opens
    a dropdown with a search box / list / date picker for that column. Replaces the separate
    filter bars above tables. The inputs belong to the page's GET form via form="…".
--}}
@php
    $currentSort = request('sort');
    $currentDir = request('dir', 'asc');
    $isSorted = $sort && $currentSort === $sort;
    $sortUrl = $sort ? request()->fullUrlWithQuery(['sort' => $sort, 'dir' => $isSorted && $currentDir === 'asc' ? 'desc' : 'asc', 'page' => null]) : null;

    $value = $name ? request($name) : null;
    $active = $type === 'daterange' ? (request($from) || request($to)) : ($value !== null && $value !== '');
    $icon = in_array($type, ['text']) ? 'search' : ($type === 'select' ? 'filter' : 'calendar');
    $activeLabel = $type === 'select' && $active ? ($options[$value] ?? $value) : null;
@endphp
<div class="thf">
    @if ($sort)
        <a href="{{ $sortUrl }}" class="thf-label {{ $isSorted ? 'is-sorted' : '' }}" title="Sort by {{ strtolower($label) }}">
            {{ $label }}
            <span class="thf-sort">{{ $isSorted ? ($currentDir === 'asc' ? '↑' : '↓') : '↕' }}</span>
        </a>
    @else
        <span class="thf-label">{{ $label }}</span>
    @endif

    @if ($name || $type === 'daterange')
        <div class="dropdown thf-dd">
            <button type="button" class="thf-btn {{ $active ? 'is-active' : '' }}" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                data-bs-display="static" aria-expanded="false" title="Filter {{ strtolower($label) }}{{ $activeLabel ? ': ' . $activeLabel : '' }}">
                <x-ui-icon :name="$icon" size="13" stroke="2.2" />
            </button>
            <div class="dropdown-menu thf-menu">
                <div class="thf-title">{{ $type === 'select' ? 'Filter' : ($type === 'text' ? 'Search' : 'Dates') }}: {{ $label }}</div>

                @if ($type === 'text')
                    <input type="search" name="{{ $name }}" form="{{ $form }}" value="{{ $value }}" class="form-control form-control-sm thf-input"
                        placeholder="{{ $placeholder ?? 'Type to search…' }}" autocomplete="off">
                @elseif ($type === 'select')
                    <select name="{{ $name }}" form="{{ $form }}" class="form-select form-select-sm thf-input thf-autosubmit">
                        <option value="">All</option>
                        @foreach ($options as $optValue => $optLabel)
                            <option value="{{ $optValue }}" {{ (string) $value === (string) $optValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                        @endforeach
                    </select>
                @elseif ($type === 'date')
                    <input type="date" name="{{ $name }}" form="{{ $form }}" value="{{ $value }}" class="form-control form-control-sm thf-input">
                @elseif ($type === 'daterange')
                    <label class="thf-sub">From</label>
                    <input type="date" name="{{ $from }}" form="{{ $form }}" value="{{ request($from) }}" class="form-control form-control-sm thf-input mb-1">
                    <label class="thf-sub">To</label>
                    <input type="date" name="{{ $to }}" form="{{ $form }}" value="{{ request($to) }}" class="form-control form-control-sm thf-input">
                @endif

                <div class="thf-actions">
                    <button type="button" class="thf-clear">Clear</button>
                    <button type="button" class="thf-apply">Apply</button>
                </div>
            </div>
        </div>
    @endif
</div>
