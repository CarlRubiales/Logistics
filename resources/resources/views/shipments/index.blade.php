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
    <aside class="sidebar" id="sidebar">
        <div class="brand-lockup"><div class="brand-mark"><span></span><span></span><span></span></div><div><div class="brand-name">LUMORA</div><div class="brand-subtitle">LOGISTICS</div></div></div>
        <div class="workspace-switcher"><div class="workspace-icon">L</div><div class="workspace-copy"><span>Workspace</span><strong>{{ $store?->name ?? 'Lumora Beauty' }}</strong></div><span class="chevron">⌄</span></div>
        <nav class="side-nav">
            <div class="nav-label">Workspace</div>
            <a class="nav-item" href="{{ route('dashboard') }}"><span class="nav-icon">⌂</span><span>Overview</span></a>
            <a class="nav-item active" href="{{ route('shipments.index') }}"><span class="nav-icon">◈</span><span>Shipments</span><span class="nav-pill">{{ $stats['all'] }}</span></a>
            <a class="nav-item" href="{{ route('orders.index') }}"><span class="nav-icon">▤</span><span>Orders</span></a>
            <a class="nav-item" href="{{ route('dashboard') }}#inventory"><span class="nav-icon">▦</span><span>Inventory</span></a>
            <div class="nav-label second">Manage</div>
            <a class="nav-item" href="{{ route('dashboard') }}#exceptions"><span class="nav-icon">!</span><span>Exceptions</span><span class="alert-dot"></span></a>
            <a class="nav-item" href="{{ route('dashboard') }}#locations"><span class="nav-icon">⌖</span><span>Locations</span></a>
            <a class="nav-item" href="{{ route('dashboard') }}#integrations"><span class="nav-icon">ϟ</span><span>Integrations</span></a>
        </nav>
        <div class="sidebar-bottom">
            <div class="health-card"><div class="health-orb"><span></span></div><div><strong>All systems healthy</strong><small>Last checked 2m ago</small></div><span class="status-check">✓</span></div>
            <div class="profile-row"><div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div><div class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ str_replace('_', ' ', ucfirst(auth()->user()->role ?? 'admin')) }}</small></div></div>
            <form action="{{ route('logout') }}" method="POST" class="logout-form">@csrf<button type="submit">↪&nbsp; Sign out</button></form>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-mobile-brand"><div class="brand-mark"><span></span><span></span><span></span></div><strong>LUMORA</strong></div>
            <button class="mobile-menu" id="mobile-menu">☰</button>
            <div class="breadcrumb"><a href="{{ route('dashboard') }}">Workspace</a><span class="crumb-divider">/</span><strong>Shipments</strong></div>
            <div class="topbar-actions">
                <form class="orders-search topbar-search" method="GET" action="{{ route('shipments.index') }}"><span>⌕</span><input name="search" value="{{ $search }}" type="search" placeholder="Search shipments..." aria-label="Search shipments"><input type="hidden" name="status" value="{{ $status }}"></form>
                <button class="icon-button" title="Notifications" aria-label="Notifications"><span class="notification-ring"></span><span class="notification-dot"></span></button>
                <button class="help-button">?</button>
                <button class="user-button"><span class="avatar small">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><span class="user-name">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span><span class="chevron">⌄</span></button>
            </div>
        </header>
        <div class="content-wrap shipments-page">
            <section class="welcome-row shipments-page-heading"><div><div class="eyebrow">FULFILLMENT OPERATIONS</div><h1>Shipments</h1><p class="intro">Track every parcel from packed to delivered in one calm workspace.</p></div></section>
            <section class="order-stat-grid shipment-stat-grid"><div class="order-stat"><span>All shipments</span><strong>{{ $stats['all'] }}</strong></div><div class="order-stat processing"><span>To be packed</span><strong>{{ $stats['pending'] }}</strong></div><div class="order-stat paid"><span>In transit</span><strong>{{ $stats['in_transit'] }}</strong></div><div class="order-stat sage-stat"><span>Delivered</span><strong>{{ $stats['delivered'] }}</strong></div></section>
            <section class="panel shipments-panel orders-panel">
                <div class="orders-toolbar"><div class="filter-tabs"><a class="filter-tab {{ $status === '' ? 'active' : '' }}" href="{{ route('shipments.index', ['search' => $search]) }}">All <span>{{ $stats['all'] }}</span></a><a class="filter-tab {{ $status === 'pending' ? 'active' : '' }}" href="{{ route('shipments.index', ['status' => 'pending', 'search' => $search]) }}">Pending <span>{{ $stats['pending'] }}</span></a><a class="filter-tab {{ $status === 'in_transit' ? 'active' : '' }}" href="{{ route('shipments.index', ['status' => 'in_transit', 'search' => $search]) }}">In transit <span>{{ $stats['in_transit'] }}</span></a><a class="filter-tab {{ $status === 'delivered' ? 'active' : '' }}" href="{{ route('shipments.index', ['status' => 'delivered', 'search' => $search]) }}">Delivered <span>{{ $stats['delivered'] }}</span></a><a class="filter-tab {{ $status === 'exception' ? 'active' : '' }}" href="{{ route('shipments.index', ['status' => 'exception', 'search' => $search]) }}">Exceptions <span>{{ $stats['exceptions'] }}</span></a></div><span class="results-label">{{ $shipments->total() }} results</span></div>
                <div class="table-wrap"><table class="shipments-table orders-table"><thead><tr><th>Shipment</th><th>Order</th><th>Recipient</th><th>Carrier</th><th>Destination</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>@forelse ($shipments as $shipment)<tr><td><a class="shipment-id" href="{{ route('shipments.show', $shipment) }}">{{ $shipment->tracking_number ?: ($shipment->external_id ?: 'SH-'.$shipment->id) }}</a><small>{{ $shipment->service_level ?: 'Standard service' }}</small></td><td><a class="order-open" href="{{ route('orders.show', $shipment->order) }}">{{ $shipment->order?->order_number ?: $shipment->order?->external_id }}</a></td><td><div class="recipient"><span class="table-avatar {{ ['blush', 'sage', 'gold', 'plum'][$loop->index % 4] }}">{{ strtoupper(substr($shipment->order?->customer_name ?? 'NA', 0, 2)) }}</span><span>{{ $shipment->order?->customer_name ?? 'Unknown customer' }}</span></div></td><td>{{ $shipment->carrier ?: 'Unassigned' }}</td><td>{{ $shipment->destination_city ?: '—' }}{{ $shipment->destination_state ? ', '.$shipment->destination_state : '' }}</td><td><span class="status-chip shipment-status {{ str_replace('_', '-', $shipment->status) }}"><b></b>{{ ucwords(str_replace('_', ' ', $shipment->status)) }}</span></td><td>{{ $shipment->updated_at?->format('M j, Y') }}<small>{{ $shipment->updated_at?->format('g:i A') }}</small></td><td><a class="row-menu order-open" href="{{ route('shipments.show', $shipment) }}" aria-label="Open shipment">→</a></td></tr>@empty<tr><td colspan="8" class="empty-state">No shipments match your search or filter.</td></tr>@endforelse</tbody></table></div>
                <div class="table-footer"><span>Showing <strong>{{ $shipments->firstItem() ?? 0 }}–{{ $shipments->lastItem() ?? 0 }}</strong> of <strong>{{ $shipments->total() }} shipments</strong></span><div class="orders-pagination">{{ $shipments->onEachSide(1)->links() }}</div></div>
            </section>
            <footer class="page-footer"><span>© 2024 Lumora Logistics</span><span><span>Connected to Lumora Beauty</span><span>Designed for calm operations.</span></span></footer>
        </div>
    </main>
</div>
</body>
</html>
