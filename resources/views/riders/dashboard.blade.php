<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Rider Hub & Scanner — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .rider-stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .scanner-card {
            background: linear-gradient(135deg, rgba(61, 27, 61, 0.03), rgba(226, 112, 58, 0.05));
            border: 2px dashed #E2703A;
            border-radius: 18px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .scanner-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .scanner-input-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .scanner-input-row input {
            flex: 1 1 240px;
            padding: 12px 14px;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            background: #FFFDFB;
        }
        .scanner-input-row select {
            flex: 0 0 190px;
            padding: 12px;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            font-size: 13px;
            background: #FFFDFB;
        }
        .camera-box {
            display: none;
            position: relative;
            max-width: 480px;
            margin: 14px auto 0;
            border-radius: 14px;
            overflow: hidden;
            background: #111;
        }
        .camera-box video {
            width: 100%;
            height: 260px;
            object-fit: cover;
            display: block;
        }
        .camera-overlay {
            position: absolute;
            inset: 20px;
            border: 2px solid #E2703A;
            border-radius: 12px;
            pointer-events: none;
            box-shadow: 0 0 0 9999px rgba(0,0,0,0.45);
        }
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .filter-tab {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            background: #FBEFEA;
            color: #8A7B7E;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .filter-tab.active {
            background: #3D1B3D;
            color: #FFFDFB;
        }
        .action-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            border: 0;
            cursor: pointer;
            transition: opacity 0.15s;
        }
        .action-chip-btn:hover { opacity: 0.9; }
        .btn-pickup { background: #E2703A; color: #FFFDFB; }
        .btn-deliver { background: #1E6B37; color: #FFFDFB; }
        .btn-exception { background: #A52222; color: #FFFDFB; }
        .btn-secondary { background: #F3D8DE; color: #3D1B3D; }
        .barcode-badge {
            display: inline-block;
            font-family: monospace;
            background: #FBEFEA;
            color: #3D1B3D;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .chat-bubble-sm {
            padding: 10px 14px;
            border-radius: 12px;
            margin-bottom: 8px;
            font-size: 12px;
            line-height: 1.4;
        }
        .chat-mine {
            background: #FBEFEA;
            border-left: 3px solid #E2703A;
            margin-left: 20px;
        }
        .chat-theirs {
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            margin-right: 20px;
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">
                <span>Rider App</span>
                <span class="crumb-divider">/</span>
                <strong>Courier Command Desk</strong>
            </div>
            <div class="topbar-actions">
                <form method="POST" action="{{ route('riders.availability') }}" style="display: inline;">
                    @csrf
                    <button class="action-chip-btn {{ $rider->availability === 'online' ? 'btn-deliver' : 'btn-secondary' }}" type="submit">
                        ● {{ strtoupper($rider->availability) }} · Click to toggle
                    </button>
                </form>
                <a href="{{ route('riders.messages') }}" class="action-chip-btn btn-secondary" title="Messages">
                    ✉ Messages @if(!empty($unreadCount) && $unreadCount > 0)<span style="background:#E2703A;color:#fff;border-radius:10px;padding:1px 6px;font-size:9px;">{{ $unreadCount }}</span>@endif
                </a>
            </div>
        </header>

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <!-- Welcome & Duty Status -->
            <section class="welcome-row">
                <div>
                    <div class="eyebrow">{{ strtoupper($rider->availability) }} · APPROVED COURIER · {{ $rider->vehicle_type ?: 'Motorcycle' }}</div>
                    <h1>Hello, {{ $rider->user->name }}</h1>
                    <p class="intro">
                        Assigned coverage: <strong>{{ $rider->areas->pluck('name')->join(', ') ?: 'All areas' }}</strong>.
                        Ready to scan barcodes, deliver parcels, and keep live tracking updated.
                    </p>
                </div>
            </section>

            <!-- Metrics Row -->
            <section class="rider-stat-grid">
                <article class="metric-card gold-wash">
                    <div class="metric-top"><span class="metric-label">Ready for Pickup</span><span class="metric-icon plum">{{ $counts['assigned'] ?? 0 }}</span></div>
                    <div class="metric-value">{{ $counts['assigned'] ?? 0 }}</div>
                    <div class="metric-trend positive">Assigned to you</div>
                </article>
                <article class="metric-card plum-wash">
                    <div class="metric-top"><span class="metric-label">Out for Delivery</span><span class="metric-icon plum">{{ $counts['in_transit'] ?? 0 }}</span></div>
                    <div class="metric-value">{{ $counts['in_transit'] ?? 0 }}</div>
                    <div class="metric-trend positive">In transit queue</div>
                </article>
                <article class="metric-card sage-wash">
                    <div class="metric-top"><span class="metric-label">Delivered Today</span><span class="metric-icon plum">{{ $counts['delivered'] ?? 0 }}</span></div>
                    <div class="metric-value">{{ $counts['delivered'] ?? 0 }}</div>
                    <div class="metric-trend positive">Completed</div>
                </article>
                <article class="metric-card coral-wash">
                    <div class="metric-top"><span class="metric-label">Today's Earnings</span><span class="metric-icon plum">₱</span></div>
                    <div class="metric-value">₱{{ number_format($todayEarnings ?? 0, 2) }}</div>
                    <div class="metric-trend positive">Balance: ₱{{ number_format($totalBalance ?? 0, 2) }}</div>
                </article>
            </section>

            <!-- Quick Barcode Scanner & Dispatch Intake Section -->
            <section class="scanner-card">
                <div class="scanner-header">
                    <div>
                        <div class="section-kicker" style="color: #E2703A;">LIVE BARCODE SCANNER</div>
                        <h2 style="margin: 2px 0 0; font-size: 18px;">Scan or enter parcel barcode</h2>
                    </div>
                    <div>
                        <button type="button" id="toggleCameraBtn" class="primary-button" style="padding: 8px 16px; font-size: 12px; background: #3D1B3D;">
                            📷 Use Camera Scanner
                        </button>
                    </div>
                </div>

                <form method="POST" action="{{ route('riders.scan') }}" id="barcodeForm">
                    @csrf
                    <div class="scanner-input-row">
                        <input
                            type="text"
                            name="barcode"
                            id="barcodeInput"
                            placeholder="Scan barcode with laser or type barcode / track ID (e.g. PRC-..., BC-1001)"
                            autofocus
                            required
                        >
                        <select name="action">
                            <option value="accept">Pickup & Start Delivery</option>
                            <option value="out_for_delivery">Mark Out for Delivery</option>
                            <option value="delivered">Confirm Delivered (₱ payout)</option>
                            <option value="lookup">Verify / Look Up Only</option>
                        </select>
                        <input type="text" name="notes" placeholder="Optional notes (e.g. Left with reception)" style="flex: 1 1 200px;">
                        <button type="submit" class="primary-button" style="padding: 12px 20px;">
                            ⚡ Submit Scan
                        </button>
                    </div>
                </form>

                <!-- Camera Viewfinder -->
                <div class="camera-box" id="cameraBox">
                    <video id="cameraVideo" playsinline></video>
                    <div class="camera-overlay"></div>
                    <div style="position: absolute; bottom: 10px; width: 100%; text-align: center; color: #fff; font-size: 11px;">
                        Point camera at package barcode or QR code
                    </div>
                </div>
            </section>

            <!-- Main Layout: Assigned Deliveries Queue & Quick Messages -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
                <!-- Left: Delivery Queue Table -->
                <section class="panel shipments-panel" style="margin-bottom: 0;">
                    <div class="panel-heading shipments-heading">
                        <div>
                            <div class="section-kicker">DELIVERY MANIFEST</div>
                            <h2>Assigned parcels queue</h2>
                        </div>
                        <form method="GET" action="{{ route('riders.dashboard') }}" style="display: flex; gap: 8px;">
                            <input name="search" value="{{ request('search') }}" placeholder="Search parcel or address..." style="padding: 6px 10px; border-radius: 8px; border: 1px solid #EBDDD8; font-size: 12px;">
                            <button class="secondary-button" type="submit" style="padding: 6px 12px;">Search</button>
                        </form>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <a href="{{ route('riders.dashboard') }}" class="filter-tab {{ !request('status') ? 'active' : '' }}">All ({{ $counts['total'] ?? 0 }})</a>
                        <a href="{{ route('riders.dashboard', ['status' => 'assigned']) }}" class="filter-tab {{ request('status') === 'assigned' ? 'active' : '' }}">Ready for Pickup ({{ $counts['assigned'] ?? 0 }})</a>
                        <a href="{{ route('riders.dashboard', ['status' => 'active']) }}" class="filter-tab {{ request('status') === 'active' ? 'active' : '' }}">Out for Delivery ({{ $counts['in_transit'] ?? 0 }})</a>
                        <a href="{{ route('riders.dashboard', ['status' => 'delivered']) }}" class="filter-tab {{ request('status') === 'delivered' ? 'active' : '' }}">Delivered ({{ $counts['delivered'] ?? 0 }})</a>
                        <a href="{{ route('riders.dashboard', ['status' => 'exception']) }}" class="filter-tab {{ request('status') === 'exception' ? 'active' : '' }}">Exceptions ({{ $counts['exception'] ?? 0 }})</a>
                    </div>

                    <div class="table-wrap">
                        <table class="shipments-table">
                            <thead>
                                <tr>
                                    <th>Parcel / Barcode</th>
                                    <th>Recipient & Contact</th>
                                    <th>Destination Address</th>
                                    <th>Status</th>
                                    <th>Tracking Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($parcels as $parcel)
                                    <tr>
                                        <td>
                                            <strong>{{ $parcel->parcel_number }}</strong>
                                            @if($parcel->barcode)
                                                <div style="margin-top: 3px;">
                                                    <span class="barcode-badge">||| {{ $parcel->barcode }}</span>
                                                </div>
                                            @endif
                                            @if($parcel->shipment)
                                                <small style="color: #8A7B7E; display: block; margin-top: 2px;">Track: {{ $parcel->shipment->lumora_tracking_id }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <strong>{{ $parcel->recipient_name }}</strong>
                                            @if($parcel->recipient_phone)
                                                <div style="margin-top: 2px;">
                                                    <a href="tel:{{ $parcel->recipient_phone }}" style="color: #E2703A; text-decoration: none; font-size: 11px; font-weight: 700;">
                                                        📞 {{ $parcel->recipient_phone }}
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <span>{{ $parcel->address_line }}</span>
                                            <small style="display: block; color: #8A7B7E;">{{ $parcel->city }}, {{ $parcel->province }}</small>
                                            @if($parcel->area)
                                                <small style="display: inline-block; background: #FFFDFB; border: 1px solid #EBDDD8; padding: 1px 6px; border-radius: 4px; margin-top: 2px;">
                                                    📍 {{ $parcel->area->name }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $chipClass = match($parcel->status) {
                                                    'delivered' => 'sage-wash',
                                                    'in_transit', 'out_for_delivery', 'rider_received' => 'in-transit',
                                                    'exception', 'failed' => 'exception',
                                                    default => 'packed',
                                                };
                                            @endphp
                                            <span class="status-chip {{ $chipClass }}">
                                                <b></b>{{ ucwords(str_replace('_', ' ', $parcel->status)) }}
                                            </span>
                                            @if($parcel->scans->isNotEmpty())
                                                <small style="display: block; font-size: 10px; color: #8A7B7E; margin-top: 4px;">
                                                    Last scan: {{ $parcel->scans->first()->scanned_at->diffForHumans() }}
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                @if($parcel->status === 'assigned')
                                                    <!-- Accept assignment -->
                                                    <form method="POST" action="{{ route('riders.status', $parcel) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="in_transit">
                                                        <input type="hidden" name="note" value="Picked up from sorting center by {{ auth()->user()->name }}">
                                                        <button class="action-chip-btn btn-pickup" type="submit">
                                                            ✓ Accept & Pick Up
                                                        </button>
                                                    </form>
                                                @elseif(in_array($parcel->status, ['rider_received', 'in_transit']))
                                                    <form method="POST" action="{{ route('riders.status', $parcel) }}" style="display: inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="status" value="out_for_delivery">
                                                        <button class="action-chip-btn btn-pickup" type="submit">
                                                            ⛟ Out for Delivery
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('riders.status', $parcel) }}" style="display: inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button class="action-chip-btn btn-deliver" type="submit">
                                                            ✓ Mark Delivered
                                                        </button>
                                                    </form>
                                                @elseif($parcel->status === 'out_for_delivery')
                                                    <form method="POST" action="{{ route('riders.status', $parcel) }}" style="display: inline-block;">
                                                        @csrf
                                                        <input type="hidden" name="status" value="delivered">
                                                        <button class="action-chip-btn btn-deliver" type="submit">
                                                            ✓ Mark Delivered
                                                        </button>
                                                    </form>
                                                    <button class="action-chip-btn btn-exception" type="button" onclick="reportIssue({{ $parcel->id }}, '{{ $parcel->parcel_number }}')">
                                                        ⚠ Report Issue
                                                    </button>
                                                @elseif($parcel->status === 'delivered')
                                                    <span style="color: #1E6B37; font-weight: 700; font-size: 11px;">
                                                        ✓ Completed (+₱150.00)
                                                    </span>
                                                @elseif($parcel->status === 'exception')
                                                    <span style="color: #A52222; font-weight: 700; font-size: 11px;">
                                                        ⚠ Exception noted
                                                    </span>
                                                    <a href="{{ route('riders.messages', ['parcel_id' => $parcel->id]) }}" class="action-chip-btn btn-secondary">
                                                        Contact Support
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 28px; color: #8A7B7E;">
                                            No assigned parcels matching this view. Check the barcode scanner above to accept a delivery!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">{{ $parcels->links() }}</div>
                </section>

                <!-- Right: Operations Messaging Widget -->
                <section class="panel" style="margin-bottom: 0;">
                    <div class="panel-heading" style="margin-bottom: 12px;">
                        <div>
                            <div class="section-kicker">OPERATIONS CHAT</div>
                            <h2 style="font-size: 16px;">Live sorting desk</h2>
                        </div>
                        <a href="{{ route('riders.messages') }}" style="font-size: 11px; color: #E2703A; font-weight: 700; text-decoration: none;">View all →</a>
                    </div>

                    <!-- Mini message history -->
                    <div style="max-height: 280px; overflow-y: auto; padding-right: 4px; margin-bottom: 14px;">
                        @forelse($recentMessages as $msg)
                            <div class="chat-bubble-sm {{ $msg->sender_id === auth()->id() ? 'chat-mine' : 'chat-theirs' }}">
                                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #8A7B7E; margin-bottom: 3px;">
                                    <strong>{{ $msg->sender_id === auth()->id() ? 'You' : ($msg->sender?->name ?: 'Sorting Center') }}</strong>
                                    <span>{{ $msg->created_at->format('h:i A') }}</span>
                                </div>
                                <div>{{ $msg->message }}</div>
                                @if($msg->parcel)
                                    <small style="display: inline-block; background: rgba(0,0,0,0.04); padding: 1px 4px; border-radius: 3px; font-size: 9px; margin-top: 4px;">
                                        Ref: {{ $msg->parcel->parcel_number }}
                                    </small>
                                @endif
                            </div>
                        @empty
                            <p style="color: #8A7B7E; font-size: 12px; margin: 12px 0;">No messages yet. Send an operational update below.</p>
                        @endforelse
                    </div>

                    <!-- Fast Send message -->
                    <form method="POST" action="{{ route('riders.messages.send') }}">
                        @csrf
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <textarea
                                name="message"
                                placeholder="Message sorting center staff (e.g. Traffic delay on Area A)..."
                                required
                                rows="3"
                                style="width: 100%; border: 1px solid #EBDDD8; border-radius: 10px; padding: 10px; font-size: 12px; resize: none; background: #FFFDFB;"
                            ></textarea>
                            <button type="submit" class="primary-button" style="width: 100%; font-size: 12px; padding: 10px;">
                                ✉ Send to Operations
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </main>
</div>

<!-- Modal for reporting delivery issues -->
<dialog id="issueModal" style="border: 0; border-radius: 16px; padding: 24px; max-width: 420px; width: 90%; box-shadow: 0 20px 40px rgba(0,0,0,0.25);">
    <form method="POST" id="issueForm" action="">
        @csrf
        <input type="hidden" name="status" value="exception">
        <h3 style="margin-top: 0; color: #3D1B3D; font-size: 18px;">Report Delivery Issue</h3>
        <p style="font-size: 12px; color: #8A7B7E; margin-bottom: 14px;" id="issueParcelNumber"></p>
        <label style="display: block; margin-bottom: 12px; font-size: 12px; font-weight: 700;">
            Reason for issue:
            <select name="note" required style="width: 100%; margin-top: 6px; padding: 10px; border: 1px solid #EBDDD8; border-radius: 8px;">
                <option value="Recipient unreachable or phone unattended">Recipient unreachable or phone unattended</option>
                <option value="Incorrect address or inaccessible location">Incorrect address or inaccessible location</option>
                <option value="Recipient rejected delivery or requested rescheduling">Recipient rejected delivery / rescheduled</option>
                <option value="Severe weather or impassable route">Severe weather or impassable route</option>
                <option value="Package damaged or broken seal">Package damaged or broken seal</option>
            </select>
        </label>
        <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px;">
            <button type="button" class="secondary-button" onclick="document.getElementById('issueModal').close()">Cancel</button>
            <button type="submit" class="primary-button" style="background: #A52222;">Confirm Issue</button>
        </div>
    </form>
</dialog>

<script>
    function reportIssue(parcelId, parcelNumber) {
        const modal = document.getElementById('issueModal');
        const form = document.getElementById('issueForm');
        form.action = '/rider/parcels/' + parcelId + '/status';
        document.getElementById('issueParcelNumber').innerText = 'Reporting exception for ' + parcelNumber;
        modal.showModal();
    }

    // Camera Barcode Scanning Support
    let stream = null;
    const toggleBtn = document.getElementById('toggleCameraBtn');
    const cameraBox = document.getElementById('cameraBox');
    const video = document.getElementById('cameraVideo');
    const barcodeInput = document.getElementById('barcodeInput');

    toggleBtn.addEventListener('click', async () => {
        if (stream) {
            stopCamera();
        } else {
            startCamera();
        }
    });

    async function startCamera() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            video.srcObject = stream;
            video.play();
            cameraBox.style.display = 'block';
            toggleBtn.innerText = '✕ Close Camera';
            scanLoop();
        } catch (err) {
            alert('Camera access unavailable or denied. Please enter or laser-scan the barcode manually.');
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        cameraBox.style.display = 'none';
        toggleBtn.innerText = '📷 Use Camera Scanner';
    }

    async function scanLoop() {
        if (!stream) return;
        if ('BarcodeDetector' in window) {
            try {
                const detector = new BarcodeDetector({
                    formats: ['code_128', 'code_39', 'qr_code', 'ean_13', 'upc_a']
                });
                const barcodes = await detector.detect(video);
                if (barcodes.length > 0) {
                    const rawValue = barcodes[0].rawValue;
                    barcodeInput.value = rawValue;
                    stopCamera();
                    // Audio beep
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        osc.connect(ctx.destination);
                        osc.frequency.setValueAtTime(880, ctx.currentTime);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.15);
                    } catch(e) {}
                    document.getElementById('barcodeForm').submit();
                    return;
                }
            } catch(e) {}
        }
        requestAnimationFrame(scanLoop);
    }
</script>
</body>
</html>
