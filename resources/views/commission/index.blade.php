<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Commission — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
@php
    $fmt = fn (int $c) => \App\Models\CommissionRule::formatPeso($c);
    $baseQuery = array_filter([
        'period' => $selectedPeriod['key'],
        'hub' => $filterHub !== '' ? $filterHub : null,
        'search' => $search !== '' ? $search : null,
        'tab' => $activeTab !== 'all' ? $activeTab : null,
    ], fn ($v) => $v !== null);
@endphp
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Finance',
            'title' => 'Commission'
        ])

        <div class="content-wrap module-page comm-page">
            <!-- 1. Header -->
            <section class="welcome-row module-heading comm-heading">
                <div>
                    <div class="eyebrow">FINANCE &amp; PAYOUTS</div>
                    <h1>Commission</h1>
                    <p class="intro">Review rider earnings per pay period — delivered parcels, on-time bonuses and rider-fault return deductions — then approve and record payouts.</p>
                </div>
                <div class="comm-header-actions">
                    <button type="button" class="rr-btn secondary" onclick="openRulesModal()">⚙ Commission rules</button>
                    <a class="rr-btn secondary" href="{{ route('commission.export', $baseQuery) }}" aria-label="Export filtered commission table to CSV">⇩ Export CSV</a>
                    <button type="button" class="rr-btn primary" id="comm-approve-btn" disabled onclick="openApproveModal()">✓ Approve selected (0)</button>
                </div>
            </section>

            @if (session('status'))
                <div class="server-alert" role="status"><span>✓</span>{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>
            @endif

            <!-- 2. Filters (URL query string) -->
            <form id="commFilterForm" method="GET" action="{{ route('commission.index') }}" class="comm-filters" aria-label="Commission filters">
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <div class="rr-filter-field">
                    <label for="comm-period">Pay period</label>
                    <select id="comm-period" name="period" class="rr-filter-select" onchange="this.form.submit()">
                        @foreach($periods as $p)
                            <option value="{{ $p['key'] }}" @selected($p['key'] === $selectedPeriod['key'])>{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rr-filter-field">
                    <label for="comm-hub">Hub</label>
                    <select id="comm-hub" name="hub" class="rr-filter-select" onchange="this.form.submit()">
                        <option value="">All hubs</option>
                        @foreach($hubs as $h)
                            <option value="{{ $h }}" @selected(strcasecmp($filterHub, $h) === 0)>{{ $h }} Hub</option>
                        @endforeach
                    </select>
                </div>
                <div class="rr-filter-field comm-search-field">
                    <label for="comm-search">Search</label>
                    <div class="rr-search-wrap">
                        <span class="rr-search-icon" aria-hidden="true">⌕</span>
                        <input id="comm-search" name="search" type="search" class="rr-filter-input" value="{{ $search }}" placeholder="Rider name or hub..." autocomplete="off">
                    </div>
                </div>
            </form>

            <!-- 3. Summary cards -->
            <section class="rr-stat-grid" aria-label="Commission summary for {{ $selectedPeriod['label'] }}">
                <article class="rr-stat-card">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Net commission</span>
                        <span class="rr-stat-pill neutral">{{ number_format($stats['delivered_count']) }} delivered</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['net_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['delivered_count'] === 0 ? 'No delivered parcels this period' : $selectedPeriod['label'] }}</span>
                </article>
                <article class="rr-stat-card urgent">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Pending approval</span>
                        <span class="rr-stat-pill warn">{{ $stats['pending_riders'] }} {{ $stats['pending_riders'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['pending_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['pending_riders'] === 0 ? 'Nothing waiting for approval' : 'Review and approve below' }}</span>
                </article>
                <article class="rr-stat-card evidence">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Approved, awaiting payout</span>
                        <span class="rr-stat-pill info">{{ $stats['approved_riders'] }} {{ $stats['approved_riders'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['approved_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['approved_riders'] === 0 ? 'No approved payouts outstanding' : 'Mark as paid once transferred' }}</span>
                </article>
                <article class="rr-stat-card sla-time">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Paid out</span>
                        <span class="rr-stat-pill neutral">{{ $stats['paid_riders'] }} {{ $stats['paid_riders'] === 1 ? 'rider' : 'riders' }}</span>
                    </div>
                    <strong class="rr-stat-value">{{ $stats['paid_formatted'] }}</strong>
                    <span class="rr-stat-meta">{{ $stats['paid_riders'] === 0 ? 'No payouts recorded yet' : 'Recorded with date and admin' }}</span>
                </article>
            </section>

            <div class="comm-layout">
                <!-- Left: tabs + table -->
                <section class="panel comm-table-panel" aria-label="Rider commission table">
                    <div class="rr-tabs-bar">
                        <div class="rr-status-tabs" role="tablist" aria-label="Commission status">
                            @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'paid' => 'Paid'] as $tabKey => $tabLabel)
                                @php $tq = array_merge($baseQuery, ['tab' => $tabKey]); unset($tq['page']); @endphp
                                <a href="{{ route('commission.index', $tq) }}" role="tab" aria-selected="{{ $activeTab === $tabKey ? 'true' : 'false' }}" class="rr-status-tab {{ $activeTab === $tabKey ? 'active' : '' }}">
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
                                        <input type="checkbox" id="comm-select-all" aria-label="Select all pending riders on this page" onchange="toggleAllCommission(this.checked)">
                                    </th>
                                    <th scope="col">Rider / Hub</th>
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
                                    @endphp
                                    <tr data-commission-id="{{ $rc->id }}">
                                        <td>
                                            @if($rc->status === 'pending')
                                                @if($rc->is_flagged)
                                                    <input type="checkbox" disabled aria-label="Cannot approve: flagged with issue by hub" title="Flagged by hub: {{ $rc->flagged_note }}">
                                                @else
                                                    <input type="checkbox" class="comm-row-checkbox" value="{{ $rc->id }}" data-rider="{{ $riderName }}" data-net="{{ $rc->formatted_net }}" aria-label="Select {{ $riderName }}" onchange="updateCommissionSelection()">
                                                @endif
                                            @else
                                                <span class="shipment-sr-only">Not selectable</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="comm-rider-cell">
                                                <span class="comm-avatar" aria-hidden="true">{{ $initials ?: 'R' }}</span>
                                                <div>
                                                    <span class="rr-cell-title">{{ $riderName }}</span>
                                                    <span class="rr-cell-sub">{{ $rc->hub_branch }} Hub · {{ number_format($rc->on_time_rate_percent, 1) }}% on-time</span>
                                                    @if($rc->is_flagged)
                                                        <div style="margin-top: 2px;">
                                                            <span class="rr-stat-pill danger" style="font-size: 10px; padding: 2px 6px;" title="{{ $rc->flagged_note }}">
                                                                ⚠ Flagged by Hub: {{ Str::limit($rc->flagged_note, 45) }}
                                                            </span>
                                                            <form method="POST" action="{{ route('commission.resolve-flag', $rc->id) }}" style="display: inline-block; margin-left: 4px;">
                                                                @csrf
                                                                <button type="submit" class="text-button" style="font-size: 10px; color: var(--orange); font-weight: 700; background: none; border: 0; cursor: pointer;">
                                                                    Resolve flag ✓
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="num">{{ number_format($rc->delivered_count) }}</td>
                                        <td class="num">{{ $rc->formatted_base }}</td>
                                        <td class="num {{ $rc->bonus_centavos > 0 ? 'comm-positive' : 'comm-muted' }}">{{ $rc->formatted_bonus }}</td>
                                        <td class="num">
                                            @if($rc->deductions_centavos > 0 && count($returnIds) > 0)
                                                <a class="comm-deduction-link" href="{{ route('exceptions.index', ['ids' => implode(',', $returnIds)]) }}" aria-label="View {{ $rc->return_fault_count }} return or refund cases behind this deduction">{{ $rc->formatted_deductions }}</a>
                                                <span class="rr-cell-sub">{{ $rc->return_fault_count }} {{ $rc->return_fault_count === 1 ? 'return' : 'returns' }}</span>
                                            @else
                                                <span class="comm-muted">{{ $fmt(0) }}</span>
                                            @endif
                                        </td>
                                        <td class="num"><strong class="comm-net">{{ $rc->formatted_net }}</strong></td>
                                        <td>
                                            <span class="comm-status {{ $rc->status }}"><b></b>{{ ucfirst($rc->status) }}</span>
                                            @if($rc->status === 'approved')
                                                <button type="button" class="comm-paid-btn" onclick="openPaidModal({{ $rc->id }}, @js($riderName), @js($rc->formatted_net))">Mark as paid</button>
                                            @elseif($rc->status === 'paid')
                                                <span class="rr-cell-sub">{{ $rc->paid_at?->copy()->setTimezone('Asia/Manila')->format('M j, Y') }} · {{ $rc->payer?->name ?? 'Admin' }}</span>
                                                @if($rc->payment_reference)
                                                    <span class="rr-cell-sub">Ref: {{ $rc->payment_reference }}</span>
                                                @endif
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
                                                    @if($search !== '' || $filterHub !== '' || $activeTab !== 'all')
                                                        No riders match the current hub, search or status filter.
                                                    @else
                                                        Commissions appear here once approved riders deliver parcels during {{ $selectedPeriod['label'] }}.
                                                    @endif
                                                </p>
                                                @if($search !== '' || $filterHub !== '' || $activeTab !== 'all')
                                                    <a href="{{ route('commission.index', ['period' => $selectedPeriod['key']]) }}" class="rr-btn secondary">Reset filters</a>
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

                <!-- Right: side panels -->
                <div class="comm-side">
                    <section class="panel comm-side-panel" aria-labelledby="comm-rules-title">
                        <div class="comm-side-head">
                            <h2 id="comm-rules-title">Commission rules</h2>
                            <button type="button" class="rr-btn secondary comm-small-btn" onclick="openRulesModal()">Edit rules</button>
                        </div>
                        <dl class="comm-rules-list">
                            <div><dt>Base rate</dt><dd>{{ $fmt((int) $rule->base_rate_centavos) }} / delivered parcel</dd></div>
                            <div><dt>On-time ≥ {{ $rule->bonus_tier1_rate_pct }}%</dt><dd class="comm-positive">+{{ $fmt((int) $rule->bonus_tier1_centavos) }}</dd></div>
                            <div><dt>On-time ≥ {{ $rule->bonus_tier2_rate_pct }}%</dt><dd class="comm-positive">+{{ $fmt((int) $rule->bonus_tier2_centavos) }}</dd></div>
                            <div><dt>Rider-fault return</dt><dd class="comm-negative">−{{ $fmt((int) $rule->deduction_per_return_centavos) }} each</dd></div>
                            <div><dt>Pay period</dt><dd>{{ ['semi_monthly' => 'Semi-monthly (1–15, 16–end)', 'monthly' => 'Monthly', 'weekly' => 'Weekly (Mon–Sun)'][$rule->period_type] ?? 'Semi-monthly' }}</dd></div>
                        </dl>
                        <p class="comm-note">Rule changes recalculate Pending rows only. Approved and Paid records keep the rates they were approved with.</p>
                    </section>

                    <section class="panel comm-side-panel" aria-labelledby="comm-hub-title">
                        <div class="comm-side-head">
                            <h2 id="comm-hub-title">By hub</h2>
                            <span class="rr-cell-sub">{{ $selectedPeriod['label'] }}</span>
                        </div>
                        @if($byHub->isEmpty() || $byHub->sum('net_centavos') === 0)
                            <p class="comm-note">No commission earned by any hub this period.</p>
                        @else
                            <ul class="comm-bars">
                                @foreach($byHub->where('net_centavos', '>', 0) as $hb)
                                    <li>
                                        <div class="comm-bar-label"><span>{{ $hb['label'] }}</span><strong>{{ $hb['net_formatted'] }}</strong></div>
                                        <div class="comm-bar-track" role="img" aria-label="{{ $hb['label'] }}: {{ $hb['net_formatted'] }}">
                                            <span class="comm-bar-fill" style="width: {{ $hb['pct_width'] }}%;"></span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    @if($recentAudits->isNotEmpty())
                        <section class="panel comm-side-panel" aria-labelledby="comm-audit-title">
                            <div class="comm-side-head"><h2 id="comm-audit-title">Recent activity</h2></div>
                            <ul class="rr-audit-list">
                                @foreach($recentAudits as $a)
                                    @php $m = is_array($a->metadata) ? $a->metadata : []; @endphp
                                    <li class="rr-audit-item">
                                        <strong>
                                            @if($a->event === 'commission.approved') Approved {{ $m['rider_name'] ?? '' }} · {{ $m['net_formatted'] ?? '' }}
                                            @elseif($a->event === 'commission.paid') Paid {{ $m['rider_name'] ?? '' }} · {{ $m['net_formatted'] ?? '' }}
                                            @elseif($a->event === 'commission.rules.updated') Updated commission rules
                                            @else {{ $a->event }}
                                            @endif
                                        </strong>
                                        @if(!empty($m['payment_reference']))<p>Ref: {{ $m['payment_reference'] }}</p>@endif
                                        <time>{{ $a->user?->name ?? 'Admin' }} · {{ $a->created_at?->copy()->setTimezone('Asia/Manila')->format('M j, Y · g:i A') }}</time>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>
            </div>

            <footer class="page-footer">
                <span>© 2026 Lumora Logistics</span>
                <span>Amounts are calculated on the server and stored in centavos.</span>
            </footer>
        </div>
    </main>
</div>

<!-- Approve confirmation -->
<div class="modal-backdrop" id="commApproveModal" hidden role="dialog" aria-modal="true" aria-labelledby="commApproveTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close approve dialog" onclick="closeModal('commApproveModal')">✕</button>
        <div class="eyebrow">CONFIRM APPROVAL</div>
        <h2 id="commApproveTitle">Approve commissions</h2>
        <p class="modal-intro" id="commApproveIntro"></p>
        <form method="POST" action="{{ route('commission.approve') }}">
            @csrf
            <div id="commApproveInputs"></div>
            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeModal('commApproveModal')">Cancel</button>
                <button type="submit" class="rr-btn primary">Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- Mark as paid -->
<div class="modal-backdrop" id="commPaidModal" hidden role="dialog" aria-modal="true" aria-labelledby="commPaidTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close mark as paid dialog" onclick="closeModal('commPaidModal')">✕</button>
        <div class="eyebrow">RECORD PAYOUT</div>
        <h2 id="commPaidTitle">Mark as paid</h2>
        <p class="modal-intro" id="commPaidIntro"></p>
        <form method="POST" id="commPaidForm" action="">
            @csrf
            <label for="comm-payment-ref">
                Payment reference (optional)
                <input id="comm-payment-ref" name="payment_reference" maxlength="160" placeholder="e.g. GCash ref, bank transfer no.">
            </label>
            <p class="comm-note">Today's date and your account ({{ auth()->user()->name }}) will be recorded.</p>
            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeModal('commPaidModal')">Cancel</button>
                <button type="submit" class="rr-btn primary">Confirm paid</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit rules -->
<div class="modal-backdrop" id="commRulesModal" hidden role="dialog" aria-modal="true" aria-labelledby="commRulesTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close commission rules dialog" onclick="closeModal('commRulesModal')">✕</button>
        <div class="eyebrow">SUPER ADMIN</div>
        <h2 id="commRulesTitle">Edit commission rules</h2>
        <p class="modal-intro">Only Pending commissions are recalculated. Approved and Paid records keep their stored rules.</p>
        <form method="POST" action="{{ route('commission.rules.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="period" value="{{ $selectedPeriod['key'] }}">
            <label for="rule-base">Base rate per delivered parcel (₱)
                <input id="rule-base" type="number" name="base_rate_pesos" min="0" step="0.01" required value="{{ number_format($rule->base_rate_centavos / 100, 2, '.', '') }}">
            </label>
            <div class="comm-rule-row">
                <label for="rule-t1-pct">Tier 1 on-time ≥ (%)
                    <input id="rule-t1-pct" type="number" name="bonus_tier1_rate_pct" min="1" max="100" required value="{{ $rule->bonus_tier1_rate_pct }}">
                </label>
                <label for="rule-t1-amt">Tier 1 bonus (₱)
                    <input id="rule-t1-amt" type="number" name="bonus_tier1_pesos" min="0" step="0.01" required value="{{ number_format($rule->bonus_tier1_centavos / 100, 2, '.', '') }}">
                </label>
            </div>
            <div class="comm-rule-row">
                <label for="rule-t2-pct">Tier 2 on-time ≥ (%)
                    <input id="rule-t2-pct" type="number" name="bonus_tier2_rate_pct" min="1" max="100" required value="{{ $rule->bonus_tier2_rate_pct }}">
                </label>
                <label for="rule-t2-amt">Tier 2 bonus (₱)
                    <input id="rule-t2-amt" type="number" name="bonus_tier2_pesos" min="0" step="0.01" required value="{{ number_format($rule->bonus_tier2_centavos / 100, 2, '.', '') }}">
                </label>
            </div>
            <label for="rule-deduct">Deduction per rider-fault return (₱)
                <input id="rule-deduct" type="number" name="deduction_per_return_pesos" min="0" step="0.01" required value="{{ number_format($rule->deduction_per_return_centavos / 100, 2, '.', '') }}">
            </label>
            <label for="rule-period">Pay period
                <select id="rule-period" name="period_type">
                    <option value="semi_monthly" @selected($rule->period_type === 'semi_monthly')>Semi-monthly (1st–15th, 16th–end)</option>
                    <option value="monthly" @selected($rule->period_type === 'monthly')>Monthly</option>
                    <option value="weekly" @selected($rule->period_type === 'weekly')>Weekly (Mon–Sun)</option>
                </select>
            </label>
            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeModal('commRulesModal')">Cancel</button>
                <button type="submit" class="rr-btn primary">Save rules</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const search = document.getElementById('comm-search');
    let t = null;
    if (search) {
        search.addEventListener('input', () => {
            clearTimeout(t);
            t = setTimeout(() => document.getElementById('commFilterForm').submit(), 400);
        });
    }
})();

const MARK_PAID_URL = @json(route('commission.mark-paid', ['riderCommission' => '__ID__']));

function escapeHtml(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
function selectedCommissionBoxes() {
    return Array.from(document.querySelectorAll('.comm-row-checkbox:checked'));
}
function updateCommissionSelection() {
    const all = document.querySelectorAll('.comm-row-checkbox');
    const sel = selectedCommissionBoxes();
    const btn = document.getElementById('comm-approve-btn');
    const master = document.getElementById('comm-select-all');
    if (btn) {
        btn.disabled = sel.length === 0;
        btn.textContent = `✓ Approve selected (${sel.length})`;
    }
    if (master) {
        master.checked = all.length > 0 && sel.length === all.length;
        master.indeterminate = sel.length > 0 && sel.length < all.length;
        master.disabled = all.length === 0;
    }
}
function toggleAllCommission(checked) {
    document.querySelectorAll('.comm-row-checkbox').forEach(cb => cb.checked = checked);
    updateCommissionSelection();
}
function openApproveModal() {
    const sel = selectedCommissionBoxes();
    if (!sel.length) return;
    document.getElementById('commApproveInputs').innerHTML = sel.map(cb => `<input type="hidden" name="commission_ids[]" value="${escapeHtml(cb.value)}">`).join('');
    const names = sel.slice(0, 5).map(cb => `${cb.dataset.rider} (${cb.dataset.net})`).join(', ');
    document.getElementById('commApproveIntro').textContent =
        `Move ${sel.length} ${sel.length === 1 ? 'rider' : 'riders'} from Pending to Approved: ${names}${sel.length > 5 ? ', …' : ''}. The current rules will be locked on these records.`;
    document.getElementById('commApproveModal').hidden = false;
}
function openPaidModal(id, rider, net) {
    document.getElementById('commPaidForm').action = MARK_PAID_URL.replace('__ID__', id);
    document.getElementById('commPaidIntro').textContent = `Record the ${net} payout to ${rider} as paid.`;
    document.getElementById('comm-payment-ref').value = '';
    document.getElementById('commPaidModal').hidden = false;
}
function openRulesModal() {
    document.getElementById('commRulesModal').hidden = false;
}
function closeModal(id) {
    document.getElementById(id).hidden = true;
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') ['commApproveModal', 'commPaidModal', 'commRulesModal'].forEach(closeModal);
});
updateCommissionSelection();
</script>
</body>
</html>
