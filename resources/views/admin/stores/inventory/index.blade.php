@extends('layouts.vertical', ['subtitle' => 'Inventory'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'Inventory'])
    @include('admin.stores._styles')

    @php
        $categoryOptions = array_combine(\App\Models\InventoryItem::CATEGORIES, \App\Models\InventoryItem::CATEGORIES);
        $stockOptions = ['low' => 'Low stock', 'out' => 'Out of stock', 'ok' => 'In stock'];
    @endphp

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="row g-2 mb-3">
        <div class="col-6 col-lg">
            <div class="st-stat" style="--c:#2E6BD8;"><div class="ic"><x-ui-icon name="package" size="20" /></div>
                <div><div class="lbl">Active items</div><div class="num">{{ number_format($summary['items']) }}</div></div></div>
        </div>
        <div class="col-6 col-lg">
            <div class="st-stat" style="--c:#2E9E5B;"><div class="ic"><x-ui-icon name="bar-chart" size="20" /></div>
                <div><div class="lbl">Stock value</div><div class="num">{{ money($summary['value']) }}</div></div></div>
        </div>
        <div class="col-6 col-lg">
            <a href="{{ route('admin.stores.inventory.index', ['stock' => 'low']) }}" class="st-stat" style="--c:#E08A00;"><div class="ic"><x-ui-icon name="alert" size="20" /></div>
                <div><div class="lbl">Low stock</div><div class="num">{{ $summary['low'] }}</div></div></a>
        </div>
        <div class="col-6 col-lg">
            <a href="{{ route('admin.stores.inventory.index', ['stock' => 'out']) }}" class="st-stat" style="--c:#D64545;"><div class="ic"><x-ui-icon name="x-circle" size="20" /></div>
                <div><div class="lbl">Out of stock</div><div class="num">{{ $summary['out'] }}</div></div></a>
        </div>
        <div class="col-12 col-lg">
            <a href="{{ route('admin.stores.requests.index', ['status' => 'approved']) }}" class="st-stat" style="--c:#7A6FD0;"><div class="ic"><x-ui-icon name="shopping-cart" size="20" /></div>
                <div><div class="lbl">Approved requests to issue</div><div class="num">{{ $summary['to_issue'] }}</div></div></a>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <x-active-filters :filters="[
            'item_code' => 'Code',
            'part_name' => 'Part',
            'category' => 'Category',
            'brand' => 'Brand',
            'part_number' => 'Part No',
            'store_location' => 'Location',
            'stock' => ['Stock', $stockOptions],
            'active' => ['Status', ['0' => 'Inactive', 'all' => 'All']],
        ]" />
        <div class="d-flex gap-2 ms-auto">
            <a href="{{ route('admin.stores.analytics') }}" class="btn btn-outline-secondary btn-sm"><x-ui-icon name="bar-chart" size="15" /> Analytics</a>
            @if ($canManage)
                <a href="{{ route('admin.stores.inventory.create') }}" class="btn btn-primary btn-sm"><x-ui-icon name="plus" size="15" /> Add Item</a>
            @endif
        </div>
    </div>

    <form method="GET" action="{{ route('admin.stores.inventory.index') }}" id="filterForm">
        @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}"><input type="hidden" name="dir" value="{{ request('dir') }}">@endif
    </form>

    <div class="card st-card">
        <div class="table-responsive">
            <table class="st-table">
                <thead>
                    <tr>
                        <th><x-th-filter label="Code" name="item_code" sort="item_code" /></th>
                        <th><x-th-filter label="Part" name="part_name" sort="part_name" /></th>
                        <th><x-th-filter label="Category" name="category" type="select" :options="$categoryOptions" sort="category" /></th>
                        <th><x-th-filter label="Brand" name="brand" /></th>
                        <th><x-th-filter label="Part No" name="part_number" /></th>
                        <th><x-th-filter label="In Stock" name="stock" type="select" :options="$stockOptions" sort="stock_qty" /></th>
                        <th>Min</th>
                        <th><x-th-filter label="Unit Cost" sort="unit_cost" /></th>
                        <th>Value</th>
                        <th><x-th-filter label="Location" name="store_location" /></th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php $out = (float) $item->stock_qty <= 0; @endphp
                        <tr class="{{ $out ? 'is-out' : ($item->isLow() ? 'is-low' : '') }}">
                            <td class="fw-semibold text-nowrap">{{ $item->item_code }}</td>
                            <td>
                                <a href="{{ route('admin.stores.inventory.show', $item) }}" class="st-name text-decoration-none">{{ $item->part_name }}</a>
                                @unless ($item->is_active)<span class="st-soft ms-1" style="--c:#8792a2;">Inactive</span>@endunless
                            </td>
                            <td>{{ $item->category ?? '—' }}</td>
                            <td>{{ $item->brand ?? '—' }}</td>
                            <td>{{ $item->part_number ?? '—' }}</td>
                            <td class="text-nowrap">
                                <strong>{{ qty($item->stock_qty) }}</strong> {{ $item->unit }}
                                @if ($out)
                                    <span class="st-soft ms-1" style="--c:#D64545;">Out</span>
                                @elseif ($item->isLow())
                                    <span class="st-soft ms-1" style="--c:#E08A00;">Low</span>
                                @endif
                            </td>
                            <td>{{ $item->min_stock > 0 ? qty($item->min_stock) : '—' }}</td>
                            <td class="text-nowrap">{{ number_format((float) $item->unit_cost, 2) }}</td>
                            <td class="text-nowrap">{{ number_format($item->stockValue(), 2) }}</td>
                            <td>{{ $item->store_location ?? '—' }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('admin.stores.inventory.show', $item) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @if ($canManage)
                                    <button type="button" class="btn btn-sm btn-outline-success receive-btn" data-url="{{ route('admin.stores.inventory.receive', $item) }}"
                                        data-name="{{ $item->part_name }}" data-unit="{{ $item->unit }}" data-cost="{{ $item->unit_cost }}">+ Receive</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-5">
                            <x-ui-icon name="package" size="30" /><div class="mt-2">No store items found.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">{{ $items->links() }}</div>

    @include('admin.stores._stock-scripts')
@endsection
