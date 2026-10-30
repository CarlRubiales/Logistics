<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Branches & Assigned Couriers — Lumora Master Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .branch-card-elevated {
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 18px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(61, 27, 61, 0.03);
            transition: box-shadow 0.2s;
        }
        .branch-card-elevated:hover {
            box-shadow: 0 8px 24px rgba(61, 27, 61, 0.06);
        }
        .branch-header-elevated {
            padding: 20px 24px;
            background: linear-gradient(135deg, rgba(61, 27, 61, 0.02) 0%, rgba(226, 112, 58, 0.04) 100%), #FAF6F0;
            border-bottom: 1px solid #EBDDD8;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }
        .branch-badge-lg {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #3D1B3D, #793779);
            color: #FFFDFB;
            display: grid;
            place-items: center;
            font-size: 19px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(61, 27, 61, 0.2);
            flex-shrink: 0;
        }
        .branch-pill-clean {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            color: #3D1B3D;
        }
        .branch-stats-strip-clean {
            display: flex;
            align-items: center;
            gap: 20px;
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 12px;
            padding: 10px 18px;
        }
        .branch-stat-mini-clean {
            text-align: center;
        }
        .branch-stat-mini-clean strong {
            display: block;
            font-size: 18px;
            font-weight: 800;
            color: #3D1B3D;
            line-height: 1.1;
        }
        .branch-stat-mini-clean small {
            font-size: 9.5px;
            text-transform: uppercase;
            color: #8A7B7E;
            font-weight: 700;
        }
        .riders-table-clean {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .riders-table-clean th {
            text-align: left;
            padding: 12px 22px;
            background: #FFFDFB;
            color: #8A7B7E;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #EBDDD8;
        }
        .riders-table-clean td {
            padding: 14px 22px;
            border-bottom: 1px solid rgba(235, 221, 216, 0.5);
            vertical-align: middle;
        }
        .riders-table-clean tr:last-child td {
            border-bottom: none;
        }
        .riders-table-clean tr:hover {
            background: #FAF6F0;
        }
        .doc-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }
        .doc-badge-pill.submitted {
            background: rgba(30, 107, 55, 0.08);
            color: #1E6B37;
            border: 1px solid rgba(30, 107, 55, 0.25);
        }
        .doc-badge-pill.submitted:hover {
            background: #1E6B37;
            color: #FFFDFB;
        }
        .doc-badge-pill.missing {
            background: rgba(138, 123, 126, 0.08);
            color: #8A7B7E;
            border: 1px dashed #D3C4BE;
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Master Administration',
            'title' => 'Branches & Assigned Couriers'
        ])

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <!-- Hero Banner -->
            <section class="dash-hero-banner">
                <div class="dash-hero-title">
                    <div class="eyebrow" style="color: #793779;">FLEET ASSIGNMENT DIRECTORY</div>
                    <h1>Branch Couriers & Coverage Directory</h1>
                    <p>
                        Comprehensive central view of each operating branch hub, detailing specifically which couriers are stationed at that branch.
                    </p>
                </div>
                <div class="dash-quick-links">
                    <a href="{{ route('admin.riders') }}" class="dash-link-pill primary">
                        ♙ Review Documents & Approvals
                    </a>
                    <a href="{{ route('admin.accounts') }}" class="dash-link-pill secondary">
                        + New Branch Account
                    </a>
                </div>
            </section>

            <!-- Dashboard Date Selector -->
            @include('partials.dashboard_date_selector')

            <!-- Metric Cards -->
            <section class="metric-grid-elevated">
                <article class="metric-card-elevated plum">
                    <div class="metric-header">
                        <span class="metric-label-clean">Active Branches</span>
                        <div class="metric-icon-bubble" style="background: rgba(61, 27, 61, 0.08); color: #3D1B3D;">🏢</div>
                    </div>
                    <div class="metric-value-clean">{{ $branchData->count() }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Operational hubs
                    </div>
                </article>

                <article class="metric-card-elevated green">
                    <div class="metric-header">
                        <span class="metric-label-clean">Assigned Couriers</span>
                        <div class="metric-icon-bubble" style="background: rgba(30, 107, 55, 0.08); color: #1E6B37;">⛟</div>
                    </div>
                    <div class="metric-value-clean">{{ $branchData->sum('total_riders') }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Assigned to branch hubs
                    </div>
                </article>

                <article class="metric-card-elevated orange">
                    <div class="metric-header">
                        <span class="metric-label-clean">Online on Duty</span>
                        <div class="metric-icon-bubble" style="background: rgba(226, 112, 58, 0.1); color: #E2703A;">⚡</div>
                    </div>
                    <div class="metric-value-clean">{{ $branchData->sum('online_riders') }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Active accepting parcels
                    </div>
                </article>

                <article class="metric-card-elevated blue">
                    <div class="metric-header">
                        <span class="metric-label-clean">Unassigned Couriers</span>
                        <div class="metric-icon-bubble" style="background: rgba(24, 93, 143, 0.1); color: #185D8F;">⏳</div>
                    </div>
                    <div class="metric-value-clean">{{ $unassignedRiders->count() }}</div>
                    <div class="metric-sub-clean warning">
                        <span>●</span> Awaiting hub allocation
                    </div>
                </article>
            </section>

            <!-- Loop Each Branch and Show Who Are The Riders For That Branch -->
            @forelse($branchData as $branch)
                <div class="branch-card-elevated">
                    <!-- Branch Header -->
                    <div class="branch-header-elevated">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div class="branch-badge-lg">
                                {{ strtoupper(substr($branch['name'], 0, 2)) }}
                            </div>
                            <div>
                                <h2 style="margin: 0; font-size: 19px; color: #3D1B3D; font-family: 'Playfair Display', Georgia, serif;">
                                    {{ $branch['name'] }} Hub
                                </h2>
                                <div style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 12px; color: #8A7B7E; margin-top: 5px; align-items: center;">
                                    <span>
                                        👤 Operator: <strong>{{ $branch['account']?->name ?: 'Unassigned Account' }}</strong> ({{ $branch['account']?->email ?: 'No login' }})
                                    </span>
                                    <span>·</span>
                                    <span>
                                        📍 Zones: 
                                        @forelse($branch['areas'] as $area)
                                            <span class="branch-pill-clean">{{ $area->name }}</span>
                                        @empty
                                            <em style="color: #8A7B7E;">No areas configured yet</em>
                                        @endforelse
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mini stats for this branch -->
                        <div class="branch-stats-strip-clean">
                            <div class="branch-stat-mini-clean">
                                <strong>{{ $branch['total_riders'] }}</strong>
                                <small>Couriers</small>
                            </div>
                            <div class="branch-stat-mini-clean">
                                <strong style="color: #1E6B37;">{{ $branch['online_riders'] }}</strong>
                                <small>Online</small>
                            </div>
                            <div class="branch-stat-mini-clean">
                                <strong style="color: #E2703A;">{{ $branch['in_transit'] }}</strong>
                                <small>In Transit</small>
                            </div>
                            <div class="branch-stat-mini-clean">
                                <strong>{{ $branch['delivered'] }}</strong>
                                <small>Delivered</small>
                            </div>
                            <a href="{{ route('messages.index', ['contact_user_id' => $branch['account']?->id]) }}" class="primary-button" style="padding: 5px 12px; font-size: 11px; height: auto; text-decoration: none;">
                                Message Hub
                            </a>
                        </div>
                    </div>

                    <!-- Who Are The Riders For This Branch -->
                    <div style="overflow-x: auto;">
                        @if($branch['riders']->isNotEmpty())
                            <table class="riders-table-clean">
                                <thead>
                                    <tr>
                                        <th>Courier Name & Email</th>
                                        <th>Contact & Vehicle</th>
                                        <th>Duty Status</th>
                                        <th>Active Deliveries</th>
                                        <th>Assigned Sub-Areas</th>
                                        <th>Requirements / Documents</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($branch['riders'] as $rider)
                                        @php
                                            $isOnline = in_array($rider['availability'], ['online', 'available']);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 34px; height: 34px; border-radius: 50%; background: #FAF6F0; border: 1px solid #EBDDD8; display: grid; place-items: center; font-weight: 800; font-size: 11px; color: #3D1B3D;">
                                                        {{ strtoupper(substr($rider['name'], 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <strong style="color: #3D1B3D; font-size: 13px;">{{ $rider['name'] }}</strong>
                                                        <small style="display: block; color: #8A7B7E; font-size: 11px;">{{ $rider['email'] }}</small>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <span style="font-weight: 600; color: #3D1B3D;">🛵 {{ $rider['vehicle_type'] }}</span>
                                                <small style="display: block; color: #8A7B7E; font-size: 11px;">📞 {{ $rider['phone'] }}</small>
                                            </td>

                                            <td>
                                                @if($isOnline)
                                                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; color: #1E6B37;">
                                                        <span class="live-pulse-dot" style="width: 6px; height: 6px;"></span> Online
                                                    </span>
                                                @else
                                                    <span style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; color: #8A7B7E;">
                                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #8A7B7E;"></span> Offline
                                                    </span>
                                                @endif
                                                <div style="margin-top: 3px;">
                                                    <span class="status-chip {{ $rider['registration_status'] === 'approved' ? 'in-transit' : ($rider['registration_status'] === 'pending' ? 'pending' : 'exception') }}" style="font-size: 9.5px; padding: 1px 7px;">
                                                        {{ ucfirst($rider['registration_status']) }}
                                                    </span>
                                                </div>
                                            </td>

                                            <td>
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 700; color: {{ $rider['active_parcels'] > 0 ? '#E2703A' : '#8A7B7E' }};">
                                                    📦 {{ $rider['active_parcels'] }} active
                                                </span>
                                            </td>

                                            <td>
                                                <span style="font-size: 11.5px; color: #3D1B3D;">
                                                    {{ $rider['assigned_areas'] ?: 'General Branch Zone' }}
                                                </span>
                                            </td>

                                            <td>
                                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                    @if($rider['has_license'])
                                                        <a href="{{ route('riders.documents', [$rider['profile'], 'license']) }}" target="_blank" class="doc-badge-pill submitted" title="View submitted driver's license">
                                                            🪪 License ✓
                                                        </a>
                                                    @else
                                                        <span class="doc-badge-pill missing">🪪 Missing</span>
                                                    @endif

                                                    @if($rider['has_or_cr'])
                                                        <a href="{{ route('riders.documents', [$rider['profile'], 'or_cr']) }}" target="_blank" class="doc-badge-pill submitted" title="View submitted vehicle OR/CR">
                                                            📄 OR/CR ✓
                                                        </a>
                                                    @else
                                                        <span class="doc-badge-pill missing">📄 Missing</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td style="text-align: right;">
                                                <a href="{{ route('admin.riders', ['search' => $rider['name']]) }}" class="secondary-button" style="padding: 3px 8px; font-size: 11px; height: auto; text-decoration: none;">
                                                    Review ↗
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div style="padding: 36px 20px; text-align: center; color: #8A7B7E;">
                                <div style="font-size: 32px; margin-bottom: 8px;">🛵</div>
                                <strong style="color: #3D1B3D; display: block; font-size: 14px;">No riders assigned to {{ $branch['name'] }} Hub yet</strong>
                                <p style="font-size: 12px; margin: 4px 0 14px;">Assign available couriers below or via the Rider Management dashboard.</p>
                                <a href="{{ route('admin.riders') }}" class="secondary-button" style="font-size: 11px; padding: 5px 14px; text-decoration: none;">
                                    Assign Available Couriers
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="empty-state">No branches have been registered in the system yet.</div>
            @endforelse

            <!-- Unassigned Couriers Callout -->
            @if($unassignedRiders->isNotEmpty())
                <div class="branch-card-elevated" style="border: 2px dashed #D3C4BE; background: #FFFDFB;">
                    <div class="branch-header-elevated" style="background: #FFF9F5;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div class="branch-badge-lg" style="background: linear-gradient(135deg, #E2703A, #8C4A15);">
                                ?
                            </div>
                            <div>
                                <h2 style="margin: 0; font-size: 17px; color: #3D1B3D;">
                                    Unassigned Couriers ({{ $unassignedRiders->count() }} couriers)
                                </h2>
                                <small style="color: #8A7B7E;">
                                    These couriers are registered in the system but have not been assigned to a branch hub area yet.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="riders-table-clean">
                            <thead>
                                <tr>
                                    <th>Courier</th>
                                    <th>Vehicle & Phone</th>
                                    <th>Registration Status</th>
                                    <th>Submitted Documents</th>
                                    <th>Assign to Branch</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unassignedRiders as $rider)
                                    <tr>
                                        <td>
                                            <strong style="color: #3D1B3D;">{{ $rider->user->name }}</strong>
                                            <small style="display: block; color: #8A7B7E;">{{ $rider->user->email }}</small>
                                        </td>
                                        <td>
                                            🛵 {{ $rider->vehicle_type ?: 'Motorcycle' }} · 📞 {{ $rider->phone ?: 'No phone' }}
                                        </td>
                                        <td>
                                            <span class="status-chip {{ $rider->registration_status === 'approved' ? 'in-transit' : 'pending' }}">
                                                {{ ucfirst($rider->registration_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 6px;">
                                                @if($rider->license_photo_path)
                                                    <a href="{{ route('riders.documents', [$rider, 'license']) }}" target="_blank" class="doc-badge-pill submitted">🪪 License ✓</a>
                                                @else
                                                    <span class="doc-badge-pill missing">🪪 Missing</span>
                                                @endif
                                                @if($rider->or_cr_photo_path)
                                                    <a href="{{ route('riders.documents', [$rider, 'or_cr']) }}" target="_blank" class="doc-badge-pill submitted">📄 OR/CR ✓</a>
                                                @else
                                                    <span class="doc-badge-pill missing">📄 Missing</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('riders.areas', $rider) }}" style="display: flex; gap: 6px; align-items: center;">
                                                @csrf
                                                <select name="destination_area_id" required style="padding: 4px 8px; font-size: 11px; border-radius: 6px; border: 1px solid #EBDDD8; background: #FAF6F0;">
                                                    <option value="">Assign to branch area...</option>
                                                    @foreach($allAreas as $a)
                                                        <option value="{{ $a->id }}">{{ $a->branch ?: 'General' }} — {{ $a->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="secondary-button" style="padding: 4px 10px; font-size: 11px; height: auto;">
                                                    Assign Hub
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </main>
</div>
</body>
</html>
