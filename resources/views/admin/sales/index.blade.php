@extends('admin.layouts.app')

@section('title', __('Sales Statistics'))
@section('page_title', __('Sales Statistics'))

@section('content')
    {{-- Page Header --}}
    <div class="d-flex align-items-center justify-content-between mb-5 flex-wrap gap-3">
        <div>
            <h2 class="welcome-title mb-1">{{ __('Sales Statistics') }}</h2>
            <p class="text-muted mb-0 small">{{ __('Overview of product sales and revenue performance') }}</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @foreach(['month' => __('This Month'), 'quarter' => __('This Quarter'), 'year' => __('This Year'), 'all' => __('All Time')] as $key => $label)
                <a href="{{ route('admin.sales.index', ['range' => $key]) }}"
                   class="btn btn-sm {{ $range === $key ? 'btn-primary' : 'btn-light' }} rounded-3">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- KPI Stat Cards --}}
    <div class="row g-4 mb-5">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-success-soft">
                        <i class="bi bi-currency-dollar text-success"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Total Revenue') }}</span>
                        <h3 class="stat-value">{{ number_format($totalRevenue, 2) }} ₾</h3>
                        <div class="stat-status {{ $revenueGrowth === null ? 'neutral' : ($revenueGrowth >= 0 ? 'positive' : 'negative') }}">
                            @if($revenueGrowth !== null)
                                <i class="bi bi-graph-{{ $revenueGrowth >= 0 ? 'up' : 'down' }}"></i>
                                <span>{{ $revenueGrowth >= 0 ? '+' : '' }}{{ $revenueGrowth }}% {{ __('vs previous period') }}</span>
                            @else
                                <i class="bi bi-dash-circle"></i>
                                <span>{{ __('All time') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100 bg-success"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-primary-soft">
                        <i class="bi bi-bag-fill text-primary"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Total Orders') }}</span>
                        <h3 class="stat-value">{{ number_format($totalOrders) }}</h3>
                        <div class="stat-status {{ $ordersGrowth === null ? 'neutral' : ($ordersGrowth >= 0 ? 'positive' : 'negative') }}">
                            @if($ordersGrowth !== null)
                                <i class="bi bi-graph-{{ $ordersGrowth >= 0 ? 'up' : 'down' }}"></i>
                                <span>{{ $ordersGrowth >= 0 ? '+' : '' }}{{ $ordersGrowth }}% {{ __('vs previous period') }}</span>
                            @else
                                <i class="bi bi-dash-circle"></i>
                                <span>{{ __('All time') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-info-soft">
                        <i class="bi bi-box-seam-fill text-info"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Products Sold') }}</span>
                        <h3 class="stat-value">{{ number_format($productsSold) }}</h3>
                        <div class="stat-status positive">
                            <i class="bi bi-lightning-fill"></i>
                            <span>{{ __('Units sold') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100 bg-info"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="stat-card premium-shadow">
                <div class="stat-card-inner">
                    <div class="stat-icon-wrap bg-warning-soft">
                        <i class="bi bi-receipt text-warning"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-label">{{ __('Avg. Order Value') }}</span>
                        <h3 class="stat-value">{{ number_format($avgOrderValue, 2) }} ₾</h3>
                        <div class="stat-status neutral">
                            <i class="bi bi-calculator"></i>
                            <span>{{ __('Per completed order') }}</span>
                        </div>
                    </div>
                </div>
                <div class="stat-card-progress">
                    <div class="progress-bar w-100 bg-warning"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Trend Chart --}}
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="dashboard-panel premium-shadow">
                <div class="panel-header d-flex align-items-center justify-content-between">
                    <div class="panel-header-title">
                        <i class="bi bi-graph-up-arrow me-2 text-success"></i>
                        <span>{{ __('Revenue & Orders Trend') }}</span>
                    </div>
                    <span class="badge-soft badge-secondary small">{{ __('Last 12 months') }}</span>
                </div>
                <div class="panel-body">
                    <canvas id="revenueTrendChart" height="90"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products + Orders by Status --}}
    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-8">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>
                        <span>{{ __('Top Selling Products') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    @if($topProducts->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('No sales data available yet.') }}</p>
                    @else
                        <canvas id="topProductsChart" height="180"></canvas>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="dashboard-panel premium-shadow h-100">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-pie-chart-fill me-2 text-primary"></i>
                        <span>{{ __('Orders by Status') }}</span>
                    </div>
                </div>
                <div class="panel-body d-flex flex-column align-items-center">
                    @if(empty($ordersByStatus))
                        <p class="text-muted text-center py-4">{{ __('No orders yet.') }}</p>
                    @else
                        <canvas id="statusDonutChart" height="220"></canvas>
                        <div class="mt-3 w-100">
                            @php
                                $statusColors = [
                                    'pending_payment' => ['bg' => 'bg-warning-soft', 'text' => 'text-warning'],
                                    'paid'       => ['bg' => 'bg-primary-soft', 'text' => 'text-primary'],
                                    'processing' => ['bg' => 'bg-info-soft',    'text' => 'text-info'],
                                    'completed'  => ['bg' => 'bg-success-soft', 'text' => 'text-success'],
                                    'cancelled'  => ['bg' => 'bg-danger-soft',  'text' => 'text-danger'],
                                ];
                                $statusTotal = array_sum($ordersByStatus);
                            @endphp
                            @foreach($ordersByStatus as $status => $count)
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge-soft {{ $statusColors[$status]['bg'] ?? 'bg-primary-soft' }} {{ $statusColors[$status]['text'] ?? 'text-primary' }} px-2 py-1 rounded-2 small fw-medium text-capitalize">
                                        {{ __(str_replace('_', ' ', ucfirst($status))) }}
                                    </span>
                                    <span class="small fw-bold">{{ $count }} <span class="text-muted fw-normal">({{ $statusTotal > 0 ? round($count/$statusTotal*100) : 0 }}%)</span></span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Orders + Category Revenue --}}
    <div class="row g-4">
        <div class="col-12 col-lg-8">
            <div class="dashboard-panel premium-shadow">
                <div class="panel-header d-flex align-items-center justify-content-between">
                    <div class="panel-header-title">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        <span>{{ __('Recent Orders') }}</span>
                    </div>
                </div>
                <div class="panel-body p-0">
                    @if($recentOrders->isEmpty())
                        <p class="text-muted text-center py-5">{{ __('No orders yet.') }}</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light-soft">
                                    <tr>
                                        <th class="py-3 px-4 text-muted small fw-bold">#</th>
                                        <th class="py-3 text-muted small fw-bold">{{ __('Customer') }}</th>
                                        <th class="py-3 text-muted small fw-bold table-col-secondary">{{ __('Items') }}</th>
                                        <th class="py-3 text-muted small fw-bold">{{ __('Total') }}</th>
                                        <th class="py-3 text-muted small fw-bold">{{ __('Status') }}</th>
                                        <th class="py-3 px-4 text-muted small fw-bold table-col-secondary">{{ __('Date') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentOrders as $order)
                                        @php
                                            $statusMap = [
                                                'pending'    => 'badge-soft badge-warning',
                                                'processing' => 'badge-soft badge-info',
                                                'completed'  => 'badge-soft badge-success',
                                                'cancelled'  => 'badge-soft badge-danger',
                                            ];
                                        @endphp
                                        <tr>
                                            <td class="py-3 px-4 text-muted small">#{{ $order->id }}</td>
                                            <td class="py-3">
                                                <div class="fw-medium">{{ $order->customer_name }}</div>
                                                <div class="text-muted small">{{ $order->customer_email }}</div>
                                            </td>
                                            <td class="py-3 table-col-secondary">
                                                <span class="badge-soft badge-secondary">{{ $order->items_count }} {{ __('items') }}</span>
                                            </td>
                                            <td class="py-3 fw-bold text-success">{{ number_format((float) $order->total, 2) }} ₾</td>
                                            <td class="py-3">
                                                <span class="{{ $statusMap[$order->status] ?? 'badge-soft badge-secondary' }} text-capitalize">
                                                    {{ __(ucfirst($order->status)) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-muted small table-col-secondary">
                                                {{ $order->ordered_at->format('d M Y') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="dashboard-panel premium-shadow">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <i class="bi bi-tag-fill me-2 text-info"></i>
                        <span>{{ __('Revenue by Category') }}</span>
                    </div>
                </div>
                <div class="panel-body">
                    @if($categoryRevenue->isEmpty())
                        <p class="text-muted text-center py-4">{{ __('No data yet.') }}</p>
                    @else
                        @foreach($categoryRevenue as $cat)
                            @php $pct = $maxCategoryRevenue > 0 ? round($cat->revenue / $maxCategoryRevenue * 100) : 0; @endphp
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-medium">{{ $cat->category }}</span>
                                    <span class="small text-muted">{{ number_format((float) $cat->revenue, 0) }} ₾</span>
                                </div>
                                <div class="progress" style="height: 8px; border-radius: 99px; background: var(--admin-surface-2);">
                                    <div class="progress-bar bg-primary" role="progressbar"
                                         style="width: {{ $pct }}%; border-radius: 99px;"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const isDark = document.body.classList.contains('theme-dark');
    const textColor   = isDark ? '#94a3b8' : '#64748b';
    const gridColor   = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.05)';
    const surfaceColor = isDark ? '#1e293b' : '#ffffff';

    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
    Chart.defaults.color = textColor;

    // ---- Revenue Trend ----
    const trendCtx = document.getElementById('revenueTrendChart');
    if (trendCtx) {
        const monthlyData = @json($monthlySales);
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: monthlyData.labels,
                datasets: [
                    {
                        label: '{{ __("Revenue") }}',
                        data: monthlyData.revenue,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16,185,129,0.08)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 4,
                        tension: 0.4,
                        fill: true,
                        yAxisID: 'yRevenue',
                    },
                    {
                        label: '{{ __("Orders") }}',
                        data: monthlyData.orders,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.07)',
                        borderWidth: 2,
                        pointBackgroundColor: '#6366f1',
                        pointRadius: 3,
                        tension: 0.4,
                        fill: false,
                        yAxisID: 'yOrders',
                    },
                ],
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } },
                    tooltip: {
                        backgroundColor: surfaceColor,
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                    },
                },
                scales: {
                    x: { grid: { color: gridColor } },
                    yRevenue: {
                        position: 'left',
                        grid: { color: gridColor },
                        ticks: { callback: v => v.toLocaleString() + ' ₾' },
                    },
                    yOrders: {
                        position: 'right',
                        grid: { drawOnChartArea: false },
                    },
                },
            },
        });
    }

    // ---- Top Products Bar ----
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx) {
        const topData = @json($topProducts);
        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topData.map(p => p.product_name),
                datasets: [{
                    label: '{{ __("Units Sold") }}',
                    data: topData.map(p => p.total_qty),
                    backgroundColor: 'rgba(99,102,241,0.7)',
                    borderColor: '#6366f1',
                    borderWidth: 1,
                    borderRadius: 6,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: surfaceColor,
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                    },
                },
                scales: {
                    x: { grid: { color: gridColor } },
                    y: { grid: { display: false } },
                },
            },
        });
    }

    // ---- Orders by Status Doughnut ----
    const donutCtx = document.getElementById('statusDonutChart');
    if (donutCtx) {
        const statusData = @json($ordersByStatus);
        const statusColors = {
            pending_payment: '#f59e0b',
            paid:       '#2563eb',
            processing: '#0ea5e9',
            completed:  '#10b981',
            cancelled:  '#ef4444',
        };
        const labelsMap = {
            pending_payment: '{{ __("Pending Payment") }}',
            paid: '{{ __("Paid") }}',
            processing: '{{ __("Processing") }}',
            completed: '{{ __("Completed") }}',
            cancelled: '{{ __("Cancelled") }}',
        };
        const labels = Object.keys(statusData).map(key => labelsMap[key] || key);
        const values = Object.values(statusData);
        const colors = Object.keys(statusData).map(key => statusColors[key] || '#6366f1');

        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.map(c => c + 'cc'),
                    borderColor: colors,
                    borderWidth: 2,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: surfaceColor,
                        titleColor: isDark ? '#f1f5f9' : '#0f172a',
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                    },
                },
            },
        });
    }
})();
</script>
@endpush
