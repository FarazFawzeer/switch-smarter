@extends('layouts.technician', ['subtitle' => 'Select Activity'])

@section('content')
    <style>
        .sa-header {
            background: #2E5AAC; color: #fff; padding: 14px 16px; border-radius: 14px;
            display: flex; align-items: center; gap: 10px; margin-bottom: 16px;
        }
        .sa-header a { color: #fff; font-size: 20px; }
        .sa-option {
            display: flex; align-items: center; gap: 14px; background: #fff; border-radius: 14px;
            padding: 16px; box-shadow: 0 1px 3px rgba(15,42,67,0.06); margin-bottom: 10px;
            text-decoration: none; color: inherit; border-left: 5px solid var(--opt-color);
        }
        .sa-option:hover { box-shadow: 0 6px 16px rgba(15,42,67,0.10); }
        .sa-icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #fff; background: var(--opt-color); flex-shrink: 0; }
        .sa-option .title { font-weight: 700; color: #16233b; }
        .sa-option .desc { font-size: 12px; color: #8792a2; }
        .sa-option .chev { margin-left: auto; color: #c3c9d1; font-size: 20px; }
    </style>

    <div class="sa-header">
        <a href="{{ route('technician.jobs.index') }}"><iconify-icon icon="solar:arrow-left-outline"></iconify-icon></a>
        <span class="fw-bold">Select Activity</span>
    </div>

    <p class="text-muted small mb-3">Choose the type of activity</p>

    @php
        $options = [
            ['type' => 'ppm', 'label' => 'Preventive Maintenance', 'desc' => 'Planned and scheduled maintenance', 'color' => '#2E9E5B', 'icon' => 'solar:calendar-mark-bold'],
            ['type' => 'casual_job', 'label' => 'Casual Job', 'desc' => 'Non-routine work / paid service', 'color' => '#9B59B6', 'icon' => 'solar:case-round-bold'],
            ['type' => 'repair', 'label' => 'Repair', 'desc' => 'Repair under warranty / free of charge', 'color' => '#F0730A', 'icon' => 'mdi:wrench'],
            ['type' => 'breakdown', 'label' => 'Breakdown', 'desc' => 'Unplanned breakdown / emergency', 'color' => '#D64545', 'icon' => 'solar:danger-triangle-bold'],
        ];
    @endphp

    @foreach($options as $opt)
        <a href="{{ route('technician.activities.search-form', $opt['type']) }}" class="sa-option" style="--opt-color: {{ $opt['color'] }};">
            <div class="sa-icon"><iconify-icon icon="{{ $opt['icon'] }}"></iconify-icon></div>
            <div>
                <div class="title">{{ $opt['label'] }}</div>
                <div class="desc">{{ $opt['desc'] }}</div>
            </div>
            <iconify-icon icon="solar:alt-arrow-right-outline" class="chev"></iconify-icon>
        </a>
    @endforeach

    <div class="alert alert-light border small mt-3 mb-0">
        <iconify-icon icon="solar:info-circle-outline"></iconify-icon>
        Date &amp; Time will be auto-captured once you start the activity.
    </div>
@endsection