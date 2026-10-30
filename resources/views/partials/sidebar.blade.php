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
            
            <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                <span class="nav-icon">⚙</span>
                <span>Settings</span>
            </a>

            <!-- Rider White Pill Matching Toolbar Exactly -->
            <div class="sidebar-user-pill" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 12px 6px 6px; border-radius: 999px; background: #FFFDFB; border: 1px solid #EBDDD8; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16); margin-top: 14px;">
                <div class="sidebar-user-pill-left" style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <div class="sidebar-pill-avatar" style="width: 34px; height: 34px; min-width: 34px; border-radius: 50%; color: #FFFDFB; font-weight: 800; font-size: 11px; display: grid; place-items: center; flex-shrink: 0; background: linear-gradient(135deg, #E2703A, #B96562); box-shadow: 0 2px 5px rgba(0, 0, 0, 0.18);">
                        {{ strtoupper(substr(auth()->user()->name ?? 'R', 0, 2)) }}
                    </div>
                    <div class="sidebar-pill-info" style="display: flex; flex-direction: column; min-width: 0; line-height: 1.2;">
                        <span class="sidebar-pill-name" title="{{ auth()->user()->name }}" style="font-size: 12.5px; font-weight: 800; color: #3D1B3D; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase;">{{ strtoupper(auth()->user()->name ?? 'Rider') }}</span>
                        <span class="sidebar-pill-role" style="font-size: 9.5px; font-weight: 700; color: #8A7B7E; text-transform: uppercase; letter-spacing: 0.4px;">DELIVERY PARTNER</span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0; padding: 0; display: inline-flex;">
                    @csrf
                    <button type="submit" class="sidebar-pill-logout" title="Sign out" style="width: 28px; height: 28px; border-radius: 50%; border: 1px solid rgba(198, 40, 40, 0.2); background: rgba(198, 40, 40, 0.08); color: #C62828; display: grid; place-items: center; cursor: pointer; flex-shrink: 0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </form>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin-top: 8px;">
                @csrf
                <button type="submit" class="sidebar-logout-btn" style="display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; height: 32px; border: 1px solid rgba(255, 255, 255, 0.14); background: rgba(255, 255, 255, 0.08); border-radius: 8px; color: rgba(255, 253, 251, 0.9); font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.18s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    @else
        <div class="brand-lockup"><div class="brand-mark"><span></span><span></span><span></span></div><div><div class="brand-name">LUMORA</div><div class="brand-subtitle">LOGISTICS</div></div></div>
        <div class="workspace-switcher">
            <div class="workspace-icon" style="{{ auth()->user()->isMasterAdmin() ? 'background: linear-gradient(145deg, #3D1B3D, #793779); color: #FFFDFB;' : '' }}">{{ auth()->user()->isMasterAdmin() ? 'HQ' : strtoupper(substr(auth()->user()->getBranchName() ?? auth()->user()->name ?? 'L', 0, 1)) }}</div>
            <div class="workspace-copy">
                <span>{{ auth()->user()->isMasterAdmin() ? 'Central Command' : 'Branch Workspace' }}</span>
                <strong>{{ auth()->user()->isMasterAdmin() ? 'Headquarters (All Hubs)' : (auth()->user()->getBranchName() ? (auth()->user()->getBranchName() . ' Hub') : (auth()->user()->name ?? 'Lumora')) }}</strong>
            </div>
        </div>
        <nav class="side-nav" aria-label="Primary navigation">
            @if(auth()->user()->isMasterAdmin())
                <div class="nav-label">Master Administration</div>
                <a class="nav-item {{ request()->routeIs('admin.dashboard*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="nav-icon">🏢</span>
                    <span>Headquarters</span>
                </a>
                <a class="nav-item {{ request()->routeIs('admin.branches*') ? 'active' : '' }}" href="{{ route('admin.branches') }}">
                    <span class="nav-icon">📍</span>
                    <span>Branches</span>
                </a>
                <a class="nav-item {{ request()->routeIs('admin.riders*') ? 'active' : '' }}" href="{{ route('admin.riders') }}">
                    <span class="nav-icon">♙</span>
                    <span>Riders & Documents</span>
                </a>
                <a class="nav-item {{ request()->routeIs('admin.accounts*') ? 'active' : '' }}" href="{{ route('admin.accounts') }}">
                    <span class="nav-icon">👤</span>
                    <span>Create Account</span>
                </a>
                <div class="nav-label second">Communications & Audits</div>
                <a class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}">
                    <span class="nav-icon">✉</span>
                    <span>Messages</span>
                </a>
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                    <span class="nav-icon">▤</span>
                    <span>Reports</span>
                </a>
            @else
                <div class="nav-label">Workspace</div>
                <a class="nav-item {{ request()->routeIs('sorting-center.*') ? 'active' : '' }}" href="{{ route('sorting-center.index') }}"><span class="nav-icon">⌂</span><span>Sorting Center</span></a>
                <a class="nav-item {{ request()->routeIs('shipments.*') ? 'active' : '' }}" href="{{ route('shipments.index') }}"><span class="nav-icon">◈</span><span>Shipments</span></a>
                <div class="nav-label second">Manage</div>
                <a class="nav-item {{ request()->routeIs('riders.*') ? 'active' : '' }}" href="{{ route('riders.index') }}"><span class="nav-icon">♙</span><span>Riders</span></a>
                <a class="nav-item {{ request()->routeIs('areas.*') ? 'active' : '' }}" href="{{ route('areas.index') }}"><span class="nav-icon">⌖</span><span>Delivery Areas</span></a>
                <a class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}" href="{{ route('messages.index') }}"><span class="nav-icon">✉</span><span>Messages</span></a>
                <a class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><span class="nav-icon">▤</span><span>Reports</span></a>
            @endif
        </nav>
        <div class="sidebar-bottom">
            <div class="health-card">
                <div class="health-orb"><span></span></div>
                <div>
                    <strong>All systems healthy</strong>
                    <small>{{ auth()->user()->getBranchName() ? (auth()->user()->getBranchName() . ' Online') : 'HQ Central Connected' }}</small>
                </div>
                <span class="status-check">✓</span>
            </div>

            <!-- Settings strictly on sidebar -->
            <a class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                <span class="nav-icon">⚙</span>
                <span>Settings</span>
            </a>

            <!-- White User Pill matching the photo in media_1792254499508_1167562a.png -->
            <div class="sidebar-user-pill" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 12px 6px 6px; border-radius: 999px; background: #FFFDFB; border: 1px solid #EBDDD8; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.16); margin-top: 14px;">
                <div class="sidebar-user-pill-left" style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                    <div class="sidebar-pill-avatar" style="width: 34px; height: 34px; min-width: 34px; border-radius: 50%; color: #FFFDFB; font-weight: 800; font-size: 11px; display: grid; place-items: center; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.18); {{ auth()->user()->isMasterAdmin() ? 'background: linear-gradient(135deg, #3D1B3D, #793779);' : 'background: linear-gradient(135deg, #E2703A, #B96562);' }}">
                        {{ strtoupper(substr(auth()->user()->isMasterAdmin() ? 'HQ' : (auth()->user()->getBranchName() ?? auth()->user()->name), 0, 2)) }}
                    </div>
                    <div class="sidebar-pill-info" style="display: flex; flex-direction: column; min-width: 0; line-height: 1.2;">
                        <span class="sidebar-pill-name" title="{{ auth()->user()->name }}" style="font-size: 12.5px; font-weight: 800; color: #3D1B3D; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-transform: uppercase;">
                            {{ auth()->user()->getBranchName() ? strtoupper(auth()->user()->getBranchName()) : (auth()->user()->isMasterAdmin() ? 'CENTRAL HQ' : strtoupper(auth()->user()->name)) }}
                        </span>
                        <span class="sidebar-pill-role" style="font-size: 9.5px; font-weight: 700; color: #8A7B7E; text-transform: uppercase; letter-spacing: 0.4px;">
                            {{ auth()->user()->isMasterAdmin() ? 'SUPER ADMIN' : (auth()->user()->getBranchName() ? strtoupper(auth()->user()->getBranchName()) . ' ADMIN' : 'ADMIN') }}
                        </span>
                    </div>
                </div>

                <!-- Sign Out icon button inside the white pill -->
                <form action="{{ route('logout') }}" method="POST" style="margin: 0; padding: 0; display: inline-flex;">
                    @csrf
                    <button type="submit" class="sidebar-pill-logout" title="Sign out" style="width: 28px; height: 28px; border-radius: 50%; border: 1px solid rgba(198, 40, 40, 0.2); background: rgba(198, 40, 40, 0.08); color: #C62828; display: grid; place-items: center; cursor: pointer; flex-shrink: 0;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Sign Out Full Button on Sidebar -->
            <form action="{{ route('logout') }}" method="POST" style="margin-top: 8px;">
                @csrf
                <button type="submit" class="sidebar-logout-btn" style="display: flex; align-items: center; justify-content: center; gap: 7px; width: 100%; height: 32px; border: 1px solid rgba(255, 255, 255, 0.14); background: rgba(255, 255, 255, 0.08); border-radius: 8px; color: rgba(255, 253, 251, 0.9); font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.18s;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Sign out</span>
                </button>
            </form>
        </div>
    @endif
</aside>
