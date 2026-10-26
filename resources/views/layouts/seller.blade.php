<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumora Seller | @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#FBF1EC; --card-bg:#FFFFFF; --sidebar-bg:#FFFFFF; --maroon:#5B1A35; --maroon-dark:#45132A; --coral:#E8674A; --terra-1:#D98A5E; --terra-2:#B85C3B; --sage-1:#7C9473; --sage-2:#5C7355; --text-dark:#2B1C22; --text-muted:#8B7A80; --border:#F0E2DA; --radius:18px; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Poppins',sans-serif; background:var(--bg); color:var(--text-dark); display:flex; min-height:100vh; }
        .sidebar { width:250px; background:var(--sidebar-bg); border-right:1px solid var(--border); padding:30px 22px; display:flex; flex-direction:column; gap:6px; position:fixed; top:0; left:0; height:100vh; overflow-y:auto; }
        .nav-link { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:11px 14px; border-radius:12px; color:var(--text-muted); text-decoration:none; font-size:14.5px; font-weight:500; transition:all .18s ease; }
        .nav-content { display:flex; align-items:center; gap:11px; min-width:0; }
        .nav-icon { width:19px; height:19px; flex:0 0 19px; color:currentColor; }
        .nav-icon svg { display:block; width:100%; height:100%; }
        .nav-link:hover { background:var(--bg); color:var(--maroon); }
        .nav-link.active { background:var(--maroon); color:#fff; font-weight:600; }
        .nav-badge { background:var(--coral); color:#fff; font-size:11.5px; font-weight:700; border-radius:999px; padding:1px 8px; }

        .sidebar-footer { margin-top:auto; display:flex; flex-direction:column; gap:10px; }
        .switch-account-btn { width:100%; display:flex; align-items:center; justify-content:center; gap:7px; padding:11px 6px; border:1.5px solid var(--maroon); border-radius:12px; background:#fff; color:var(--maroon); font-family:inherit; font-size:12px; line-height:1.2; font-weight:600; white-space:nowrap; cursor:pointer; transition:all .18s ease; }
        .switch-account-btn:hover { background:var(--maroon); color:#fff; }
        .switch-icon { width:19px; height:19px; flex:0 0 19px; }
        .switch-icon svg { width:100%; height:100%; display:block; }
        .logout-btn { width:100%; padding:11px 14px; border-radius:12px; border:1px solid var(--border); background:transparent; color:var(--text-muted); font-weight:500; font-size:14px; cursor:pointer; transition:all .18s ease; font-family:inherit; }
        .logout-btn:hover { border-color:var(--coral); color:var(--coral); }

        .main { flex:1; min-width:0; padding:42px 48px; margin-left:250px; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
        .topbar h1 { font-size:28px; font-weight:800; }
        .topbar-right { display:flex; align-items:center; gap:14px; }
        .seller-pill { background:#fff; border:1px solid var(--border); padding:8px 16px; border-radius:999px; font-size:13.5px; font-weight:600; color:var(--maroon); }
        .bell-wrap { position:relative; }
        .bell-btn { width:40px; height:40px; border-radius:50%; background:#fff; border:1px solid var(--border); display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:18px; color:var(--maroon); position:relative; }
        .bell-badge { position:absolute; top:-4px; right:-4px; background:var(--coral); color:#fff; font-size:10.5px; font-weight:700; border-radius:999px; min-width:18px; height:18px; display:none; align-items:center; justify-content:center; padding:0 4px; }
        .bell-dropdown { display:none; position:absolute; top:48px; right:0; width:320px; background:#fff; border:1px solid var(--border); border-radius:14px; box-shadow:0 8px 24px rgba(91,26,53,.12); z-index:100; overflow:hidden; }
        .bell-dropdown-header { padding:14px 16px; font-size:13.5px; font-weight:700; border-bottom:1px solid var(--border); }
        .bell-dropdown-item { padding:12px 16px; border-bottom:1px solid var(--border); text-decoration:none; color:var(--text-dark); display:block; }
        .bell-dropdown-item:hover { background:var(--bg); }
        .bell-dropdown-item .order-id { font-weight:700; font-size:13.5px; }
        .bell-dropdown-item .order-meta { font-size:12px; color:var(--text-muted); margin-top:2px; }
        .bell-dropdown-empty { padding:20px 16px; font-size:13px; color:var(--text-muted); text-align:center; }
        .stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:22px; margin-bottom:22px; }
        .stat-card { background:var(--card-bg); border-radius:var(--radius); padding:24px; display:flex; flex-direction:column; gap:14px; border:1px solid var(--border); box-shadow:0 4px 18px rgba(91,26,53,.04); }
        .stat-icon { width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; }
        .icon-sales { background:linear-gradient(135deg,var(--terra-1),var(--terra-2)); }
        .icon-orders { background:linear-gradient(135deg,var(--maroon),var(--maroon-dark)); }
        .icon-products { background:linear-gradient(135deg,var(--sage-1),var(--sage-2)); }
        .stat-label { font-size:13.5px; color:var(--text-muted); font-weight:500; }
        .stat-value { font-size:26px; font-weight:800; }
        .panel { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border); padding:24px; box-shadow:0 4px 18px rgba(91,26,53,.04); }
        .panel h2 { font-size:18px; font-weight:800; margin-bottom:16px; }
        table { width:100%; border-collapse:collapse; }
        th, td { text-align:left; padding:10px 8px; border-bottom:1px solid var(--border); font-size:.9rem; }
        th { color:var(--text-muted); font-weight:600; }
        tr:hover td { background-color:var(--bg); }
        .stock-badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:.75rem; color:#fff; background:var(--terra-2); }

        /* Products page additions */
        .products-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:22px; }
        .product-tile { background:var(--card-bg); border-radius:var(--radius); border:1px solid var(--border); overflow:hidden; box-shadow:0 4px 18px rgba(91,26,53,.04); display:flex; flex-direction:column; }
        .add-product-tile { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:12px; background:transparent; border:2px dashed var(--border); border-radius:var(--radius); height:230px; text-decoration:none; color:var(--text-muted); transition:all .18s ease; }
        .add-product-tile:hover { border-color:var(--maroon); color:var(--maroon); background:rgba(91,26,53,.03); }
        .add-icon { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,var(--maroon),var(--coral)); color:#fff; font-size:26px; font-weight:700; display:flex; align-items:center; justify-content:center; }
        .add-label { font-size:14.5px; font-weight:600; }
        .product-image { height:130px; background:var(--bg); display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size:13px; overflow:hidden; }
        .product-image img { width:100%; height:100%; object-fit:cover; }
        .product-body { padding:16px; display:flex; flex-direction:column; gap:6px; flex:1; }
        .product-name { font-size:15px; font-weight:700; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .product-price { font-size:13.5px; color:var(--text-muted); font-weight:500; }
        .product-stock { font-size:13.5px; margin-top:2px; }
        .product-stock .count { font-weight:700; }
        .product-stock .low { color:var(--coral); }
        .stock-form { margin-top:auto; padding-top:10px; display:flex; gap:8px; }
        .stock-input { width:60px; border:1px solid var(--border); border-radius:8px; padding:6px 8px; font-size:13px; font-family:inherit; }
        .edit-stock-btn { flex:1; background:var(--maroon); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:600; padding:8px 10px; cursor:pointer; font-family:inherit; transition:background .18s ease; }
        .edit-stock-btn:hover { background:var(--maroon-dark); }
        .alert-success { background:#eaf3e8; color:var(--sage-2); border:1px solid var(--sage-1); padding:12px 16px; border-radius:12px; font-size:13.5px; margin-bottom:20px; }
        @media (max-width:1100px) { .products-grid { grid-template-columns:repeat(2,1fr); } .main { padding-left:32px; padding-right:32px; } }
        @media (max-width:900px) { .stats-grid { grid-template-columns:1fr; } }
        @media (max-width:600px) { .products-grid { grid-template-columns:1fr; } .sidebar { width:190px; padding-left:14px; padding-right:14px; } .main { margin-left:190px; padding:28px 18px; } .switch-account-btn { white-space:normal; } }
        /* Payouts page additions */
        .alert-error { background:#fbeaea; color:#a33; border:1px solid #e0a5a5; padding:12px 16px; border-radius:12px; font-size:13.5px; margin-bottom:20px; }
        .earnings-actions { display:flex; gap:12px; margin:22px 0; }
        .btn-outline { padding:11px 20px; border-radius:12px; border:1px solid var(--maroon); background:transparent; color:var(--maroon); font-weight:600; font-size:14px; cursor:pointer; font-family:inherit; transition:all .18s ease; }
        .btn-outline:hover { background:var(--maroon); color:#fff; }
        .btn-solid { padding:11px 20px; border-radius:12px; border:none; background:var(--maroon); color:#fff; font-weight:600; font-size:14px; cursor:pointer; font-family:inherit; transition:background .18s ease; }
        .btn-solid:hover { background:var(--maroon-dark); }
        .method-badge { display:inline-flex; align-items:center; gap:8px; padding:6px 14px; border-radius:999px; font-size:13px; font-weight:700; color:#fff; }
        .badge-gcash { background:#007DFE; }
        .badge-paymaya { background:#00B14F; }
        .badge-bank { background:#5B1A35; }
        .current-method-card { display:flex; align-items:center; justify-content:space-between; background:var(--card-bg); border:1px solid var(--border); border-radius:14px; padding:16px 20px; margin-bottom:22px; }
        .status-pill { display:inline-block; padding:4px 12px; border-radius:999px; font-size:12px; font-weight:700; }
        .status-pending { background:#fdf0dc; color:#a6701c; }
        .status-approved { background:#e2eef8; color:#2261a8; }
        .status-paid { background:#eaf3e8; color:#42713a; }
        .status-rejected { background:#fbeaea; color:#a33; }
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(43,28,34,.5); align-items:center; justify-content:center; z-index:50; }
        .modal-box { background:#fff; border-radius:18px; padding:28px; width:420px; max-width:90%; }
        .modal-box h2 { font-size:18px; font-weight:800; margin-bottom:18px; }
        .modal-form { display:flex; flex-direction:column; gap:12px; }
        .modal-form input, .modal-form select { border:1px solid var(--border); border-radius:8px; padding:10px 12px; font-size:14px; font-family:inherit; width:100%; }
        .modal-btn-row { display:flex; gap:10px; margin-top:8px; }
        .modal-btn-row button { flex:1; padding:10px; border-radius:8px; font-family:inherit; cursor:pointer; }
        .btn-cancel { border:1px solid var(--border); background:transparent; }
        /* ---------- final visual consistency layer ---------- */
        html { font-size: 15px; }
        body { -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility; }
        button, input, select, textarea { font-family: inherit; }
        .sidebar { z-index: 30; box-shadow: 4px 0 18px rgba(91,26,53,.025); }
        .nav-link { min-height: 42px; line-height: 1.25; }
        .sidebar-footer form { width: 100%; }
        .switch-account-btn, .logout-btn { min-height: 42px; }
        .main { max-width: 1680px; }
        .topbar { min-height: 42px; }
        .topbar h1 { margin: 0; line-height: 1.15; letter-spacing: -.02em; }
        .seller-pill { white-space: nowrap; line-height: 1.2; }
        .panel, .stat-card, .product-tile, .current-method-card, .modal-box { overflow: hidden; }
        .panel h2 { line-height: 1.25; }
        .btn-solid, .btn-outline, .edit-stock-btn, .search-button { min-height: 40px; line-height: 1.2; }
        table { table-layout: fixed; }
        th:last-child, td:last-child { white-space: nowrap; }
        .modal-overlay { padding: 20px; }
        @media (max-width: 900px) {
            .sidebar { width: 210px; padding-left: 16px; padding-right: 16px; }
            .main { margin-left: 210px; padding-left: 28px; padding-right: 28px; }
            .nav-link { font-size: 13.5px; }
            .seller-pill { max-width: 220px; overflow: hidden; text-overflow: ellipsis; }
        }
        @media (max-width: 680px) {
            .sidebar { width: 76px; padding: 22px 10px; align-items: center; }
            .nav-link { width: 48px; justify-content: center; padding: 11px 8px; font-size: 0; }
            .nav-link::before { content: '•'; font-size: 18px; color: currentColor; }
            .nav-badge { display: none !important; }
            .sidebar-footer { width: 100%; }
            .switch-account-btn, .logout-btn { width: 48px; padding: 10px 4px; font-size: 0; }
            .switch-account-btn span:first-child { font-size: 16px; }
            .logout-btn::before { content: '↪'; font-size: 18px; }
            .main { margin-left: 76px; padding: 24px 16px; }
            .topbar { align-items: flex-start; }
            .topbar-right { gap: 8px; }
            .seller-pill { display: none; }
        }
    </style>
    @stack('styles')
</head>
<body>
<aside class="sidebar">
    <x-logo class="mb-9 max-[680px]:origin-center max-[680px]:scale-[0.45]" />
    <a href="{{ route('seller.dashboard') }}" class="nav-link {{ request()->routeIs('seller.dashboard') ? 'active' : '' }}"><span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span><span>Dashboard</span></span></a>
    <a href="{{ route('seller.products.index') }}" class="nav-link {{ request()->routeIs('seller.products.*') ? 'active' : '' }}"><span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z"/><path d="m4.5 7.5 7.5 4 7.5-4M12 12v9"/></svg></span><span>My Products</span></span></a>
    <a href="{{ route('seller.orders.index') }}" class="nav-link {{ request()->routeIs('seller.orders.*') ? 'active' : '' }}">
        <span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 11h8M8 15h5"/></svg></span><span>Orders</span></span>
        <span class="nav-badge" id="sidebarOrdersBadge" style="display:none;"></span>
    </a>
    <a href="{{ route('seller.chats.index') }}" class="nav-link {{ request()->routeIs('seller.chats.*') ? 'active' : '' }}">
        <span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg></span><span>Chats</span></span>
        @if ($sellerUnreadChatCount > 0)
            <span class="nav-badge">{{ $sellerUnreadChatCount }}</span>
        @endif
    </a>
    <a href="{{ route('seller.payouts.index') }}" class="nav-link {{ request()->routeIs('seller.payouts.*') ? 'active' : '' }}"><span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18M16 14h2"/></svg></span><span>Payouts / Earnings</span></span></a>
    <a href="{{ route('seller.profile.index') }}" class="nav-link {{ request()->routeIs('seller.profile.*') ? 'active' : '' }}"><span class="nav-content"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.7-3.4 3.1-5 7-5s6.3 1.6 7 5"/></svg></span><span>Profile / Settings</span></span></a>

    <div class="sidebar-footer">
        <form method="POST" action="{{ route('seller.switchToBuyer') }}">
            @csrf
            <button type="submit" class="switch-account-btn">
                <span class="switch-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0M16 15h5M18 13l3 2-3 2"/></svg></span>
                <span>Switch to Buyer Account</span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="logout-btn">Logout</button>
        </form>
    </div>
</aside>

<main class="main">
    <div class="topbar">
        <h1>@yield('title')</h1>
        <div class="topbar-right">
            <div class="bell-wrap">
                <button type="button" class="bell-btn" id="bellBtn" onclick="toggleBellDropdown()" aria-label="Order notifications">
                    🔔
                    <span class="bell-badge" id="bellBadge"></span>
                </button>
                <div class="bell-dropdown" id="bellDropdown">
                    <div class="bell-dropdown-header">New orders</div>
                    <div id="bellDropdownList"></div>
                </div>
            </div>
            <span class="seller-pill">Seller &mdash; {{ Auth::user()->name }}</span>
        </div>
    </div>

    @yield('content')
</main>

<script>
function toggleBellDropdown() {
    const dd = document.getElementById('bellDropdown');
    dd.style.display = dd.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function (e) {
    const wrap = document.querySelector('.bell-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('bellDropdown').style.display = 'none';
    }
});

function loadOrderNotifications() {
    fetch('{{ route('seller.orders.notifications') }}')
        .then(res => res.json())
        .then(data => {
            const bellBadge = document.getElementById('bellBadge');
            const sidebarBadge = document.getElementById('sidebarOrdersBadge');
            const list = document.getElementById('bellDropdownList');

            if (data.count > 0) {
                bellBadge.textContent = data.count;
                bellBadge.style.display = 'flex';
                sidebarBadge.textContent = data.count;
                sidebarBadge.style.display = 'inline-block';
            } else {
                bellBadge.style.display = 'none';
                sidebarBadge.style.display = 'none';
            }

            if (data.orders.length === 0) {
                list.innerHTML = '<div class="bell-dropdown-empty">No new orders.</div>';
                return;
            }

            list.innerHTML = data.orders.map(o => `
                <a href="/seller/orders/${o.order_id}" class="bell-dropdown-item">
                    <div class="order-id">Order #${o.order_id} — ${o.buyer_name}</div>
                    <div class="order-meta">PHP ${parseFloat(o.subtotal).toLocaleString(undefined, {minimumFractionDigits:2})}</div>
                </a>
            `).join('');
        })
        .catch(() => {});
}

loadOrderNotifications();
</script>
    @stack('scripts')
</body>
</html>
