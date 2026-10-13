<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#3D1B3D">
    <title>Track a shipment — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<main class="content-wrap">
    <header class="welcome-row"><div><div class="eyebrow">LUMORA LOGISTICS</div><h1>Track your shipment</h1></div></header>
    <section class="panel">
        <form method="GET" action="{{ route('tracking.index') }}" class="form-grid">
            <label>Track ID<input name="tracking" value="{{ $trackingNumber }}" placeholder="LM-TRK-000013" required></label>
            <button class="primary-button" type="submit">Track shipment</button>
        </form>
        @if($notFound)<p class="server-alert error" role="alert">No shipment found. Please check your Track ID.</p>@endif
        @if($shipment)
            <div class="detail-divider"></div><h2>{{ $shipment->lumora_tracking_id }}</h2>
            <p class="intro">Lumora Express · {{ ucwords(str_replace('_', ' ', $shipment->status)) }}</p>
            <div class="section-kicker">TRACKING HISTORY</div>
            <div class="tracking-timeline">
                @forelse($shipment->trackingEvents as $event)
                    <div class="timeline-event"><span class="timeline-dot"></span><div><strong>{{ $event->label }}</strong><small>{{ $event->occurred_at?->format('M j, Y g:i A') }}</small></div></div>
                @empty<p class="intro">No tracking events recorded yet.</p>@endforelse
            </div>
        @endif
    </section>
</main>
</body>
</html>