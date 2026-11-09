<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $hubName }} Hub Commission — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
@php
    $fmt = fn (int $c) => \App\Models\CommissionRule::formatPeso($c);
    $baseQuery = array_filter([
        'period' => $selectedPeriod['key'],
        'search' => $search !== '' ? $search : null,
        'tab' => $activeTab !== 'all' ? $activeTab : null,
    ], fn ($v) => $v !== null);
@endphp
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => $hubName . ' Branch Hub',
            'title' => 'Commission'
        ])

        <div class="content-wrap module-page comm-page">
            <!-- Breadcrumb -->
            <nav class="comm-breadcrumb" aria-label="Breadcrumb">
                <span>{{ $hubName }} Branch Hub</span>
                <span class="sep">/</span>
                <span class="curr">Commission</span>
            </nav>

            <!-- 2. Header -->
            <section class="welcome-row module-heading comm-heading">
                <div>
                    <div class="eyebrow">{{ strtoupper($hubName) }} HUB · RIDER EARNINGS</div>
                    <h1>Commission</h1>
                    <p class="intro">Review rider delivery earnings, verify on-time bonuses, and record branch cash payouts. Commission rates and rules are set by Headquarters.</p>
                </div>
                <div class="comm-header-actions">
                    <a class="rr-btn secondary" href="{{ route('commission.export', $baseQuery) }}" aria-label="Export hub commission table to CSV">
                        ⇩ Export CSV
                    </a>
                    <button type="button" class="rr-btn primary" id="hub-bulk-paid-btn" disabled onclick="openHubBulkPaidModal()">
                        ✓ Mark selected as paid (0)
                    </button>
                </div>
            </section>

            @if (session('status'))
                <div class="server-alert" role="status"><span>✓</span>{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>
            @endif

            <!-- 3. Filter (Pay period & Rider Search) -->
            <form id="hubCommFilterForm" method="GET" action="{{ route('commission.hub') }}" class="comm-filters" aria-label="Hub commission filters" style="grid-template-columns: minmax(220px, 280px) minmax(220px, 1fr);">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                @if($selectedCommission)
                    <input type="hidden" name="selected" id="hubSelectedInput" value="{{ $selectedCommission->id }}">
                @else
                    <input type="hidden" name="selected" id="hubSelectedInput" value="">
                @endif
                <div class="rr-filter-field">
                    <label for="hub-period-select">Pay period</label>
                    <select id="hub-period-select" name="period" class="rr-filter-select" onchange="this.form.submit()">
                        @foreach($periods as $p)
                            <option value="{{ $p['key'] }}" @selected($p['key'] === $selectedPeriod['key'])>{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rr-filter-field comm-search-field">
                    <label for="hub-search-input">Search rider</label>
                    <div class="rr-search-wrap">
                        <span class="rr-search-icon" aria-hidden="true">⌕</span>
                        <input id="hub-search-input" name="search" type="search" class="rr-filter-input" value="{{ $search }}" placeholder="Search by rider name..." autocomplete="off">
                    </div>
                </div>
            </form>

            <!-- 4. Summary cards -->
            <section class="rr-stat-grid" aria-label="Hub commission metrics for {{ $selectedPeriod['label'] }}">
                <article class="rr-stat-card">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Hub net commission</span>
                        <span class="rr-stat-pill neutral">{{ number_format($stats['delivered_count']) }} delivered · {{ $stats['riders_count'] }} riders</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['hub_net_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $hubName }} Hub total earnings this period</span>
                </article>

                <article class="rr-stat-card urgent">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Awaiting HQ approval</span>
                        <span class="rr-stat-pill warn">{{ $stats['awaiting_hq_count'] }} {{ $stats['awaiting_hq_count'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['awaiting_hq_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['awaiting_hq_count'] === 0 ? 'No records awaiting HQ' : 'Pending headquarters review' }}</span>
                </article>

                <article class="rr-stat-card evidence">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Ready to pay</span>
                        <span class="rr-stat-pill info">{{ $stats['ready_to_pay_count'] }} {{ $stats['ready_to_pay_count'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['ready_to_pay_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['ready_to_pay_count'] === 0 ? 'No approved payouts waiting' : 'Approved by HQ — ready for cash payout' }}</span>
                </article>

                <article class="rr-stat-card sla-time">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Paid out</span>
                        <span class="rr-stat-pill neutral">{{ $stats['paid_count'] }} {{ $stats['paid_count'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['paid_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['paid_count'] === 0 ? 'No payouts recorded yet' : 'Disbursed and recorded' }}</span>
                </article>
            </section>

            <div class="comm-layout">
                <!-- Left: Status Tabs, Rider Table & Pagination -->
                <section class="panel comm-table-panel" aria-label="Hub rider commission table">
                    <div class="rr-tabs-bar">
                        <div class="rr-status-tabs" role="tablist" aria-label="Commission status">
                            @php
                                $hubTabs = [
                                    'all' => 'All',
                                    'awaiting_hq' => 'Awaiting HQ',
                                    'ready_to_pay' => 'Ready to pay',
                                    'paid' => 'Paid',
                                ];
                            @endphp
                            @foreach($hubTabs as $tabKey => $tabLabel)
                                @php
                                    $tq = array_merge($baseQuery, ['tab' => $tabKey]);
                                    unset($tq['page']);
                                @endphp
                                <a href="{{ route('commission.hub', $tq) }}" role="tab" aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}" class="rr-status-tab {{ $activeTab === $tabKey ? 'active' : '' }}">
                                    <span>{{ $tabLabel }}</span>
                                    <span class="rr-tab-count">{{ $tabCounts[$tabKey] ?? 0 }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="rr-table-scroll">
                        <table class="rr-table comm-table">
                            <thead>
                                <tr>
                                    <th scope="col">
                                        <input type="checkbox" id="hub-select-all" aria-label="Select all ready to pay riders on this page" onchange="toggleAllHubReady(this.checked)">
                                    </th>
                                    <th scope="col">Rider</th>
                                    <th scope="col" class="num">Delivered</th>
                                    <th scope="col" class="num">Base</th>
                                    <th scope="col" class="num">Bonus</th>
                                    <th scope="col" class="num">Deductions</th>
                                    <th scope="col" class="num">Net</th>
                                    <th scope="col">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $rc)
                                    @php
                                        $riderName = $rc->rider?->user?->name ?? ('Rider #' . $rc->rider_profile_id);
                                        $initials = collect(preg_split('/\s+/', trim($riderName)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                                        $returnIds = is_array($rc->return_parcel_ids) ? $rc->return_parcel_ids : [];
                                        $isSelected = $selectedCommission && $selectedCommission->id === $rc->id;
                                        $isReady = $rc->status === 'approved';
                                    @endphp
                                    <tr
                                        id="hub-row-{{ $rc->id }}"
                                        data-commission-id="{{ $rc->id }}"
                                        class="{{ $isSelected ? 'rr-row-selected' : '' }} {{ $rc->is_flagged ? 'rr-row-warning' : '' }}"
                                        onclick="selectHubCommission({{ $rc->id }}, event)"
                                        style="cursor: pointer;"
                                        tabindex="0"
                                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectHubCommission({{ $rc->id }}, event);}"
                                    >
                                        <td onclick="event.stopPropagation()">
                                            @if($isReady)
                                                <input
                                                    type="checkbox"
                                                    class="hub-ready-checkbox"
                                                    value="{{ $rc->id }}"
                                                    data-rider="{{ $riderName }}"
                                                    data-net="{{ $rc->formatted_net }}"
                                                    aria-label="Select {{ $riderName }} for bulk payout"
                                                    onchange="updateHubSelection()"
                                                >
                                            @else
                                                <input
                                                    type="checkbox"
                                                    disabled
                                                    aria-label="{{ $rc->status === 'pending' ? 'Disabled: Awaiting HQ approval before payout' : 'Disabled: Already marked as paid' }}"
                                                    title="{{ $rc->status === 'pending' ? 'Awaiting HQ approval' : 'Already paid' }}"
                                                >
                                            @endif
                                        </td>
                                        <td>
                                            <div class="comm-rider-cell">
                                                <span class="comm-avatar" aria-hidden="true">{{ $initials ?: 'R' }}</span>
                                                <div>
                                                    <span class="rr-cell-title">{{ $riderName }}</span>
                                                    <span class="rr-cell-sub">{{ number_format($rc->on_time_rate_percent, 1) }}% on-time</span>
                                                    @if($rc->is_flagged)
                                                        <span class="rr-cell-sub" style="color: var(--orange); font-weight: 700;">⚠ Flagged to HQ</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="num">{{ number_format($rc->delivered_count) }}</td>
                                        <td class="num">{{ $rc->formatted_base }}</td>
                                        <td class="num {{ $rc->bonus_centavos > 0 ? 'comm-positive' : 'comm-muted' }}">{{ $rc->formatted_bonus }}</td>
                                        <td class="num" onclick="event.stopPropagation()">
                                            @if($rc->deductions_centavos > 0 && count($returnIds) > 0)
                                                <a class="comm-deduction-link" href="{{ route('exceptions.index', ['ids' => implode(',', $returnIds)]) }}" aria-label="View {{ $rc->return_fault_count }} return cases">{{ $rc->formatted_deductions }}</a>
                                                <span class="rr-cell-sub">{{ $rc->return_fault_count }} {{ $rc->return_fault_count === 1 ? 'return' : 'returns' }}</span>
                                            @else
                                                <span class="comm-muted">{{ $fmt(0) }}</span>
                                            @endif
                                        </td>
                                        <td class="num"><strong class="comm-net">{{ $rc->formatted_net }}</strong></td>
                                        <td>
                                            @if($rc->status === 'pending')
                                                <span class="comm-status pending"><b></b>Awaiting HQ</span>
                                            @elseif($rc->status === 'approved')
                                                <span class="comm-status approved"><b></b>Ready to pay</span>
                                            @elseif($rc->status === 'paid')
                                                <span class="comm-status paid"><b></b>Paid</span>
                                                <span class="rr-cell-sub">{{ $rc->paid_at?->copy()->setTimezone('Asia/Manila')->format('M j, Y') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            <div class="rr-empty-box">
                                                <div class="rr-empty-icon" aria-hidden="true">₱</div>
                                                <h3>No commission records for this period</h3>
                                                <p>
                                                    @if($search !== '' || $activeTab !== 'all')
                                                        No riders match the current search or status filter.
                                                    @else
                                                        Commissions appear here once riders at {{ $hubName }} Hub deliver parcels during {{ $selectedPeriod['label'] }}.
                                                    @endif
                                                </p>
                                                @if($search !== '' || $activeTab !== 'all')
                                                    <a href="{{ route('commission.hub', ['period' => $selectedPeriod['key']]) }}" class="rr-btn secondary">Reset filters</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer" style="margin-top: 14px;">
                        <span>Showing <strong>{{ $commissions->firstItem() ?? 0 }}–{{ $commissions->lastItem() ?? 0 }}</strong> of <strong>{{ $commissions->total() }}</strong> {{ $commissions->total() === 1 ? 'rider' : 'riders' }}</span>
                        <div class="orders-pagination">{{ $commissions->onEachSide(1)->links() }}</div>
                    </div>
                </section>

                <!-- Right: Rider Detail Drawer & Rates Panel -->
                <div class="comm-side">
                    <!-- 7. Rider Detail Panel -->
                    <section class="panel comm-side-panel" id="hubDetailPanel" aria-labelledby="hub-detail-title">
                        <div class="comm-side-head">
                            <h2 id="hub-detail-title">Rider detail</h2>
                            <span class="rr-status-pill {{ $selectedCommission ? $selectedCommission->status : 'pending' }}" id="hub-detail-status-pill">
                                <b></b><span id="hub-detail-status-text">{{ $selectedCommission ? ($selectedCommission->status === 'approved' ? 'Ready to pay' : ($selectedCommission->status === 'pending' ? 'Awaiting HQ' : 'Paid')) : 'Select rider' }}</span>
                            </span>
                        </div>

                        @if($selectedCommission)
                            <div id="hubDetailContent">
                                <h3 id="hub-detail-rider-name" style="margin: 0 0 4px; font-size: 16px; color: var(--plum); font-weight: 800;">
                                    {{ $selectedCommission->rider?->user?->name ?? 'Rider #' . $selectedCommission->rider_profile_id }}
                                </h3>
                                <span class="rr-cell-sub" id="hub-detail-period-label">{{ $selectedCommission->period_label }} · {{ $hubName }} Hub</span>

                                @if($selectedCommission->is_flagged)
                                    <div id="hub-detail-flag-banner" style="margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: #FFF4E5; border: 1px solid #FFE2B8; color: #8A4B00; font-size: 11.5px; line-height: 1.4;">
                                        <strong>⚠ Issue flagged to HQ:</strong>
                                        <p style="margin: 3px 0 0;">{{ $selectedCommission->flagged_note }}</p>
                                    </div>
                                @else
                                    <div id="hub-detail-flag-banner" style="display: none; margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: #FFF4E5; border: 1px solid #FFE2B8; color: #8A4B00; font-size: 11.5px; line-height: 1.4;"></div>
                                @endif

                                <div class="comm-breakdown-card" style="margin-top: 14px; background: var(--blush-1); border: 1px solid var(--line); border-radius: 12px; padding: 14px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px;">
                                        <span style="color: var(--muted);">Delivered parcels</span>
                                        <strong id="hub-detail-delivered" style="color: var(--ink);">{{ number_format($selectedCommission->delivered_count) }} parcels</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px;">
                                        <span style="color: var(--muted);">Base calculation</span>
                                        <strong id="hub-detail-base-calc" style="color: var(--ink);">
                                            {{ $selectedCommission->delivered_count }} × ₱{{ number_format(($selectedCommission->rules_snapshot['base_rate_centavos'] ?? 1800) / 100, 0) }} = {{ $selectedCommission->formatted_base }}
                                        </strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12.5px;">
                                        <span style="color: var(--muted);">On-time bonus ({{ number_format($selectedCommission->on_time_rate_percent, 1) }}%)</span>
                                        <strong id="hub-detail-bonus" class="{{ $selectedCommission->bonus_centavos > 0 ? 'comm-positive' : 'comm-muted' }}">
                                            {{ $selectedCommission->formatted_bonus }}
                                        </strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 12.5px;">
                                        <span style="color: var(--muted);">Deductions ({{ $selectedCommission->return_fault_count }} returns)</span>
                                        <strong id="hub-detail-deductions" class="{{ $selectedCommission->deductions_centavos > 0 ? 'comm-negative' : 'comm-muted' }}">
                                            {{ $selectedCommission->formatted_deductions }}
                                        </strong>
                                    </div>
                                    <div style="border-top: 1px dashed var(--line); padding-top: 10px; display: flex; justify-content: space-between; align-items: baseline;">
                                        <span style="font-size: 13px; font-weight: 800; color: var(--plum);">Net to pay</span>
                                        <strong id="hub-detail-net" style="font-size: 20px; font-family: 'Playfair Display', serif; color: var(--plum);">
                                            {{ $selectedCommission->formatted_net }}
                                        </strong>
                                    </div>
                                </div>

                                <!-- Action controls -->
                                <div id="hubDetailActions" style="margin-top: 14px;">
                                    @if($selectedCommission->status === 'approved')
                                        <form method="POST" id="hubSinglePayForm" action="{{ route('commission.mark-paid', $selectedCommission->id) }}" onsubmit="return handleSinglePaySubmit(event)">
                                            @csrf
                                            <label for="hub-single-payment-ref" style="font-size: 11.5px; color: var(--muted); font-weight: 600; display: block; margin-bottom: 6px;">
                                                Payment reference (optional)
                                            </label>
                                            <input
                                                id="hub-single-payment-ref"
                                                name="payment_reference"
                                                maxlength="160"
                                                placeholder="e.g. GCash ref, voucher no."
                                                style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 12px; margin-bottom: 10px; background: white;"
                                            >
                                            <button type="submit" id="hub-single-pay-btn" class="rr-btn primary" style="width: 100%; justify-content: center;">
                                                ✓ Mark as paid
                                            </button>
                                        </form>
                                    @elseif($selectedCommission->status === 'paid')
                                        <div style="padding: 10px 12px; border-radius: 8px; background: #E3F2E7; color: #1E6B37; font-size: 12px; font-weight: 600;">
                                            ✓ Paid on {{ $selectedCommission->paid_at?->copy()->setTimezone('Asia/Manila')->format('M j, Y') }}
                                            @if($selectedCommission->payment_reference)
                                                <div style="font-size: 11px; color: var(--ink); margin-top: 2px;">Ref: {{ $selectedCommission->payment_reference }}</div>
                                            @endif
                                        </div>
                                    @else
                                        <div style="padding: 10px 12px; border-radius: 8px; background: #FFF1DC; color: #8A4B00; font-size: 12px; font-weight: 600;">
                                            ⏳ Awaiting HQ approval before payout can be disbursed.
                                        </div>
                                    @endif

                                    <!-- Flag issue to HQ button -->
                                    <div style="margin-top: 10px;">
                                        <button
                                            type="button"
                                            class="rr-btn secondary"
                                            style="width: 100%; justify-content: center; font-size: 12px; min-height: 40px;"
                                            onclick="openFlagModal({{ $selectedCommission->id }}, '{{ addslashes($selectedCommission->rider?->user?->name ?? 'Rider') }}')"
                                        >
                                            ⚐ Flag an issue to HQ
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="rr-empty-box" style="padding: 24px 12px;">
                                <div class="rr-empty-icon" aria-hidden="true">📋</div>
                                <h3>No rider selected</h3>
                                <p>Select any rider from the table to view the earnings breakdown and record payout.</p>
                            </div>
                        @endif
                    </section>

                    <!-- 8. Rates Panel (Read-only, labelled "Set by Headquarters") -->
                    <section class="panel comm-side-panel" aria-labelledby="hub-rates-title">
                        <div class="comm-side-head">
                            <h2 id="hub-rates-title">Rates</h2>
                            <span class="rr-cell-sub" style="font-weight: 700; color: var(--orange);">Set by Headquarters</span>
                        </div>
                        <dl class="comm-rules-list">
                            <div><dt>Base rate</dt><dd>{{ $fmt((int) $rule->base_rate_centavos) }} / delivered parcel</dd></div>
                            <div><dt>On-time ≥ {{ $rule->bonus_tier1_rate_pct }}%</dt><dd class="comm-positive">+{{ $fmt((int) $rule->bonus_tier1_centavos) }}</dd></div>
                            <div><dt>On-time ≥ {{ $rule->bonus_tier2_rate_pct }}%</dt><dd class="comm-positive">+{{ $fmt((int) $rule->bonus_tier2_centavos) }}</dd></div>
                            <div><dt>Rider-fault return</dt><dd class="comm-negative">−{{ $fmt((int) $rule->deduction_per_return_centavos) }} each</dd></div>
                            <div><dt>Pay period</dt><dd>Semi-monthly (1–15, 16–end)</dd></div>
                        </dl>
                        <p class="comm-note">Rates and bonus criteria are centrally governed by Lumora Headquarters.</p>
                    </section>
                </div>
            </div>

            <footer class="page-footer">
                <span>© 2026 Lumora Logistics · {{ $hubName }} Branch</span>
                <span>Branch cash payouts are recorded in the central audit log.</span>
            </footer>
        </div>
    </main>
</div>

<!-- Bulk Mark as Paid Dialog -->
<div class="modal-backdrop" id="hubBulkPaidModal" hidden role="dialog" aria-modal="true" aria-labelledby="hubBulkPaidTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close bulk payout dialog" onclick="closeModal('hubBulkPaidModal')">✕</button>
        <div class="eyebrow">BRANCH PAYOUT</div>
        <h2 id="hubBulkPaidTitle">Mark selected as paid</h2>
        <p class="modal-intro" id="hubBulkPaidIntro">Record cash or bank disbursement for selected approved riders.</p>
        <form method="POST" action="{{ route('commission.bulk-paid') }}" onsubmit="return handleBulkPaySubmit(event)">
            @csrf
            <div id="hubBulkHiddenInputs"></div>
            <label for="hub-bulk-payment-ref">
                Payment reference (optional)
                <input id="hub-bulk-payment-ref" name="payment_reference" maxlength="160" placeholder="e.g. Batch disbursement voucher no.">
            </label>
            <p class="comm-note">Date, time, and your account ({{ auth()->user()->name }}) will be recorded in the audit log.</p>
            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeModal('hubBulkPaidModal')">Cancel</button>
                <button type="submit" id="hub-bulk-confirm-btn" class="rr-btn primary">Confirm payouts</button>
            </div>
        </form>
    </div>
</div>

<!-- Flag an Issue to HQ Modal -->
<div class="modal-backdrop" id="hubFlagModal" hidden role="dialog" aria-modal="true" aria-labelledby="hubFlagTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close flag issue dialog" onclick="closeModal('hubFlagModal')">✕</button>
        <div class="eyebrow">HEADQUARTERS ESCALATION</div>
        <h2 id="hubFlagTitle">Flag an issue to HQ</h2>
        <p class="modal-intro" id="hubFlagIntro">Explain the discrepancy or issue for Headquarters review. Flagging blocks approval until HQ responds.</p>
        <form method="POST" id="hubFlagForm" action="" onsubmit="return handleFlagSubmit(event)">
            @csrf
            <label for="hub-flag-note">
                Issue description (Required, at least 5 characters)
                <textarea id="hub-flag-note" name="note" required minlength="5" maxlength="1000" placeholder="e.g. Rider Maria reported 5 delivered parcels were counted as failed on Nov 14 due to barcode damage. Please adjust." style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid var(--line); border-radius: 8px; font-size: 12.5px;"></textarea>
            </label>
            <small id="hub-flag-note-error" style="display: none; color: var(--status-failed); font-size: 11px; font-weight: 700; margin-bottom: 8px;">
                Please enter a detailed explanation before flagging to HQ.
            </small>
            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeModal('hubFlagModal')">Cancel</button>
                <button type="submit" id="hub-flag-confirm-btn" class="rr-btn danger">Flag to HQ</button>
            </div>
        </form>
    </div>
</div>

@php
    $hubRowsPayload = collect($commissions->items())->mapWithKeys(function ($rc) {
        return [
            $rc->id => [
                'id' => $rc->id,
                'rider_name' => $rc->rider?->user?->name ?? ('Rider #' . $rc->rider_profile_id),
                'period_label' => $rc->period_label,
                'hub_branch' => $rc->hub_branch,
                'status' => $rc->status,
                'status_label' => $rc->status === 'approved' ? 'Ready to pay' : ($rc->status === 'pending' ? 'Awaiting HQ' : 'Paid'),
                'delivered_count' => number_format($rc->delivered_count),
                'base_rate' => number_format(($rc->rules_snapshot['base_rate_centavos'] ?? 1800) / 100, 0),
                'base_formatted' => $rc->formatted_base,
                'bonus_centavos' => $rc->bonus_centavos,
                'bonus_formatted' => $rc->formatted_bonus,
                'on_time_pct' => number_format($rc->on_time_rate_percent, 1),
                'deductions_centavos' => $rc->deductions_centavos,
                'deductions_formatted' => $rc->formatted_deductions,
                'return_fault_count' => $rc->return_fault_count,
                'net_formatted' => $rc->formatted_net,
                'is_flagged' => (bool) $rc->is_flagged,
                'flagged_note' => $rc->flagged_note,
                'paid_at' => $rc->paid_at ? $rc->paid_at->copy()->setTimezone('Asia/Manila')->format('M j, Y') : null,
                'payment_reference' => $rc->payment_reference,
                'pay_url' => route('commission.mark-paid', $rc->id),
                'flag_url' => route('commission.flag', $rc->id),
            ],
        ];
    });
@endphp

<script>
const HUB_COMMISSIONS = @json($hubRowsPayload);
const FLAG_URL_TEMPLATE = @json(route('commission.flag', ['riderCommission' => '__ID__']));

// Instant search debounce
const hubSearchInput = document.getElementById('hub-search-input');
let hubSearchTimer = null;
if (hubSearchInput) {
    hubSearchInput.addEventListener('input', () => {
        clearTimeout(hubSearchTimer);
        hubSearchTimer = setTimeout(() => {
            document.getElementById('hubCommFilterForm').submit();
        }, 400);
    });
}

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function selectHubCommission(id, event) {
    const data = HUB_COMMISSIONS[id];
    if (!data) return;

    document.querySelectorAll('.comm-table tbody tr').forEach(tr => {
        tr.classList.toggle('rr-row-selected', Number(tr.dataset.commissionId) === Number(id));
    });

    const selectedInput = document.getElementById('hubSelectedInput');
    if (selectedInput) selectedInput.value = String(id);

    // Keep selected in URL
    const url = new URL(window.location.href);
    url.searchParams.set('selected', String(id));
    window.history.replaceState({}, '', url.toString());

    // Update detail panel
    const riderNameEl = document.getElementById('hub-detail-rider-name');
    const periodEl = document.getElementById('hub-detail-period-label');
    const statusPill = document.getElementById('hub-detail-status-pill');
    const statusText = document.getElementById('hub-detail-status-text');
    const deliveredEl = document.getElementById('hub-detail-delivered');
    const baseCalcEl = document.getElementById('hub-detail-base-calc');
    const bonusEl = document.getElementById('hub-detail-bonus');
    const deductEl = document.getElementById('hub-detail-deductions');
    const netEl = document.getElementById('hub-detail-net');
    const flagBanner = document.getElementById('hub-detail-flag-banner');
    const actionsContainer = document.getElementById('hubDetailActions');

    if (riderNameEl) riderNameEl.textContent = data.rider_name;
    if (periodEl) periodEl.textContent = `${data.period_label} · ${data.hub_branch} Hub`;
    if (statusPill) statusPill.className = `rr-status-pill ${data.status}`;
    if (statusText) statusText.textContent = data.status_label;
    if (deliveredEl) deliveredEl.textContent = `${data.delivered_count} parcels`;
    if (baseCalcEl) baseCalcEl.textContent = `${data.delivered_count} × ₱${data.base_rate} = ${data.base_formatted}`;
    if (bonusEl) {
        bonusEl.className = data.bonus_centavos > 0 ? 'comm-positive' : 'comm-muted';
        bonusEl.textContent = data.bonus_formatted;
    }
    if (deductEl) {
        deductEl.className = data.deductions_centavos > 0 ? 'comm-negative' : 'comm-muted';
        deductEl.textContent = data.deductions_formatted;
    }
    if (netEl) netEl.textContent = data.net_formatted;

    if (flagBanner) {
        if (data.is_flagged && data.flagged_note) {
            flagBanner.style.display = 'block';
            flagBanner.innerHTML = `<strong>⚠ Issue flagged to HQ:</strong><p style="margin: 3px 0 0;">${escapeHtml(data.flagged_note)}</p>`;
        } else {
            flagBanner.style.display = 'none';
        }
    }

    if (actionsContainer) {
        let actHtml = '';
        if (data.status === 'approved') {
            actHtml = `
                <form method="POST" id="hubSinglePayForm" action="${escapeHtml(data.pay_url)}" onsubmit="return handleSinglePaySubmit(event)">
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                    <label for="hub-single-payment-ref" style="font-size: 11.5px; color: var(--muted); font-weight: 600; display: block; margin-bottom: 6px;">
                        Payment reference (optional)
                    </label>
                    <input id="hub-single-payment-ref" name="payment_reference" maxlength="160" placeholder="e.g. GCash ref, voucher no." style="width: 100%; padding: 9px 12px; border: 1px solid var(--line); border-radius: 8px; font-size: 12px; margin-bottom: 10px; background: white;">
                    <button type="submit" id="hub-single-pay-btn" class="rr-btn primary" style="width: 100%; justify-content: center;">
                        ✓ Mark as paid
                    </button>
                </form>`;
        } else if (data.status === 'paid') {
            actHtml = `
                <div style="padding: 10px 12px; border-radius: 8px; background: #E3F2E7; color: #1E6B37; font-size: 12px; font-weight: 600;">
                    ✓ Paid on ${escapeHtml(data.paid_at || 'record')}
                    ${data.payment_reference ? `<div style="font-size: 11px; color: var(--ink); margin-top: 2px;">Ref: ${escapeHtml(data.payment_reference)}</div>` : ''}
                </div>`;
        } else {
            actHtml = `
                <div style="padding: 10px 12px; border-radius: 8px; background: #FFF1DC; color: #8A4B00; font-size: 12px; font-weight: 600;">
                    ⏳ Awaiting HQ approval before payout can be disbursed.
                </div>`;
        }

        actHtml += `
            <div style="margin-top: 10px;">
                <button type="button" class="rr-btn secondary" style="width: 100%; justify-content: center; font-size: 12px; min-height: 40px;" onclick="openFlagModal(${data.id}, '${escapeHtml(data.rider_name)}')">
                    ⚐ Flag an issue to HQ
                </button>
            </div>`;
        actionsContainer.innerHTML = actHtml;
    }

    if (window.innerWidth <= 1180 && event && event.type === 'click') {
        const panel = document.getElementById('hubDetailPanel');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Bulk Selection (Ready to pay rows only)
function selectedHubBoxes() {
    return Array.from(document.querySelectorAll('.hub-ready-checkbox:checked'));
}

function updateHubSelection() {
    const allReady = document.querySelectorAll('.hub-ready-checkbox');
    const sel = selectedHubBoxes();
    const btn = document.getElementById('hub-bulk-paid-btn');
    const master = document.getElementById('hub-select-all');

    if (btn) {
        btn.disabled = sel.length === 0;
        btn.textContent = `✓ Mark selected as paid (${sel.length})`;
    }
    if (master) {
        master.checked = allReady.length > 0 && sel.length === allReady.length;
        master.indeterminate = sel.length > 0 && sel.length < allReady.length;
        master.disabled = allReady.length === 0;
    }
}

function toggleAllHubReady(checked) {
    document.querySelectorAll('.hub-ready-checkbox').forEach(cb => cb.checked = checked);
    updateHubSelection();
}

function openHubBulkPaidModal() {
    const sel = selectedHubBoxes();
    if (!sel.length) return;
    document.getElementById('hubBulkHiddenInputs').innerHTML = sel.map(cb => `<input type="hidden" name="commission_ids[]" value="${escapeHtml(cb.value)}">`).join('');
    const names = sel.slice(0, 4).map(cb => `${cb.dataset.rider} (${cb.dataset.net})`).join(', ');
    document.getElementById('hubBulkPaidIntro').textContent =
        `Record payout for ${sel.length} ${sel.length === 1 ? 'rider' : 'riders'}: ${names}${sel.length > 4 ? ', …' : ''}.`;
    document.getElementById('hubBulkPaidModal').hidden = false;
}

function openFlagModal(id, riderName) {
    document.getElementById('hubFlagForm').action = FLAG_URL_TEMPLATE.replace('__ID__', id);
    document.getElementById('hubFlagIntro').textContent = `Flag discrepancy on ${riderName}'s commission for Headquarters review.`;
    document.getElementById('hub-flag-note').value = '';
    const err = document.getElementById('hub-flag-note-error');
    if (err) err.style.display = 'none';
    document.getElementById('hubFlagModal').hidden = false;
}

function handleSinglePaySubmit(event) {
    const btn = document.getElementById('hub-single-pay-btn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Recording payout...';
    }
    return true;
}

function handleBulkPaySubmit(event) {
    const btn = document.getElementById('hub-bulk-confirm-btn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Processing...';
    }
    return true;
}

function handleFlagSubmit(event) {
    const note = document.getElementById('hub-flag-note');
    const err = document.getElementById('hub-flag-note-error');
    if (!note || note.value.trim().length < 5) {
        event.preventDefault();
        if (err) err.style.display = 'block';
        if (note) note.focus();
        return false;
    }
    const btn = document.getElementById('hub-flag-confirm-btn');
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Flagging to HQ...';
    }
    return true;
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (m) m.hidden = true;
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') ['hubBulkPaidModal', 'hubFlagModal'].forEach(closeModal);
});

updateHubSelection();
</script>
</body>
</html>
