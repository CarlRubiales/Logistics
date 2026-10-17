<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>All Riders & Submitted Documents — Lumora Master Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .branch-section-elevated {
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 18px;
            margin-bottom: 24px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(61, 27, 61, 0.03);
            transition: box-shadow 0.2s;
        }
        .branch-section-elevated:hover {
            box-shadow: 0 8px 24px rgba(61, 27, 61, 0.06);
        }
        .branch-section-header-elevated {
            padding: 18px 24px;
            background: linear-gradient(135deg, rgba(61, 27, 61, 0.02) 0%, rgba(226, 112, 58, 0.04) 100%), #FAF6F0;
            border-bottom: 1px solid #EBDDD8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .branch-tag-pill {
            font-size: 11.5px;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 999px;
            background: #3D1B3D;
            color: #FFFDFB;
            letter-spacing: 0.3px;
        }
        .rider-table-clean {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }
        .rider-table-clean th {
            text-align: left;
            padding: 12px 20px;
            background: #FFFDFB;
            color: #8A7B7E;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #EBDDD8;
        }
        .rider-table-clean td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(235, 221, 216, 0.5);
            vertical-align: middle;
        }
        .rider-table-clean tr:last-child td {
            border-bottom: none;
        }
        .rider-table-clean tr:hover {
            background: #FAF6F0;
        }
        .filter-bar-clean {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 14px;
            padding: 12px 18px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(61, 27, 61, 0.02);
        }
        .filter-input-clean {
            height: 38px;
            padding: 0 14px;
            font-size: 12.5px;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            background: #FAF6F0;
            color: #3A2E30;
            transition: all 0.15s;
        }
        .filter-input-clean:focus {
            outline: none;
            border-color: #3D1B3D;
            background: #FFFDFB;
        }
        .doc-pill-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }
        .doc-pill-action.verified {
            background: rgba(30, 107, 55, 0.08);
            border: 1px solid rgba(30, 107, 55, 0.3);
            color: #1E6B37;
        }
        .doc-pill-action.verified:hover {
            background: #1E6B37;
            color: #FFFDFB;
            transform: translateY(-1px);
        }
        .doc-pill-action.missing {
            background: rgba(138, 123, 126, 0.08);
            border: 1px dashed #D3C4BE;
            color: #8A7B7E;
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Master Administration',
            'title' => 'Riders & Documents Compliance'
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
                    <div class="eyebrow" style="color: #793779;">COMPLIANCE & FLEET OVERSIGHT</div>
                    <h1>Rider Verification by Branch</h1>
                    <p>
                        Review Driver's Licenses and Vehicle OR/CR documents organized into dedicated sections per branch with instant approval controls.
                    </p>
                </div>
                <div class="dash-quick-links">
                    <a href="{{ route('admin.branches') }}" class="dash-link-pill primary">
                        📍 Branches Directory
                    </a>
                    <a href="{{ route('messages.index') }}" class="dash-link-pill secondary">
                        ✉ Courier Messages
                    </a>
                </div>
            </section>

            <!-- Metric Cards -->
            <section class="metric-grid-elevated">
                <article class="metric-card-elevated plum">
                    <div class="metric-header">
                        <span class="metric-label-clean">Total Couriers</span>
                        <div class="metric-icon-bubble" style="background: rgba(61, 27, 61, 0.08); color: #3D1B3D;">♙</div>
                    </div>
                    <div class="metric-value-clean">{{ $counts['total'] ?? 0 }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Across all regional hubs
                    </div>
                </article>

                <article class="metric-card-elevated green">
                    <div class="metric-header">
                        <span class="metric-label-clean">Approved Couriers</span>
                        <div class="metric-icon-bubble" style="background: rgba(30, 107, 55, 0.08); color: #1E6B37;">✓</div>
                    </div>
                    <div class="metric-value-clean">{{ $counts['approved'] ?? 0 }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Verified & licensed fleet
                    </div>
                </article>

                <article class="metric-card-elevated {{ ($counts['pending'] ?? 0) > 0 ? 'orange' : 'plum' }}">
                    <div class="metric-header">
                        <span class="metric-label-clean">Pending Review</span>
                        <div class="metric-icon-bubble" style="background: rgba(226, 112, 58, 0.1); color: #E2703A;">⏳</div>
                    </div>
                    <div class="metric-value-clean">{{ $counts['pending'] ?? 0 }}</div>
                    <div class="metric-sub-clean {{ ($counts['pending'] ?? 0) > 0 ? 'warning' : 'positive' }}">
                        <span>●</span> {{ ($counts['pending'] ?? 0) > 0 ? 'Documents awaiting check' : 'All applicants reviewed' }}
                    </div>
                </article>

                <article class="metric-card-elevated blue">
                    <div class="metric-header">
                        <span class="metric-label-clean">Online on Duty</span>
                        <div class="metric-icon-bubble" style="background: rgba(24, 93, 143, 0.1); color: #185D8F;">⚡</div>
                    </div>
                    <div class="metric-value-clean">{{ $counts['online'] ?? 0 }}</div>
                    <div class="metric-sub-clean positive">
                        <span>●</span> Live accepting dispatches
                    </div>
                </article>
            </section>

            <!-- Search and Filter Form -->
            <form method="GET" action="{{ route('admin.riders') }}" class="filter-bar-clean">
                <input
                    type="search"
                    name="search"
                    class="filter-input-clean"
                    style="min-width: 280px;"
                    placeholder="Search name, email, phone, or license..."
                    value="{{ $search }}"
                >
                <select name="status" class="filter-input-clean">
                    <option value="">All Statuses</option>
                    <option value="approved" @selected($status === 'approved')>Approved</option>
                    <option value="pending" @selected($status === 'pending')>Pending Approval</option>
                    <option value="disapproved" @selected($status === 'disapproved')>Disapproved</option>
                </select>
                <button type="submit" class="secondary-button" style="height: 38px; padding: 0 16px; font-size: 12px;">
                    Filter Couriers
                </button>
                @if($search || $status)
                    <a href="{{ route('admin.riders') }}" class="text-button" style="font-size: 12px; color: #E2703A; text-decoration: none;">
                        Clear Filters
                    </a>
                @endif
            </form>

            <!-- Sections For Each Branch -->
            @foreach($branchSections as $section)
                <div class="branch-section-elevated">
                    <div class="branch-section-header-elevated">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="branch-tag-pill">{{ $section['branch'] }} Branch</span>
                            <strong style="color: #3D1B3D; font-size: 15px;">
                                {{ $section['count'] }} Couriers Registered
                            </strong>
                            <small style="color: #8A7B7E;">
                                ({{ $section['approved_count'] }} approved · {{ $section['pending_count'] }} pending)
                            </small>
                        </div>
                        <div style="font-size: 12px; color: #8A7B7E;">
                            📍 Coverage: {{ $section['areas']->pluck('name')->implode(', ') ?: 'General Branch Zone' }}
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        @if($section['riders']->isNotEmpty())
                            <table class="rider-table-clean">
                                <thead>
                                    <tr>
                                        <th>Courier Name & Contact</th>
                                        <th>Vehicle</th>
                                        <th>Assigned Area(s)</th>
                                        <th style="min-width: 240px;">Submitted Requirements / Documents</th>
                                        <th>Review Status</th>
                                        <th>Duty Status</th>
                                        <th style="text-align: right;">Approval Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section['riders'] as $rider)
                                        @php
                                            $isOnline = in_array($rider->availability, ['online', 'available']);
                                        @endphp
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div style="width: 34px; height: 34px; border-radius: 50%; background: #FAF6F0; border: 1px solid #EBDDD8; display: grid; place-items: center; font-weight: 800; font-size: 12px; color: #3D1B3D;">
                                                        {{ strtoupper(substr($rider->user->name, 0, 2)) }}
                                                    </div>
                                                    <div>
                                                        <strong style="color: #3D1B3D; font-size: 13px;">{{ $rider->user->name }}</strong>
                                                        <small style="display: block; color: #8A7B7E; font-size: 11px;">{{ $rider->user->email }}</small>
                                                        <small style="display: block; color: #8A7B7E; font-size: 11px;">📞 {{ $rider->phone ?: 'No phone' }}</small>
                                                    </div>
                                                </div>
                                            </td>

                                            <td>
                                                <span style="font-weight: 600; color: #3D1B3D;">🛵 {{ $rider->vehicle_type ?: 'Motorcycle' }}</span>
                                                @if($rider->license_number)
                                                    <small style="display: block; color: #8A7B7E; font-size: 10.5px;">No. {{ $rider->license_number }}</small>
                                                @endif
                                            </td>

                                            <td>
                                                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                                    @forelse($rider->areas as $area)
                                                        <span style="display: inline-flex; align-items: center; gap: 4px; background: #FAF6F0; border: 1px solid #EBDDD8; border-radius: 999px; padding: 2px 7px; font-size: 10.5px; font-weight: 600; color: #3D1B3D;">
                                                            {{ $area->name }}
                                                            <form method="POST" action="{{ route('riders.areas.destroy', [$rider, $area]) }}" style="display: inline; margin: 0; padding: 0;" onsubmit="return confirm('Remove {{ $area->name }} from {{ $rider->user->name }}?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer; color: #C62828; font-size: 12px; font-weight: 800;" title="Remove area">×</button>
                                                            </form>
                                                        </span>
                                                    @empty
                                                        <em style="color: #8A7B7E; font-size: 11px;">Branch Central</em>
                                                    @endforelse
                                                </div>
                                            </td>

                                            <!-- REQUIREMENTS / DOCUMENTS SUBMITTED -->
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                                    @if($rider->license_photo_path)
                                                        <a href="{{ route('riders.documents', [$rider, 'license']) }}" target="_blank" rel="noopener noreferrer" class="doc-pill-action verified" title="Open Driver's License in new tab">
                                                            🪪 Driver's License ↗ <span style="font-size: 10px; opacity: 0.85;">[Uploaded]</span>
                                                        </a>
                                                    @else
                                                        <span class="doc-pill-action missing">
                                                            🪪 Driver's License: <em style="font-size: 10px;">Not submitted</em>
                                                        </span>
                                                    @endif

                                                    @if($rider->or_cr_photo_path)
                                                        <a href="{{ route('riders.documents', [$rider, 'or_cr']) }}" target="_blank" rel="noopener noreferrer" class="doc-pill-action verified" title="Open Vehicle OR/CR in new tab">
                                                            📄 Vehicle OR / CR ↗ <span style="font-size: 10px; opacity: 0.85;">[Uploaded]</span>
                                                        </a>
                                                    @else
                                                        <span class="doc-pill-action missing">
                                                            📄 Vehicle OR / CR: <em style="font-size: 10px;">Not submitted</em>
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td>
                                                @if($rider->registration_status === 'approved')
                                                    <span class="status-chip in-transit" style="font-size: 10px;"><b></b>Approved</span>
                                                @elseif($rider->registration_status === 'pending')
                                                    <span class="status-chip pending" style="font-size: 10px;"><b></b>Pending Review</span>
                                                @else
                                                    <span class="status-chip exception" style="font-size: 10px;"><b></b>Disapproved</span>
                                                @endif
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
                                            </td>

                                            <td style="text-align: right;">
                                                <div style="display: inline-flex; gap: 6px; align-items: center;">
                                                    @if($rider->registration_status !== 'approved')
                                                        <form method="POST" action="{{ route('riders.approval', $rider) }}" style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="secondary-button" style="padding: 3px 9px; font-size: 11px; color: #1E6B37; border-color: #1E6B37; height: auto;">
                                                                ✓ Approve
                                                            </button>
                                                        </form>
                                                    @endif

                                                    @if($rider->registration_status !== 'disapproved')
                                                        <form method="POST" action="{{ route('riders.approval', $rider) }}" style="display: inline;" onsubmit="return confirm('Disapprove courier account for {{ $rider->user->name }}?');">
                                                            @csrf
                                                            <input type="hidden" name="status" value="disapproved">
                                                            <button type="submit" class="secondary-button" style="padding: 3px 9px; font-size: 11px; color: #C62828; border-color: #C62828; height: auto;">
                                                                ✕ Reject
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div style="padding: 28px; text-align: center; color: #8A7B7E; font-size: 12px;">
                                No couriers found for {{ $section['branch'] }} matching the search criteria.
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <!-- Unassigned Couriers Section -->
            @if($unassignedRiders->isNotEmpty())
                <div class="branch-section-elevated" style="border: 2px dashed #D3C4BE;">
                    <div class="branch-section-header-elevated" style="background: #FFF8F4;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <span class="branch-tag-pill" style="background: #E2703A;">Unassigned / New Applicants</span>
                            <strong style="color: #3D1B3D; font-size: 15px;">
                                {{ $unassignedRiders->count() }} Couriers Awaiting Branch Assignment
                            </strong>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table class="rider-table-clean">
                            <thead>
                                <tr>
                                    <th>Courier Name & Contact</th>
                                    <th>Vehicle</th>
                                    <th style="min-width: 240px;">Submitted Requirements / Documents</th>
                                    <th>Review Status</th>
                                    <th>Assign Branch Area</th>
                                    <th style="text-align: right;">Approval Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unassignedRiders as $rider)
                                    <tr>
                                        <td>
                                            <strong style="color: #3D1B3D; font-size: 13px;">{{ $rider->user->name }}</strong>
                                            <small style="display: block; color: #8A7B7E; font-size: 11px;">{{ $rider->user->email }} · 📞 {{ $rider->phone ?: 'No phone' }}</small>
                                        </td>
                                        <td>🛵 {{ $rider->vehicle_type ?: 'Motorcycle' }}</td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                @if($rider->license_photo_path)
                                                    <a href="{{ route('riders.documents', [$rider, 'license']) }}" target="_blank" class="doc-pill-action verified">
                                                        🪪 Driver's License ↗
                                                    </a>
                                                @else
                                                    <span class="doc-pill-action missing">🪪 No License</span>
                                                @endif

                                                @if($rider->or_cr_photo_path)
                                                    <a href="{{ route('riders.documents', [$rider, 'or_cr']) }}" target="_blank" class="doc-pill-action verified">
                                                        📄 Vehicle OR / CR ↗
                                                    </a>
                                                @else
                                                    <span class="doc-pill-action missing">📄 No OR/CR</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-chip {{ $rider->registration_status === 'approved' ? 'in-transit' : 'pending' }}">
                                                {{ ucfirst($rider->registration_status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('riders.areas', $rider) }}" style="display: flex; gap: 6px; align-items: center;">
                                                @csrf
                                                <select name="destination_area_id" required style="padding: 4px 8px; font-size: 11px; border-radius: 6px; border: 1px solid #EBDDD8; background: #FAF6F0;">
                                                    <option value="">Select branch area...</option>
                                                    @foreach($allAreas as $a)
                                                        <option value="{{ $a->id }}">{{ $a->branch ?: 'General' }} — {{ $a->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="secondary-button" style="padding: 4px 8px; font-size: 11px; height: auto;">
                                                    Assign
                                                </button>
                                            </form>
                                        </td>
                                        <td style="text-align: right;">
                                            <div style="display: inline-flex; gap: 6px;">
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}" style="display: inline;">
                                                    @csrf
                                                    <input type="hidden" name="status" value="approved">
                                                    <button type="submit" class="secondary-button" style="padding: 3px 8px; font-size: 11px; color: #1E6B37; border-color: #1E6B37; height: auto;">
                                                        ✓ Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('riders.approval', $rider) }}" style="display: inline;">
                                                    @csrf
                                                    <input type="hidden" name="status" value="disapproved">
                                                    <button type="submit" class="secondary-button" style="padding: 3px 8px; font-size: 11px; color: #C62828; border-color: #C62828; height: auto;">
                                                        ✕ Reject
                                                    </button>
                                                </form>
                                            </div>
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
