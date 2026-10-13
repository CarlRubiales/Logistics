<aside class="sidebar" id="sidebar">
    <div class="brand-lockup"><div class="brand-mark"><span></span><span></span><span></span></div><div><div class="brand-name">LUMORA</div><div class="brand-subtitle">LOGISTICS</div></div></div>
    <div class="workspace-switcher"><div class="workspace-icon">L</div><div class="workspace-copy"><span>Workspace</span><strong>Lumora Logistics</strong></div><span class="chevron">⌄</span></div>
    <nav class="side-nav" aria-label="Primary navigation">
        <div class="nav-label">Workspace</div>
        <a class="nav-item {{ request()->routeIs('dashboard', 'sorting-center.*') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-icon">⌂</span><span>Sorting Center</span></a>
        <a class="nav-item {{ request()->routeIs('shipments.*') ? 'active' : '' }}" href="{{ route('shipments.index') }}"><span class="nav-icon">◈</span><span>Shipments</span></a>
        <div class="nav-label second">Manage</div>
        <a class="nav-item {{ request()->routeIs('riders.*') ? 'active' : '' }}" href="{{ route('riders.index') }}"><span class="nav-icon">♙</span><span>Riders</span></a>
        <a class="nav-item {{ request()->routeIs('areas.*') ? 'active' : '' }}" href="{{ route('areas.index') }}"><span class="nav-icon">⌖</span><span>Delivery Areas</span></a>
        <a class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}"><span class="nav-icon">✉</span><span>Messages</span></a>
        <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-icon">▤</span><span>Reports</span></a>
    </nav>
    <div class="sidebar-bottom">
        <div class="health-card"><div class="health-orb"><span></span></div><div><strong>All systems healthy</strong><small>Last checked just now</small></div><span class="status-check">✓</span></div>
        <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}"><span class="nav-icon">⚙</span><span>Settings</span></a>
        <div class="profile-row"><div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}</div><div class="profile-copy"><strong>{{ auth()->user()->name ?? 'Admin' }}</strong><small>{{ str_replace('_', ' ', ucfirst(auth()->user()->role ?? 'admin')) }}</small></div></div>
        <form action="{{ route('logout') }}" method="POST" class="logout-form">@csrf<button type="submit">↪&nbsp; Sign out</button></form>
    </div>
</aside>
