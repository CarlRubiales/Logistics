<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Riders — Lumora Logistics</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">
                <span>Manage</span>
                <span class="crumb-divider">/</span>
                <strong>Riders</strong>
            </div>
            <div class="topbar-actions">
                <span class="user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
            </div>
        </header>
        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <section class="welcome-row">
                <div>
                    <div class="eyebrow">RIDER NETWORK</div>
                    <h1>Rider registrations & coverage</h1>
                    <p class="intro">Manage rider approvals, area assignments, live availability, and communications.</p>
                </div>
            </section>

            <!-- Metric Cards Row -->
            <section class="metric-grid" style="margin-bottom: 24px;">
                <article class="metric-card plum-wash">
                    <div class="metric-top">
                        <span class="metric-label">Total Riders</span>
                        <span class="metric-icon plum">{{ $counts['total'] ?? 0 }}</span>
                    </div>
                    <div class="metric-value">{{ $counts['total'] ?? 0 }}</div>
                    <div class="metric-trend">Registered accounts</div>
                </article>

                <article class="metric-card sage-wash">
                    <div class="metric-top">
                        <span class="metric-label">Approved Riders</span>
                        <span class="metric-icon" style="color: #1E6B37;">{{ $counts['approved'] ?? 0 }}</span>
                    </div>
                    <div class="metric-value">{{ $counts['approved'] ?? 0 }}</div>
                    <div class="metric-trend positive">Active delivery fleet</div>
                </article>

                <article class="metric-card coral-wash">
                    <div class="metric-top">
                        <span class="metric-label">Online on Duty</span>
                        <span class="metric-icon" style="color: #E2703A;">{{ $counts['online'] ?? 0 }}</span>
                    </div>
                    <div class="metric-value">{{ $counts['online'] ?? 0 }}</div>
                    <div class="metric-trend positive">Ready for assignments</div>
                </article>

                <article class="metric-card {{ ($counts['pending'] ?? 0) > 0 ? 'gold-wash' : 'plum-wash' }}">
                    <div class="metric-top">
                        <span class="metric-label">Pending Approval</span>
                        <span class="metric-icon">{{ $counts['pending'] ?? 0 }}</span>
                    </div>
                    <div class="metric-value">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="metric-trend {{ ($counts['pending'] ?? 0) > 0 ? 'negative' : '' }}">
                        {{ ($counts['pending'] ?? 0) > 0 ? 'Requires attention' : 'All clear' }}
                    </div>
                </article>
            </section>

            <!-- Main Riders Panel -->
            <section class="panel shipments-panel">
                <div class="panel-heading shipments-heading" style="flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    <div>
                        <div class="section-kicker">RIDER ROSTER</div>
                        <h2>Registered couriers</h2>
                    </div>

                    <!-- Search and Filters Form -->
                    <form method="GET" action="{{ route('riders.index') }}" style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                        <input name="search" value="{{ request('search') }}" placeholder="Search name, email, phone..." style="min-width: 200px; height: 36px; padding: 0 10px; font-size: 12px; border: 1px solid #EBDDD8; border-radius: 8px; background: #FAF6F0;">
                        
                        <select name="status" style="height: 36px; padding: 0 8px; font-size: 12px; border: 1px solid #EBDDD8; border-radius: 8px; background: #FAF6F0;">
                            <option value="">All Statuses</option>
                            <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                            <option value="disapproved" @selected(request('status') === 'disapproved')>Disapproved</option>
                        </select>

                        <select name="area_id" style="height: 36px; padding: 0 8px; font-size: 12px; border: 1px solid #EBDDD8; border-radius: 8px; background: #FAF6F0;">
                            <option value="">All Areas</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}" @selected(request('area_id') == $area->id)>{{ $area->name }}</option>
                            @endforeach
                        </select>

                        <button class="secondary-button" type="submit" style="height: 36px; padding: 0 14px; font-size: 12px; font-weight: 700;">Filter</button>
                        
                        @if(request()->hasAny(['search', 'status', 'area_id']))
                            <a href="{{ route('riders.index') }}" class="text-button" style="font-size: 12px; color: #8A7B7E; text-decoration: underline;">Clear</a>
                        @endif
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="shipments-table">
                        <thead>
                            <tr>
                                <th>Rider Info</th>
                                <th>Vehicle & Phone</th>
                                <th>Account & Duty</th>
                                <th>Assigned Delivery Areas</th>
                                <th style="min-width: 170px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($riders as $rider)
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="width: 34px; height: 34px; border-radius: 50%; background: #FAF6F0; border: 1px solid #EBDDD8; display: grid; place-items: center; font-weight: 800; font-size: 12px; color: #3D1B3D;">
                                                {{ strtoupper(substr($rider->user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <strong style="color: #3D1B3D; font-size: 13px;">{{ $rider->user->name }}</strong>
                                                <small style="color: #8A7B7E; display: block; font-size: 11px;">{{ $rider->user->email }}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <span style="font-size: 12px; font-weight: 600; color: #3D1B3D;">🛵 {{ $rider->vehicle_type ?: 'Motorcycle' }}</span>
                                        <small style="color: #8A7B7E; display: block; font-size: 11px;">
                                            📞 {{ $rider->phone ?: 'No phone' }}
                                        </small>
                                        @if($rider->license_number)
                                            <small style="color: #8A7B7E; display: block; font-size: 10px;">Lic: {{ $rider->license_number }}</small>
                                        @endif
                                    </td>

                                    <td>
                                        <!-- Registration Status -->
                                        @if($rider->registration_status === 'approved')
                                            <span class="status-chip in-transit" style="font-size: 10px; margin-bottom: 4px;"><b></b>Approved</span>
                                        @elseif($rider->registration_status === 'pending')
                                            <span class="status-chip pending" style="font-size: 10px; margin-bottom: 4px;"><b></b>Pending Approval</span>
                                        @else
                                            <span class="status-chip exception" style="font-size: 10px; margin-bottom: 4px;"><b></b>Disapproved</span>
                                        @endif

                                        <!-- Availability Dot -->
                                        <div style="margin-top: 4px;">
                                            @if($rider->availability === 'online')
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: #1E6B37;">
                                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #1E6B37; display: inline-block;"></span> Online
                                                </span>
                                            @elseif($rider->availability === 'available')
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: #2B5C8F;">
                                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #2B5C8F; display: inline-block;"></span> Available
                                                </span>
                                            @else
                                                <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 600; color: #8A7B7E;">
                                                    <span style="width: 7px; height: 7px; border-radius: 50%; background: #8A7B7E; display: inline-block;"></span> Offline
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 6px;">
                                            <!-- List of Assigned Area Chips with Remove (X) button -->
                                            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                                @forelse($rider->areas as $area)
                                                    <span style="display: inline-flex; align-items: center; gap: 6px; background: #FAF6F0; border: 1px solid #EBDDD8; border-radius: 999px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #3D1B3D;">
                                                        {{ $area->name }}
                                                        <form method="POST" action="{{ route('riders.areas.destroy', [$rider, $area]) }}" style="display: inline; margin: 0; padding: 0;" onsubmit="return confirm('Remove {{ $area->name }} from {{ $rider->user->name }}?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" style="background: none; border: none; padding: 0; margin-left: 2px; cursor: pointer; color: #C62828; font-size: 13px; font-weight: 800; line-height: 1;" title="Remove area">×</button>
                                                        </form>
                                                    </span>
                                                @empty
                                                    <span style="color: #8A7B7E; font-size: 11px; font-style: italic;">No area assigned yet</span>
                                                @endforelse
                                            </div>

                                            <!-- Assign New Area Form -->
                                            @php
                                                $unassignedAreas = $areas->whereNotIn('id', $rider->areas->pluck('id'));
                                            @endphp
                                            @if($unassignedAreas->isNotEmpty())
                                                <form method="POST" action="{{ route('riders.areas', $rider) }}" style="display: flex; gap: 4px; align-items: center; margin-top: 2px;">
                                                    @csrf
                                                    <select name="destination_area_id" required style="font-size: 11px; padding: 2px 6px; border-radius: 6px; border: 1px solid #EBDDD8; height: 26px; max-width: 130px; background: #FFF;">
                                                        <option value="">+ Area...</option>
                                                        @foreach($unassignedAreas as $area)
                                                            <option value="{{ $area->id }}">{{ $area->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="secondary-button" type="submit" style="padding: 2px 8px; height: 26px; font-size: 10.5px; font-weight: 700;">Add</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>

                                    <td>
                                        <div class="table-actions" style="display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                            @if($rider->registration_status === 'approved')
                                                <!-- Message Rider link -->
                                                <a href="{{ route('messages.index', ['rider_user_id' => $rider->user_id]) }}" class="secondary-button" style="text-decoration: none; padding: 4px 9px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; color: #E2703A; border-color: rgba(226, 112, 58, 0.4); background: #FFF;" title="Message rider">
                                                    ✉ Message
                                                </a>

                                                <!-- Suspend / Disapprove button -->
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}" onsubmit="return confirm('Suspend / Disapprove {{ $rider->user->name }}? This will revoke active assignments.');">
                                                    @csrf
                                                    <input type="hidden" name="status" value="disapproved">
                                                    <button type="submit" class="secondary-button" style="padding: 4px 9px; font-size: 11px; font-weight: 700; color: #C62828; border-color: rgba(198, 40, 40, 0.35); background: #FFF;">
                                                        Suspend
                                                    </button>
                                                </form>
                                            @elseif($rider->registration_status === 'pending')
                                                <!-- Approve button -->
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="primary-button" style="padding: 4px 10px; font-size: 11px; font-weight: 800; background: #2E7D32; border: none; color: #FFF; border-radius: 6px;">
                                                        ✓ Approve
                                                    </button>
                                                </form>

                                                <!-- Reject / Disapprove button -->
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="disapproved">
                                                    <button type="submit" class="secondary-button" style="padding: 4px 9px; font-size: 11px; font-weight: 700; color: #C62828; border-color: rgba(198, 40, 40, 0.35); background: #FFF;">
                                                        ✕ Reject
                                                    </button>
                                                </form>
                                            @else
                                                <!-- Re-approve button -->
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="primary-button" style="padding: 4px 10px; font-size: 11px; font-weight: 800; background: #2E7D32; border: none; color: #FFF; border-radius: 6px;">
                                                        ✓ Re-approve
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 30px; color: #8A7B7E;">
                                        No riders match your search or filter criteria.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="table-footer">{{ $riders->links() }}</div>
            </section>
        </div>
    </main>
</div>
</body>
</html>
