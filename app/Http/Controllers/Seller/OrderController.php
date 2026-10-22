<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $sellerId = Auth::id();
        $hasVoucherCols = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'voucher_code');

        $orders = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where(function ($query) use ($sellerId) {
                $query->where('oi.seller_id', $sellerId)
                    ->orWhere('p.seller_id', $sellerId);
            })
            ->select(
                'o.id as order_id',
                'o.order_number',
                'o.status',
                'o.created_at as order_date',
                'u.name as buyer_name',
                DB::raw($hasVoucherCols ? 'o.voucher_code' : 'NULL as voucher_code'),
                DB::raw($hasVoucherCols ? 'o.voucher_type' : 'NULL as voucher_type'),
                DB::raw($hasVoucherCols ? 'o.voucher_discount' : '0 as voucher_discount'),
                DB::raw($hasVoucherCols ? 'o.shipping_discount' : '0 as shipping_discount'),
                DB::raw('SUM(oi.price * oi.quantity) as seller_subtotal'),
                DB::raw('SUM(CASE WHEN oi.seen_at IS NULL THEN 1 ELSE 0 END) as unseen_count'),
                DB::raw('SUM(CASE WHEN oi.packed_at IS NULL THEN 1 ELSE 0 END) as unpacked_count')
            )
            ->groupBy(...array_merge(
                ['o.id', 'o.order_number', 'o.status', 'o.created_at', 'u.name'],
                $hasVoucherCols ? ['o.voucher_code', 'o.voucher_type', 'o.voucher_discount', 'o.shipping_discount'] : []
            ))
            ->orderByDesc('o.created_at')
            ->get();

        return view('seller.orders', compact('orders'));
    }

    public function show($orderId)
    {
        $sellerId = Auth::id();
        $hasVoucherCols = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'voucher_code');

        $order = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->select(
                'o.id',
                'o.order_number',
                'o.created_at',
                'o.status',
                'u.name as buyer_name',
                'u.contact_number',
                'u.province',
                'u.municipality',
                'u.barangay',
                'u.street',
                'u.house_number',
                DB::raw($hasVoucherCols ? 'o.voucher_code' : 'NULL as voucher_code'),
                DB::raw($hasVoucherCols ? 'o.voucher_type' : 'NULL as voucher_type'),
                DB::raw($hasVoucherCols ? 'o.voucher_discount' : '0 as voucher_discount'),
                DB::raw($hasVoucherCols ? 'o.shipping_discount' : '0 as shipping_discount')
            )
            ->first();

        abort_unless($order, 404);

        $items = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('oi.order_id', $orderId)
            ->where(function ($query) use ($sellerId) {
                $query->where('oi.seller_id', $sellerId)
                    ->orWhere('p.seller_id', $sellerId);
            })
            ->select(
                'oi.id',
                'oi.order_id',
                'oi.product_id',
                'oi.seller_id',
                'oi.quantity',
                'oi.price',
                'oi.subtotal',
                'oi.seen_at',
                'oi.packed_at',
                'p.name as product_name'
            )
            ->get();

        abort_if($items->isEmpty(), 404);

        DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('oi.order_id', $orderId)
            ->where(function ($query) use ($sellerId) {
                $query->where('oi.seller_id', $sellerId)
                    ->orWhere('p.seller_id', $sellerId);
            })
            ->whereNull('oi.seen_at')
            ->update([
                'oi.seen_at' => now(),
                'oi.updated_at' => now(),
            ]);

        return view('seller.order-detail', compact('order', 'items'));
    }

    public function markPacked(int $id): RedirectResponse
    {
        $sellerId = Auth::id();

        $packed = DB::transaction(function () use ($id, $sellerId): bool {
            $order = Order::query()
                ->where('id', $id)
                ->whereHas('items', function ($query) use ($sellerId) {
                    $query->where('seller_id', $sellerId)
                        ->orWhereHas('product', function ($productQuery) use ($sellerId) {
                            $productQuery->where('seller_id', $sellerId);
                        });
                })
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($order->status, ['packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled', 'returned'], true)) {
                return false;
            }

            $now = now();

            DB::table('order_items as oi')
                ->join('products as p', 'p.id', '=', 'oi.product_id')
                ->where('oi.order_id', $order->id)
                ->where(function ($query) use ($sellerId) {
                    $query->where('oi.seller_id', $sellerId)
                        ->orWhere('p.seller_id', $sellerId);
                })
                ->update([
                    'oi.packed_at' => $now,
                    'oi.updated_at' => $now,
                ]);

            $order->forceFill([
                'status' => 'packed',
            ])->save();

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'packed',
                'remarks' => 'Order marked as packed by seller.',
                'changed_by' => $sellerId,
            ]);

            return true;
        });

        if (! $packed) {
            return back()->with('error', 'This order can no longer be marked as packed.');
        }

        return back()->with('success', 'Order marked as packed successfully.');
    }

    public function waybill($orderId)
    {
        $sellerId = Auth::id();

        $order = DB::table('orders as o')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('o.id', $orderId)
            ->select(
                'o.id',
                'o.order_number',
                'o.created_at',
                'u.name as buyer_name',
                'u.contact_number',
                'u.province',
                'u.municipality',
                'u.barangay',
                'u.street',
                'u.house_number'
            )
            ->firstOrFail();

        $items = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('oi.order_id', $orderId)
            ->where('p.seller_id', $sellerId)
            ->select('oi.*', 'p.name as product_name')
            ->get();

        abort_if($items->isEmpty(), 404);

        $sellerApp = DB::table('seller_applications')
            ->where('user_id', $sellerId)
            ->where('status', 'approved')
            ->first();

        $shopName = $sellerApp->business_name ?? Auth::user()->name;

        $total = $items->sum(fn ($item) => $item->price * $item->quantity);

        $pdf = Pdf::loadView('seller.waybill-pdf', compact('order', 'items', 'shopName', 'total'));

        return $pdf->download('waybill-order-' . $orderId . '.pdf');
    }

    public function notifications()
    {
        $sellerId = Auth::id();

        $count = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->where('p.seller_id', $sellerId)
            ->whereNull('oi.seen_at')
            ->distinct('oi.order_id')
            ->count('oi.order_id');

        $recent = DB::table('order_items as oi')
            ->join('products as p', 'p.id', '=', 'oi.product_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->join('users as u', 'u.id', '=', 'o.user_id')
            ->where('p.seller_id', $sellerId)
            ->whereNull('oi.seen_at')
            ->select(
                'o.id as order_id',
                'o.order_number',
                'o.created_at',
                'u.name as buyer_name',
                DB::raw('SUM(oi.price * oi.quantity) as subtotal')
            )
            ->groupBy('o.id', 'o.order_number', 'o.created_at', 'u.name')
            ->orderByDesc('o.created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'count' => $count,
            'orders' => $recent,
        ]);
    }
}
