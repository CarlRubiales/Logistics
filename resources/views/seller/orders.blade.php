@extends('layouts.seller')

@section('title', 'Orders')

@section('content')
    @if (session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
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
    @endphp

    <div class="panel">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Buyer</th>
                    <th>Date</th>
                    <th>Subtotal</th>
                    <th>Voucher</th>
                    <th>Status</th>
                    <th>Packing</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($orders as $o)
                    @php
                        $status = $o->status ?? 'pending';
                        $statusLabel = $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status));
                        $statusColor = $statusColors[$status] ?? 'var(--terra-1)';
                    @endphp

                    <tr>
                        <td>
                            @if (!empty($o->order_number))
                                {{ $o->order_number }}
                            @else
                                #{{ $o->order_id }}
                            @endif

                            @if ($o->unseen_count > 0)
                                <span class="stock-badge" style="background:var(--coral);">
                                    New
                                </span>
                            @endif
                        </td>

                        <td>{{ $o->buyer_name }}</td>

                        <td>
                            {{ \Carbon\Carbon::parse($o->order_date)->format('M d, Y') }}
                        </td>

                        <td>
                            PHP {{ number_format($o->seller_subtotal, 2) }}
                        </td>

                        <td>
                            @if (!empty($o->voucher_code))
                                <span class="stock-badge" style="background:var(--sage-2);">{{ $o->voucher_code }}</span>
                                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">
                                    @if (($o->voucher_type ?? '') === 'shipping' || (float) $o->shipping_discount > 0)
                                        Free shipping (PHP {{ number_format((float) $o->shipping_discount, 2) }})
                                    @else
                                        Discount - PHP {{ number_format((float) $o->voucher_discount, 2) }}
                                    @endif
                                </div>
                            @else
                                <span style="color:#999;">None</span>
                            @endif
                        </td>

                        <td>
                            <span class="stock-badge" style="background:{{ $statusColor }};">
                                {{ $statusLabel }}
                            </span>
                        </td>

                        <td>
                            @if ($o->unpacked_count == 0)
                                <span class="stock-badge" style="background:var(--sage-2);">
                                    Items Packed
                                </span>
                            @else
                                <span class="stock-badge" style="background:var(--terra-1);">
                                    {{ $o->unpacked_count }} Unpacked
                                </span>
                            @endif
                        </td>

                        <td style="display:flex;gap:8px;align-items:center;justify-content:flex-end;">
                            <a href="{{ route('seller.orders.show', $o->order_id) }}"
                               class="edit-stock-btn"
                               style="text-decoration:none;display:inline-block;">
                                View
                            </a>

                            @if (!in_array($status, ['packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'returned'], true))
                                <form action="{{ route('seller.orders.pack', $o->order_id) }}"
                                      method="POST"
                                      style="display:inline;">
                                    @csrf

                                    <button type="submit"
                                            class="edit-stock-btn"
                                            onclick="return confirm('Mark this order as packed?')">
                                        Mark as Packed
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:#999;padding:20px;">
                            No orders yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection