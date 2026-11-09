@extends('layouts.vertical', ['subtitle' => 'Material Requests'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'Material Requests'])
    @include('admin.stores._styles')

    @php
        $tabs = ['pending' => 'Awaiting approval', 'approved' => 'To issue', 'issued' => 'Issued', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled', 'all' => 'All'];
    @endphp

    @if (session('success'))
        <div class="alert alert-success py-2">{{ session('success') }}</div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="st-tabs">
            @foreach ($tabs as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}" class="{{ $status === $key ? 'active' : '' }}">
                    {{ $label }}
                    @if ($key !== 'all' && ($counts[$key] ?? 0) > 0)<span class="cnt">{{ $counts[$key] }}</span>@endif
                </a>
            @endforeach
        </div>
        @if ($canRequest)
            <a href="{{ route('admin.stores.requests.create') }}" class="btn btn-primary btn-sm"><x-ui-icon name="plus" size="15" /> New Material Request</a>
        @endif
    </div>

    <form method="GET" action="{{ route('admin.stores.requests.index') }}" id="filterForm">
        <input type="hidden" name="status" value="{{ $status }}">
    </form>
    <x-active-filters :filters="[
        'ref' => 'MRN / Ref',
        'project' => 'Project',
        'unit' => 'Unit',
        'part' => 'Part',
        'requested_by' => 'Requested by',
        'from' => 'From',
        'to' => 'To',
    ]" :keep="['status']" />

    <div class="card st-card">
        <div class="table-responsive">
            <table class="st-table">
                <thead>
                    <tr>
                        <th><x-th-filter label="MRN / Ref" name="ref" /></th>
                        <th><x-th-filter label="Date" type="daterange" from="from" to="to" /></th>
                        <th><x-th-filter label="Project" name="project" /></th>
                        <th><x-th-filter label="Unit" name="unit" /></th>
                        <th><x-th-filter label="Part" name="part" /></th>
                        <th class="text-end">Qty</th>
                        <th>In Stock</th>
                        <th><x-th-filter label="Requested by" name="requested_by" /></th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $r)
                        @php $short = $r->status === 'approved' && $r->item && (float) $r->item->stock_qty < (float) $r->quantity_requested; @endphp
                        <tr>
                            <td class="fw-semibold text-nowrap"><a href="{{ route('admin.stores.requests.show', $r) }}">{{ $r->reference() }}</a></td>
                            <td class="text-nowrap">{{ $r->created_at->format('d M Y') }}<div class="st-sub">{{ $r->created_at->format('h:i A') }}</div></td>
                            <td>{{ optional($r->contract)->project_name ?? '—' }}</td>
                            <td class="text-nowrap">
                                {{ optional($r->unit)->identification_no ?? '—' }}
                                @if (optional($r->unit)->unit_number)<div class="st-sub">{{ $r->unit->unit_number }}{{ $r->unit->brand ? ' · ' . $r->unit->brand : '' }}</div>@endif
                            </td>
                            <td>
                                <div class="st-name">{{ optional($r->item)->part_name ?? '—' }}</div>
                                <div class="st-sub">{{ optional($r->item)->item_code }}</div>
                            </td>
                            <td class="text-end text-nowrap">
                                {{ qty($r->quantity_requested) }}
                                @if ($r->status === 'issued' && (float) $r->quantity_issued !== (float) $r->quantity_requested)
                                    <div class="st-sub">issued {{ qty($r->quantity_issued) }}</div>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if ($r->item)
                                    <span class="{{ $short ? 'text-danger fw-bold' : '' }}">{{ qty($r->item->stock_qty) }} {{ $r->item->unit }}</span>
                                    @if ($short)<div class="st-sub text-danger">not enough</div>@endif
                                @endif
                            </td>
                            <td>{{ optional($r->requester)->name ?? '—' }}<div class="st-sub">{{ optional($r->requester)->type }}</div></td>
                            <td><span class="st-pill" style="background:{{ $r->statusColor() }};">{{ $r->statusLabel() }}</span></td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('admin.stores.requests.show', $r) }}" class="btn btn-sm {{ ($r->status === 'pending' && $canApprove) || ($r->status === 'approved' && $canIssue) ? 'btn-primary' : 'btn-outline-primary' }}">
                                    {{ $r->status === 'pending' && $canApprove ? 'Review' : ($r->status === 'approved' && $canIssue ? 'Issue' : 'View') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-5">
                            <x-ui-icon name="shopping-cart" size="30" /><div class="mt-2">No material requests here.</div>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="d-flex justify-content-end mt-2">{{ $requests->links() }}</div>
@endsection
