@extends('layouts.vertical', ['subtitle' => 'Store Analytics'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Stores', 'subtitle' => 'Analytics'])
    @include('admin.stores._styles')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div class="st-tabs">
            @foreach ([3 => 'Last 3 months', 6 => 'Last 6 months', 12 => 'Last 12 months'] as $m => $label)
                <a href="{{ route('admin.stores.analytics', ['months' => $m]) }}" class="{{ $months === $m ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <a href="{{ route('admin.stores.inventory.index') }}" class="btn btn-outline-secondary btn-sm"><x-ui-icon name="package" size="15" /> Inventory</a>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-xl-2"><div class="st-stat" style="--c:#2E9E5B;"><div class="ic"><x-ui-icon name="package" size="20" /></div><div><div class="lbl">Stock value now</div><div class="num" style="font-size:17px;">{{ money($summary['value']) }}</div></div></div></div>
        <div class="col-6 col-xl-2"><div class="st-stat" style="--c:#D64545;"><div class="ic"><x-ui-icon name="shopping-cart" size="20" /></div><div><div class="lbl">Issued ({{ $months }} mo)</div><div class="num" style="font-size:17px;">{{ money($summary['issued']) }}</div></div></div></div>
        <div class="col-4 col-xl-2"><div class="st-stat" style="--c:#2E6BD8;"><div class="ic"><x-ui-icon name="file-text" size="20" /></div><div><div class="lbl">Requests</div><div class="num">{{ $summary['requests'] }}</div></div></div></div>
        <div class="col-4 col-xl-2"><a class="st-stat" href="{{ route('admin.stores.requests.index', ['status' => 'pending']) }}" style="--c:#E08A00;"><div class="ic"><x-ui-icon name="clock" size="20" /></div><div><div class="lbl">Awaiting approval</div><div class="num">{{ $summary['pending'] }}</div></div></a></div>
        <div class="col-4 col-xl-2"><a class="st-stat" href="{{ route('admin.stores.requests.index', ['status' => 'approved']) }}" style="--c:#7A6FD0;"><div class="ic"><x-ui-icon name="package" size="20" /></div><div><div class="lbl">To issue</div><div class="num">{{ $summary['to_issue'] }}</div></div></a></div>
        <div class="col-12 col-xl-2"><a class="st-stat" href="{{ route('admin.stores.inventory.index', ['stock' => 'low']) }}" style="--c:#E08A00;"><div class="ic"><x-ui-icon name="alert" size="20" /></div><div><div class="lbl">Low stock items</div><div class="num">{{ $summary['low'] }}</div></div></a></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card st-card h-100">
                <div class="card-body">
                    <h6 class="mb-1">Material cost issued per month</h6>
                    <div class="st-sub mb-2">Value of parts handed over from the store (quantity × unit cost at the time of issue).</div>
                    <div id="trendChart" style="min-height: 280px;"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card st-card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Cost by project ({{ $months }} months)</h6>
                    @php $maxProject = max(1, (float) $byProject->max('cost')); @endphp
                    @forelse ($byProject as $row)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small">
                                <span class="st-name text-truncate" style="max-width: 65%;">{{ $projectNames[$row->contract_id] ?? 'Project #' . $row->contract_id }}</span>
                                <span class="fw-bold">{{ money($row->cost) }}</span>
                            </div>
                            <div class="progress" style="height: 6px;"><div class="progress-bar" style="width: {{ round((float) $row->cost / $maxProject * 100) }}%; background:#2E6BD8;"></div></div>
                            <div class="st-sub">{{ $row->requests }} request(s)</div>
                        </div>
                    @empty
                        <div class="text-muted small py-4 text-center">Nothing issued in this period.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card st-card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Most used parts ({{ $months }} months)</h6>
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead><tr><th>Part</th><th class="text-end">Qty issued</th><th class="text-end">Cost</th></tr></thead>
                            <tbody>
                                @forelse ($topItems as $row)
                                    @php $it = $itemNames[$row->inventory_item_id] ?? null; @endphp
                                    <tr>
                                        <td>@if ($it)<a href="{{ route('admin.stores.inventory.show', $it->id) }}" class="st-name text-decoration-none">{{ $it->part_name }}</a><div class="st-sub">{{ $it->item_code }}</div>@else — @endif</td>
                                        <td class="text-end">{{ qty($row->qty) }} {{ optional($it)->unit }}</td>
                                        <td class="text-end fw-semibold">{{ money($row->cost) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">Nothing issued in this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card st-card h-100">
                <div class="card-body">
                    <h6 class="mb-3">Low stock — reorder</h6>
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead><tr><th>Part</th><th class="text-end">In stock</th><th class="text-end">Minimum</th></tr></thead>
                            <tbody>
                                @forelse ($lowStock as $it)
                                    <tr class="{{ (float) $it->stock_qty <= 0 ? 'is-out' : 'is-low' }}">
                                        <td><a href="{{ route('admin.stores.inventory.show', $it) }}" class="st-name text-decoration-none">{{ $it->part_name }}</a><div class="st-sub">{{ $it->item_code }}{{ $it->store_location ? ' · ' . $it->store_location : '' }}</div></td>
                                        <td class="text-end fw-bold">{{ qty($it->stock_qty) }} {{ $it->unit }}</td>
                                        <td class="text-end">{{ qty($it->min_stock) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">All items are above their minimum level.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card st-card">
                <div class="card-body">
                    <h6 class="mb-3">Stock value by category</h6>
                    <div class="table-responsive">
                        <table class="st-table">
                            <thead><tr><th>Category</th><th class="text-end">Items</th><th class="text-end">Value</th></tr></thead>
                            <tbody>
                                @forelse ($byCategory as $row)
                                    <tr><td class="st-name">{{ $row->cat }}</td><td class="text-end">{{ $row->items }}</td><td class="text-end fw-semibold">{{ money($row->value) }}</td></tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-4">No items yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const trend = @json($trend);
            if (!window.ApexCharts) {
                document.getElementById('trendChart').innerHTML = '<div class="text-muted small py-5 text-center">Chart could not load (no internet). Totals: '
                    + trend.map(t => t.label + ': QAR ' + t.cost.toFixed(2)).join(' · ') + '</div>';
                return;
            }
            new ApexCharts(document.getElementById('trendChart'), {
                chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [{ name: 'Issued (QAR)', data: trend.map(t => t.cost) }],
                xaxis: { categories: trend.map(t => t.label) },
                yaxis: { labels: { formatter: v => v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toFixed(0) } },
                colors: ['#2E6BD8'],
                plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: v => 'QAR ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2 }) } },
                grid: { borderColor: 'rgba(135,146,162,0.2)' },
            }).render();
        });
    </script>
@endsection
