@extends('layouts.seller')

@section('title', 'Order ' . ($order->order_number ?? '#' . $order->id))

@section('content')
    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert-error" style="background:#ffe6e6;color:#8a1f1f;padding:12px 16px;border-radius:8px;margin-bottom:16px;">
            {{ session('error') }}
        </div>
    @endif

    @php
        $statusLabels = [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'packed' => 'Packed',
            'shipped' => 'Shipped',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
        ];

        $statusColors = [
            'pending' => 'var(--terra-1)',
            'processing' => 'var(--terra-1)',
            'packed' => 'var(--sage-2)',
            'shipped' => 'var(--sage-2)',
            'out_for_delivery' => 'var(--sage-2)',
            'delivered' => 'var(--sage-2)',
            'cancelled' => 'var(--coral)',
            'returned' => '#999',
        ];

        $status = $order->status ?? 'pending';
        $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
        $statusColor = $statusColors[$status] ?? 'var(--terra-1)';

        $allPacked = $items->every(fn ($item) => $item->packed_at !== null);

        $canMarkPacked = ! in_array($status, [
            'packed',
            'shipped',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'returned',
        ], true);
    @endphp

    <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;">
            <div>
                <h2 style="margin-bottom:2px;">
                    Order {{ $order->order_number ?? '#' . $order->id }}
                </h2>

                <div style="font-size:13px;color:var(--text-muted);">
                    {{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y g:i A') }}
                </div>
            </div>

            <div style="display:flex;gap:8px;align-items:center;">
                <span class="stock-badge" style="background:{{ $statusColor }};">
                    {{ $statusLabel }}
                </span>

                @if ($allPacked && $status !== 'packed')
                    <span class="stock-badge" style="background:var(--sage-2);">
                        Items Packed
                    </span>
                @endif
            </div>
        </div>

        <table style="margin-bottom:20px;">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Packing</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td>{{ $item->product_name }}</td>
                        <td>x{{ $item->quantity }}</td>
                        <td>PHP {{ number_format($item->price * $item->quantity, 2) }}</td>
                        <td>
                            @if ($item->packed_at)
                                <span class="stock-badge" style="background:var(--sage-2);">Packed</span>
                            @else
                                <span class="stock-badge" style="background:var(--terra-1);">Pending</span>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td colspan="2" style="font-weight:700;border-bottom:none;">Total</td>
                    <td colspan="2" style="font-weight:700;border-bottom:none;">
                        PHP {{ number_format($items->sum(fn ($item) => $item->price * $item->quantity), 2) }}
                    </td>
                </tr>

                @if (!empty($order->voucher_code))
                    <tr>
                        <td colspan="2" style="border-bottom:none;color:var(--text-muted);">Voucher used by buyer</td>
                        <td colspan="2" style="border-bottom:none;">
                            <span class="stock-badge" style="background:var(--sage-2);">{{ $order->voucher_code }}</span>
                        </td>
                    </tr>
                    @if ((float) $order->voucher_discount > 0)
                        <tr>
                            <td colspan="2" style="border-bottom:none;color:var(--text-muted);">Voucher discount</td>
                            <td colspan="2" style="border-bottom:none;color:var(--coral);font-weight:600;">- PHP {{ number_format((float) $order->voucher_discount, 2) }}</td>
                        </tr>
                    @endif
                    @if ((float) $order->shipping_discount > 0)
                        <tr>
                            <td colspan="2" style="border-bottom:none;color:var(--text-muted);">Free shipping</td>
                            <td colspan="2" style="border-bottom:none;color:var(--sage-2);font-weight:600;">Shipping waived (PHP {{ number_format((float) $order->shipping_discount, 2) }})</td>
                        </tr>
                    @endif
                @endif
            </tbody>
        </table>

        <div style="border-top:1px solid var(--border);padding-top:14px;margin-bottom:20px;font-size:13.5px;color:var(--text-muted);line-height:1.7;">
            <div>
                {{ $order->buyer_name }} &middot; {{ $order->contact_number ?? '—' }}
            </div>

            <div>
                {{ collect([
                    $order->house_number,
                    $order->street,
                    $order->barangay,
                    $order->municipality,
                    $order->province,
                ])->filter()->implode(', ') ?: 'No address on file' }}
            </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            @if ($canMarkPacked)
                <form action="{{ route('seller.orders.pack', $order->id) }}" method="POST">
                    @csrf

                    <button type="submit"
                            class="btn-solid"
                            onclick="return confirm('Mark this order as packed?')">
                        Mark as Packed
                    </button>
                </form>
            @endif

            <a href="{{ route('seller.orders.waybill', $order->id) }}"
               class="btn-outline"
               style="text-decoration:none;display:inline-block;">
                Print waybill
            </a>
        </div>
    </div>
@endsection