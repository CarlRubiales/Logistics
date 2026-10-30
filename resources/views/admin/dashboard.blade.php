<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Master Logistics Headquarters — Lumora</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Master Administration',
            'title' => 'Headquarters Command'
        ])

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <!-- Executive Hero Banner -->
            <section class="dash-hero-banner">
                <div class="dash-hero-title">
                    <div class="eyebrow" style="color: #793779;">CENTRAL LOGISTICS COMMAND</div>
                    <h1>Master Network & Hub Governance</h1>
                    <p>
                        Real-time oversight across regional logistics hubs, courier compliance, branch data isolation, and fleet operations.
                    </p>
                </div>
                <div class="dash-quick-links">
                    <a href="{{ route('admin.branches', request()->has('date') ? ['date' => request('date')] : []) }}" class="dash-link-pill primary">
                        📍 Branches & Couriers
                    </a>
                    <a href="{{ route('admin.riders') }}" class="dash-link-pill secondary">
                        ♙ Review Documents
                    </a>
                    <a href="{{ route('admin.accounts') }}" class="dash-link-pill secondary">
                        + New Branch Account
                    </a>
                    <a href="{{ route('messages.index') }}" class="dash-link-pill secondary">
                        ✉ Branch Messages
                    </a>
                </div>
            </section>

            <!-- Dashboard Date Selector -->
            @include('partials.dashboard_date_selector')

            <!-- Elevated KPI Metric Cards -->
            <section class="metric-grid-elevated">
                <article class="metric-card-elevated plum">
                    <div class="metric-header">
                        <span class="metric-label-clean">Active Branches</span>
                        <div class="metric-icon-bubble" style="background: rgba(61, 27, 61, 0.08); color: #3D1B3D;">🏢</div>
                    </div>
                    <div class="metric-value-clean">{{ $hubSummaries->count() }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Regional operating hubs
                    </div>
                </article>

                <article class="metric-card-elevated orange">
                    <div class="metric-header">
                        <span class="metric-label-clean">Network Parcels</span>
                        <div class="metric-icon-bubble" style="background: rgba(226, 112, 58, 0.1); color: #E2703A;">📦</div>
                    </div>
                    <div class="metric-value-clean">{{ $totalParcels }}</div>
                    <div class="metric-sub-clean">
                        <span>↑</span> Total parcels tracked
                    </div>
                </article>

                <article class="metric-card-elevated blue">
                    <div class="metric-header">
                        <span class="metric-label-clean">In Transit Delivery</span>
                        <div class="metric-icon-bubble" style="background: rgba(24, 93, 143, 0.1); color: #185D8F;">⛟</div>
                    </div>
                    <div class="metric-value-clean">{{ $totalInTransit }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Out for dispatch / sorting
                    </div>
                </article>

                <article class="metric-card-elevated green">
                    <div class="metric-header">
                        <span class="metric-label-clean">Fulfilled & Delivered</span>
                        <div class="metric-icon-bubble" style="background: rgba(30, 107, 55, 0.1); color: #1E6B37;">✓</div>
                    </div>
                    <div class="metric-value-clean">{{ $totalDelivered }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Successfully delivered
                    </div>
                </article>

                <article class="metric-card-elevated plum">
                    <div class="metric-header">
                        <span class="metric-label-clean">Approved Couriers</span>
                        <div class="metric-icon-bubble" style="background: rgba(121, 55, 121, 0.1); color: #793779;">👥</div>
                    </div>
                    <div class="metric-value-clean">{{ $totalRiders }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Active across network
                    </div>
                </article>
            </section>

            <!-- Regional Hubs Overview Section -->
            <section class="panel" style="margin-bottom: 24px;">
                <div class="panel-heading">
                    <div>
                        <div class="section-kicker">REGIONAL HUBS MONITOR</div>
                        <h2>Branch Operations Status</h2>
                    </div>
                    <a href="{{ route('admin.branches') }}" class="secondary-button" style="text-decoration: none;">
                        Detailed Courier Coverage <span>→</span>
                    </a>
                </div>

                <div class="hub-directory-grid">
                    @foreach($hubSummaries as $hub)
                        @php
                            $deliveredPct = $hub['total_parcels'] > 0 
                                ? round(($hub['delivered'] / $hub['total_parcels']) * 100) 
                                : 0;
                        @endphp
                        <div class="hub-box-elevated">
                            <div>
                                <div class="hub-box-top">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="hub-badge-circle">
                                            {{ strtoupper(substr($hub['branch'], 0, 2)) }}
                                        </div>
                                        <div>
                                            <h3 class="hub-name-clean">{{ $hub['branch'] }} Hub</h3>
                                            <span class="hub-operator-clean">
                                                👤 {{ $hub['account']->name ?? 'Hub Admin' }} ({{ $hub['account']->email ?? 'No login' }})
                                            </span>
                                        </div>
                                    </div>
                                    <span class="badge-branch" style="background: #FAF6F0; border: 1px solid #EBDDD8;">
                                        📍 {{ $hub['branch'] }}
                                    </span>
                                </div>

                                <!-- 4-metric strip -->
                                <div class="hub-stats-row">
                                    <div class="hub-stat-item">
                                        <strong>{{ $hub['total_parcels'] }}</strong>
                                        <small>Parcels</small>
                                    </div>
                                    <div class="hub-stat-item">
                                        <strong style="color: #E2703A;">{{ $hub['in_transit'] }}</strong>
                                        <small>In Transit</small>
                                    </div>
                                    <div class="hub-stat-item">
                                        <strong style="color: #1E6B37;">{{ $hub['delivered'] }}</strong>
                                        <small>Delivered</small>
                                    </div>
                                    <div class="hub-stat-item">
                                        <strong style="color: #3D1B3D;">{{ $hub['riders_count'] }}</strong>
                                        <small>Couriers</small>
                                    </div>
                                </div>

                                <!-- Delivery Progress Bar -->
                                <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 2px;">
                                    <span style="color: #8A7B7E;">Fulfillment Rate</span>
                                    <strong style="color: #1E6B37;">{{ $deliveredPct }}%</strong>
                                </div>
                                <div class="hub-progress-bar">
                                    <div class="hub-progress-fill" style="width: {{ $deliveredPct }}%;"></div>
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #FAF6FC;">
                                <span style="font-size: 11px; color: #8A7B7E;">
                                    ⌖ {{ $hub['areas_count'] }} Coverage Areas
                                </span>
                                <div style="display: flex; gap: 6px;">
                                    <a href="{{ route('admin.branches') }}" class="secondary-button" style="padding: 3px 8px; font-size: 11px; height: auto; text-decoration: none;">
                                        Couriers ↗
                                    </a>
                                    <a href="{{ route('messages.index', ['contact_user_id' => $hub['account']?->id]) }}" class="primary-button" style="padding: 3px 10px; font-size: 11px; height: auto; text-decoration: none;">
                                        Message
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Quick Creation & Registered Accounts Grid -->
            <section class="dashboard-grid">
                <!-- Fast Provision Form -->
                <article class="panel">
                    <div class="panel-heading">
                        <div>
                            <div class="section-kicker">QUICK ACTION</div>
                            <h2>Provision New Branch Account</h2>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.users.store') }}" class="form-grid">
                        @csrf
                        <label>
                            Branch Hub Location
                            <input name="branch" value="{{ old('branch') }}" placeholder="e.g. Calamba, Los Baños, San Pablo" required>
                        </label>
                        <label>
                            Operator / Contact Name
                            <input name="name" value="{{ old('name') }}" placeholder="e.g. Dispatch Manager" required>
                        </label>
                        <label>
                            Login Email
                            <input name="email" type="email" value="{{ old('email') }}" placeholder="e.g. calamba@gmail.com" required>
                        </label>
                        <label>
                            Password
                            <input name="password" type="password" placeholder="Minimum 6 characters" required>
                        </label>
                        <label class="full-span">
                            Access Level
                            <select name="role" required>
                                <option value="admin">Branch Hub Administrator</option>
                                <option value="operations_manager">Operations Manager</option>
                                <option value="warehouse_staff">Warehouse Sorting Staff</option>
                            </select>
                        </label>
                        <button class="primary-button full-span" type="submit" style="margin-top: 8px;">
                            + Create & Auto-Configure Hub Area
                        </button>
                    </form>
                </article>

                <!-- Data Isolation Architecture Note -->
                <article class="panel" style="display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div class="panel-heading">
                            <div>
                                <div class="section-kicker">GOVERNANCE & SECURITY</div>
                                <h2>Branch Data Isolation</h2>
                            </div>
                        </div>
                        <div style="font-size: 12.5px; color: #523A52; line-height: 1.6;">
                            <p style="margin-top: 0;">
                                🛡️ <strong>Strict Multi-Tenant Scoping:</strong> Branch accounts (such as <code>pila@gmail.com</code> and <code>santacruz@example.com</code>) only see parcels, riders, and shipments bound to their specific branch hub.
                            </p>
                            <p>
                                🏢 <strong>Central Command:</strong> As Master Administrator (<code>admin@gmail.com</code>), you have network-wide visibility, multi-branch messaging, compliance review, and account lifecycle control.
                            </p>
                        </div>
                    </div>
                    <div style="padding-top: 14px; border-top: 1px solid #EBDDD8; display: flex; gap: 10px;">
                        <a href="{{ route('admin.accounts') }}" class="secondary-button" style="text-decoration: none; width: 100%; text-align: center;">
                            Manage All Branch Logins ↗
                        </a>
                    </div>
                </article>
            </section>
        </div>
    </main>
</div>
</body>
</html>
