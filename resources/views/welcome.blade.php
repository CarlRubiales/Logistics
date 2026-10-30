<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Lumora Logistics — Operations console</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
    <div class="app-shell">
        @include('partials.sidebar')

        <main class="main-content" id="overview">
            <header class="topbar">
                <div class="topbar-mobile-brand"><div class="brand-mark"><span></span><span></span><span></span></div><strong>LUMORA</strong></div>
                <button class="mobile-menu" id="mobile-menu" aria-label="Open navigation">☰</button>
                <div class="breadcrumb"><span>Workspace</span><span class="crumb-divider">/</span><strong>Overview</strong></div>
                <div class="topbar-actions">
                    <label class="search-field"><span>⌕</span><input id="global-search" type="search" placeholder="Search orders, shipments..." aria-label="Search orders and shipments"></label>
                    <button class="icon-button" title="Notifications" aria-label="Notifications"><span class="notification-ring"></span><span class="notification-dot"></span></button>
                    <button class="help-button">?</button>
                    <button class="user-button"><span class="avatar small">AM</span><span class="user-name">Amelia</span><span class="chevron">⌄</span></button>
                </div>
            </header>

            <div class="content-wrap">
                @if (session('status'))
                    <div class="server-alert" role="status"><span>✓</span>{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>
                @endif
                <section class="welcome-row">
                    <div>
                        <div class="eyebrow">THURSDAY, 12 SEPTEMBER 2024</div>
                        <h1>Good morning, Amelia <span class="wave">✦</span></h1>
                        <p class="intro">Here’s the pulse of your fulfillment floor today.</p>
                    </div>
                    <div class="welcome-actions">
                        <button class="ghost-button" id="export-button"><span>↓</span> Export report</button>
                        <button class="primary-button" id="create-shipment"><span>＋</span> Create shipment</button>
                    </div>
                </section>

                <section class="metric-grid" aria-label="Fulfillment metrics">
                    <article class="metric-card plum-wash">
                        <div class="metric-top"><span class="metric-label">Orders today</span><span class="metric-icon plum">▤</span></div>
                        <div class="metric-value">{{ $metrics['orders_today'] ?? 128 }}</div>
                        <div class="metric-trend positive"><span>↗</span> 12.8% <small>vs yesterday</small></div>
                        <div class="sparkline plum-line"><i style="height:35%"></i><i style="height:49%"></i><i style="height:43%"></i><i style="height:68%"></i><i style="height:58%"></i><i style="height:82%"></i><i style="height:75%"></i><i style="height:100%"></i></div>
                    </article>
                    <article class="metric-card coral-wash">
                        <div class="metric-top"><span class="metric-label">To be packed</span><span class="metric-icon coral">◌</span></div>
                        <div class="metric-value">{{ $metrics['to_be_packed'] ?? 42 }}</div>
                        <div class="metric-trend warning"><span>↗</span> 8.4% <small>vs yesterday</small></div>
                        <div class="sparkline coral-line"><i style="height:64%"></i><i style="height:49%"></i><i style="height:70%"></i><i style="height:43%"></i><i style="height:58%"></i><i style="height:36%"></i><i style="height:28%"></i><i style="height:22%"></i></div>
                    </article>
                    <article class="metric-card sage-wash">
                        <div class="metric-top"><span class="metric-label">In transit</span><span class="metric-icon sage">↝</span></div>
                        <div class="metric-value">{{ $metrics['in_transit'] ?? 76 }}</div>
                        <div class="metric-trend positive"><span>↗</span> 4.2% <small>vs yesterday</small></div>
                        <div class="sparkline sage-line"><i style="height:35%"></i><i style="height:43%"></i><i style="height:56%"></i><i style="height:47%"></i><i style="height:70%"></i><i style="height:64%"></i><i style="height:82%"></i><i style="height:88%"></i></div>
                    </article>
                    <article class="metric-card gold-wash">
                        <div class="metric-top"><span class="metric-label">Exceptions</span><span class="metric-icon gold">!</span></div>
                        <div class="metric-value">{{ $metrics['exceptions'] ?? 4 }}</div>
                        <div class="metric-trend positive"><span>↘</span> 33.3% <small>vs yesterday</small></div>
                        <div class="sparkline gold-line"><i style="height:82%"></i><i style="height:70%"></i><i style="height:64%"></i><i style="height:56%"></i><i style="height:43%"></i><i style="height:35%"></i><i style="height:28%"></i><i style="height:22%"></i></div>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="panel pipeline-panel">
                        <div class="panel-heading"><div><div class="section-kicker">FULFILLMENT FLOW</div><h2>Today’s pipeline</h2></div><button class="text-button" data-toast="Pipeline view coming soon">View full flow <span>→</span></button></div>
                        <div class="pipeline-visual">
                            <div class="pipeline-axis"><span>00</span><span>06</span><span>12</span><span>18</span><span>24</span></div>
                            <div class="flow-row"><span class="flow-label"><b class="dot coral-dot"></b>Received</span><div class="flow-track"><div class="flow-fill coral-fill" style="width: 88%"></div></div><strong>128</strong></div>
                            <div class="flow-row"><span class="flow-label"><b class="dot plum-dot"></b>Picking</span><div class="flow-track"><div class="flow-fill plum-fill" style="width: 48%"></div></div><strong>64</strong></div>
                            <div class="flow-row"><span class="flow-label"><b class="dot gold-dot"></b>Packing</span><div class="flow-track"><div class="flow-fill gold-fill" style="width: 31%"></div></div><strong>42</strong></div>
                            <div class="flow-row"><span class="flow-label"><b class="dot sage-dot"></b>Dispatched</span><div class="flow-track"><div class="flow-fill sage-fill" style="width: 64%"></div></div><strong>76</strong></div>
                        </div>
                        <div class="pipeline-footer"><div class="mini-status"><span class="status-dot green"></span><span>On track</span></div><span>Next carrier pickup <strong>in 2h 18m</strong></span></div>
                    </article>

                    <article class="panel exception-panel">
                        <div class="panel-heading"><div><div class="section-kicker">NEEDS ATTENTION</div><h2>Open exceptions <span class="count-badge">4</span></h2></div><button class="icon-button small-icon" data-toast="Exception filters coming soon">•••</button></div>
                        <div class="exception-list">
                            <button class="exception-item" data-toast="Opening exception EX-2048"><span class="exception-symbol coral-bg">!</span><span class="exception-copy"><strong>Address needs review</strong><small>Order #LM-2048 · 12 min ago</small></span><span class="exception-arrow">→</span></button>
                            <button class="exception-item" data-toast="Opening exception EX-2042"><span class="exception-symbol gold-bg">⌁</span><span class="exception-copy"><strong>Carrier delay reported</strong><small>Order #LM-2042 · 44 min ago</small></span><span class="exception-arrow">→</span></button>
                            <button class="exception-item" data-toast="Opening exception EX-2037"><span class="exception-symbol plum-bg">↺</span><span class="exception-copy"><strong>Return requested</strong><small>Order #LM-2037 · 1h ago</small></span><span class="exception-arrow">→</span></button>
                            <button class="exception-item" data-toast="Opening exception EX-2019"><span class="exception-symbol sage-bg">▣</span><span class="exception-copy"><strong>Package damaged</strong><small>Order #LM-2019 · 2h ago</small></span><span class="exception-arrow">→</span></button>
                        </div>
                        <button class="secondary-button full-button" data-nav="exceptions">Review all exceptions <span>→</span></button>
                    </article>
                </section>

                <section class="panel shipments-panel" id="shipments">
                    <div class="panel-heading shipments-heading"><div><div class="section-kicker">LIVE OPERATIONS</div><h2>Recent shipments</h2></div><div class="table-actions"><div class="filter-tabs"><button class="filter-tab active" data-filter="all">All <span>12</span></button><button class="filter-tab" data-filter="in-transit">In transit <span>7</span></button><button class="filter-tab" data-filter="exception">Exceptions <span>4</span></button></div><button class="icon-button filter-button" data-toast="Advanced filters coming soon">≡</button></div></div>
                    <div class="table-wrap">
                        <table class="shipments-table">
                            <thead><tr><th>Shipment</th><th>Recipient</th><th>Carrier</th><th>Destination</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                            <tbody id="shipments-body">
                                <tr data-status="in-transit"><td><button class="shipment-id" data-toast="Opening shipment SH-240912-081">SH-240912-081</button><small>#LM-2187</small></td><td><div class="recipient"><span class="table-avatar blush">SC</span><span>Sophia Chen</span></div></td><td><span class="carrier"><span class="carrier-logo dhl">dhl</span>DHL Express</span></td><td>New York, NY</td><td><span class="status-chip in-transit"><b></b>In transit</span></td><td>8 min ago</td><td><button class="row-menu" data-toast="Shipment actions coming soon">•••</button></td></tr>
                                <tr data-status="packed"><td><button class="shipment-id" data-toast="Opening shipment SH-240912-080">SH-240912-080</button><small>#LM-2186</small></td><td><div class="recipient"><span class="table-avatar sage">JR</span><span>James Robinson</span></div></td><td><span class="carrier"><span class="carrier-logo ups">UPS</span>UPS Ground</span></td><td>Chicago, IL</td><td><span class="status-chip packed"><b></b>Ready to ship</span></td><td>22 min ago</td><td><button class="row-menu" data-toast="Shipment actions coming soon">•••</button></td></tr>
                                <tr data-status="in-transit"><td><button class="shipment-id" data-toast="Opening shipment SH-240912-079">SH-240912-079</button><small>#LM-2184</small></td><td><div class="recipient"><span class="table-avatar gold">OV</span><span>Olivia Valdez</span></div></td><td><span class="carrier"><span class="carrier-logo fedex">FedEx</span>FedEx Home</span></td><td>Austin, TX</td><td><span class="status-chip in-transit"><b></b>In transit</span></td><td>38 min ago</td><td><button class="row-menu" data-toast="Shipment actions coming soon">•••</button></td></tr>
                                <tr data-status="exception"><td><button class="shipment-id" data-toast="Opening shipment SH-240912-077">SH-240912-077</button><small>#LM-2182</small></td><td><div class="recipient"><span class="table-avatar plum">NK</span><span>Noah Kim</span></div></td><td><span class="carrier"><span class="carrier-logo dhl">dhl</span>DHL Express</span></td><td>Seattle, WA</td><td><span class="status-chip exception"><b></b>Exception</span></td><td>1h ago</td><td><button class="row-menu" data-toast="Shipment actions coming soon">•••</button></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer"><span>Showing <strong>1–4</strong> of <strong>128 shipments</strong></span><div class="pagination"><button disabled>←</button><button class="page active">1</button><button class="page">2</button><button class="page">3</button><span>…</span><button class="page">32</button><button>→</button></div></div>
                </section>

                <section class="bottom-grid">
                    <article class="panel activity-panel"><div class="panel-heading"><div><div class="section-kicker">RECENT ACTIVITY</div><h2>What’s happening</h2></div><button class="text-button" data-toast="Activity history coming soon">See history <span>→</span></button></div><div class="activity-list"><div class="activity-item"><span class="activity-icon sage-bg">✓</span><div><p><strong>Shipment SH-240912-074</strong> was delivered</p><small>Today at 10:42 AM · Brooklyn, NY</small></div><span class="activity-time">12m</span></div><div class="activity-item"><span class="activity-icon plum-bg">↗</span><div><p><strong>New order batch</strong> imported from Lumora storefront</p><small>Today at 10:30 AM · 18 orders</small></div><span class="activity-time">24m</span></div><div class="activity-item"><span class="activity-icon coral-bg">◌</span><div><p><strong>Pickup manifest</strong> is ready for DHL Express</p><small>Today at 9:58 AM · Dock 02</small></div><span class="activity-time">56m</span></div></div></article>
                    <article class="panel integration-panel"><div class="integration-art"><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="integration-center">↯</div><span class="satellite one">▤</span><span class="satellite two">↝</span><span class="satellite three">✓</span></div><div class="integration-copy"><div class="section-kicker">CONNECTED</div><h2>Your storefront is in sync.</h2><p>Orders are flowing in from Lumora Beauty and tracking updates are ready to send back.</p><button class="secondary-button" data-nav="integrations">Manage integrations <span>→</span></button></div></article>
                </section>

                <footer class="page-footer"><span>© 2024 Lumora Logistics</span><span>Designed for calm operations.</span><span><a href="#support">Support</a><a href="#docs">API docs</a></span></footer>
            </div>
        </main>
    </div>

    <div class="modal-backdrop" id="shipment-modal" hidden>
        <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <button class="modal-close" id="modal-close" aria-label="Close dialog">×</button>
            <div class="section-kicker">NEW FULFILLMENT</div><h2 id="modal-title">Create a shipment</h2><p class="modal-intro">Start from an order in your storefront, then add the package and carrier details.</p>
            <form id="shipment-form" action="{{ route('shipments.store') }}" method="POST">@csrf<label>Order reference<input name="order" placeholder="e.g. LM-2190" required></label><div class="form-grid"><label>Package weight<input name="weight" placeholder="0.0 kg" required></label><label>Carrier<select name="carrier"><option>DHL Express</option><option>UPS Ground</option><option>FedEx Home</option></select></label></div><label>Internal note<textarea name="note" placeholder="Optional packing instructions"></textarea></label><div class="modal-actions"><button type="button" class="ghost-button" id="modal-cancel">Cancel</button><button type="submit" class="primary-button">Create shipment <span>→</span></button></div></form>
        </div>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"><span>✓</span><span id="toast-message">Saved</span></div>
</body>
</html>
