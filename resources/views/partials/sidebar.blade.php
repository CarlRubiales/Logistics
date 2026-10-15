<aside class="sidebar" id="sidebar">
    @if(auth()->check() && auth()->user()->role === 'rider')
        @php
            $riderProfile = auth()->user()->riderProfile;
            $isOnline = $riderProfile && $riderProfile->availability === 'online';
        @endphp
        <div class="brand-lockup">
            <div class="brand-mark"><span></span><span></span><span></span></div>
            <div>
                <div class="brand-name">LUMORA</div>
                <div class="brand-subtitle">RIDER DESK</div>
            </div>
        </div>
        <div class="workspace-switcher">
            <div class="workspace-icon" style="background: linear-gradient(145deg, #E2703A, #B96562); color: #FFFDFB;">R</div>
            <div class="workspace-copy">
                <span>Portal</span>
                <strong>Rider Courier Hub</strong>
            </div>
        </div>
        <nav class="side-nav" aria-label="Rider navigation">
            <div class="nav-label">Deliveries</div>
            <a class="nav-item {{ request()->routeIs('riders.dashboard*') ? 'active' : '' }}" href="{{ route('riders.dashboard') }}">
                <span class="nav-icon">⛟</span>
                <span>My Deliveries & Scanner</span>
            </a>
            <a class="nav-item {{ request()->routeIs('riders.messages*') ? 'active' : '' }}" href="{{ route('riders.messages') }}">
                <span class="nav-icon">✉</span>
                <span>Messages & Support</span>
            </a>
            <a class="nav-item {{ request()->routeIs('tracking.*') ? 'active' : '' }}" href="{{ route('tracking.index') }}">
                <span class="nav-icon">◈</span>
                <span>Track Parcel / Barcode</span>
            </a>
        </nav>
        <div class="sidebar-bottom">
            <div class="health-card" style="background: {{ $isOnline ? 'rgba(30, 107, 55, .2)' : 'rgba(138, 123, 126, .2)' }};">
                <div class="health-orb" style="background: {{ $isOnline ? 'rgba(30, 107, 55, .4)' : 'rgba(138, 123, 126, .3)' }};">
                    <span style="background: {{ $isOnline ? '#76c58e' : '#8A7B7E' }};"></span>
                </div>
                <div>
                    <strong style="color: {{ $isOnline ? '#bfe3ca' : '#d2c7c9' }};">{{ $isOnline ? 'Online · Accepting' : 'Offline' }}</strong>
                    <small style="color: {{ $isOnline ? '#9ed2ad' : '#b1a6a8' }};">Duty status</small>
                </div>
                <form action="{{ route('riders.availability') }}" method="POST" style="margin-left: auto;">
                    @csrf
                    <button type="submit" style="background: rgba(255,255,255,0.18); border: 0; color: #FFFDFB; border-radius: 6px; padding: 3px 8px; font-size: 10px; font-weight: 700; cursor: pointer;">
                        {{ $isOnline ? 'Go offline' : 'Go online' }}
                    </button>
                </form>
            </div>
            <div class="profile-row">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'R', 0, 2)) }}</div>
                <div class="profile-copy">
                    <strong>{{ auth()->user()->name ?? 'Rider' }}</strong>
                    <small>Delivery Partner</small>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" class="logout-form">
                @csrf
                <button type="submit">↪&nbsp; Sign out</button>
            </form>
        </div>
    @else
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
    @endif
</aside>
