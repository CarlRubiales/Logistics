<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#71163d">
    <title>Track your parcel — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="tracking-page">
    <main class="tracking-shell">
        <header class="tracking-header">
            <a class="tracking-brand" href="{{ route('dashboard') }}"><span class="brand-mark"><i></i><i></i><i></i></span><span><strong>LUMORA</strong><small>LOGISTICS</small></span></a>
            <a class="tracking-back" href="{{ route('login') }}">Log in to portal</a>
        </header>

        <section class="tracking-hero">
            <div class="tracking-eyebrow">PARCEL TRACKING</div>
            <h1>Where is your parcel?</h1>
            <p>Enter your tracking number to see the latest delivery status and movement history.</p>
            <form class="tracking-search" method="GET" action="{{ route('tracking.index') }}">
                <label for="tracking">Tracking number</label>
                <div class="tracking-search-row"><input id="tracking" name="tracking" value="{{ $trackingNumber }}" placeholder="Example: LUM-1001" required><button class="primary-button" type="submit">Track parcel</button></div>
            </form>
        </section>

        @if ($notFound)
            <div class="tracking-alert"><strong>We could not find that parcel.</strong><span>Check the tracking number and try again.</span></div>
        @elseif ($shipment)
            @php
                $currentLabel = $shipment->status === 'delivered' ? 'Delivered' : ($shipment->status === 'in_transit' ? 'Out for delivery' : ($shipment->status === 'packed' ? 'Shipped' : 'Order received'));
                $lastEvent = $shipment->trackingEvents->first();
            @endphp
            <section class="tracking-card tracking-summary">
                <div class="tracking-status-heading"><div><div class="tracking-eyebrow">CURRENT STATUS</div><h2>{{ $currentLabel }}{{ $shipment->delivered_at ? ' on '.$shipment->delivered_at->format('j M') : '' }}</h2></div><span class="tracking-status-icon">{{ $shipment->status === 'delivered' ? '✓' : '→' }}</span></div>
                <div class="progress-track"><div class="progress-line"><span style="width: {{ ($currentStage / 3) * 100 }}%"></span></div>@foreach ($stages as $index => $stage)<div class="progress-step {{ $index <= $currentStage ? 'complete' : '' }} {{ $index === $currentStage ? 'current' : '' }}"><span>{{ $index < $currentStage ? '✓' : $index + 1 }}</span><strong>{{ $stage['label'] }}</strong></div>@endforeach</div>
            </section>

            <section class="tracking-card tracking-parcel-card"><div class="parcel-meta"><div class="parcel-icon">▣</div><div><strong>{{ $shipment->carrier ?: 'Lumora Logistics' }}</strong><span>{{ $shipment->tracking_number }}</span></div></div><a class="outline-button" href="{{ route('tracking.index', ['tracking' => $shipment->tracking_number]) }}">Refresh</a></section>

            <section class="tracking-card tracking-history"><div class="tracking-section-heading"><div><div class="tracking-eyebrow">DELIVERY HISTORY</div><h2>Tracking updates</h2></div><span class="event-count">{{ $shipment->trackingEvents->count() }} updates</span></div>
                <div class="timeline">
                    @forelse ($shipment->trackingEvents as $event)
                        <article class="timeline-event {{ $loop->first ? 'latest' : '' }}"><time>{{ $event->occurred_at?->format('j M') }}<small>{{ $event->occurred_at?->format('H:i') }}</small></time><span class="timeline-dot"></span><div class="timeline-copy"><strong>{{ $event->label }}</strong>@if ($event->description)<p>{{ $event->description }}</p>@endif@if ($event->location)<small>{{ $event->location }}</small>@endif</div></article>
                    @empty
                        <div class="empty-tracking">No tracking updates have been recorded yet.</div>
                    @endforelse
                </div>
            </section>
        @else
            <section class="tracking-card tracking-empty"><div class="empty-tracking-icon">⌖</div><h2>Track a delivery</h2><p>Your shipment progress and delivery history will appear here after you enter a tracking number.</p></section>
        @endif
        <footer class="tracking-footer">© {{ date('Y') }} Lumora Logistics <span>•</span> Delivery updates are provided by the connected store.</footer>
    </main>
</body>
</html>
