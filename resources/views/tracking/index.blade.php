<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Track a shipment — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .tracking-progress-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 28px 0;
            position: relative;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        .step-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #EBDDD8;
            color: #8A7B7E;
            display: grid;
            place-items: center;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }
        .step-circle.active {
            background: #3D1B3D;
            color: #FFFDFB;
            box-shadow: 0 0 0 4px rgba(226, 112, 58, 0.25);
        }
        .step-circle.completed {
            background: #1E6B37;
            color: #FFFDFB;
        }
        .step-title {
            font-size: 11px;
            font-weight: 700;
            color: #3A2E30;
        }
        .step-title.inactive {
            color: #8A7B7E;
            font-weight: 500;
        }
        .progress-track-line {
            position: absolute;
            top: 16px;
            left: 12%;
            right: 12%;
            height: 3px;
            background: #EBDDD8;
            z-index: 1;
        }
        .progress-track-fill {
            height: 100%;
            background: #1E6B37;
            transition: width 0.3s ease;
        }
        .courier-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            background: #FBEFEA;
            border-radius: 14px;
            margin: 20px 0;
        }
    </style>
</head>
<body class="logistics-page">
<main class="content-wrap" style="max-width: 720px; margin: 40px auto; padding: 0 16px;">
    <header class="welcome-row" style="text-align: center; justify-content: center;">
        <div>
            <div class="eyebrow" style="color: #E2703A;">LUMORA LOGISTICS NETWORK</div>
            <h1 style="font-size: 28px;">Live Shipment Tracker</h1>
            <p class="intro">Enter your Track ID, parcel number, or barcode to view live courier status.</p>
        </div>
    </header>

    <section class="panel" style="padding: 28px;">
        <form method="GET" action="{{ route('tracking.index') }}" class="form-grid">
            <label class="full-span">
                Tracking code / Barcode
                <input
                    name="tracking"
                    value="{{ $trackingNumber }}"
                    placeholder="e.g. LM-TRK-000001, PRC-1001, or barcode"
                    required
                    style="padding: 14px; font-size: 15px; font-weight: 600;"
                >
            </label>
            <button class="primary-button full-span" type="submit" style="padding: 14px; font-size: 14px;">
                ⚡ Track Live Delivery
            </button>
        </form>

        @if($notFound)
            <div class="server-alert error" role="alert" style="margin-top: 20px;">
                <span>!</span> No shipment or parcel found for "{{ $trackingNumber }}". Please verify your barcode or tracking number.
            </div>
        @endif

        @if($shipment || $parcel)
            @php
                $displayStatus = $shipment?->status ?? $parcel?->status ?? 'pending';
                $trackingCode = $shipment ? $shipment->lumora_tracking_id : $parcel->parcel_number;
                $rider = $parcel?->rider ?? $shipment?->parcels?->first()?->rider;
                $destination = $parcel ? ($parcel->city . ', ' . $parcel->province) : ($shipment->destination_city . ', ' . $shipment->destination_state);
                $barcode = $parcel?->barcode ?? $shipment?->parcels?->first()?->barcode;
            @endphp

            <div class="detail-divider" style="margin: 24px 0;"></div>

            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="margin: 0; font-size: 22px;">{{ $trackingCode }}</h2>
                    @if($barcode)
                        <div style="margin-top: 4px;">
                            <span style="font-family: monospace; background: #FBEFEA; color: #3D1B3D; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700;">
                                ||| Barcode: {{ $barcode }}
                            </span>
                        </div>
                    @endif
                    <p class="intro" style="margin-top: 6px;">
                        Destination: <strong>{{ $destination }}</strong>
                    </p>
                </div>
                <div>
                    @php
                        $chipClass = match($displayStatus) {
                            'delivered' => 'sage-wash',
                            'in_transit', 'out_for_delivery', 'rider_received' => 'in-transit',
                            'exception', 'failed' => 'exception',
                            default => 'packed',
                        };
                    @endphp
                    <span class="status-chip {{ $chipClass }}" style="font-size: 12px; padding: 6px 14px;">
                        <b></b>{{ ucwords(str_replace('_', ' ', $displayStatus)) }}
                    </span>
                </div>
            </div>

            <!-- Visual Progress Stepper -->
            <div class="tracking-progress-row">
                <div class="progress-track-line">
                    <div class="progress-track-fill" style="width: {{ $displayStatus === 'delivered' ? 100 : min(100, $currentStage * 25) }}%;"></div>
                </div>
                @foreach($stages as $index => $stage)
                    @php
                        $isCompleted = ($displayStatus === 'delivered' || $index < $currentStage);
                        $isActive = ($displayStatus !== 'delivered' && $index === $currentStage);
                    @endphp
                    <div class="progress-step">
                        <div class="step-circle {{ $isCompleted ? 'completed' : ($isActive ? 'active' : '') }}">
                            {{ $isCompleted ? '✓' : ($index + 1) }}
                        </div>
                        <div class="step-title {{ $isCompleted || $isActive ? '' : 'inactive' }}">
                            {{ $stage['label'] }}
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Assigned Courier Card -->
            @if($rider && $rider->user)
                <div class="courier-card">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #3D1B3D; color: #FFFDFB; display: grid; place-items: center; font-size: 18px; flex-shrink: 0;">
                        ⛟
                    </div>
                    <div style="flex: 1;">
                        <div style="font-size: 11px; color: #8A7B7E; font-weight: 700;">ASSIGNED COURIER</div>
                        <strong style="color: #3D1B3D; font-size: 14px;">{{ $rider->user->name }}</strong>
                        <small style="display: block; color: #8A7B7E;">Vehicle: {{ $rider->vehicle_type ?: 'Motorcycle Fleet' }}</small>
                    </div>
                </div>
            @endif

            <!-- Tracking Timeline -->
            <div class="section-kicker" style="margin-top: 24px;">LIVE TIMELINE & SCANS</div>
            <div class="tracking-timeline">
                @if($parcel && $parcel->scans->isNotEmpty())
                    @foreach($parcel->scans as $scan)
                        <div class="timeline-event">
                            <span class="timeline-dot" style="background: #E2703A;"></span>
                            <div>
                                <strong>{{ ucwords(str_replace('_', ' ', $scan->scan_type)) }}</strong>
                                @if($scan->note)
                                    <div style="font-size: 12px; color: #3A2E30; margin-top: 2px;">{{ $scan->note }}</div>
                                @endif
                                <small>{{ $scan->scanned_at?->format('M j, Y g:i A') }} · Lumora Courier Scan</small>
                            </div>
                        </div>
                    @endforeach
                @endif

                @if($shipment && $shipment->trackingEvents->isNotEmpty())
                    @foreach($shipment->trackingEvents as $event)
                        <div class="timeline-event">
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>{{ $event->label }}</strong>
                                @if($event->description)
                                    <div style="font-size: 12px; color: #3A2E30; margin-top: 2px;">{{ $event->description }}</div>
                                @endif
                                <small>{{ $event->occurred_at?->format('M j, Y g:i A') }} @if($event->location)· {{ $event->location }}@endif</small>
                            </div>
                        </div>
                    @endforeach
                @elseif(! $parcel || $parcel->scans->isEmpty())
                    <p class="intro" id="emptyEventsNotice">No tracking events recorded yet. Parcel is awaiting first processing scan.</p>
                @endif
            </div>

            <!-- Live Polling Indicator -->
            <div style="margin-top: 20px; padding: 10px 14px; background: rgba(30, 107, 55, 0.08); border-radius: 10px; display: flex; align-items: center; justify-content: space-between;" id="liveStatusBanner">
                <span style="font-size: 11px; color: #1E6B37; font-weight: 700; display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #1E6B37; display: inline-block; animation: pulse 1.5s infinite;"></span>
                    Live Tracking: Auto-updating when rider updates status
                </span>
                <small style="font-size: 10px; color: #8A7B7E;" id="lastCheckedTime">Checking live...</small>
            </div>
        @endif
    </section>

    <div style="text-align: center; margin-top: 24px;">
        <a href="{{ route('login') }}" style="color: #8A7B7E; font-size: 12px; text-decoration: none;">
            Employee & Rider sign in →
        </a>
    </div>
</main>

@if(!empty($trackingNumber) && ($shipment || $parcel))
<script>
    (function() {
        const trackingCode = @json($trackingNumber);
        let currentStatus = @json($displayStatus ?? 'pending');
        let pollInterval = 3500;
        let pollTimer = null;

        async function checkStatus() {
            try {
                const response = await fetch('/track/' + encodeURIComponent(trackingCode) + '?format=json', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) return;

                const data = await response.json();
                if (!data.found) return;

                const lastChecked = document.getElementById('lastCheckedTime');
                if (lastChecked) {
                    lastChecked.innerText = 'Last updated: ' + new Date().toLocaleTimeString();
                }

                // If status changed to out_for_delivery or delivered
                if (data.effective_status && data.effective_status !== currentStatus) {
                    currentStatus = data.effective_status;
                    // Auto-reload the page seamlessly to display full courier events & animations
                    window.location.reload();
                }
            } catch (err) {
                // Silently handle offline/network blips
            }
        }

        // Only poll if not yet final (delivered / cancelled)
        if (currentStatus !== 'delivered' && currentStatus !== 'cancelled') {
            pollTimer = setInterval(checkStatus, pollInterval);
            checkStatus();
        }
    })();
</script>
<style>
@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.3); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.7; }
}
</style>
@endif
</body>
</html>