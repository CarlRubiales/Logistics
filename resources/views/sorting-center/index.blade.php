 <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#3D1B3D">
    <title>Sorting Center — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => (auth()->user()->getBranchName() ? auth()->user()->getBranchName() . ' Branch Hub' : 'Operations Desk'),
            'title' => 'Sorting Center'
        ])
        <div class="content-wrap">
            @if(session('success'))<div class="server-alert" role="status"><span>✓</span>{{ session('success') }}</div>@endif
            @if($errors->any())<div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>@endif
            <section class="welcome-row"><div><div class="eyebrow">{{ strtoupper(auth()->user()->getBranchName() ? (auth()->user()->getBranchName() . ' SORTING CENTER HUB') : 'SORTING CENTER OPERATIONS') }}</div><h1>{{ auth()->user()->getBranchName() ? (auth()->user()->getBranchName() . ' parcel control desk') : 'Parcel control desk' }}</h1><p class="intro">Receive, scan, determine the delivery area, sort, and assign every parcel to the correct rider for {{ auth()->user()->getBranchName() ?? 'your hub' }}.</p></div></section>
            @include('partials.dashboard_date_selector')
            <section class="metric-grid">
                @foreach([['received','Received'],['scanned','Scanned'],['sorted','Sorted'],['assigned','Assigned']] as [$key,$label])
                    <article class="metric-card {{ ['received'=>'coral-wash','scanned'=>'plum-wash','sorted'=>'sage-wash','assigned'=>'gold-wash'][$key] }}"><div class="metric-top"><span class="metric-label">{{ $label }}</span><span class="metric-icon plum">{{ $counts[$key] ?? 0 }}</span></div><div class="metric-value">{{ $counts[$key] ?? 0 }}</div><div class="metric-trend {{ $isToday ? 'positive' : '' }}">{{ $isToday ? 'Live queue' : 'Archived' }} <small>{{ $isToday ? 'today' : $formattedDate }}</small></div></article>
                @endforeach
            </section>
            <section class="dashboard-grid">
                <article class="panel"><div class="panel-heading"><div><div class="section-kicker">RECEIVE PARCEL</div><h2>New parcel intake</h2></div></div>
                    <form method="POST" action="{{ route('sorting-center.receive') }}" class="form-grid">@csrf
                        <label>Track ID<input value="Auto-generated after receiving parcel" readonly aria-readonly="true"></label>
                        <label>Barcode<input name="barcode" value="{{ old('barcode') }}" placeholder="Scan or type barcode"></label>
                        <label>Recipient name<input name="recipient_name" value="{{ old('recipient_name') }}" required></label>
                        <label>Phone<input name="recipient_phone" type="tel" value="{{ old('recipient_phone') }}" placeholder="09171234567" required></label>
                        <label class="full-span">Delivery address<textarea name="address_line" required>{{ old('address_line') }}</textarea></label>
                        <label>City / municipality<input name="city" value="{{ old('city', auth()->user()->getBranchName()) }}" placeholder="{{ auth()->user()->getBranchName() ?? 'Santa Cruz' }}" required></label>
                        <label>Province<input name="province" value="{{ old('province', 'Laguna') }}" required></label>
                        <label>Postal code<input name="postal_code" value="{{ old('postal_code') }}"></label>
                        <label>Area override<select name="destination_area_id"><option value="">Determine after scan</option>@foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('destination_area_id') == $area->id)>{{ $area->name }} ({{ $area->province ?: 'Laguna' }})</option>@endforeach</select></label>
                        <button class="primary-button full-span" type="submit">＋ Receive parcel</button>
                    </form>
                </article>
                <article class="panel"><div class="panel-heading"><div><div class="section-kicker">WORKFLOW</div><h2>Processing sequence</h2></div></div><div class="exception-list"><div class="exception-item"><span class="exception-symbol coral-bg">1</span><span class="exception-copy"><strong>Receive</strong><small>Register the parcel and address.</small></span></div><div class="exception-item"><span class="exception-symbol plum-bg">2</span><span class="exception-copy"><strong>Scan</strong><small>Confirm barcode and parcel identity.</small></span></div><div class="exception-item"><span class="exception-symbol sage-bg">3</span><span class="exception-copy"><strong>Determine area</strong><small>Use the destination mapping or override.</small></span></div><div class="exception-item"><span class="exception-symbol gold-bg">4</span><span class="exception-copy"><strong>Assign rider</strong><small>Only riders assigned to that area are eligible.</small></span></div></div></article>
            </section>
            <section class="panel shipments-panel"><div class="panel-heading shipments-heading"><div><div class="section-kicker" style="display: flex; align-items: center; gap: 8px;">LIVE PARCEL QUEUE <span id="live-sync-indicator" style="display: inline-flex; align-items: center; gap: 5px; font-size: 10.5px; color: #1E6B37; font-weight: 700; background: rgba(30, 107, 55, 0.1); padding: 3px 10px; border-radius: 999px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #1E6B37; display: inline-block;"></span> Live Courier Sync</span></div><h2>Sorting center parcels</h2></div><form method="GET"><input type="hidden" name="date" value="{{ $selectedDateStr }}"><input name="search" value="{{ request('search') }}" placeholder="Search parcel, recipient, city"><select name="status"><option value="">All statuses</option>@foreach(['received','scanned','sorted','assigned','rider_received','in_transit','out_for_delivery','delivered','exception','cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucwords(str_replace('_',' ', $status)) }}</option>@endforeach</select><button class="secondary-button" type="submit">Filter</button></form></div>
                <div class="table-wrap"><table class="shipments-table"><thead><tr><th>Track ID</th><th>Recipient / address</th><th>Area</th><th>Status</th><th>Rider</th><th>Actions</th></tr></thead><tbody>
                    @forelse($parcels as $parcel)<tr><td>@if($parcel->shipment)<a class="shipment-id" href="{{ route('shipments.show', $parcel->shipment) }}">{{ $parcel->shipment->lumora_tracking_id }}</a>@else<strong>{{ $parcel->parcel_number }}</strong>@endif<small>{{ $parcel->barcode ?: 'No barcode' }}</small></td><td>{{ $parcel->recipient_name }}<small>{{ $parcel->city }}, {{ $parcel->province }}</small></td><td>{{ $parcel->area?->name ?: 'Not determined' }}<small>{{ $parcel->sort_bin ?: 'No bin' }}</small></td><td>@php $cClass = match($parcel->status) { 'delivered' => 'sage-wash', 'assigned', 'rider_received', 'in_transit', 'out_for_delivery' => 'in-transit', 'exception', 'failed', 'cancelled' => 'exception', default => 'packed' }; @endphp<span class="status-chip {{ $cClass }}"><b></b>{{ ucwords(str_replace('_',' ', $parcel->status)) }}</span></td><td>@if($parcel->rider){{ $parcel->rider->user->name }}@if($parcel->rider->user_id)<a href="{{ route('messages.index', ['rider_user_id' => $parcel->rider->user_id, 'parcel_id' => $parcel->id]) }}" title="Message rider" style="margin-left: 4px; text-decoration: none; font-size: 11px;">✉</a>@endif @else<span style="color: #8A7B7E;">Unassigned</span>@endif</td><td><div class="table-actions">
                        @if(in_array($parcel->status, ['received']))<form method="POST" action="{{ route('sorting-center.scan', $parcel) }}">@csrf<button class="text-button" type="submit">Scan</button></form>@endif
                        @if(in_array($parcel->status, ['scanned','received']))<form method="POST" action="{{ route('sorting-center.sort', $parcel) }}">@csrf<select name="destination_area_id" required><option value="">Area</option>@foreach($areas as $area)<option value="{{ $area->id }}">{{ $area->name }} ({{ $area->province ?: 'Laguna' }})</option>@endforeach</select><input name="sort_bin" placeholder="Bin"><button class="text-button" type="submit">Sort</button></form>@endif
                        @if(in_array($parcel->status, ['sorted']))<form method="POST" action="{{ route('sorting-center.assign', $parcel) }}">@csrf<select name="assigned_rider_id" required style="max-width: 180px;"><option value="">Select Rider...</option>@php $areaRiders = $parcel->destination_area_id ? $riders->filter(fn($r) => $r->areas->contains('id', $parcel->destination_area_id)) : collect(); $otherRiders = $parcel->destination_area_id ? $riders->reject(fn($r) => $r->areas->contains('id', $parcel->destination_area_id)) : $riders; @endphp @if($areaRiders->isNotEmpty())<optgroup label="Assigned to {{ $parcel->area?->name }}">@foreach($areaRiders as $rider)<option value="{{ $rider->id }}">{{ $rider->user->name }} ({{ ucfirst($rider->availability) }})</option>@endforeach</optgroup>@endif @if($otherRiders->isNotEmpty())<optgroup label="{{ $areaRiders->isNotEmpty() ? 'Other Approved Riders' : 'Approved Riders' }}">@foreach($otherRiders as $rider)<option value="{{ $rider->id }}">{{ $rider->user->name }} ({{ ucfirst($rider->availability) }})</option>@endforeach</optgroup>@endif</select><input name="commission_amount" type="number" min="0" step="0.01" placeholder="₱ comm" style="width: 70px;"><button class="text-button" type="submit">Assign</button></form>@endif
                    </div></td></tr>@empty<tr><td colspan="6">No parcels found. Receive the first parcel above.</td></tr>@endforelse
                </tbody></table></div><div class="table-footer">{{ $parcels->links() }}</div>
            </section>
        </div>
    </main>
</div>
<script>
(function() {
    let lastSignature = null;
    let isUpdating = false;
    const pollInterval = 1600; // Check every 1.6s for instantaneous updates without manual refresh

    async function checkLiveUpdates() {
        if (isUpdating) return;

        try {
            const res = await fetch('/api/v1/scanner/status', {
                headers: { 'Accept': 'application/json' },
                cache: 'no-store'
            });
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.signature) return;

            if (lastSignature === null) {
                lastSignature = data.signature;
                return;
            }

            if (lastSignature !== data.signature) {
                lastSignature = data.signature;
                await refreshTableData();
            }
        } catch (err) {}
    }

    async function refreshTableData() {
        isUpdating = true;
        try {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('_t', Date.now());

            const pageRes = await fetch(currentUrl.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store'
            });

            if (!pageRes.ok) {
                window.location.reload();
                return;
            }

            const html = await pageRes.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 1. Update Metric Cards
            const newMetrics = doc.querySelector('.metric-grid');
            const currentMetrics = document.querySelector('.metric-grid');
            if (newMetrics && currentMetrics) {
                currentMetrics.innerHTML = newMetrics.innerHTML;
            }

            // 2. Update Table Rows
            const newTbody = doc.querySelector('.shipments-table tbody');
            const currentTbody = document.querySelector('.shipments-table tbody');
            if (newTbody && currentTbody) {
                currentTbody.innerHTML = newTbody.innerHTML;

                // Subtle highlight pulse on newly added/updated top row
                const topRow = currentTbody.querySelector('tr');
                if (topRow) {
                    topRow.style.transition = 'background-color 0.4s ease';
                    topRow.style.backgroundColor = 'rgba(30, 107, 55, 0.15)';
                    setTimeout(() => {
                        topRow.style.backgroundColor = '';
                    }, 2400);
                }
            }

            // 3. Update Table Footer
            const newFooter = doc.querySelector('.table-footer');
            const currentFooter = document.querySelector('.table-footer');
            if (newFooter && currentFooter) {
                currentFooter.innerHTML = newFooter.innerHTML;
            }

            // 4. Update Live Indicator
            const indicator = document.getElementById('live-sync-indicator');
            if (indicator) {
                indicator.innerHTML = '<span style="width: 7px; height: 7px; border-radius: 50%; background: #28a745; display: inline-block; box-shadow: 0 0 6px #28a745;"></span> Live Sync: Updated';
                setTimeout(() => {
                    if (indicator) {
                        indicator.innerHTML = '<span style="width: 6px; height: 6px; border-radius: 50%; background: #1E6B37; display: inline-block;"></span> Live Courier Sync';
                    }
                }, 3000);
            }
        } catch (e) {
            window.location.reload();
        } finally {
            isUpdating = false;
        }
    }

    setInterval(checkLiveUpdates, pollInterval);
    checkLiveUpdates();
})();
</script>
</body></html>
