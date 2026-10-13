<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Orders — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
    <div class="app-shell">
        @include('partials.sidebar')
        <main class="main-content">
            <header class="topbar"><div class="topbar-mobile-brand"><div class="brand-mark"><span></span><span></span><span></span></div><strong>LUMORA</strong></div><button class="mobile-menu" id="mobile-menu">☰</button><div class="breadcrumb"><a href="{{ route('dashboard') }}">Workspace</a><span class="crumb-divider">/</span><strong>Orders</strong></div><div class="topbar-actions"><a href="{{ route('dashboard') }}" class="ghost-button topbar-back">← Overview</a><form class="orders-search topbar-search" method="GET" action="{{ route('orders.index') }}"><span>⌕</span><input name="search" value="{{ $search }}" type="search" placeholder="Search orders..." aria-label="Search orders"><input type="hidden" name="status" value="{{ $status }}"></form><button class="icon-button" title="Notifications" aria-label="Notifications"><span class="notification-ring"></span><span class="notification-dot"></span></button><button class="help-button">?</button><button class="user-button"><span class="avatar small">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><span class="user-name">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span><span class="chevron">⌄</span></button></div></header>
            <div class="content-wrap orders-page">
                <section class="welcome-row orders-page-heading"><div><div class="eyebrow">STOREFRONT FULFILLMENT</div><h1>Orders</h1><p class="intro">Review every order, customer, and fulfillment handoff in one place.</p></div></section>
                <section class="order-stat-grid"><div class="order-stat"><span>All orders</span><strong>{{ $stats['all'] }}</strong></div><div class="order-stat paid"><span>Paid</span><strong>{{ $stats['paid'] }}</strong></div><div class="order-stat processing"><span>Processing</span><strong>{{ $stats['processing'] }}</strong></div><div class="order-stat cancelled"><span>Cancelled</span><strong>{{ $stats['cancelled'] }}</strong></div></section>
                <section class="panel orders-panel">
                    <div class="orders-toolbar"><div class="filter-tabs"><a class="filter-tab {{ $status === '' ? 'active' : '' }}" href="{{ route('orders.index', ['search' => $search]) }}">All <span>{{ $stats['all'] }}</span></a><a class="filter-tab {{ $status === 'paid' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => 'paid', 'search' => $search]) }}">Paid <span>{{ $stats['paid'] }}</span></a><a class="filter-tab {{ $status === 'processing' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => 'processing', 'search' => $search]) }}">Processing <span>{{ $stats['processing'] }}</span></a><a class="filter-tab {{ $status === 'cancelled' ? 'active' : '' }}" href="{{ route('orders.index', ['status' => 'cancelled', 'search' => $search]) }}">Cancelled <span>{{ $stats['cancelled'] }}</span></a></div><span class="results-label">{{ $orders->total() }} results</span></div>
                    <div class="table-wrap"><table class="shipments-table orders-table"><thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Ordered</th><th></th></tr></thead><tbody>@forelse ($orders as $order)<tr><td><a class="shipment-id" href="{{ route('orders.show', $order) }}">{{ $order->order_number ?: $order->external_id }}</a><small>{{ $order->external_id }}</small></td><td><div class="recipient"><span class="table-avatar {{ ['blush', 'sage', 'gold', 'plum'][$loop->index % 4] }}">{{ strtoupper(substr($order->customer_name, 0, 2)) }}</span><span>{{ $order->customer_name }}</span></div><small>{{ $order->customer_email ?: 'No email provided' }}</small></td><td>{{ $order->items->sum('quantity') }} item{{ $order->items->sum('quantity') === 1 ? '' : 's' }}</td><td><strong class="order-total">${{ number_format((float) $order->total, 2) }}</strong></td><td><span class="status-chip order-status {{ str_replace('_', '-', $order->status) }}"><b></b>{{ ucwords(str_replace('_', ' ', $order->status)) }}</span></td><td>{{ optional($order->ordered_at)->format('M j, Y') ?: $order->created_at->format('M j, Y') }}<small>{{ optional($order->ordered_at)->format('g:i A') ?: '' }}</small></td><td><a class="row-menu order-open" href="{{ route('orders.show', $order) }}" aria-label="Open order">→</a></td></tr>@empty<tr><td colspan="7" class="empty-state">No orders match your search or filter.</td></tr>@endforelse</tbody></table></div>
                    <div class="table-footer"><span>Showing <strong>{{ $orders->firstItem() ?: 0 }}–{{ $orders->lastItem() ?: 0 }}</strong> of <strong>{{ $orders->total() }} orders</strong></span><div class="orders-pagination">{{ $orders->onEachSide(1)->links() }}</div></div>
                </section>
                <footer class="page-footer"><span>© 2024 Lumora Logistics</span><span>Orders sync from your connected storefront.</span></footer>
            </div>
        </main>
    </div>
</body>
</html>
