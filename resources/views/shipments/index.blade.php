<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Shipments — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', ['category' => 'Fulfillment', 'title' => 'Shipments'])
        <div class="content-wrap shipments-page">
            <section class="welcome-row shipments-page-heading"><div><div class="eyebrow">FULFILLMENT OPERATIONS</div><h1>Shipments</h1><p class="intro">Track every parcel from packed to delivered in one calm workspace.</p></div></section>
            @include('partials.dashboard_date_selector')
            <section class="order-stat-grid shipment-stat-grid"><div class="order-stat"><span>All Shipments</span><strong>{{ $stats['all'] }}</strong></div><div class="order-stat processing"><span>Pending</span><strong>{{ $stats['pending'] }}</strong></div><div class="order-stat paid"><span>In Transit</span><strong>{{ $stats['in_transit'] }}</strong></div><div class="order-stat sage-stat"><span>Delivered</span><strong>{{ $stats['delivered'] }}</strong></div></section>
            <section class="panel shipments-panel orders-panel">
                <div class="orders-toolbar">
                    <div class="filter-tabs">
                        <a class="filter-tab {{ $status === '' ? 'active' : '' }}" href="{{ route('shipments.index', array_filter(['date' => $selectedDateStr, 'search' => $search])) }}">All <span>{{ $stats['all'] }}</span></a>
                        <a class="filter-tab {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('shipments.index', array_filter(['status' => 'pending', 'date' => $selectedDateStr, 'search' => $search])) }}">Pending <span>{{ $stats['pending'] }}</span></a>
                        <a class="filter-tab {{ $status === 'in_transit' ? 'active' : '' }}" href="{{ route('shipments.index', array_filter(['status' => 'in_transit', 'date' => $selectedDateStr, 'search' => $search])) }}">In Transit <span>{{ $stats['in_transit'] }}</span></a>
                        <a class="filter-tab {{ $status === 'delivered' ? 'active' : '' }}" href="{{ route('shipments.index', array_filter(['status' => 'delivered', 'date' => $selectedDateStr, 'search' => $search])) }}">Delivered <span>{{ $stats['delivered'] }}</span></a>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <form method="GET" action="{{ route('shipments.index') }}" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                            <input type="hidden" name="date" value="{{ $selectedDateStr }}">
                            @if($status)
                                <input type="hidden" name="status" value="{{ $status }}">
                            @endif
                            <div class="search-field" style="width: 220px; height: 34px;">
                                <span>⌕</span>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Search shipments..." style="font-size: 11px;">
                            </div>
                            @if($search)
                                <a href="{{ route('shipments.index', array_filter(['date' => $selectedDateStr, 'status' => $status])) }}" style="font-size: 11px; color: #E2703A; text-decoration: none; font-weight: 600;">Clear</a>
                            @endif
                        </form>
                        <span class="results-label">{{ $shipments->total() }} results</span>
                    </div>
                </div>
                <div class="table-wrap"><table class="shipments-table orders-table"><thead><tr><th>Track ID</th><th>Recipient</th><th>Carrier</th><th>Destination</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>@forelse ($shipments as $shipment)<tr><td><a class="shipment-id" href="{{ route('shipments.show', $shipment) }}">{{ $shipment->lumora_tracking_id }}</a><small>{{ $shipment->service_level ?: 'Standard service' }}</small></td><td><div class="recipient"><span class="table-avatar {{ ['blush', 'sage', 'gold', 'plum'][$loop->index % 4] }}">{{ strtoupper(substr($shipment->order?->customer_name ?? 'NA', 0, 2)) }}</span><span>{{ $shipment->order?->customer_name ?? 'Unknown customer' }}</span></div></td><td>{{ $shipment->carrier_display }}</td><td>{{ $shipment->destination_city ?: '—' }}{{ $shipment->destination_state ? ', '.$shipment->destination_state : '' }}</td><td><span class="status-chip shipment-status {{ str_replace('_', '-', $shipment->status) }}"><b></b>{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span></td><td>{{ $shipment->updated_at?->format('M j, Y') }}<small>{{ $shipment->updated_at?->format('g:i A') }}</small></td><td><a class="row-menu order-open" href="{{ route('shipments.show', $shipment) }}" aria-label="Open shipment">→</a></td></tr>@empty<tr><td colspan="7" class="empty-state">No shipments match your search or filter.</td></tr>@endforelse</tbody></table></div>
                <div class="table-footer"><span>Showing <strong>{{ $shipments->firstItem() ?? 0 }}–{{ $shipments->lastItem() ?? 0 }}</strong> of <strong>{{ $shipments->total() }} shipments</strong></span><div class="orders-pagination">{{ $shipments->onEachSide(1)->links() }}</div></div>
            </section>
            <footer class="page-footer"><span>© 2024 Lumora Logistics</span><span><span>Lumora Logistics</span><span>Designed for calm operations.</span></span></footer>
        </div>
    </main>
</div>
<script>
(function() {
    let lastUpdate = null;
    const pollInterval = 5000;

    async function pollShipments() {
        const active = document.activeElement;
        const isInteracting = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT');
        if (isInteracting) return;

        try {
            const url = new URL(window.location.href);
            url.searchParams.set('format', 'json');
            const res = await fetch(url.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data && data.latest_update) {
                if (lastUpdate && lastUpdate !== data.latest_update) {
                    window.location.reload();
                }
                lastUpdate = data.latest_update;
            }
        } catch (e) {}
    }

    setInterval(pollShipments, pollInterval);
    pollShipments();
})();
</script>
</body>
</html>
