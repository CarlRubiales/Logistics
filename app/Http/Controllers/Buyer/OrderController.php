<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class OrderController extends Controller
{
    private const CART_KEY = 'lumora_cart';
    private const SELECTED_KEY = 'lumora_cart_selected';
    private const BUY_NOW_KEY = 'lumora_buy_now';

    public function index(Request $request): View
    {
        $statuses = [
            'pending',
            'processing',
            'packed',
            'shipped',
            'out_for_delivery',
            'delivered',
            'cancelled',
            'returned',
        ];

        $sortOptions = [
            'latest',
            'oldest',
            'highest',
            'lowest',
        ];

        $activeStatus = $request->get('status', 'all');

        if (! in_array($activeStatus, array_merge(['all'], $statuses), true)) {
            $activeStatus = 'all';
        }

        $search = trim((string) $request->get('search', ''));
        $sort = $request->get('sort', 'latest');

        if (! in_array($sort, $sortOptions, true)) {
            $sort = 'latest';
        }

        $query = Order::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'items.product',
                'statusHistory' => function ($query) {
                    $query->latest();
                },
            ]);

        if ($activeStatus !== 'all') {
            $query->where('status', $activeStatus);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('items.product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        match ($sort) {
            'oldest' => $query->oldest(),
            'highest' => $query->orderByDesc('total'),
            'lowest' => $query->orderBy('total'),
            default => $query->latest(),
        };

        $orders = $query->paginate(10)->withQueryString();

        return view('buyer.orders.index', compact(
            'orders',
            'statuses',
            'activeStatus',
            'search',
            'sort'
        ));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $checkoutMode = $request->routeIs('buyer.buy-now.checkout') ? 'buy_now' : 'cart';
        $items = $checkoutMode === 'buy_now' ? $this->buyNowItems($request) : $this->selectedItems($request);
        $cartPaymentOption = $request->input('cart_payment_option', 'cod');

        if (! in_array($cartPaymentOption, ['cod', 'wallet'], true)) {
            $cartPaymentOption = 'cod';
        }

        $request->session()->put('cart_payment_option', $cartPaymentOption);

        if ($items->isEmpty()) {
            if ($checkoutMode === 'buy_now') {
                return redirect()
                    ->route('shop.index')
                    ->with('error', 'Choose an available product before checkout.');
            }

            return redirect()
                ->route('buyer.cart')
                ->with('error', 'Select at least one product before checkout.');
        }

        $activeVouchers = [];
        try {
            if (Schema::hasTable('vouchers')) {
                $rows = DB::table('vouchers')
                    ->where('is_active', true)
                    ->whereIn('type', ['percent', 'shipping'])
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                    })
                    ->get();
                foreach ($rows as $row) {
                    $codeKey = strtoupper(trim((string) $row->code));
                    $label = $row->type === 'percent'
                        ? (rtrim(rtrim(number_format((float) $row->value, 2), '0'), '.').'% OFF')
                        : ($row->type === 'shipping' ? 'Free Shipping' : ('₱'.number_format((float) $row->value, 2).' OFF'));
                    $activeVouchers[$codeKey] = [
                        'type' => $row->type,
                        'value' => (float) $row->value,
                        'min_spend' => (float) ($row->min_spend ?? 0),
                        'label' => $label,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // Ignore if table not migrated yet
        }

        return view('buyer.checkout', [
            'cartItems' => $items,
            'summary' => $this->summary($items),
            'buyer' => $request->user(),
            'cartPaymentOption' => $cartPaymentOption,
            'checkoutMode' => $checkoutMode,
            'activeVouchers' => $activeVouchers,
            'returnUrl' => $checkoutMode === 'buy_now'
                ? route('shop.product', ['id' => $items->first()['product_id']])
                : route('buyer.cart'),
            'paymentMethods' => $request->user()
                ->paymentMethods()
                ->whereIn('type', ['gcash', 'maya', 'bank_transfer'])
                ->orderByDesc('is_default')
                ->latest()
                ->get(),
        ]);
    }

    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = (int) $validated['quantity'];

        if ($quantity > (int) $product->stock) {
            return back()
                ->with('error', 'The requested quantity is not available.')
                ->withInput();
        }

        $request->session()->put(self::BUY_NOW_KEY, [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return redirect()->route('buyer.buy-now.checkout');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'checkout_mode' => ['nullable', 'string', 'in:cart,buy_now'],
            'payment_method' => ['required', 'string', 'in:cod,gcash,maya,bank_transfer'],
            'payment_method_id' => ['nullable', 'integer'],
            'voucher_code' => ['nullable', 'string', 'max:50'],
        ]);

        $selectedWallet = null;

        if ($validated['payment_method'] === 'cod' && ! empty($validated['payment_method_id'])) {
            return back()
                ->withErrors(['payment_method_id' => 'Cash on Delivery cannot use a saved wallet.'])
                ->withInput();
        }

        if (! empty($validated['payment_method_id'])) {
            $selectedWallet = $request->user()
                ->paymentMethods()
                ->whereIn('type', ['gcash', 'maya', 'bank_transfer'])
                ->find($validated['payment_method_id']);

            if (! $selectedWallet || $selectedWallet->type !== $validated['payment_method']) {
                return back()
                    ->withErrors(['payment_method_id' => 'Select a saved wallet that matches your payment method.'])
                    ->withInput();
            }
        }

        if ($validated['payment_method'] === 'cod') {
            $selectedWallet = null;
        } elseif (! $selectedWallet) {
            return back()
                ->withErrors(['payment_method_id' => 'Select a saved wallet for this payment method.'])
                ->withInput();
        }

        $checkoutMode = $validated['checkout_mode'] ?? 'cart';

        $order = DB::transaction(function () use ($request, $validated, $selectedWallet, $checkoutMode) {
            $lines = $checkoutMode === 'buy_now'
                ? $this->buyNowOrderLines($request)
                : $this->cartOrderLines($request);

            $shipping = 15;

            $subtotal = round($lines->sum(function (array $line) {
                return (float) $line['product']->price * $line['quantity'];
            }), 2);

            $discount = round($lines->sum(function (array $line) {
                return ((float) $line['product']->price - $line['unit_price']) * $line['quantity'];
            }), 2);

            $netItems = max(0, round($subtotal - $discount, 2));
            $voucherCode = strtoupper(trim((string) ($validated['voucher_code'] ?? '')));
            $voucherSavings = 0;
            $shippingDiscount = 0;
            $appliedVoucherCode = null;
            $appliedVoucherType = null;

            if ($voucherCode !== '') {
                $dbVoucher = null;
                try {
                    if (Schema::hasTable('vouchers')) {
                        $dbVoucher = DB::table('vouchers')
                            ->where('code', $voucherCode)
                            ->where('is_active', true)
                            ->whereIn('type', ['percent', 'shipping'])
                            ->where(function ($q) {
                                $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString());
                            })
                            ->first();
                    }
                } catch (\Throwable $e) {
                    $dbVoucher = null;
                }

                if ($dbVoucher) {
                    if ($netItems >= (float) ($dbVoucher->min_spend ?? 0)) {
                        $appliedVoucherCode = $voucherCode;
                        $appliedVoucherType = $dbVoucher->type;
                        if ($dbVoucher->type === 'percent') {
                            $voucherSavings = round($netItems * (((float) $dbVoucher->value) / 100), 2);
                        } else {
                            $shippingDiscount = $shipping;
                        }
                    }
                } elseif (in_array($voucherCode, ['LUMORA10', 'LUMORA20', 'FREESHIP'], true)) {
                    $appliedVoucherCode = $voucherCode;
                    if ($voucherCode === 'FREESHIP') {
                        $appliedVoucherType = 'shipping';
                        $shippingDiscount = $shipping;
                    } else {
                        $appliedVoucherType = 'percent';
                        $voucherSavings = round($netItems * ($voucherCode === 'LUMORA20' ? 0.20 : 0.10), 2);
                    }
                }
            }

            $discount = round($discount + $voucherSavings, 2);
            $total = max(0, round($subtotal + $shipping - $discount - $shippingDiscount, 2));

            $orderData = [
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $request->user()->id,
                'seller_id' => $lines->pluck('product.seller_id')->filter()->first(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_method_id' => $selectedWallet?->id,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'discount' => $discount,
                'total' => $total,
            ];

            if (Schema::hasColumn('orders', 'buyer_id')) {
                $orderData['buyer_id'] = $request->user()->id;
            }

            if (Schema::hasColumn('orders', 'voucher_code')) {
                $orderData['voucher_code'] = $appliedVoucherCode;
                $orderData['voucher_type'] = $appliedVoucherType;
                $orderData['voucher_discount'] = $voucherSavings;
                $orderData['shipping_discount'] = $shippingDiscount;
            }

            $order = Order::create($orderData);

            $order->statusHistory()->create([
                'status' => 'pending',
                'remarks' => 'Order Placed',
                'changed_by' => null,
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'seller_id' => $product->seller_id,
                    'quantity' => $line['quantity'],
                    'price' => $line['unit_price'],
                    'subtotal' => $line['line_total'],
                ]);

                $product->decrement('stock', $line['quantity']);
                $product->increment('sales_count', $line['quantity']);
            }

            if ($checkoutMode === 'buy_now') {
                $request->session()->forget(self::BUY_NOW_KEY);
            } else {
                $selectedIds = $lines->pluck('product.id')->map(fn ($id) => (int) $id);
                $storedCart = $request->session()->get(self::CART_KEY, []);
                $remainingCart = collect($storedCart)
                    ->reject(fn ($item, $id) => $selectedIds->contains((int) $id))
                    ->all();

                $request->session()->put(self::CART_KEY, $remainingCart);
                $request->session()->put(self::SELECTED_KEY, []);
            }

            return $order;
        });

        return redirect()
            ->route('buyer.orders.show', $order)
            ->with('success', 'Your order has been placed successfully.');
    }

    public function show(Request $request, Order $order): View
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403);

        $order->load([
            'items.product',
            'statusHistory.changedBy',
        ]);

        return view('buyer.order-success', compact('order'));
    }

    private function selectedItems(Request $request)
    {
        $storedCart = $request->session()->get(self::CART_KEY, []);

        $selectedIds = collect($request->session()->get(self::SELECTED_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $selectedIds)
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        return $selectedIds->map(function (int $productId) use ($storedCart, $products) {
            $product = $products->get($productId);
            $quantity = (int) ($storedCart[(string) $productId]['quantity'] ?? 0);

            if (! $product || $quantity < 1) {
                return null;
            }

            $originalPrice = (float) $product->price;
            $discountPercent = (float) ($product->discount_percent ?? 0);
            $unitPrice = round($originalPrice * (1 - ($discountPercent / 100)), 2);

            return [
                'product' => $product,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'original_price' => $originalPrice,
                'unit_price' => $unitPrice,
                'line_discount' => round(($originalPrice - $unitPrice) * $quantity, 2),
                'line_total' => round($unitPrice * $quantity, 2),
            ];
        })->filter()->values();
    }

    private function buyNowItems(Request $request)
    {
        $payload = $request->session()->get(self::BUY_NOW_KEY);

        if (! is_array($payload)) {
            return collect();
        }

        $product = Product::query()
            ->where('status', 'active')
            ->find((int) ($payload['product_id'] ?? 0));

        $quantity = (int) ($payload['quantity'] ?? 0);

        if (! $product || $quantity < 1 || $quantity > (int) $product->stock) {
            return collect();
        }

        $originalPrice = (float) $product->price;
        $discountPercent = (float) ($product->discount_percent ?? 0);
        $unitPrice = round($originalPrice * (1 - ($discountPercent / 100)), 2);

        return collect([[
            'product' => $product,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'original_price' => $originalPrice,
            'unit_price' => $unitPrice,
            'line_discount' => round(($originalPrice - $unitPrice) * $quantity, 2),
            'line_total' => round($unitPrice * $quantity, 2),
        ]]);
    }

    private function cartOrderLines(Request $request)
    {
        $storedCart = $request->session()->get(self::CART_KEY, []);

        $selectedIds = collect($request->session()->get(self::SELECTED_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            abort(422, 'Select at least one product before checkout.');
        }

        $products = Product::query()
            ->whereIn('id', $selectedIds)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $lines = collect();

        foreach ($selectedIds as $productId) {
            $product = $products->get($productId);
            $quantity = (int) ($storedCart[(string) $productId]['quantity'] ?? 0);

            if (! $product || $quantity < 1) {
                abort(422, 'One of the selected products is no longer available.');
            }

            if ($quantity > (int) $product->stock) {
                abort(422, "Insufficient stock for {$product->name}.");
            }

            $lines->push($this->orderLine($product, $quantity));
        }

        return $lines;
    }

    private function buyNowOrderLines(Request $request)
    {
        $payload = $request->session()->get(self::BUY_NOW_KEY);

        if (! is_array($payload)) {
            abort(422, 'Choose an available product before checkout.');
        }

        $product = Product::query()
            ->where('status', 'active')
            ->whereKey((int) ($payload['product_id'] ?? 0))
            ->lockForUpdate()
            ->first();

        $quantity = (int) ($payload['quantity'] ?? 0);

        if (! $product || $quantity < 1) {
            abort(422, 'The selected product is no longer available.');
        }

        if ($quantity > (int) $product->stock) {
            abort(422, "Insufficient stock for {$product->name}.");
        }

        return collect([$this->orderLine($product, $quantity)]);
    }

    private function orderLine(Product $product, int $quantity): array
    {
        $originalPrice = (float) $product->price;
        $discountPercent = (float) ($product->discount_percent ?? 0);
        $unitPrice = round($originalPrice * (1 - ($discountPercent / 100)), 2);

        return [
            'product' => $product,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'line_total' => round($unitPrice * $quantity, 2),
        ];
    }

    private function summary($items): array
    {
        $shipping = $items->isEmpty() ? 0 : 15;

        $subtotal = round($items->sum(function (array $item) {
            return $item['original_price'] * $item['quantity'];
        }), 2);

        $discount = round($items->sum('line_discount'), 2);

        $total = round($items->sum('line_total') + $shipping, 2);

        return [
            'item_count' => (int) $items->sum('quantity'),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $total,
        ];
    }

    private function generateOrderNumber(): string
    {
        $next = ((int) Order::query()->max('id')) + 1;

        do {
            $orderNumber = 'LMR-' . now()->year . '-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
            $next++;
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
