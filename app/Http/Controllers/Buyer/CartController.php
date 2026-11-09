<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    private const CART_KEY = 'lumora_cart';
    private const SELECTED_KEY = 'lumora_cart_selected';

    public function index(Request $request): View
    {
        $cart = $this->hydrateCart($request);
        $selectedIds = collect($request->session()->get(self::SELECTED_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->values();

        $selectedItems = $cart->filter(fn (array $item) => $selectedIds->contains($item['product_id']));
        $summary = $this->summary($selectedItems);
        $cartPaymentOption = $request->session()->get('cart_payment_option', 'cod');

        if (! in_array($cartPaymentOption, ['cod', 'wallet'], true)) {
            $cartPaymentOption = 'cod';
        }

        $defaultWallet = $request->user()
            ?->paymentMethods()
            ->whereIn('type', ['gcash', 'maya', 'bank_transfer'])
            ->orderByDesc('is_default')
            ->latest()
            ->first();

        return view('buyer.cart', [
            'cartItems' => $cart,
            'selectedIds' => $selectedIds,
            'summary' => $summary,
            'cartPaymentOption' => $cartPaymentOption,
            'defaultWallet' => $defaultWallet,
        ]);
    }

    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->status === 'active', 404);

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $quantity = (int) ($validated['quantity'] ?? 1);
        abort_if($product->stock < $quantity, 422, 'The requested quantity is not available.');

        $cart = $request->session()->get(self::CART_KEY, []);
        $productId = (string) $product->id;
        $newQuantity = ((int) ($cart[$productId]['quantity'] ?? 0)) + $quantity;
        abort_if($newQuantity > (int) $product->stock, 422, 'You cannot add more than the available stock.');

        $cart[$productId] = ['quantity' => $newQuantity];
        $request->session()->put(self::CART_KEY, $cart);

        $selected = collect($request->session()->get(self::SELECTED_KEY, []));
        if (! $selected->contains((int) $product->id)) {
            $selected->push((int) $product->id);
            $request->session()->put(self::SELECTED_KEY, $selected->unique()->values()->all());
        }

        $count = collect($cart)->sum(fn (array $item) => (int) $item['quantity']);
        $message = "{$product->name} was added to your cart.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cart_count' => $count]);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $quantity = (int) $validated['quantity'];
        abort_if($product->status !== 'active' || $quantity > (int) $product->stock, 422, 'The requested quantity is not available.');

        $cart = $request->session()->get(self::CART_KEY, []);
        $cart[(string) $product->id] = ['quantity' => $quantity];
        $request->session()->put(self::CART_KEY, $cart);

        return $this->cartResponse($request, $product, 'Cart quantity updated.');
    }

    public function remove(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $cart = $request->session()->get(self::CART_KEY, []);
        unset($cart[(string) $product->id]);
        $request->session()->put(self::CART_KEY, $cart);

        $selected = collect($request->session()->get(self::SELECTED_KEY, []))
            ->reject(fn ($id) => (int) $id === (int) $product->id)
            ->values()
            ->all();
        $request->session()->put(self::SELECTED_KEY, $selected);

        return $this->cartResponse($request, $product, 'Product removed from your cart.');
    }

    public function select(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer', 'distinct'],
        ]);

        $cartIds = collect(array_keys($request->session()->get(self::CART_KEY, [])))->map(fn ($id) => (int) $id);
        $selectedIds = collect($validated['selected_ids'] ?? [])->map(fn ($id) => (int) $id)
            ->intersect($cartIds)->values()->all();
        $request->session()->put(self::SELECTED_KEY, $selectedIds);

        if ($request->expectsJson()) {
            return response()->json(['selected_ids' => $selectedIds, 'summary' => $this->summary($this->hydrateCart($request)->filter(fn ($item) => in_array($item['product_id'], $selectedIds, true))) ]);
        }

        return back();
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $cart = $this->hydrateCart($request);
        $selectedIds = collect($request->session()->get(self::SELECTED_KEY, []))->map(fn ($id) => (int) $id);
        $selectedItems = $cart->filter(fn (array $item) => $selectedIds->contains($item['product_id']));

        if ($selectedItems->isEmpty()) {
            return redirect()->route('buyer.cart')->with('error', 'Select at least one product before checkout.');
        }

        return view('buyer.checkout', [
            'cartItems' => $selectedItems,
            'summary' => $this->summary($selectedItems),
        ]);
    }

    private function hydrateCart(Request $request)
    {
        $stored = $request->session()->get(self::CART_KEY, []);
        if (empty($stored)) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', array_keys($stored))
            ->where('status', 'active')
            ->get()
            ->keyBy('id');

        $items = collect();
        foreach ($stored as $productId => $storedItem) {
            $product = $products->get((int) $productId);
            if (! $product) {
                continue;
            }

            $quantity = min((int) ($storedItem['quantity'] ?? 1), (int) $product->stock);
            if ($quantity < 1) {
                continue;
            }

            $originalPrice = (float) $product->price;
            $discountPercent = (float) ($product->discount_percent ?? 0);
            $unitPrice = round($originalPrice * (1 - ($discountPercent / 100)), 2);

            $items->push([
                'product_id' => (int) $product->id,
                'product' => $product,
                'quantity' => $quantity,
                'original_price' => $originalPrice,
                'discount_percent' => $discountPercent,
                'unit_price' => $unitPrice,
                'line_total' => round($unitPrice * $quantity, 2),
                'line_discount' => round(($originalPrice - $unitPrice) * $quantity, 2),
            ]);
        }

        return $items->values();
    }

    private function summary($items): array
    {
        return [
            'item_count' => (int) $items->sum('quantity'),
            'subtotal' => round($items->sum(fn (array $item) => $item['original_price'] * $item['quantity']), 2),
            'discount' => round($items->sum('line_discount'), 2),
            'shipping' => $items->isEmpty() ? 0 : 50,
            'total' => round($items->sum('line_total') + ($items->isEmpty() ? 0 : 50), 2),
        ];
    }

    private function cartResponse(Request $request, Product $product, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }
}
