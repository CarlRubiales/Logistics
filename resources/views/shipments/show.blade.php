<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#3D1B3D"><title>{{ $shipment->lumora_tracking_id }} — Lumora Logistics</title>@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content"><header class="topbar"><div class="topbar-mobile-brand"><div class="brand-mark"><span></span><span></span><span></span></div><strong>LUMORA</strong></div><button class="mobile-menu" id="mobile-menu">☰</button><div class="breadcrumb"><a href="{{ route('shipments.index') }}">Shipments</a><span class="crumb-divider">/</span><strong>{{ $shipment->lumora_tracking_id }}</strong></div><div class="topbar-actions"><button class="icon-button" title="Notifications" aria-label="Notifications"><span class="notification-ring"></span><span class="notification-dot"></span></button><button class="help-button">?</button><button class="user-button"><span class="avatar small">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span><span class="user-name">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span><span class="chevron">⌄</span></button></div></header>
        @php
            $parcel = $shipment->parcels->first();
            $address = $shipment->order?->shipping_address ?? [];
            $statusLabel = \App\Services\ShipmentStatusService::label($shipment->status);
            $isException = $currentStage === false;
            $canUpdate = in_array(auth()->user()->role, ['admin', 'operations_manager', 'warehouse_staff'], true);
            $destination = implode(', ', array_filter([$shipment->destination_city, $shipment->destination_state]));
            $lastEvent = $shipment->trackingEvents->first();
        @endphp
        <div class="content-wrap shipment-detail-page">
            <a class="back-link" href="{{ route('shipments.index') }}">← Back to shipments</a>
            @if(session('success'))<div class="server-alert" role="status">{{ session('success') }}</div>@endif
            @if($errors->any())<div class="server-alert error" role="alert">{{ $errors->first() }}</div>@endif
            <section class="shipment-heading">
                <div>
                    <div class="eyebrow">SHIPMENT DETAILS</div>
                    <div class="shipment-title-row">
                        <h1>{{ $shipment->lumora_tracking_id }}</h1>
                        <button type="button" class="outline-button copy-track-id" data-copy-track="{{ $shipment->lumora_tracking_id }}" aria-label="Copy Track ID">Copy</button>
                    </div>
                    <p class="intro">{{ $shipment->carrier_display }} · {{ $shipment->service_level ?: 'Standard' }}</p>
                    <p class="shipment-updated">Last updated {{ $shipment->updated_at?->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</p>
                    <span class="copy-feedback" role="status" aria-live="polite"></span>
                </div>
                <div class="shipment-heading-actions">
                    <span class="status-chip shipment-status {{ str_replace('_', '-', $shipment->status) }} {{ $isException ? 'shipment-warning' : '' }}"><b></b>{{ $shipment->status === 'delivered' ? '✓ ' : '' }}{{ $statusLabel }}</span>
                    @if($canUpdate && $allowedStatuses->isNotEmpty())
                        <button type="button" class="primary-button" data-open-shipment-status>Update status</button>
                    @endif
                </div>
            </section>

            <ol class="shipment-progress" aria-label="Shipment progress">
                @foreach($stages as $key => $label)
                    @php
                        $completed = !$isException && ($loop->index < $currentStage || $shipment->status === 'delivered');
                        $current = !$isException && $loop->index === $currentStage;
                    @endphp
                    <li class="{{ $completed ? 'complete' : '' }} {{ $current ? 'current' : '' }}" @if($current) aria-current="step" @endif>
                        <span class="shipment-step-marker" aria-hidden="true">{{ $completed ? '✓' : ($current ? '●' : '○') }}</span>
                        <strong>{{ $label }}</strong>
                        <span class="shipment-sr-only">{{ $completed ? 'Completed' : ($current ? 'Current stage' : 'Upcoming') }}</span>
                    </li>
                @endforeach
            </ol>
            @if($isException)<p class="shipment-exception-note" role="status">{{ $statusLabel }} — normal delivery progress is paused.</p>@endif

            <div class="shipment-detail-grid">
                <section class="panel shipment-information" aria-label="Shipment information">
                    <section>
                        <div class="section-kicker">DELIVERY DESTINATION</div>
                        <h2>{{ $destination ?: 'Destination pending' }}</h2>
                        @if($parcel?->address_line || ($address['line1'] ?? null))
                            <address>{{ implode(', ', array_filter([$parcel?->address_line ?: ($address['line1'] ?? null), $destination, $parcel?->postal_code ?: ($address['postal_code'] ?? null)])) }}</address>
                        @endif
                        @if($parcel)<p class="intro">Delivery area: {{ $parcel->area?->name ?: 'Determine after scan' }}</p>@endif
                    </section>
                    <section>
                        <div class="section-kicker">RECIPIENT</div>
                        <h2>{{ $shipment->order?->customer_name ?? 'Customer unavailable' }}</h2>
                        @if($shipment->order?->customer_email)<p>{{ $shipment->order->customer_email }}</p>@endif
                        @if($parcel?->recipient_phone || ($address['phone'] ?? null))<p>{{ $parcel?->recipient_phone ?: $address['phone'] }}</p>@endif
                    </section>
                    <section>
                        <div class="section-kicker">PARCEL INFORMATION</div>
                        <dl class="shipment-facts">
                            @if($parcel?->barcode)<div><dt>Barcode</dt><dd>{{ $parcel->barcode }}</dd></div>@endif
                            <div><dt>Package weight</dt><dd>{{ $shipment->weight !== null ? $shipment->weight.' kg' : 'Not recorded' }}</dd></div>
                            <div><dt>Service level</dt><dd>{{ $shipment->service_level ?: 'Standard' }}</dd></div>
                            <div><dt>Date received</dt><dd>{{ $parcel?->received_at?->timezone('Asia/Manila')->format('M j, Y · g:i A') ?? 'Not recorded' }}</dd></div>
                        </dl>
                    </section>
                    <section><div class="section-kicker">CARRIER INFORMATION</div><h2>{{ $shipment->carrier_display }}</h2></section>
                </section>

                <section class="panel shipment-history-card" aria-label="Tracking timeline">
                    <div class="section-kicker">TRACKING TIMELINE</div><h2>Journey so far</h2>
                    <div class="shipment-current-state {{ $isException ? 'shipment-warning' : '' }}">
                        <div><small>Current status</small><strong>{{ $shipment->status === 'delivered' ? '✓ ' : '' }}{{ $statusLabel }}</strong></div>
                        <div><small>Last update</small><time>{{ ($lastEvent?->occurred_at ?? $shipment->updated_at)?->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</time></div>
                        @if($shipment->status === 'delivered')
                            <p>{{ $lastEvent?->location ?: $destination }}</p>
                            @if($shipment->delivered_at)<p>Delivered {{ $shipment->delivered_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</p>@endif
                        @endif
                    </div>
                    <ol class="shipment-history">
                        @forelse($shipment->trackingEvents as $event)
                            @php $warning = in_array($event->status, ['exception', 'failed', 'cancelled', 'returned', 'on_hold'], true); @endphp
                            <li class="shipment-history-event {{ $loop->first ? 'latest' : '' }} {{ $warning ? 'is-warning' : '' }}">
                                <span class="shipment-event-marker" aria-hidden="true">{{ match($event->status) { 'delivered' => '✓', 'packed' => '▤', 'in_transit' => '→', 'out_for_delivery' => '⌖', 'exception', 'failed', 'cancelled', 'returned', 'on_hold' => '!', default => '↓' } }}</span>
                                <div class="shipment-event-copy">
                                    <h3>{{ $event->label }}</h3>
                                    @if($event->location)<p class="shipment-event-location">{{ $event->location }}</p>@endif
                                    @if($event->description)<p>{{ $event->description }}</p>@endif
                                    <time datetime="{{ $event->occurred_at?->toIso8601String() }}">{{ $event->occurred_at?->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</time>
                                </div>
                            </li>
                        @empty
                            <li class="shipment-history-empty"><strong>No tracking activity yet.</strong><p>Tracking updates will appear here as the parcel moves through Lumora Logistics.</p></li>
                        @endforelse
                    </ol>
                </section>
            </div>

            @if($canUpdate && $allowedStatuses->isNotEmpty())
                <dialog class="shipment-status-dialog" id="shipment-status-dialog" aria-labelledby="shipment-status-title" @if($errors->any()) data-reopen @endif>
                    <h2 id="shipment-status-title">Update shipment status</h2>
                    <p class="intro">Track ID: <strong>{{ $shipment->lumora_tracking_id }}</strong></p>
                    <p class="intro">Confirm the new status below. This creates a timestamped tracking event.</p>
                    <form method="POST" action="{{ route('shipments.status', $shipment) }}" class="form-grid">
                        @csrf
                        <label class="full-span">New status<select name="status" required>
                            @foreach($allowedStatuses as $value => $label)<option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>@endforeach
                        </select></label>
                        <label class="full-span">Location<input name="location" maxlength="160" value="{{ old('location', $destination) }}" placeholder="Santa Cruz, Laguna"></label>
                        <label class="full-span">Tracking note<textarea name="description" maxlength="500" placeholder="Optional tracking note">{{ old('description') }}</textarea></label>
                        <div class="shipment-dialog-actions full-span"><button type="button" class="secondary-button" data-close-shipment-status>Cancel</button><button type="submit" class="primary-button">Update shipment</button></div>
                    </form>
                </dialog>
            @endif
        </div>
    </main>
</div>
</body>
</html>