<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Return / Refund — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => ($branch ? $branch . ' Branch Hub' : 'Operations Audit'),
            'title' => 'Main Return / Refund'
        ])

        <div class="content-wrap module-page rr-page">
            <section class="welcome-row module-heading">
                <div>
                    <div class="eyebrow">RIDER EVIDENCE & SLA RESOLUTION DESK</div>
                    <h1>{{ $branch ? ($branch . ' Return / Refund') : 'Main Return / Refund' }}</h1>
                    <p class="intro">Review rider photo and video evidence, inspect customer claims, and resolve return or refund cases before the 24-hour SLA expires.</p>
                </div>
            </section>

            @if (session('status'))
                <div class="server-alert" role="status">
                    <span>✓</span>{{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="server-alert error" role="alert">
                    <span>!</span>{{ $errors->first() }}
                </div>
            @endif

            <!-- 1. Summary Cards (Computed from real data) -->
            <section class="rr-stat-grid" aria-label="Return and refund summary metrics">
                <article class="rr-stat-card urgent">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Needs action</span>
                        <span class="rr-stat-pill danger">
                            {{ $stats['past_sla'] }} past SLA
                        </span>
                    </div>
                    <strong class="rr-stat-value" id="stat-needs-action">{{ $stats['needs_action'] }}</strong>
                    <span class="rr-stat-meta">
                        @if($stats['needs_action'] === 0)
                            All open cases cleared
                        @elseif($stats['near_sla'] > 0)
                            {{ $stats['near_sla'] }} expiring within 4h
                        @else
                            Pending &amp; under review cases
                        @endif
                    </span>
                </article>

                <article class="rr-stat-card evidence">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">With picture / video</span>
                        <span class="rr-stat-pill info">
                            {{ $stats['media_pct'] }}% of open cases
                        </span>
                    </div>
                    <strong class="rr-stat-value" id="stat-with-media">{{ $stats['with_media'] }}</strong>
                    <span class="rr-stat-meta">
                        @if($stats['with_media'] === 0)
                            No rider media uploaded yet
                        @else
                            {{ $stats['with_media_open'] }} open with rider proof attached
                        @endif
                    </span>
                </article>

                <article class="rr-stat-card damage">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Damaged / wrong item</span>
                        <span class="rr-stat-pill warn">
                            {{ $stats['damaged_count'] }} damaged · {{ $stats['wrong_item_count'] }} wrong
                        </span>
                    </div>
                    <strong class="rr-stat-value" id="stat-damaged-wrong">{{ $stats['damaged_or_wrong'] }}</strong>
                    <span class="rr-stat-meta">
                        @if($stats['damaged_or_wrong'] === 0)
                            Zero product condition claims
                        @else
                            Requires photo / video inspection
                        @endif
                    </span>
                </article>

                <article class="rr-stat-card sla-time">
                    <div class="rr-stat-top">
                        <span class="rr-stat-label">Average resolution time</span>
                        <span class="rr-stat-pill neutral">
                            Target &lt; 24h
                        </span>
                    </div>
                    <strong class="rr-stat-value" id="stat-avg-resolution">{{ $stats['avg_resolution'] }}</strong>
                    <span class="rr-stat-meta">
                        @if($stats['resolved_count'] === 0)
                            No resolved cases yet
                        @else
                            Across {{ $stats['resolved_count'] }} resolved {{ $stats['resolved_count'] === 1 ? 'case' : 'cases' }}
                        @endif
                    </span>
                </article>
            </section>

            <!-- Working Desk Split View: Left Table + Right Detail Panel -->
            <div class="rr-desk-layout">
                <!-- Left Column: Tabs, Instant Filters, Bulk Bar & Table -->
                <section class="panel rr-table-panel" aria-label="Return and refund cases list">
                    <!-- 2. Status Tabs with live counts -->
                    <div class="rr-tabs-bar">
                        <div class="rr-status-tabs" role="tablist" aria-label="Case status filter tabs">
                            @php
                                $tabs = [
                                    'all' => 'All',
                                    'pending' => 'Pending',
                                    'under_review' => 'Under review',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ];
                            @endphp
                            @foreach($tabs as $tabKey => $tabLabel)
                                @php
                                    $isActive = $activeTab === $tabKey;
                                    $tabQuery = array_merge(request()->query(), ['tab' => $tabKey, 'page' => 1]);
                                @endphp
                                <a
                                    href="{{ route('exceptions.index', $tabQuery) }}"
                                    role="tab"
                                    aria-selected="{{ $isActive ? 'true' : 'false' }}"
                                    class="rr-status-tab {{ $isActive ? 'active' : '' }}"
                                >
                                    <span>{{ $tabLabel }}</span>
                                    <span class="rr-tab-count">{{ $tabCounts[$tabKey] ?? 0 }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- 3. Instant Filters (Search, Reason, Hub, Date Range - no separate Filter button) -->
                    <form id="rrFilterForm" method="GET" action="{{ route('exceptions.index') }}" class="rr-filters-grid" aria-label="Filter return and refund cases">
                        <input type="hidden" name="tab" value="{{ $activeTab }}">
                        @if($selectedCase)
                            <input type="hidden" name="selected" id="rrSelectedInput" value="{{ $selectedCase['id'] }}">
                        @else
                            <input type="hidden" name="selected" id="rrSelectedInput" value="">
                        @endif

                        <div class="rr-filter-field">
                            <label for="rr-search-input">Search</label>
                            <div class="rr-search-wrap">
                                <span class="rr-search-icon" aria-hidden="true">⌕</span>
                                <input
                                    id="rr-search-input"
                                    name="search"
                                    value="{{ $search }}"
                                    type="search"
                                    class="rr-filter-input"
                                    placeholder="Track ID, barcode, customer, reason..."
                                    autocomplete="off"
                                >
                                @if($search !== '')
                                    <button type="button" class="rr-clear-btn" aria-label="Clear search" onclick="clearSearchFilter()">✕</button>
                                @endif
                            </div>
                        </div>

                        <div class="rr-filter-field">
                            <label for="rr-reason-select">Reason</label>
                            <select id="rr-reason-select" name="reason" class="rr-filter-select" onchange="submitInstantFilters()">
                                <option value="">All reasons</option>
                                @foreach($reasons as $reasonOpt)
                                    <option value="{{ $reasonOpt }}" @selected($filterReason === $reasonOpt)>{{ $reasonOpt }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rr-filter-field">
                            <label for="rr-hub-select">Hub</label>
                            <select id="rr-hub-select" name="hub" class="rr-filter-select" onchange="submitInstantFilters()" @if($branch && !$isMasterAdmin) disabled title="Scoped to {{ $branch }} Hub" @endif>
                                @if($branch && !$isMasterAdmin)
                                    <option value="{{ $branch }}" selected>{{ $branch }} Hub</option>
                                @else
                                    <option value="">All Laguna hubs</option>
                                    @foreach($hubs as $hubOpt)
                                        <option value="{{ $hubOpt }}" @selected(strcasecmp($filterHub, $hubOpt) === 0)>{{ $hubOpt }} Hub</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="rr-filter-field">
                            <label for="rr-date-select">Date range</label>
                            <select id="rr-date-select" name="date_range" class="rr-filter-select" onchange="submitInstantFilters()">
                                <option value="all" @selected($dateRange === 'all' || $dateRange === '')>All dates</option>
                                <option value="today" @selected($dateRange === 'today')>Today</option>
                                <option value="7d" @selected($dateRange === '7d')>Last 7 days</option>
                                <option value="30d" @selected($dateRange === '30d')>Last 30 days</option>
                                <option value="overdue" @selected($dateRange === 'overdue')>Past SLA only</option>
                            </select>
                        </div>
                    </form>

                    <!-- 6. Bulk Action Bar + Export CSV -->
                    <div class="rr-bulk-bar" role="region" aria-label="Bulk actions and export">
                        <div class="rr-bulk-left">
                            <label class="rr-checkbox-label" for="rr-select-all">
                                <input type="checkbox" id="rr-select-all" onchange="toggleSelectAllRows(this.checked)">
                                <span id="rr-selected-count-label">Select all</span>
                            </label>

                            <button
                                type="button"
                                id="rr-bulk-resolve-btn"
                                class="rr-btn primary"
                                disabled
                                onclick="openBulkResolveDialog()"
                            >
                                ✓ Bulk resolve (0)
                            </button>
                        </div>

                        <div class="rr-bulk-right">
                            @if($search !== '' || $filterReason !== '' || $filterHub !== '' || ($dateRange !== '' && $dateRange !== 'all') || $activeTab !== 'all')
                                <a href="{{ route('exceptions.index') }}" class="rr-btn secondary">
                                    Reset filters
                                </a>
                            @endif

                            <a
                                href="{{ route('exceptions.export', request()->query()) }}"
                                class="rr-btn secondary"
                                aria-label="Export filtered return and refund cases to CSV"
                            >
                                ⇩ Export CSV
                            </a>
                        </div>
                    </div>

                    <!-- 4. Table -->
                    <div class="rr-table-scroll">
                        <table class="rr-table">
                            <thead>
                                <tr>
                                    <th scope="col"><span class="shipment-sr-only">Select</span></th>
                                    <th scope="col">Parcel</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Rider / Hub</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">Evidence</th>
                                    <th scope="col">Status + SLA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cases as $c)
                                    @php
                                        $isSelected = $selectedCase && $selectedCase['id'] === $c['id'];
                                        $rowSlaClass = $c['sla_state'] === 'overdue'
                                            ? 'rr-row-overdue'
                                            : ($c['sla_state'] === 'warning' ? 'rr-row-warning' : '');
                                    @endphp
                                    <tr
                                        id="rr-row-{{ $c['id'] }}"
                                        data-case-id="{{ $c['id'] }}"
                                        class="{{ $rowSlaClass }} {{ $isSelected ? 'rr-row-selected' : '' }}"
                                        onclick="selectCaseRow({{ $c['id'] }}, event)"
                                        tabindex="0"
                                        role="button"
                                        aria-label="Inspect parcel {{ $c['track_id'] }} for {{ $c['customer_name'] }}"
                                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();selectCaseRow({{ $c['id'] }}, event);}"
                                    >
                                        <td onclick="event.stopPropagation()">
                                            <input
                                                type="checkbox"
                                                class="rr-row-checkbox"
                                                name="selected_parcels[]"
                                                value="{{ $c['id'] }}"
                                                aria-label="Select parcel {{ $c['track_id'] }}"
                                                onchange="updateBulkSelectionState()"
                                            >
                                        </td>
                                        <td>
                                            <span class="rr-cell-title">{{ $c['track_id'] }}</span>
                                            <span class="rr-cell-sub">{{ $c['reported_date'] }} · {{ $c['reported_time'] }}</span>
                                            <span class="rr-cell-sub">Barcode: {{ $c['barcode'] }}</span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--ink); font-size: 12px;">{{ $c['customer_name'] }}</strong>
                                            <span class="rr-cell-sub">{{ $c['destination_short'] }}</span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--ink); font-size: 12px;">{{ $c['rider_name'] }}</strong>
                                            <span class="rr-cell-sub">{{ $c['hub_label'] }}</span>
                                        </td>
                                        <td>
                                            <span class="rr-reason-badge">{{ $c['reason'] }}</span>
                                            <span class="rr-note-snippet">
                                                {{ $c['notes'] !== '' ? $c['notes'] : 'No additional rider note.' }}
                                            </span>
                                        </td>
                                        <td onclick="event.stopPropagation()">
                                            @if($c['has_photo'] || $c['has_video'])
                                                <div class="rr-evidence-thumbs">
                                                    @if($c['has_photo'])
                                                        <button
                                                            type="button"
                                                            class="rr-thumb-btn"
                                                            aria-label="View return photo for {{ $c['track_id'] }}"
                                                            onclick="openMediaModal('photo', '{{ $c['photo_url'] }}', '{{ addslashes($c['track_id'] . ' — ' . $c['reason']) }}')"
                                                        >
                                                            <img src="{{ $c['photo_url'] }}" alt="Return photo for {{ $c['track_id'] }}" loading="lazy">
                                                            <span class="rr-thumb-tag">IMG</span>
                                                        </button>
                                                    @endif
                                                    @if($c['has_video'])
                                                        <button
                                                            type="button"
                                                            class="rr-thumb-btn"
                                                            aria-label="Play return video for {{ $c['track_id'] }}"
                                                            onclick="openMediaModal('video', '{{ $c['video_url'] }}', '{{ addslashes($c['track_id'] . ' — ' . $c['reason']) }}')"
                                                        >
                                                            <video src="{{ $c['video_url'] }}" preload="metadata" muted playsinline></video>
                                                            <span class="rr-thumb-tag">▶ VID</span>
                                                        </button>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="rr-cell-sub">No media</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>
                                                <span class="rr-status-pill {{ $c['return_status'] }}">
                                                    <b></b>{{ $c['return_status_label'] }}
                                                </span>
                                            </div>
                                            <div class="rr-sla-countdown {{ $c['sla_state'] }}">
                                                @if($c['sla_state'] === 'overdue')
                                                    <span>⚠ {{ $c['sla_text'] }}</span>
                                                @elseif($c['sla_state'] === 'warning')
                                                    <span>⏱ {{ $c['sla_text'] }}</span>
                                                @elseif($c['sla_state'] === 'resolved')
                                                    <span>✓ {{ $c['sla_text'] }}</span>
                                                @else
                                                    <span>🕒 {{ $c['sla_text'] }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- 8. Friendly Empty State -->
                                    <tr>
                                        <td colspan="7">
                                            <div class="rr-empty-box">
                                                <div class="rr-empty-icon" aria-hidden="true">↺</div>
                                                <h3>No return / refund parcels yet</h3>
                                                <p>
                                                    @if($search !== '' || $filterReason !== '' || $filterHub !== '' || ($dateRange !== '' && $dateRange !== 'all') || $activeTab !== 'all')
                                                        No cases match your current search or filter criteria. Try clearing your filters to view all return and refund cases.
                                                    @else
                                                        When riders report a return or refund with reason, photo, or video evidence in the Rider App, cases will appear here for review.
                                                    @endif
                                                </p>
                                                @if($search !== '' || $filterReason !== '' || $filterHub !== '' || ($dateRange !== '' && $dateRange !== 'all') || $activeTab !== 'all')
                                                    <a href="{{ route('exceptions.index') }}" class="rr-btn secondary">
                                                        Reset all filters
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="table-footer" style="margin-top: 14px;">
                        <span>
                            Showing <strong>{{ $cases->firstItem() ?? 0 }}–{{ $cases->lastItem() ?? 0 }}</strong> of <strong>{{ $cases->total() }}</strong> return / refund {{ $cases->total() === 1 ? 'case' : 'cases' }}
                        </span>
                        <div class="orders-pagination">
                            {{ $cases->onEachSide(1)->links() }}
                        </div>
                    </div>
                </section>

                <!-- 5. Right-Side Detail Panel (Stacks below table on narrow screens) -->
                <aside class="panel rr-detail-panel" id="rrDetailPanel" aria-label="Selected case details and resolution controls">
                    @if($selectedCase)
                        <div id="rrDetailContent">
                            <div class="rr-detail-header">
                                <div>
                                    <span class="section-kicker">SELECTED CASE</span>
                                    <h2 id="detail-track-id">{{ $selectedCase['track_id'] }}</h2>
                                    <span class="rr-cell-sub" id="detail-reported-at">
                                        Barcode: {{ $selectedCase['barcode'] }} · Reported {{ $selectedCase['reported_formatted'] }}
                                    </span>
                                </div>
                                <div style="text-align: right;">
                                    <span class="rr-status-pill {{ $selectedCase['return_status'] }}" id="detail-status-pill">
                                        <b></b><span id="detail-status-text">{{ $selectedCase['return_status_label'] }}</span>
                                    </span>
                                    <div class="rr-sla-countdown {{ $selectedCase['sla_state'] }}" id="detail-sla-badge" style="display: block; margin-top: 6px;">
                                        {{ $selectedCase['sla_text'] }}
                                    </div>
                                </div>
                            </div>

                            <!-- Photo & Video Evidence Viewer -->
                            <div class="rr-detail-section">
                                <h3 class="rr-section-title">Rider Photo &amp; Video Evidence</h3>
                                <div id="detail-media-container">
                                    @if($selectedCase['has_photo'] || $selectedCase['has_video'])
                                        <div class="rr-media-grid">
                                            @if($selectedCase['has_photo'])
                                                <div class="rr-media-card">
                                                    <img src="{{ $selectedCase['photo_url'] }}" alt="Rider return photo for {{ $selectedCase['track_id'] }}">
                                                    <div class="rr-media-footer">
                                                        <span>📷 Attached Picture</span>
                                                        <button
                                                            type="button"
                                                            class="text-button"
                                                            aria-label="Enlarge attached picture"
                                                            onclick="openMediaModal('photo', '{{ $selectedCase['photo_url'] }}', '{{ addslashes($selectedCase['track_id'] . ' — ' . $selectedCase['reason']) }}')"
                                                        >
                                                            Enlarge ↗
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif

                                            @if($selectedCase['has_video'])
                                                <div class="rr-media-card">
                                                    <video src="{{ $selectedCase['video_url'] }}" controls preload="metadata"></video>
                                                    <div class="rr-media-footer">
                                                        <span>🎬 Attached Video</span>
                                                        <button
                                                            type="button"
                                                            class="text-button"
                                                            aria-label="Expand attached video"
                                                            onclick="openMediaModal('video', '{{ $selectedCase['video_url'] }}', '{{ addslashes($selectedCase['track_id'] . ' — ' . $selectedCase['reason']) }}')"
                                                        >
                                                            Fullscreen ↗
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div style="padding: 16px; border: 1px dashed var(--line); border-radius: 10px; background: var(--blush-1); color: var(--muted); font-size: 12px; text-align: center;">
                                            No picture or video was attached for this parcel.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Reason & Rider Note -->
                            <div class="rr-detail-section">
                                <h3 class="rr-section-title">Return / Refund Reason &amp; Rider Note</h3>
                                <div>
                                    <span class="rr-reason-badge" id="detail-reason">{{ $selectedCase['reason'] }}</span>
                                    <p id="detail-rider-note" style="margin: 8px 0 0; color: var(--ink); font-size: 12.5px; line-height: 1.5; background: var(--blush-1); padding: 10px 12px; border-radius: 9px; border: 1px solid var(--line);">
                                        {{ $selectedCase['notes'] !== '' ? $selectedCase['notes'] : 'No additional note entered by rider.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Customer & Rider Info -->
                            <div class="rr-detail-section">
                                <h3 class="rr-section-title">Customer &amp; Route Details</h3>
                                <div class="rr-info-grid">
                                    <div class="rr-info-item">
                                        <small>Customer</small>
                                        <strong id="detail-customer-name">{{ $selectedCase['customer_name'] }}</strong>
                                        <span class="rr-cell-sub" id="detail-customer-phone">{{ $selectedCase['customer_phone'] }}</span>
                                    </div>
                                    <div class="rr-info-item">
                                        <small>Assigned Rider &amp; Hub</small>
                                        <strong id="detail-rider-name">{{ $selectedCase['rider_name'] }}</strong>
                                        <span class="rr-cell-sub" id="detail-hub-label">{{ $selectedCase['hub_label'] }}</span>
                                    </div>
                                    <div class="rr-info-item" style="grid-column: 1 / -1;">
                                        <small>Delivery Address</small>
                                        <strong id="detail-address">{{ $selectedCase['customer_address'] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Resolution Form & Actions -->
                            <div class="rr-detail-section">
                                <h3 class="rr-section-title">Resolve Case</h3>
                                <form id="rrResolveForm" method="POST" action="{{ route('exceptions.resolve-parcel', $selectedCase['id']) }}" onsubmit="return handleSingleResolveSubmit(event)">
                                    @csrf
                                    <input type="hidden" name="action" id="rr-resolve-action" value="approve_refund">

                                    <div class="rr-filter-field" style="margin-bottom: 10px;">
                                        <label for="rr-resolution-note">Resolution note <span style="color: var(--muted); font-weight: 500;">(Required when rejecting)</span></label>
                                        <textarea
                                            id="rr-resolution-note"
                                            name="resolution_note"
                                            rows="3"
                                            placeholder="Add inspection findings, refund approval details, or rejection reason..."
                                            style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--cream); color: var(--ink); font-size: 12px; resize: vertical;"
                                        >{{ $selectedCase['resolution_note'] }}</textarea>
                                        <small id="rr-note-error" role="alert" style="display: none; color: var(--status-failed); font-size: 11px; font-weight: 700; margin-top: 4px;">
                                            Please enter a resolution note before rejecting this case.
                                        </small>
                                    </div>

                                    <div class="rr-action-grid">
                                        <button
                                            type="button"
                                            class="rr-btn primary"
                                            onclick="triggerCaseAction('approve_refund', 'Approve refund for this parcel?')"
                                        >
                                            ✓ Approve refund
                                        </button>
                                        <button
                                            type="button"
                                            class="rr-btn secondary"
                                            onclick="triggerCaseAction('redeliver', 'Re-dispatch this parcel for re-delivery to the customer?')"
                                        >
                                            🚚 Re-deliver
                                        </button>
                                        <button
                                            type="button"
                                            class="rr-btn secondary"
                                            onclick="triggerCaseAction('return_to_hub', 'Mark this parcel as returned to hub (Under review)?')"
                                        >
                                            ⌂ Return to hub
                                        </button>
                                        <button
                                            type="button"
                                            class="rr-btn danger"
                                            onclick="triggerCaseAction('reject', 'Reject this return / refund request with the entered reason?')"
                                        >
                                            ✕ Reject with reason
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Chronological Audit Trail -->
                            <div class="rr-detail-section">
                                <h3 class="rr-section-title">Audit Trail (Who did what &amp; when)</h3>
                                <ul class="rr-audit-list" id="detail-audit-list">
                                    @foreach($selectedCase['audit_trail'] as $event)
                                        <li class="rr-audit-item">
                                            <strong>{{ $event['action'] }} · <span style="color: var(--orange);">{{ $event['actor'] }}</span> <small style="color: var(--muted); font-weight: 600;">({{ $event['role'] }})</small></strong>
                                            @if(!empty($event['note']))
                                                <p>{{ $event['note'] }}</p>
                                            @endif
                                            <time>{{ $event['timestamp_formatted'] }}</time>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="rr-empty-box">
                            <div class="rr-empty-icon" aria-hidden="true">📋</div>
                            <h3>No case selected</h3>
                            <p>Select any return or refund row from the table to inspect its photo and video evidence, audit trail, and resolution controls.</p>
                        </div>
                    @endif
                </aside>
            </div>

            <footer class="page-footer">
                <span>© 2026 Lumora Logistics</span>
                <span>Every return/refund decision is recorded in the parcel audit trail.</span>
            </footer>
        </div>
    </main>
</div>

<!-- Confirmation Dialog for Single Case Action -->
<div class="modal-backdrop" id="rrConfirmModal" hidden role="dialog" aria-modal="true" aria-labelledby="rrConfirmTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close confirmation dialog" onclick="closeConfirmModal()">✕</button>
        <div class="eyebrow">CONFIRM RESOLUTION</div>
        <h2 id="rrConfirmTitle">Confirm Action</h2>
        <p class="modal-intro" id="rrConfirmMessage">Are you sure you want to update this case?</p>
        <div class="modal-actions">
            <button type="button" class="rr-btn secondary" onclick="closeConfirmModal()">Cancel</button>
            <button type="button" class="rr-btn primary" id="rrConfirmSubmitBtn" onclick="confirmAndSubmitSingleAction()">Confirm &amp; Save</button>
        </div>
    </div>
</div>

<!-- Bulk Resolve Modal -->
<div class="modal-backdrop" id="rrBulkModal" hidden role="dialog" aria-modal="true" aria-labelledby="rrBulkModalTitle">
    <div class="modal-card">
        <button type="button" class="modal-close" aria-label="Close bulk resolve dialog" onclick="closeBulkResolveDialog()">✕</button>
        <div class="eyebrow">BULK RESOLUTION</div>
        <h2 id="rrBulkModalTitle">Bulk resolve selected cases</h2>
        <p class="modal-intro" id="rrBulkModalIntro">Apply a resolution action and audit trail entry to all selected parcels.</p>
        <form id="rrBulkForm" method="POST" action="{{ route('exceptions.bulk-resolve') }}" onsubmit="return validateBulkForm(event)">
            @csrf
            <div id="rrBulkHiddenInputs"></div>

            <label for="rr-bulk-action">
                Action
                <select id="rr-bulk-action" name="action" required>
                    <option value="approve_refund">Approve refund</option>
                    <option value="return_to_hub">Return to hub (Under review)</option>
                    <option value="redeliver">Re-deliver to customer</option>
                    <option value="reject">Reject with reason</option>
                </select>
            </label>

            <label for="rr-bulk-note">
                Resolution note (Required when rejecting)
                <textarea id="rr-bulk-note" name="resolution_note" placeholder="Enter bulk resolution note..."></textarea>
            </label>
            <small id="rr-bulk-note-error" role="alert" style="display: none; color: var(--status-failed); font-size: 11px; font-weight: 700; margin-bottom: 10px;">
                A resolution note is required when rejecting cases.
            </small>

            <div class="modal-actions">
                <button type="button" class="rr-btn secondary" onclick="closeBulkResolveDialog()">Cancel</button>
                <button type="submit" class="rr-btn primary">Confirm Bulk Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Fullscreen Lightbox Modal for Photo & Video Evidence -->
<div class="modal-backdrop" id="returnMediaModal" hidden role="dialog" aria-modal="true" aria-labelledby="returnMediaTitle" onclick="if(event.target === this) closeMediaModal()">
    <div class="modal-card" style="width: min(100%, 780px); padding: 20px;">
        <button type="button" class="modal-close" aria-label="Close media viewer" onclick="closeMediaModal()">✕</button>
        <div class="eyebrow">RIDER EVIDENCE VIEWER</div>
        <h2 id="returnMediaTitle" style="font-size: 20px; margin-bottom: 12px;">Return / Refund Evidence</h2>
        <div id="returnMediaBody" style="display: flex; align-items: center; justify-content: center; background: #181018; border-radius: 12px; min-height: 280px; max-height: 72vh; overflow: hidden; padding: 12px;">
        </div>
    </div>
</div>

@php
    $casesPayload = collect($cases->items())->mapWithKeys(function (array $c) {
        return [
            $c['id'] => [
                'id' => $c['id'],
                'track_id' => $c['track_id'],
                'barcode' => $c['barcode'],
                'reported_formatted' => $c['reported_formatted'],
                'return_status' => $c['return_status'],
                'return_status_label' => $c['return_status_label'],
                'sla_state' => $c['sla_state'],
                'sla_text' => $c['sla_text'],
                'has_photo' => $c['has_photo'],
                'has_video' => $c['has_video'],
                'photo_url' => $c['photo_url'],
                'video_url' => $c['video_url'],
                'reason' => $c['reason'],
                'notes' => $c['notes'],
                'customer_name' => $c['customer_name'],
                'customer_phone' => $c['customer_phone'],
                'customer_address' => $c['customer_address'],
                'rider_name' => $c['rider_name'],
                'hub_label' => $c['hub_label'],
                'resolution_note' => $c['resolution_note'],
                'resolve_url' => route('exceptions.resolve-parcel', $c['id']),
                'audit_trail' => $c['audit_trail'],
            ],
        ];
    });
@endphp

<script>
const RR_CASES = @json($casesPayload);
let pendingSingleAction = null;
let searchDebounceTimer = null;

// Instant search input debounce (updates URL & filters without a Filter button)
const searchInput = document.getElementById('rr-search-input');
if (searchInput) {
    searchInput.addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            submitInstantFilters();
        }, 380);
    });
}

function submitInstantFilters() {
    const form = document.getElementById('rrFilterForm');
    if (form) form.submit();
}

function clearSearchFilter() {
    const input = document.getElementById('rr-search-input');
    if (input) {
        input.value = '';
        submitInstantFilters();
    }
}

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Select a row in the table and populate the right-side detail panel immediately
function selectCaseRow(caseId, event) {
    const data = RR_CASES[caseId];
    if (!data) return;

    document.querySelectorAll('.rr-table tbody tr').forEach(tr => {
        tr.classList.toggle('rr-row-selected', Number(tr.dataset.caseId) === Number(caseId));
    });

    const selectedInput = document.getElementById('rrSelectedInput');
    if (selectedInput) selectedInput.value = String(caseId);

    // Keep selected state in the URL query string
    const url = new URL(window.location.href);
    url.searchParams.set('selected', String(caseId));
    window.history.replaceState({}, '', url.toString());

    const trackEl = document.getElementById('detail-track-id');
    const repEl = document.getElementById('detail-reported-at');
    const pillEl = document.getElementById('detail-status-pill');
    const statusTextEl = document.getElementById('detail-status-text');
    const slaEl = document.getElementById('detail-sla-badge');
    const reasonEl = document.getElementById('detail-reason');
    const noteEl = document.getElementById('detail-rider-note');
    const custNameEl = document.getElementById('detail-customer-name');
    const custPhoneEl = document.getElementById('detail-customer-phone');
    const riderNameEl = document.getElementById('detail-rider-name');
    const hubLabelEl = document.getElementById('detail-hub-label');
    const addrEl = document.getElementById('detail-address');
    const resNoteEl = document.getElementById('rr-resolution-note');
    const formEl = document.getElementById('rrResolveForm');
    const mediaContainer = document.getElementById('detail-media-container');
    const auditList = document.getElementById('detail-audit-list');
    const noteErr = document.getElementById('rr-note-error');

    if (noteErr) noteErr.style.display = 'none';
    if (trackEl) trackEl.textContent = data.track_id;
    if (repEl) repEl.textContent = `Barcode: ${data.barcode} · Reported ${data.reported_formatted}`;
    if (pillEl) pillEl.className = `rr-status-pill ${data.return_status}`;
    if (statusTextEl) statusTextEl.textContent = data.return_status_label;
    if (slaEl) {
        slaEl.className = `rr-sla-countdown ${data.sla_state}`;
        slaEl.textContent = data.sla_text;
    }
    if (reasonEl) reasonEl.textContent = data.reason;
    if (noteEl) noteEl.textContent = data.notes ? data.notes : 'No additional note entered by rider.';
    if (custNameEl) custNameEl.textContent = data.customer_name;
    if (custPhoneEl) custPhoneEl.textContent = data.customer_phone;
    if (riderNameEl) riderNameEl.textContent = data.rider_name;
    if (hubLabelEl) hubLabelEl.textContent = data.hub_label;
    if (addrEl) addrEl.textContent = data.customer_address;
    if (resNoteEl) resNoteEl.value = data.resolution_note || '';
    if (formEl) formEl.action = data.resolve_url;

    if (mediaContainer) {
        if (data.has_photo || data.has_video) {
            let html = '<div class="rr-media-grid">';
            const safeTitle = escapeHtml(`${data.track_id} — ${data.reason}`);
            if (data.has_photo) {
                html += `
                    <div class="rr-media-card">
                        <img src="${escapeHtml(data.photo_url)}" alt="Rider return photo for ${escapeHtml(data.track_id)}">
                        <div class="rr-media-footer">
                            <span>📷 Attached Picture</span>
                            <button type="button" class="text-button" aria-label="Enlarge attached picture" onclick="openMediaModal('photo', '${escapeHtml(data.photo_url)}', '${safeTitle}')">Enlarge ↗</button>
                        </div>
                    </div>`;
            }
            if (data.has_video) {
                html += `
                    <div class="rr-media-card">
                        <video src="${escapeHtml(data.video_url)}" controls preload="metadata"></video>
                        <div class="rr-media-footer">
                            <span>🎬 Attached Video</span>
                            <button type="button" class="text-button" aria-label="Expand attached video" onclick="openMediaModal('video', '${escapeHtml(data.video_url)}', '${safeTitle}')">Fullscreen ↗</button>
                        </div>
                    </div>`;
            }
            html += '</div>';
            mediaContainer.innerHTML = html;
        } else {
            mediaContainer.innerHTML = '<div style="padding: 16px; border: 1px dashed var(--line); border-radius: 10px; background: var(--blush-1); color: var(--muted); font-size: 12px; text-align: center;">No picture or video was attached for this parcel.</div>';
        }
    }

    if (auditList && Array.isArray(data.audit_trail)) {
        auditList.innerHTML = data.audit_trail.map(ev => `
            <li class="rr-audit-item">
                <strong>${escapeHtml(ev.action)} · <span style="color: var(--orange);">${escapeHtml(ev.actor)}</span> <small style="color: var(--muted); font-weight: 600;">(${escapeHtml(ev.role)})</small></strong>
                ${ev.note ? `<p>${escapeHtml(ev.note)}</p>` : ''}
                <time>${escapeHtml(ev.timestamp_formatted)}</time>
            </li>
        `).join('');
    }

    // On narrow screens where the detail panel stacks below the table, scroll smoothly to it
    if (window.innerWidth <= 1180 && event && event.type === 'click') {
        const panel = document.getElementById('rrDetailPanel');
        if (panel) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Single-case action confirmation & validation (require note when rejecting)
function triggerCaseAction(action, confirmPrompt) {
    const noteEl = document.getElementById('rr-resolution-note');
    const noteErr = document.getElementById('rr-note-error');
    const noteVal = (noteEl ? noteEl.value : '').trim();

    if (action === 'reject' && noteVal === '') {
        if (noteErr) noteErr.style.display = 'block';
        if (noteEl) noteEl.focus();
        return;
    }
    if (noteErr) noteErr.style.display = 'none';

    pendingSingleAction = action;
    const actionInput = document.getElementById('rr-resolve-action');
    if (actionInput) actionInput.value = action;

    const modal = document.getElementById('rrConfirmModal');
    const msgEl = document.getElementById('rrConfirmMessage');
    if (msgEl) msgEl.textContent = confirmPrompt;
    if (modal) modal.hidden = false;
}

function closeConfirmModal() {
    const modal = document.getElementById('rrConfirmModal');
    if (modal) modal.hidden = true;
    pendingSingleAction = null;
}

function confirmAndSubmitSingleAction() {
    const form = document.getElementById('rrResolveForm');
    if (form) form.submit();
}

function handleSingleResolveSubmit(event) {
    const actionInput = document.getElementById('rr-resolve-action');
    const noteEl = document.getElementById('rr-resolution-note');
    const noteErr = document.getElementById('rr-note-error');
    if (actionInput && actionInput.value === 'reject' && (!noteEl || noteEl.value.trim() === '')) {
        event.preventDefault();
        if (noteErr) noteErr.style.display = 'block';
        if (noteEl) noteEl.focus();
        return false;
    }
    return true;
}

// Bulk selection handling
function getSelectedParcelIds() {
    return Array.from(document.querySelectorAll('.rr-row-checkbox:checked')).map(cb => cb.value);
}

function toggleSelectAllRows(checked) {
    document.querySelectorAll('.rr-row-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    updateBulkSelectionState();
}

function updateBulkSelectionState() {
    const ids = getSelectedParcelIds();
    const totalCheckboxes = document.querySelectorAll('.rr-row-checkbox').length;
    const selectAllCb = document.getElementById('rr-select-all');
    const countLabel = document.getElementById('rr-selected-count-label');
    const bulkBtn = document.getElementById('rr-bulk-resolve-btn');

    if (selectAllCb) {
        selectAllCb.checked = totalCheckboxes > 0 && ids.length === totalCheckboxes;
        selectAllCb.indeterminate = ids.length > 0 && ids.length < totalCheckboxes;
    }
    if (countLabel) {
        countLabel.textContent = ids.length > 0 ? `${ids.length} selected` : 'Select all';
    }
    if (bulkBtn) {
        bulkBtn.disabled = ids.length === 0;
        bulkBtn.textContent = `✓ Bulk resolve (${ids.length})`;
    }
}

function openBulkResolveDialog() {
    const ids = getSelectedParcelIds();
    if (ids.length === 0) return;

    const container = document.getElementById('rrBulkHiddenInputs');
    if (container) {
        container.innerHTML = ids.map(id => `<input type="hidden" name="parcel_ids[]" value="${escapeHtml(id)}">`).join('');
    }
    const titleEl = document.getElementById('rrBulkModalTitle');
    if (titleEl) {
        titleEl.textContent = `Bulk resolve ${ids.length} ${ids.length === 1 ? 'case' : 'cases'}`;
    }
    const modal = document.getElementById('rrBulkModal');
    if (modal) modal.hidden = false;
}

function closeBulkResolveDialog() {
    const modal = document.getElementById('rrBulkModal');
    if (modal) modal.hidden = true;
}

function validateBulkForm(event) {
    const actionEl = document.getElementById('rr-bulk-action');
    const noteEl = document.getElementById('rr-bulk-note');
    const errEl = document.getElementById('rr-bulk-note-error');

    if (actionEl && actionEl.value === 'reject' && (!noteEl || noteEl.value.trim() === '')) {
        event.preventDefault();
        if (errEl) errEl.style.display = 'block';
        if (noteEl) noteEl.focus();
        return false;
    }
    if (errEl) errEl.style.display = 'none';
    return true;
}

// Media viewer modal
function openMediaModal(type, url, title) {
    const modal = document.getElementById('returnMediaModal');
    const titleEl = document.getElementById('returnMediaTitle');
    const bodyEl = document.getElementById('returnMediaBody');
    if (!modal || !bodyEl) return;

    if (titleEl) titleEl.textContent = title || 'Return / Refund Evidence';
    if (type === 'video') {
        bodyEl.innerHTML = `<video src="${escapeHtml(url)}" controls autoplay style="max-width: 100%; max-height: 66vh; border-radius: 10px;"></video>`;
    } else {
        bodyEl.innerHTML = `<img src="${escapeHtml(url)}" alt="${escapeHtml(title || 'Return/Refund Evidence')}" style="max-width: 100%; max-height: 66vh; border-radius: 10px; object-fit: contain;">`;
    }
    modal.hidden = false;
}

function closeMediaModal() {
    const modal = document.getElementById('returnMediaModal');
    const bodyEl = document.getElementById('returnMediaBody');
    if (bodyEl) bodyEl.innerHTML = '';
    if (modal) modal.hidden = true;
}
</script>
</body>
</html>
