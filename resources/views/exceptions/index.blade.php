<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Return / Refund — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => ($branch ? $branch . ' Branch Hub' : 'Operations Audit'),
            'title' => 'Return / Refund'
        ])
        <div class="content-wrap module-page">
            <section class="welcome-row module-heading">
                <div>
                    <div class="eyebrow">RIDER RETURN & REFUND EVIDENCE DESK</div>
                    <h1>{{ $branch ? ($branch . ' Return / Refund') : 'Return / Refund' }}</h1>
                    <p class="intro">Review rider-submitted Return / Refund parcels, inspect their reason, notes, attached picture, and video evidence, and process hub resolution.</p>
                </div>
            </section>

            @if (session('status'))
                <div class="server-alert" role="status"><span>✓</span>{{ session('status') }}</div>
            @endif

            <section class="order-stat-grid module-stat-grid">
                <div class="order-stat cancelled">
                    <span>Total Return / Refunds</span>
                    <strong>{{ $stats['open'] }}</strong>
                </div>
                <div class="order-stat paid">
                    <span>With Picture / Video</span>
                    <strong>{{ $stats['with_media'] }}</strong>
                </div>
                <div class="order-stat processing">
                    <span>Damaged / Wrong Item</span>
                    <strong>{{ $stats['damaged_or_wrong'] }}</strong>
                </div>
                <div class="order-stat">
                    <span>Refused / Unreachable</span>
                    <strong>{{ $stats['refused_or_unreachable'] }}</strong>
                </div>
            </section>

            <section class="panel orders-panel">
                <div class="orders-toolbar" style="flex-wrap: wrap; gap: 10px;">
                    <form class="orders-search" method="GET" action="{{ route('exceptions.index') }}" style="display: flex; gap: 8px; flex-wrap: wrap; flex: 1;">
                        <div style="display: flex; align-items: center; gap: 6px; flex: 1; min-width: 220px;">
                            <span>⌕</span>
                            <input name="search" value="{{ $search }}" type="search" placeholder="Search parcel Track ID, barcode, customer, or reason..." aria-label="Search Return/Refund" style="width: 100%;">
                        </div>
                        <select name="reason" onchange="this.form.submit()" style="height: 36px; border-radius: 8px; border: 1px solid #EBDDD8; padding: 0 10px; font-size: 12px; background: #FFFDFB; color: #3D1B3D;">
                            <option value="">All Return / Refund Reasons</option>
                            @foreach([
                                'Damaged / Defective Item',
                                'Wrong Item / Incomplete Order',
                                'Customer Refused / Cancelled Order',
                                'Item Does Not Match Description',
                                'Customer Unreachable / Not Home',
                                'Wrong / Incomplete Delivery Address',
                                'Other Return / Refund Reason',
                            ] as $reasonOpt)
                                <option value="{{ $reasonOpt }}" @selected(($filterReason ?? '') === $reasonOpt)>{{ $reasonOpt }}</option>
                            @endforeach
                        </select>
                        <button class="secondary-button" type="submit" style="height: 36px;">Filter</button>
                    </form>
                    <span class="results-label">{{ $returnParcels->total() }} return / refund parcels</span>
                </div>

                <div class="table-wrap">
                    <table class="shipments-table orders-table">
                        <thead>
                            <tr>
                                <th>Parcel / Track ID</th>
                                <th>Customer & Destination</th>
                                <th>Rider & Hub</th>
                                <th>Return / Refund Reason & Notes</th>
                                <th>Picture & Video Evidence</th>
                                <th>Status</th>
                                <th>Resolution Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($returnParcels as $parcel)
                                @php
                                    $meta = is_array($parcel->metadata) ? $parcel->metadata : [];
                                    $reason = $meta['return_reason'] ?? $meta['failure_reason'] ?? 'Return / Refund Requested';
                                    $notes = $meta['return_notes'] ?? $meta['failure_notes'] ?? null;
                                    $resolution = $meta['return_resolution'] ?? null;
                                    $resolvedBy = $meta['return_resolved_by'] ?? null;
                                    $hasPhoto = !empty($meta['return_photo_path']) || !empty($meta['return_photo_data']) || !empty($meta['proof_url']);
                                    $hasVideo = !empty($meta['return_video_path']) || !empty($meta['return_video_data']) || !empty($meta['proof_video_url']);
                                    $photoUrl = $hasPhoto ? route('returns.media', ['parcel' => $parcel->id, 'type' => 'photo']) : null;
                                    $videoUrl = $hasVideo ? route('returns.media', ['parcel' => $parcel->id, 'type' => 'video']) : null;
                                @endphp
                                <tr>
                                    <td>
                                        @if($parcel->shipment)
                                            <a class="shipment-id" href="{{ route('shipments.show', $parcel->shipment) }}">{{ $parcel->shipment->lumora_tracking_id }}</a>
                                        @else
                                            <strong class="shipment-id">{{ $parcel->parcel_number }}</strong>
                                        @endif
                                        <small>Barcode: {{ $parcel->barcode ?: '—' }}</small>
                                        @if($parcel->order)
                                            <div style="margin-top: 3px;">
                                                <a class="order-open" href="{{ route('orders.show', $parcel->order) }}" style="font-size: 11px;">Order {{ $parcel->order->order_number ?: $parcel->order->external_id }}</a>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $parcel->recipient_name }}</strong>
                                        <small>{{ $parcel->recipient_phone ?: 'No phone' }}</small>
                                        <small style="display: block; color: #6E5D57; margin-top: 2px;">{{ $parcel->area?->name ? ($parcel->area->name . ' · ') : '' }}{{ $parcel->city }}, {{ $parcel->province }}</small>
                                    </td>
                                    <td>
                                        @if($parcel->rider && $parcel->rider->user)
                                            <strong>{{ $parcel->rider->user->name }}</strong>
                                            <small>{{ $parcel->branch ?: $parcel->city }} Hub</small>
                                            <div style="margin-top: 4px;">
                                                <a href="{{ route('messages.index', ['rider_user_id' => $parcel->rider->user_id, 'parcel_id' => $parcel->id]) }}" style="font-size: 11px; color: #3D1B3D; font-weight: 700; text-decoration: none;">✉ Message Rider</a>
                                            </div>
                                        @else
                                            <span style="color: #8A7B7E;">Unassigned</span>
                                            <small>{{ $parcel->branch ?: $parcel->city }} Hub</small>
                                        @endif
                                    </td>
                                    <td style="max-width: 260px;">
                                        <span style="display: inline-block; padding: 3px 9px; border-radius: 999px; background: rgba(198, 40, 40, 0.1); color: #C62828; font-size: 11px; font-weight: 800; margin-bottom: 4px;">
                                            ↺ {{ $reason }}
                                        </span>
                                        @if($notes)
                                            <div style="font-size: 12px; color: #3D1B3D; line-height: 1.35; margin-top: 2px;">{{ $notes }}</div>
                                        @else
                                            <small style="color: #8A7B7E;">No additional notes provided.</small>
                                        @endif
                                        <small style="display: block; margin-top: 4px; color: #8A7B7E;">
                                            Reported {{ $parcel->updated_at?->format('M j, Y · g:i A') }}
                                        </small>
                                    </td>
                                    <td style="min-width: 210px;">
                                        @if($hasPhoto || $hasVideo)
                                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                                @if($hasPhoto)
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <a href="{{ $photoUrl }}" target="_blank" rel="noopener" onclick="openMediaModal('photo', '{{ $photoUrl }}', '{{ addslashes($parcel->parcel_number . ' — ' . $reason) }}'); return false;" style="display: inline-block; border-radius: 8px; overflow: hidden; border: 1px solid #EBDDD8; width: 58px; height: 46px; flex-shrink: 0; background: #F5ECE8;">
                                                            <img src="{{ $photoUrl }}" alt="Return Picture" style="width: 100%; height: 100%; object-fit: cover;">
                                                        </a>
                                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                                            <button type="button" onclick="openMediaModal('photo', '{{ $photoUrl }}', '{{ addslashes($parcel->parcel_number . ' — ' . $reason) }}')" style="background: none; border: 0; padding: 0; text-align: left; color: #3D1B3D; font-size: 11.5px; font-weight: 800; cursor: pointer; text-decoration: underline;">
                                                                📷 View Picture
                                                            </button>
                                                            <a href="{{ $photoUrl }}" target="_blank" rel="noopener" style="font-size: 10.5px; color: #6E5D57;">Open full image ↗</a>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if($hasVideo)
                                                    <div style="display: flex; flex-direction: column; gap: 4px; background: #FBF6F3; padding: 6px 8px; border-radius: 8px; border: 1px solid #EBDDD8;">
                                                        <video src="{{ $videoUrl }}" controls preload="metadata" style="width: 185px; max-height: 110px; border-radius: 6px; background: #000;"></video>
                                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                                            <button type="button" onclick="openMediaModal('video', '{{ $videoUrl }}', '{{ addslashes($parcel->parcel_number . ' — ' . $reason) }}')" style="background: none; border: 0; padding: 0; color: #E2703A; font-size: 11px; font-weight: 800; cursor: pointer;">
                                                                🎬 Expand Video
                                                            </button>
                                                            <a href="{{ $videoUrl }}" target="_blank" rel="noopener" style="font-size: 10.5px; color: #6E5D57;">Open ↗</a>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <span style="font-size: 11.5px; color: #8A7B7E;">No picture or video attached</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-chip exception">
                                            <b></b>{{ $parcel->status === 'exception' ? 'Return / Refund' : ucwords(str_replace('_', ' ', $parcel->status)) }}
                                        </span>
                                        @if($resolution)
                                            <small style="display: block; margin-top: 4px; font-weight: 700; color: #1E6B37;">
                                                ✓ {{ $resolution }}
                                            </small>
                                            @if($resolvedBy)
                                                <small style="color: #8A7B7E;">by {{ $resolvedBy }}</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('exceptions.resolve-parcel', $parcel) }}" class="inline-action" style="flex-direction: column; align-items: stretch; gap: 6px; min-width: 180px;">
                                            @csrf
                                            <select name="action" aria-label="Return/Refund Resolution" style="font-size: 11.5px;">
                                                <option value="approve_return_refund" @selected($resolution === 'Return / Refund Approved')>✓ Approve Return / Refund</option>
                                                <option value="receive_returned_parcel" @selected($resolution === 'Returned Parcel Received at Hub')>📦 Receive Returned Parcel at Hub</option>
                                                <option value="out_for_delivery">🚚 Re-dispatch Out for Delivery</option>
                                                <option value="in_transit">↺ Resume Transit</option>
                                                <option value="cancelled">✕ Cancel / Close Order</option>
                                            </select>
                                            <input type="text" name="resolution_note" placeholder="Optional hub note..." value="{{ $meta['return_resolution_note'] ?? '' }}" style="height: 30px; font-size: 11px; padding: 0 8px; border-radius: 6px; border: 1px solid #EBDDD8;">
                                            <button class="secondary-button" type="submit" style="height: 30px; font-size: 11.5px; justify-content: center;">Update Return/Refund</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="empty-state">No Return / Refund parcels reported yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="table-footer">
                    <span>Showing <strong>{{ $returnParcels->firstItem() ?? 0 }}–{{ $returnParcels->lastItem() ?? 0 }}</strong> of <strong>{{ $returnParcels->total() }} return / refund parcels</strong></span>
                    <div class="orders-pagination">{{ $returnParcels->onEachSide(1)->links() }}</div>
                </div>
            </section>

            <footer class="page-footer">
                <span>© 2026 Lumora Logistics</span>
                <span>All rider Return / Refund reasons, pictures, and videos are logged to the parcel audit trail.</span>
            </footer>
        </div>
    </main>
</div>

<!-- Lightbox Modal for Picture & Video Inspection -->
<div id="returnMediaModal" style="display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(25, 12, 25, 0.82); backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 24px;" onclick="if(event.target === this) closeMediaModal()">
    <div style="background: #FFFDFB; border-radius: 18px; max-width: 780px; width: 100%; overflow: hidden; box-shadow: 0 24px 60px rgba(0,0,0,0.4); border: 1px solid #EBDDD8;">
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; border-bottom: 1px solid #EBDDD8; background: #FBF6F3;">
            <strong id="returnMediaTitle" style="color: #3D1B3D; font-size: 14px;">Return / Refund Evidence</strong>
            <button type="button" onclick="closeMediaModal()" style="background: none; border: 0; font-size: 20px; line-height: 1; color: #6E5D57; cursor: pointer;">✕</button>
        </div>
        <div id="returnMediaBody" style="padding: 20px; display: flex; align-items: center; justify-content: center; background: #181018; min-height: 280px; max-height: 75vh;">
        </div>
    </div>
</div>

<script>
function openMediaModal(type, url, title) {
    const modal = document.getElementById('returnMediaModal');
    const titleEl = document.getElementById('returnMediaTitle');
    const bodyEl = document.getElementById('returnMediaBody');
    if (!modal || !bodyEl) return;

    titleEl.textContent = title || 'Return / Refund Evidence';
    if (type === 'video') {
        bodyEl.innerHTML = '<video src="' + url + '" controls autoplay style="max-width: 100%; max-height: 68vh; border-radius: 10px;"></video>';
    } else {
        bodyEl.innerHTML = '<img src="' + url + '" alt="Return/Refund Evidence" style="max-width: 100%; max-height: 68vh; border-radius: 10px; object-fit: contain;">';
    }
    modal.style.display = 'flex';
}

function closeMediaModal() {
    const modal = document.getElementById('returnMediaModal');
    const bodyEl = document.getElementById('returnMediaBody');
    if (bodyEl) bodyEl.innerHTML = '';
    if (modal) modal.style.display = 'none';
}
</script>
</body>
</html>
