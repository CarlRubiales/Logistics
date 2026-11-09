<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $hubName }} Hub Remittance — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .remit-page { padding-bottom: 60px; }
        .remit-banner {
            display: none;
            background: linear-gradient(135deg, #FFF4E5 0%, #FFE9D1 100%);
            border: 1px solid #F5C68A;
            border-left: 5px solid var(--orange);
            color: #7A3E00;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            animation: remitSlideDown 0.3s ease;
        }
        @keyframes remitSlideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .remit-banner-text { display: flex; align-items: center; gap: 10px; font-size: 13.5px; font-weight: 700; }
        .remit-banner-pulse {
            width: 10px; height: 10px; border-radius: 50%; background: var(--orange);
            box-shadow: 0 0 0 0 rgba(226, 112, 58, 0.7);
            animation: remitPulse 1.8s infinite;
        }
        @keyframes remitPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(226, 112, 58, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(226, 112, 58, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(226, 112, 58, 0); }
        }
        .remit-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;
            gap: 22px;
            align-items: start;
        }
        @media (max-width: 1100px) {
            .remit-grid { grid-template-columns: 1fr; }
        }
        .remit-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }
        @media (max-width: 900px) {
            .remit-cards { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 540px) {
            .remit-cards { grid-template-columns: 1fr; }
        }
        .remit-card {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(61, 27, 61, 0.03);
        }
        .remit-card.highlight {
            border-left: 4px solid var(--orange);
            background: linear-gradient(180deg, #FFFFFF 0%, #FFFDFB 100%);
        }
        .remit-card.success {
            border-left: 4px solid #1E6B37;
        }
        .remit-card.danger {
            border-left: 4px solid #C93B2B;
        }
        .remit-card-title {
            font-size: 11.5px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .remit-card-val {
            font-size: 22px;
            font-weight: 800;
            color: var(--plum);
            font-family: var(--font-serif, serif);
            line-height: 1.2;
        }
        .remit-card-sub {
            font-size: 11.5px;
            color: var(--muted);
            font-weight: 600;
        }
        .remit-card-sub.warn {
            color: #C93B2B;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .status-pill.awaiting {
            background: #FFF4E5;
            color: #B45309;
            border: 1px solid #FDE68A;
        }
        .status-pill.remitted {
            background: #ECFDF5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .status-pill.short {
            background: #FEF2F2;
            color: #991B1B;
            border: 1px solid #FECACA;
        }
        .status-pill.over {
            background: #EFF6FF;
            color: #1E40AF;
            border: 1px solid #BFDBFE;
        }
        .status-pill.not_remitted {
            background: #F3F4F6;
            color: #4B5563;
            border: 1px solid #E5E7EB;
        }
        .status-pill.not_remitted.overdue {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FCA5A5;
            animation: pulseSoft 2s infinite;
        }
        .status-pill.nothing_due {
            background: #F9FAFB;
            color: #9CA3AF;
            border: 1px solid #E5E7EB;
        }
        .remit-row-selected {
            background-color: rgba(226, 112, 58, 0.08) !important;
            border-left: 3px solid var(--orange) !important;
        }
        .remit-row-overdue {
            background-color: rgba(220, 38, 38, 0.03);
        }
        .drawer-box {
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(61, 27, 61, 0.06);
            overflow: hidden;
            position: sticky;
            top: 18px;
        }
        .drawer-header {
            padding: 16px 20px;
            background: #FAFAF7;
            border-bottom: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .drawer-body {
            padding: 20px;
            max-height: calc(100vh - 160px);
            overflow-y: auto;
        }
        .parcel-item {
            padding: 10px 12px;
            background: #FAF9F6;
            border: 1px solid var(--line);
            border-radius: 8px;
            margin-bottom: 8px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .activity-feed-item {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px dashed var(--line);
            font-size: 12px;
        }
        .activity-feed-item:last-child { border-bottom: none; }
    </style>
</head>
<body class="logistics-page">
@php
    $fmt = fn (float|int $amount) => '₱' . number_format($amount, 2);
    $baseQuery = array_filter([
        'date' => $dateStr,
        'tab' => $currentTab !== 'all' ? $currentTab : null,
        'q' => $search !== '' ? $search : null,
        'hub' => $isMasterAdmin ? $hubName : null,
    ], fn ($v) => $v !== null);
@endphp

<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => $hubName . ' Branch Hub',
            'title' => 'Remittance'
        ])

        <div class="content-wrap module-page remit-page">
            <!-- 1. Live Banner (auto populated via polling or immediate) -->
            <div id="liveRemitBanner" class="remit-banner" role="alert" aria-live="polite">
                <div class="remit-banner-text">
                    <span class="remit-banner-pulse" aria-hidden="true"></span>
                    <span id="liveBannerMessage">New remit: Rider submitted cash</span>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <a id="liveBannerReviewBtn" href="#" class="rr-btn primary sm" style="text-decoration:none; white-space:nowrap;">
                        Review now
                    </a>
                    <button type="button" onclick="dismissBanner()" class="rr-btn secondary sm" style="padding: 4px 8px;">✕</button>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav class="comm-breadcrumb" aria-label="Breadcrumb">
                <span>{{ $hubName }} Branch Hub</span>
                <span class="sep">/</span>
                <span class="curr">Remittance</span>
            </nav>

            <!-- 2. Header & Controls -->
            <section class="welcome-row module-heading comm-heading" style="margin-bottom: 18px;">
                <div>
                    <div class="eyebrow">{{ strtoupper($hubName) }} HUB · COD CASH</div>
                    <h1>Remittance</h1>
                    <p class="intro">Count and reconcile physical Cash-on-Delivery collections remitted by riders during End-of-Day intake.</p>
                </div>
                <div class="comm-header-actions" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <a class="rr-btn secondary" href="{{ route('remittance.export', $baseQuery) }}" title="Export remittance CSV">
                        ⇩ Export CSV
                    </a>
                    <form method="POST" action="{{ route('remittance.remind') }}" style="display: inline;" onsubmit="return confirm('Send in-app reminder to all unremitted riders in {{ $hubName }} Hub?');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $dateStr }}">
                        @if($isMasterAdmin)
                            <input type="hidden" name="hub" value="{{ $hubName }}">
                        @endif
                        <button type="submit" class="rr-btn secondary" @disabled($tabCounts['not_remitted'] === 0)>
                            🔔 Remind unremitted ({{ $tabCounts['not_remitted'] }})
                        </button>
                    </form>
                </div>
            </section>

            @if (session('status'))
                <div class="server-alert" role="status"><span>✓</span>{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>
            @endif

            <!-- Cut-off Notice Banner -->
            <div style="background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 12px 18px; margin-bottom: 20px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 20px;">⏱️</span>
                    <div>
                        <div style="font-size: 13.5px; font-weight: 800; color: var(--plum);">
                            Branch Remit Cut-off: {{ $cutoffFormatted }}
                            @if($isPastCutoff)
                                <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px; border-radius: 999px; background: #FEF2F2; color: #DC2626; font-size: 11px; font-weight: 800;">
                                    ⚠️ PAST CUT-OFF
                                </span>
                            @else
                                <span style="display: inline-flex; align-items: center; gap: 4px; margin-left: 8px; padding: 2px 8px; border-radius: 999px; background: #ECFDF5; color: #065F46; font-size: 11px; font-weight: 800;">
                                    ACTIVE
                                </span>
                            @endif
                        </div>
                        <div style="font-size: 11.5px; color: var(--muted);">
                            @if($isPastCutoff)
                                Unremitted riders are flagged in red. Please follow up on physical cash intake immediately.
                            @else
                                Riders must submit physical COD cash collections before {{ $cutoffFormatted }}.
                            @endif
                        </div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="rr-btn secondary sm" onclick="openCutoffModal()">
                        ⚙ Set Cut-off Time
                    </button>
                </div>
            </div>

            <!-- Filters Bar (Date & Search) -->
            <form id="remitFilterForm" method="GET" action="{{ route('remittance.index') }}" class="comm-filters" style="grid-template-columns: minmax(180px, 220px) minmax(200px, 1fr) auto; margin-bottom: 20px;">
                <input type="hidden" name="tab" value="{{ $currentTab }}">
                @if($isMasterAdmin)
                    <input type="hidden" name="hub" value="{{ $hubName }}">
                @endif

                <div class="rr-filter-field">
                    <label for="date-select">Intake Date</label>
                    <input type="date" id="date-select" name="date" class="rr-filter-select" value="{{ $dateStr }}" onchange="this.form.submit()">
                </div>

                <div class="rr-filter-field comm-search-field">
                    <label for="search-input">Search Rider</label>
                    <div class="rr-search-wrap">
                        <span class="rr-search-icon" aria-hidden="true">⌕</span>
                        <input id="search-input" name="q" type="search" class="rr-filter-input" value="{{ $search }}" placeholder="Search by rider name or phone..." autocomplete="off">
                    </div>
                </div>

                <div style="display: flex; align-items: flex-end;">
                    <a href="{{ route('remittance.index', ['date' => $todayStr]) }}" class="rr-btn secondary" style="height: 42px; display: inline-flex; align-items: center;">
                        Today
                    </a>
                </div>
            </form>

            <!-- 4. Summary Cards -->
            <div class="remit-cards">
                <div class="remit-card">
                    <span class="remit-card-title">Total COD Collected</span>
                    <span class="remit-card-val">{{ $fmt($totalCodCollected) }}</span>
                    <span class="remit-card-sub">{{ $codRidersCount }} active riders · {{ $codParcelsTotal }} COD parcels</span>
                </div>
                <div class="remit-card success">
                    <span class="remit-card-title">Remitted & Confirmed</span>
                    <span class="remit-card-val" style="color: #1E6B37;">{{ $fmt($remittedConfirmedAmount) }}</span>
                    <span class="remit-card-sub">{{ $tabCounts['confirmed'] }} cashier-verified reconciliations</span>
                </div>
                <div class="remit-card highlight">
                    <span class="remit-card-title">Submitted, Awaiting Cashier</span>
                    <span class="remit-card-val" style="color: var(--orange);">{{ $fmt($awaitingCashierAmount) }}</span>
                    <span class="remit-card-sub">{{ $tabCounts['awaiting_cashier'] }} riders waiting at counter</span>
                </div>
                <div class="remit-card danger">
                    <span class="remit-card-title">Not Remitted</span>
                    <span class="remit-card-val" style="color: #C93B2B;">{{ $fmt($totalNotRemittedIncludingShort) }}</span>
                    <span class="remit-card-sub warn">
                        {{ $tabCounts['not_remitted'] }} unremitted
                        @if($shortagesSum > 0)
                            · includes {{ $fmt($shortagesSum) }} short
                        @endif
                    </span>
                </div>
            </div>

            <!-- 5. Status Tabs -->
            <div class="rr-tabs" role="tablist" style="margin-bottom: 16px;">
                @php
                    $tabUrl = fn (string $t) => route('remittance.index', array_merge($baseQuery, ['tab' => $t !== 'all' ? $t : null]));
                @endphp
                <a href="{{ $tabUrl('all') }}" class="rr-tab {{ $currentTab === 'all' ? 'active' : '' }}" role="tab">
                    All <span class="tab-badge">{{ $tabCounts['all'] }}</span>
                </a>
                <a href="{{ $tabUrl('awaiting_cashier') }}" class="rr-tab {{ $currentTab === 'awaiting_cashier' ? 'active' : '' }}" role="tab">
                    Awaiting cashier <span class="tab-badge" style="background: var(--orange); color: #fff;">{{ $tabCounts['awaiting_cashier'] }}</span>
                </a>
                <a href="{{ $tabUrl('not_remitted') }}" class="rr-tab {{ $currentTab === 'not_remitted' ? 'active' : '' }}" role="tab">
                    Not remitted <span class="tab-badge">{{ $tabCounts['not_remitted'] }}</span>
                </a>
                <a href="{{ $tabUrl('confirmed') }}" class="rr-tab {{ $currentTab === 'confirmed' ? 'active' : '' }}" role="tab">
                    Confirmed <span class="tab-badge">{{ $tabCounts['confirmed'] }}</span>
                </a>
            </div>

            <!-- 6. Main Grid: Table & Right Detail Drawer -->
            <div class="remit-grid">
                <!-- Left: Table of Riders -->
                <div class="rr-table-card" style="background:#fff; border:1px solid var(--line); border-radius:12px; overflow:hidden;">
                    <div style="overflow-x: auto;">
                        <table class="comm-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                            <thead>
                                <tr style="background: #FAFAF7; border-bottom: 1px solid var(--line); font-size: 11px; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px;">
                                    <th style="padding: 12px 16px;">Rider</th>
                                    <th style="padding: 12px 16px; text-align: center;">COD Parcels</th>
                                    <th style="padding: 12px 16px; text-align: right;">Collected</th>
                                    <th style="padding: 12px 16px; text-align: right;">Remitted</th>
                                    <th style="padding: 12px 16px; text-align: right;">Balance Due</th>
                                    <th style="padding: 12px 16px; text-align: center;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filteredRows as $row)
                                    @php
                                        $isSelected = $selectedRow && $selectedRow->rider_id === $row->rider_id;
                                        $isOverdue = $isPastCutoff && $row->status === \App\Models\Remittance::STATUS_NOT_REMITTED && $row->expected_amount > 0;
                                        $rowUrl = route('remittance.index', array_merge($baseQuery, ['rider_id' => $row->rider_id]));
                                    @endphp
                                    <tr onclick="window.location='{{ $rowUrl }}'"
                                        style="cursor: pointer; border-bottom: 1px solid var(--line); transition: background 0.15s;"
                                        class="{{ $isSelected ? 'remit-row-selected' : '' }} {{ $isOverdue ? 'remit-row-overdue' : '' }}">
                                        <td style="padding: 14px 16px;">
                                            <div style="font-weight: 800; color: var(--plum);">{{ $row->name }}</div>
                                            <div style="font-size: 11.5px; color: var(--muted);">
                                                @if($row->status === \App\Models\Remittance::STATUS_AWAITING_CASHIER && $row->submitted_at)
                                                    Submitted at {{ \Carbon\Carbon::parse($row->submitted_at)->timezone('Asia/Manila')->format('g:i A') }}
                                                @elseif(in_array($row->status, [\App\Models\Remittance::STATUS_REMITTED, \App\Models\Remittance::STATUS_SHORT, \App\Models\Remittance::STATUS_OVER]) && $row->cashier_name)
                                                    Confirmed by {{ $row->cashier_name }}
                                                @else
                                                    {{ $row->phone }}
                                                @endif
                                            </div>
                                        </td>
                                        <td style="padding: 14px 16px; text-align: center;">
                                            <span style="font-weight: 700; color: var(--plum);">{{ $row->delivered_cod_count }}</span>
                                            <span style="color: var(--muted); font-size: 11.5px;">/ {{ $row->total_cod_count }}</span>
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right; font-weight: 700;">
                                            {{ $fmt($row->expected_amount) }}
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right; font-weight: 700;">
                                            @if($row->counted_amount !== null)
                                                <span style="color: #1E6B37;">{{ $fmt($row->counted_amount) }}</span>
                                            @elseif($row->status === \App\Models\Remittance::STATUS_AWAITING_CASHIER)
                                                <span style="color: var(--orange);">{{ $fmt($row->declared_amount) }}</span>
                                                <span style="display:block; font-size:10px; color:var(--muted);">(Declared)</span>
                                            @else
                                                <span style="color: var(--muted);">—</span>
                                            @endif
                                        </td>
                                        <td style="padding: 14px 16px; text-align: right; font-weight: 800;">
                                            @if($row->balance_due > 0)
                                                <span style="color: #C93B2B;">{{ $fmt($row->balance_due) }}</span>
                                            @elseif($row->difference < 0)
                                                <span style="color: #1E40AF;">+{{ $fmt(abs($row->difference)) }} (Over)</span>
                                            @else
                                                <span style="color: #1E6B37;">₱0.00</span>
                                            @endif
                                        </td>
                                        <td style="padding: 14px 16px; text-align: center;">
                                            @if($row->status === \App\Models\Remittance::STATUS_AWAITING_CASHIER)
                                                <span class="status-pill awaiting">Awaiting Cashier</span>
                                            @elseif($row->status === \App\Models\Remittance::STATUS_REMITTED)
                                                <span class="status-pill remitted">✓ Remitted</span>
                                            @elseif($row->status === \App\Models\Remittance::STATUS_SHORT)
                                                <span class="status-pill short">⚠️ Short {{ $fmt(abs($row->difference)) }}</span>
                                            @elseif($row->status === \App\Models\Remittance::STATUS_OVER)
                                                <span class="status-pill over">Over +{{ $fmt(abs($row->difference)) }}</span>
                                            @elseif($row->status === \App\Models\Remittance::STATUS_NOT_REMITTED)
                                                <span class="status-pill not_remitted {{ $isOverdue ? 'overdue' : '' }}">
                                                    {{ $isOverdue ? '⚠️ Overdue' : 'Not Remitted' }}
                                                </span>
                                            @else
                                                <span class="status-pill nothing_due">Nothing Due</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" style="padding: 40px 20px; text-align: center; color: var(--muted);">
                                            <div style="font-size: 28px; margin-bottom: 8px;">📦</div>
                                            <div style="font-weight: 700; font-size: 14px; color: var(--plum);">No rider remittance records found</div>
                                            <p style="font-size: 12px; margin: 4px 0 0 0;">No riders match the active tab or search criteria for {{ $selectedDate->format('M d, Y') }}.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div style="padding: 12px 18px; background: #FAFAF7; border-top: 1px solid var(--line); font-size: 11.5px; color: var(--muted); display: flex; justify-content: space-between;">
                        <span>Showing {{ $filteredRows->count() }} of {{ $riderRows->count() }} riders</span>
                        <span>Auto-refreshes every 30s</span>
                    </div>
                </div>

                <!-- Right: Cashier Action & Rider Detail Drawer -->
                <div class="drawer-box">
                    @if($selectedRow)
                        <div class="drawer-header">
                            <div>
                                <div style="font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase;">
                                    RIDER INTAKE RECONCILIATION
                                </div>
                                <h3 style="margin: 2px 0 0 0; font-size: 16px; font-weight: 800; color: var(--plum);">
                                    {{ $selectedRow->name }}
                                </h3>
                            </div>
                            <div>
                                @if($selectedRow->status === \App\Models\Remittance::STATUS_AWAITING_CASHIER)
                                    <span class="status-pill awaiting">Awaiting</span>
                                @elseif($selectedRow->status === \App\Models\Remittance::STATUS_REMITTED)
                                    <span class="status-pill remitted">✓ Remitted</span>
                                @elseif($selectedRow->status === \App\Models\Remittance::STATUS_SHORT)
                                    <span class="status-pill short">Short</span>
                                @elseif($selectedRow->status === \App\Models\Remittance::STATUS_OVER)
                                    <span class="status-pill over">Over</span>
                                @else
                                    <span class="status-pill not_remitted">Not Remitted</span>
                                @endif
                            </div>
                        </div>

                        <div class="drawer-body">
                            <!-- Amounts Comparison Box -->
                            <div style="background: #FAFAF7; border: 1px solid var(--line); border-radius: 10px; padding: 14px; margin-bottom: 18px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px;">
                                    <span style="color: var(--muted);">Server Expected COD:</span>
                                    <strong style="color: var(--plum);">{{ $fmt($selectedRow->expected_amount) }}</strong>
                                </div>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px;">
                                    <span style="color: var(--muted);">Rider Declared COD:</span>
                                    <strong style="color: var(--orange);">{{ $fmt($selectedRow->declared_amount) }}</strong>
                                </div>
                                @if($selectedRow->counted_amount !== null)
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px; border-top: 1px dashed var(--line); padding-top: 8px;">
                                        <span style="color: var(--muted);">Cashier Counted:</span>
                                        <strong style="color: #1E6B37;">{{ $fmt($selectedRow->counted_amount) }}</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; font-size: 12.5px;">
                                        <span style="color: var(--muted);">Difference:</span>
                                        <strong style="color: {{ $selectedRow->difference > 0 ? '#C93B2B' : ($selectedRow->difference < 0 ? '#1E40AF' : '#1E6B37') }};">
                                            @if($selectedRow->difference > 0)
                                                -{{ $fmt($selectedRow->difference) }} (Shortage)
                                            @elseif($selectedRow->difference < 0)
                                                +{{ $fmt(abs($selectedRow->difference)) }} (Overage)
                                            @else
                                                ₱0.00 (Balanced)
                                            @endif
                                        </strong>
                                    </div>
                                @endif
                            </div>

                            <!-- Cashier Action Form (When Awaiting Cashier or Re-confirming) -->
                            @if($selectedRow->status === \App\Models\Remittance::STATUS_AWAITING_CASHIER || ($selectedRow->remittance_id && in_array($selectedRow->status, [\App\Models\Remittance::STATUS_SHORT, \App\Models\Remittance::STATUS_OVER])))
                                <form method="POST" action="{{ route('remittance.confirm', ['id' => $selectedRow->remittance_id]) }}" style="margin-bottom: 20px;">
                                    @csrf
                                    <div style="font-weight: 800; font-size: 13px; color: var(--plum); margin-bottom: 6px;">
                                        Cashier Physical Count
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--muted); margin-bottom: 12px;">
                                        Enter physical cash handed over by the rider at the branch counter:
                                    </div>

                                    <div style="margin-bottom: 12px;">
                                        <label for="counted_amount" style="display: block; font-size: 11.5px; font-weight: 700; color: var(--plum); margin-bottom: 4px;">
                                            Amount Counted (₱)
                                        </label>
                                        <div style="position: relative;">
                                            <span style="position: absolute; left: 12px; top: 10px; font-weight: 700; color: var(--muted);">₱</span>
                                            <input type="number" step="0.01" min="0" id="counted_amount" name="counted_amount"
                                                value="{{ old('counted_amount', $selectedRow->declared_amount > 0 ? $selectedRow->declared_amount : $selectedRow->expected_amount) }}"
                                                required
                                                style="width: 100%; padding: 10px 12px 10px 28px; border: 1px solid var(--line); border-radius: 8px; font-size: 15px; font-weight: 800; color: var(--plum);"
                                                oninput="checkDifference(this.value, {{ $selectedRow->expected_amount }})">
                                        </div>
                                    </div>

                                    <div id="diffAlertBox" style="display: none; padding: 10px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; margin-bottom: 12px;"></div>

                                    <div id="cashierNoteWrap" style="margin-bottom: 14px;">
                                        <label for="cashier_note" style="display: block; font-size: 11.5px; font-weight: 700; color: var(--plum); margin-bottom: 4px;">
                                            Cashier Note (Required if Short/Over)
                                        </label>
                                        <textarea id="cashier_note" name="cashier_note" rows="2" placeholder="e.g. Rider short ₱50 due to change discrepancy, agreed to deduct on next pay period..."
                                            style="width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 12px;"></textarea>
                                    </div>

                                    <button type="submit" id="confirmRemitBtn" class="rr-btn primary" style="width: 100%; justify-content: center; padding: 12px;">
                                        ✓ Confirm Cash Intake
                                    </button>
                                </form>
                            @elseif($selectedRow->status === \App\Models\Remittance::STATUS_REMITTED)
                                <div style="background: #ECFDF5; border: 1px solid #A7F3D0; border-radius: 8px; padding: 14px; margin-bottom: 18px;">
                                    <div style="font-weight: 800; font-size: 13px; color: #065F46; margin-bottom: 4px;">
                                        ✓ Reconciled & Remitted
                                    </div>
                                    <div style="font-size: 11.5px; color: #047857; line-height: 1.4;">
                                        Cashier {{ $selectedRow->cashier_name ?? 'Branch Cashier' }} confirmed the intake of {{ $fmt($selectedRow->counted_amount ?? $selectedRow->expected_amount) }} on {{ \Carbon\Carbon::parse($selectedRow->confirmed_at)->format('M d, Y · g:i A') }}.
                                    </div>
                                </div>
                            @elseif($selectedRow->status === \App\Models\Remittance::STATUS_NOT_REMITTED)
                                <div style="background: #F9FAFB; border: 1px solid var(--line); border-radius: 8px; padding: 14px; margin-bottom: 18px; text-align: center;">
                                    <div style="font-size: 24px; margin-bottom: 4px;">⏳</div>
                                    <div style="font-weight: 800; font-size: 13px; color: var(--plum); margin-bottom: 4px;">
                                        Awaiting Rider Submission
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--muted); line-height: 1.4;">
                                        Rider has not submitted their End-of-Day remit run from the mobile app yet.
                                    </div>
                                </div>
                            @endif

                            <!-- COD Parcels List -->
                            <div style="margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span style="font-size: 12px; font-weight: 800; color: var(--plum); text-transform: uppercase;">
                                        Delivered COD Parcels ({{ $selectedRow->delivered_cod_parcels->count() }})
                                    </span>
                                    <span style="font-size: 11.5px; font-weight: 700; color: var(--orange);">
                                        {{ $fmt($selectedRow->expected_amount) }} Total
                                    </span>
                                </div>
                                <div style="max-height: 240px; overflow-y: auto;">
                                    @forelse($selectedRow->delivered_cod_parcels as $p)
                                        @php
                                            $meta = $p->metadata ?? [];
                                            $codVal = (float) ($meta['cod_amount'] ?? ($p->order?->total_amount ?? 0));
                                        @endphp
                                        <div class="parcel-item">
                                            <div>
                                                <div style="font-weight: 800; color: var(--plum);">{{ $p->tracking_number }}</div>
                                                <div style="font-size: 10.5px; color: var(--muted);">{{ $p->recipient_name }}</div>
                                            </div>
                                            <div style="text-align: right;">
                                                <strong style="color: var(--orange); font-size: 12.5px;">{{ $fmt($codVal) }}</strong>
                                                <div style="font-size: 10px; color: #1E6B37;">Delivered</div>
                                            </div>
                                        </div>
                                    @empty
                                        <div style="padding: 16px; text-align: center; color: var(--muted); font-size: 12px; background: #FAF9F6; border-radius: 8px;">
                                            No delivered COD parcels for this intake date.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="padding: 50px 20px; text-align: center; color: var(--muted);">
                            <div style="font-size: 32px; margin-bottom: 10px;">👈</div>
                            <div style="font-weight: 800; font-size: 14px; color: var(--plum);">Select a Rider</div>
                            <div style="font-size: 12px; margin-top: 4px;">Click on any row in the table to review COD cash collections.</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- 7. Activity Feed (Audit trail) -->
            <div style="margin-top: 36px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h3 style="font-size: 14px; font-weight: 800; color: var(--plum); text-transform: uppercase; letter-spacing: 0.5px; margin: 0;">
                        Today's Remittance Audit Activity
                    </h3>
                    <span style="font-size: 11.5px; color: var(--muted);">Recent {{ $activityLogs->count() }} events</span>
                </div>
                <div style="background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px 20px;">
                    @forelse($activityLogs as $log)
                        @php
                            $evt = strtolower($log->event ?? '');
                        @endphp
                        <div class="activity-feed-item">
                            <span style="font-size: 15px;">
                                @if(str_contains($evt, 'confirm'))
                                    💵
                                @elseif(str_contains($evt, 'submit'))
                                    📥
                                @else
                                    📝
                                @endif
                            </span>
                            <div style="flex: 1;">
                                <div style="color: var(--plum); font-weight: 700;">
                                    {{ $log->user?->name ?? 'System' }}: {{ ucwords(str_replace(['remittance_', '_'], ['', ' '], $log->event ?? 'Activity')) }}
                                </div>
                                @if(!empty($log->metadata['note']) || !empty($log->metadata['cashier_note']))
                                    <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                                        Note: "{{ $log->metadata['note'] ?? $log->metadata['cashier_note'] }}"
                                    </div>
                                @endif
                            </div>
                            <div style="color: var(--muted); font-size: 11px; white-space: nowrap;">
                                {{ \Carbon\Carbon::parse($log->created_at ?? now())->timezone('Asia/Manila')->format('g:i A') }}
                            </div>
                        </div>
                    @empty
                        <div style="padding: 16px 0; text-align: center; color: var(--muted); font-size: 12px;">
                            No remittance activity logged yet today.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal: Set Cut-off Time -->
<div id="cutoffModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:14px; width:92%; max-width:400px; padding:22px; box-shadow:0 8px 30px rgba(0,0,0,0.2);">
        <h3 style="margin:0 0 8px 0; color:var(--plum); font-size:16px; font-weight:800;">Set Hub Remit Cut-off</h3>
        <p style="font-size:12px; color:var(--muted); margin:0 0 16px 0;">Configure the daily end-of-day deadline for physical cash submission at {{ $hubName }} Hub.</p>
        <form method="POST" action="{{ route('remittance.cutoff') }}">
            @csrf
            @if($isMasterAdmin)
                <input type="hidden" name="hub" value="{{ $hubName }}">
            @endif
            <div style="margin-bottom:16px;">
                <label for="modal-cutoff-time" style="display:block; font-size:11.5px; font-weight:700; color:var(--plum); margin-bottom:4px;">Daily Cut-off Time</label>
                <input type="time" id="modal-cutoff-time" name="cutoff_time" value="{{ substr($cutoffTime, 0, 5) }}" required
                    style="width:100%; padding:8px 12px; border:1px solid var(--line); border-radius:8px; font-size:14px;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="rr-btn secondary" onclick="closeCutoffModal()">Cancel</button>
                <button type="submit" class="rr-btn primary">Save Cut-off</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCutoffModal() {
        document.getElementById('cutoffModal').style.display = 'flex';
    }
    function closeCutoffModal() {
        document.getElementById('cutoffModal').style.display = 'none';
    }

    function checkDifference(countedVal, expectedVal) {
        const counted = parseFloat(countedVal) || 0;
        const diff = expectedVal - counted;
        const alertBox = document.getElementById('diffAlertBox');
        const noteArea = document.getElementById('cashier_note');
        const btn = document.getElementById('confirmRemitBtn');

        if (Math.abs(diff) < 0.01) {
            alertBox.style.display = 'block';
            alertBox.style.background = '#ECFDF5';
            alertBox.style.color = '#065F46';
            alertBox.style.border = '1px solid #A7F3D0';
            alertBox.innerText = '✓ Exact match. Status: Remitted.';
            noteArea.required = false;
            btn.innerText = '✓ Confirm Cash Intake';
        } else if (diff > 0) {
            alertBox.style.display = 'block';
            alertBox.style.background = '#FEF2F2';
            alertBox.style.color = '#991B1B';
            alertBox.style.border = '1px solid #FECACA';
            alertBox.innerText = '⚠️ Shortage: ₱' + diff.toFixed(2) + ' less than expected. Status will be marked as Short. Note required.';
            noteArea.required = true;
            btn.innerText = '⚠️ Confirm Intake with Shortage (₱' + diff.toFixed(2) + ')';
        } else {
            const over = Math.abs(diff);
            alertBox.style.display = 'block';
            alertBox.style.background = '#EFF6FF';
            alertBox.style.color = '#1E40AF';
            alertBox.style.border = '1px solid #BFDBFE';
            alertBox.innerText = 'ℹ️ Overage: ₱' + over.toFixed(2) + ' more than expected. Status will be marked as Over. Note required.';
            noteArea.required = true;
            btn.innerText = 'ℹ️ Confirm Intake with Overage (+₱' + over.toFixed(2) + ')';
        }
    }

    function dismissBanner() {
        document.getElementById('liveRemitBanner').style.display = 'none';
    }

    // 30-second live polling for new submissions
    let lastLatestSubmission = null;
    async function pollRemittances() {
        try {
            const params = new URLSearchParams({
                date: '{{ $dateStr }}',
                @if($isMasterAdmin) hub: '{{ $hubName }}' @endif
            });
            const res = await fetch('{{ route('remittance.poll') }}?' + params.toString(), {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.latest_submission) {
                const sub = data.latest_submission;
                const banner = document.getElementById('liveRemitBanner');
                const bannerMsg = document.getElementById('liveBannerMessage');
                const reviewBtn = document.getElementById('liveBannerReviewBtn');

                if (!lastLatestSubmission || lastLatestSubmission.remittance_id !== sub.remittance_id) {
                    lastLatestSubmission = sub;
                    bannerMsg.innerText = `New remit: ${sub.rider_name} submitted ₱${parseFloat(sub.declared_amount).toFixed(2)} at ${sub.submitted_at}`;
                    reviewBtn.href = `{{ route('remittance.index') }}?date={{ $dateStr }}&rider_id=${sub.rider_id}&tab=awaiting_cashier`;
                    banner.style.display = 'flex';
                }
            }
        } catch (e) {
            // silent fail on network blip
        }
    }

    // Initial check on load
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('counted_amount');
        if (input) {
            checkDifference(input.value, {{ $selectedRow ? $selectedRow->expected_amount : 0 }});
        }
        setInterval(pollRemittances, 30000);
    });
</script>
</body>
</html>
