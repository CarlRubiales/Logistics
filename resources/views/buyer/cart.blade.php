@php
    $cartProductIds = $cartItems->pluck('product_id')->map(fn ($id) => (int) $id)->all();
    $recommendedProducts = \App\Models\Product::query()
        ->where('status', 'active')
        ->when(! empty($cartProductIds), fn ($query) => $query->whereNotIn('id', $cartProductIds))
        ->orderByDesc('sales_count')
        ->orderByDesc('created_at')
        ->limit(8)
        ->get();
    $cartPaymentOption = in_array($cartPaymentOption ?? 'cod', ['cod', 'wallet'], true) ? $cartPaymentOption : 'cod';
    $walletLabels = [
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank Account',
    ];
    $maskWallet = function ($wallet): string {
        if (! $wallet?->account_identifier) {
            return 'Saved wallet';
        }

        $identifier = (string) $wallet->account_identifier;
        $digits = preg_replace('/\D+/', '', $identifier);
        $tail = substr($digits ?: $identifier, -4);

        if (in_array($wallet->type, ['gcash', 'maya'], true)) {
            return substr($digits ?: $identifier, 0, 4) . ' *** ' . $tail;
        }

        return '**** **** ' . $tail;
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumora | Shopping Cart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root{--background:#F7F1EC;--card:#FFFDFC;--primary:#3B1E34;--accent:#C98F72;--border:#EAE3DD;--text:#2F2528;--muted:#8B7B78;--success:#6F8F78;--hover:#F3EBE5;--shadow:0 10px 40px rgba(0,0,0,.04)}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--background);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif;font-size:16px;letter-spacing:0}
        h1,h2,h3{font-family:'Playfair Display',Georgia,serif;color:var(--primary);letter-spacing:0}
        a{text-decoration:none;color:inherit}
        button,input{font:inherit}
        button{cursor:pointer}
        .cart-topbar{position:sticky;top:0;z-index:30;background:rgba(255,253,252,.95);border-bottom:1px solid var(--border);backdrop-filter:blur(14px)}
        .cart-topbar-inner{max-width:1440px;margin:0 auto;padding:16px 40px;display:flex;align-items:center;gap:28px}
        .topbar-search{flex:1;max-width:520px;min-height:42px;display:flex;align-items:center;gap:10px;border:1px solid var(--border);border-radius:999px;background:var(--card);padding:0 17px;color:var(--muted);font-size:13px}
        .topbar-search svg{width:17px;height:17px}
        .topbar-links{margin-left:auto;display:flex;align-items:center;gap:18px;color:var(--primary);font-size:13px;font-weight:600}
        .topbar-link{transition:color .2s ease}
        .topbar-link:hover{color:var(--accent)}
        .cart-link{position:relative;display:inline-flex;align-items:center;gap:7px}
        .cart-count{position:absolute;top:-12px;right:-14px;min-width:18px;height:18px;display:grid;place-items:center;border-radius:999px;background:var(--accent);color:var(--card);font-size:10px;font-weight:700}
        .page{max-width:1440px;margin:0 auto;padding:64px 40px 0;animation:fadeIn .2s ease both}
        .breadcrumb{display:flex;align-items:center;gap:9px;color:var(--muted);font-size:13px}
        .breadcrumb a{color:var(--primary);transition:color .2s ease}
        .breadcrumb a:hover{color:var(--accent)}
        .page-head{max-width:760px;margin-top:24px}
        .page-head h1{margin:0;font-size:64px;font-weight:600;line-height:1}
        .page-head p{max-width:590px;margin:18px 0 0;color:var(--muted);font-size:18px;line-height:1.7}
        .alert{margin-top:24px;border:1px solid var(--border);border-radius:16px;background:var(--card);padding:14px 18px;color:var(--accent);font-size:13px}
        .cart-layout{display:grid;grid-template-columns:minmax(0,7fr) minmax(320px,3fr);gap:40px;align-items:start;margin-top:56px}
        .panel,.summary,.recommendations,.empty-panel{border:1px solid var(--border);border-radius:24px;background:var(--card);box-shadow:var(--shadow)}
        .panel{padding:32px;animation:fadeUp .2s ease both}
        .panel-head{display:flex;align-items:center;justify-content:space-between;gap:18px;padding-bottom:22px;border-bottom:1px solid var(--border)}
        .panel-title{margin:0;font-size:30px;font-weight:600;line-height:1.1}
        .cart-toolbar{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
        .select-all{display:inline-flex;align-items:center;gap:10px;color:var(--primary);font-size:13px;font-weight:600}
        .select-all input,.item-check{width:18px;height:18px;accent-color:var(--primary);cursor:pointer}
        .remove-selected{display:inline-flex;align-items:center;gap:7px;border:1px solid var(--border);border-radius:999px;background:transparent;padding:9px 13px;color:var(--muted);font-size:12px;font-weight:700;transition:background-color .2s ease,border-color .2s ease,color .2s ease,transform .2s ease}
        .remove-selected svg{width:15px;height:15px}
        .remove-selected:hover{border-color:var(--accent);background:var(--hover);color:var(--accent);transform:translateY(-1px)}
        .cart-list{display:grid}
        .cart-item{min-height:140px;display:grid;grid-template-columns:20px 96px minmax(0,1fr) auto auto auto;gap:24px;align-items:center;padding:24px 0;border-bottom:1px solid var(--border)}
        .cart-item:last-child{border-bottom:0;padding-bottom:0}
        .item-image{width:96px;height:96px;display:grid;place-items:center;overflow:hidden;border:1px solid var(--border);border-radius:18px;background:var(--background);color:var(--accent);font:600 22px 'Playfair Display',Georgia,serif;object-fit:cover}
        .item-copy{min-width:0}
        .seller{margin-bottom:7px;color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
        .item-name{margin:0;color:var(--primary);font-size:26px;font-weight:500;line-height:1.12}
        .variant{margin-top:8px;color:var(--muted);font-size:13px}
        .stock{display:inline-flex;align-items:center;gap:7px;margin-top:12px;color:var(--success);font-size:13px;font-weight:600}
        .stock::before{content:"";width:7px;height:7px;border-radius:999px;background:var(--success)}
        .item-price{min-width:118px;text-align:right}
        .item-price strong{display:block;color:var(--primary);font-size:21px;font-weight:700}
        .item-price del{display:block;margin-top:5px;color:var(--muted);font-size:12px}
        .sale{display:inline-flex;align-items:center;margin-top:8px;border:1px solid var(--accent);border-radius:999px;padding:4px 8px;color:var(--accent);font-size:10px;font-weight:700}
        .quantity{height:42px;display:inline-flex;align-items:center;overflow:hidden;border:1px solid var(--border);border-radius:999px;background:var(--card)}
        .quantity button{width:38px;height:40px;border:0;background:transparent;color:var(--primary);font-size:18px;line-height:1;transition:background-color .2s ease,color .2s ease}
        .quantity button:hover{background:var(--hover);color:var(--accent)}
        .quantity span{min-width:34px;text-align:center;color:var(--primary);font-size:13px;font-weight:700}
        .remove{width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--border);border-radius:999px;background:transparent;color:var(--muted);transition:background-color .2s ease,border-color .2s ease,color .2s ease,transform .2s ease}
        .remove svg{width:17px;height:17px}
        .remove:hover{border-color:var(--accent);background:var(--hover);color:var(--accent);transform:translateY(-1px)}
        .summary{position:sticky;top:120px;padding:32px;animation:fadeUp .2s ease both}
        .summary h2{margin:0 0 22px;padding-bottom:20px;border-bottom:1px solid var(--border);font-size:30px;font-weight:600}
        .selected-label{margin:0 0 20px;color:var(--accent);font-size:13px;font-weight:700}
        .summary-row{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:16px 0;color:var(--muted);font-size:14px}
        .summary-row strong{color:var(--primary);font-weight:700}
        .discount strong{color:var(--accent)}
        .promo-box{margin:24px 0;padding:16px;border:1px solid var(--border);border-radius:18px;background:var(--background)}
        .promo-box label{display:block;margin-bottom:10px;color:var(--primary);font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
        .promo-row{display:flex;gap:10px}
        .promo-row input{min-width:0;flex:1;height:44px;border:1px solid var(--border);border-radius:999px;background:var(--card);padding:0 15px;color:var(--text);outline:none}
        .promo-row input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(201,143,114,.15)}
        .promo-row button{height:44px;border:1px solid var(--accent);border-radius:999px;background:var(--card);padding:0 17px;color:var(--primary);font-size:13px;font-weight:700;transition:background-color .2s ease,transform .2s ease}
        .promo-row button:hover{background:var(--hover);transform:scale(1.02)}
        .total{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-top:24px;padding-top:24px;border-top:1px solid var(--border);color:var(--primary)}
        .total span{color:var(--muted);font-size:14px}
        .total strong{font-family:'Playfair Display',Georgia,serif;font-size:40px;font-weight:600;line-height:1}
        .payment-preview{margin-top:24px;padding:17px;border:1px solid var(--border);border-radius:18px;background:var(--background)}
        .payment-preview-head{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:12px}
        .payment-preview-title{margin:0;color:var(--primary);font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .wallet-link{flex:0 0 auto;color:var(--accent);font-size:12px;font-weight:800;text-decoration:underline;text-underline-offset:3px}
        .payment-preview-options{display:grid;gap:9px}
        .cart-payment-option{display:grid;grid-template-columns:auto minmax(0,1fr);gap:10px;align-items:center;min-height:44px;border:1px solid var(--border);border-radius:14px;background:var(--card);padding:11px 12px;color:var(--primary);font-size:13px;font-weight:700;cursor:pointer;transition:background-color .2s ease,border-color .2s ease,color .2s ease}
        .cart-payment-option input{width:18px;height:18px;accent-color:var(--primary)}
        .cart-payment-option:has(input:checked){border-color:var(--accent);background:#FFF4EE;color:var(--primary);box-shadow:inset 0 0 0 1px rgba(201,143,114,.18)}
        .wallet-preview{margin-top:12px;border:1px solid var(--border);border-radius:14px;background:var(--card);padding:13px}
        .wallet-preview[hidden]{display:none}
        .wallet-preview-label{display:block;color:var(--muted);font-size:11px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
        .wallet-preview strong{display:block;margin-top:6px;color:var(--primary);font-size:14px}
        .wallet-preview span{display:block;margin-top:4px;color:var(--muted);font-size:12px;line-height:1.45}
        .wallet-empty{margin:12px 0 0;color:var(--muted);font-size:12px;line-height:1.5}
        .wallet-empty a{color:var(--accent);font-weight:800;text-decoration:underline;text-underline-offset:3px}
        .payment-preview-note{margin:10px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
        .checkout,.continue{width:100%;min-height:58px;display:inline-flex;align-items:center;justify-content:center;gap:9px;border-radius:999px;padding:0 20px;font-size:14px;font-weight:700;transition:background-color .2s ease,border-color .2s ease,color .2s ease,transform .2s ease}
        .checkout{margin-top:28px;border:1px solid var(--primary);background:var(--primary);color:var(--card)}
        .checkout:hover:not(:disabled){background:#4E2A47;transform:scale(1.02)}
        .checkout:disabled{opacity:.45;cursor:not-allowed;transform:none}
        .continue{margin-top:12px;border:1px solid var(--border);background:var(--card);color:var(--primary)}
        .continue:hover{border-color:var(--accent);background:var(--hover);transform:scale(1.02)}
        .summary-note{margin:16px 0 0;color:var(--muted);font-size:12px;line-height:1.6}
        .assurances{display:grid;gap:10px;margin-top:24px;padding-top:22px;border-top:1px solid var(--border)}
        .assurance{display:flex;align-items:center;gap:10px;color:var(--muted);font-size:12px}
        .assurance svg{width:17px;height:17px;color:var(--accent)}
        .empty-panel{max-width:780px;margin:56px auto 0;padding:54px 34px;text-align:center;animation:fadeUp .2s ease both}
        .bag-icon{width:82px;height:82px;display:grid;place-items:center;margin:0 auto 22px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary)}
        .bag-icon svg{width:38px;height:38px}
        .empty-panel h2{margin:0;color:var(--primary);font-size:42px;font-weight:600}
        .empty-panel p{max-width:430px;margin:13px auto 26px;color:var(--muted);font-size:15px;line-height:1.7}
        .empty-panel .continue{max-width:240px;margin:0 auto;background:var(--primary);border-color:var(--primary);color:var(--card)}
        .recommendations{margin-top:56px;padding:32px;animation:fadeUp .2s ease both}
        .recommendations-head{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin-bottom:22px}
        .recommendations h2{margin:0;color:var(--primary);font-size:34px;font-weight:600}
        .recommendations-head a{color:var(--accent);font-size:13px;font-weight:700}
        .recommended-rail{display:grid;grid-auto-flow:column;grid-auto-columns:minmax(230px,1fr);gap:18px;overflow-x:auto;padding-bottom:6px;scroll-snap-type:x proximity}
        .product-card{position:relative;overflow:hidden;border:1px solid var(--border);border-radius:18px;background:var(--card);scroll-snap-align:start;transition:box-shadow .2s ease,transform .2s ease,border-color .2s ease}
        .product-card:hover{border-color:var(--accent);box-shadow:var(--shadow);transform:translateY(-4px)}
        .wish{position:absolute;top:12px;right:12px;z-index:2;width:34px;height:34px;border:1px solid var(--border);border-radius:999px;background:rgba(255,253,252,.92);color:var(--primary);font-size:18px;transition:transform .2s ease,color .2s ease,border-color .2s ease}
        .wish:hover{border-color:var(--accent);color:var(--accent);transform:rotate(-8deg) scale(1.04)}
        .product-card .sale{position:absolute;top:12px;left:12px;z-index:2;background:var(--card)}
        .product-image{height:230px;display:grid;place-items:center;overflow:hidden;background:var(--background)}
        .product-image img{width:100%;height:100%;object-fit:cover;transition:transform .2s ease}
        .product-card:hover .product-image img{transform:scale(1.04)}
        .product-image .fallback{color:var(--accent);font:600 32px 'Playfair Display',Georgia,serif}
        .product-info{padding:16px}
        .product-info h3{min-height:44px;margin:7px 0;color:var(--primary);font-size:19px;font-weight:500;line-height:1.15}
        .product-price{display:flex;align-items:baseline;gap:8px;margin-top:9px}
        .product-price strong{color:var(--primary);font-size:15px}
        .product-price del{color:var(--muted);font-size:12px}
        .product-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:15px}
        .product-actions form{display:flex;margin:0}
        .product-actions a,.product-actions button{min-height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary);font-size:12px;font-weight:700;transition:background-color .2s ease,border-color .2s ease,transform .2s ease}
        .product-actions button{width:100%;border-color:var(--accent)}
        .product-actions a:hover,.product-actions button:hover{background:var(--hover);border-color:var(--accent);transform:scale(1.02)}
        .site-footer{margin-top:64px;border-top:1px solid var(--border);background:var(--card)}
        .footer-inner{max-width:1440px;margin:0 auto;padding:38px 40px;display:flex;align-items:center;justify-content:space-between;gap:24px;color:var(--muted);font-size:12px}
        .footer-links{display:flex;align-items:center;gap:18px;flex-wrap:wrap}
        .footer-links a{transition:color .2s ease}
        .footer-links a:hover{color:var(--accent)}
        .remove-form{margin:0}
        @keyframes fadeIn{from{opacity:0}to{opacity:1}}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{animation:none!important;transition:none!important}}
        @media(max-width:1199px){.page{padding-top:54px}.cart-layout{grid-template-columns:1fr;gap:32px}.summary{position:static}.cart-item{grid-template-columns:20px 88px minmax(0,1fr) auto auto}.remove-wrap{grid-column:5}.item-price{grid-column:3;text-align:left}.recommended-rail{grid-auto-columns:minmax(240px,42%)}}
        @media(max-width:767px){body{font-size:15px}.cart-topbar-inner{padding:14px 20px;gap:16px}.topbar-search{display:none}.topbar-links{gap:12px}.topbar-links .topbar-link:first-child{display:none}.page{padding:40px 20px 0}.page-head h1{font-size:46px}.page-head p{font-size:16px}.cart-layout{margin-top:40px}.panel,.summary,.recommendations{padding:20px;border-radius:20px}.panel-head{align-items:flex-start;flex-direction:column}.cart-toolbar{width:100%;justify-content:space-between}.cart-item{min-height:0;grid-template-columns:18px 72px minmax(0,1fr);gap:14px;padding:20px 0}.item-image{width:72px;height:72px;border-radius:14px}.item-name{font-size:20px}.item-price{grid-column:2 / 4;text-align:left}.quantity{grid-column:2 / 3}.remove-wrap{grid-column:3 / 4;justify-self:end}.summary h2{font-size:26px}.promo-row{flex-direction:column}.promo-row button{width:100%}.total strong{font-size:34px}.checkout,.continue{min-height:52px}.empty-panel{margin-top:40px;padding:42px 22px}.empty-panel h2{font-size:34px}.recommendations{margin-top:40px}.recommendations-head{align-items:flex-start;flex-direction:column}.recommended-rail{grid-auto-columns:minmax(210px,82%)}.product-image{height:190px}.product-actions{grid-template-columns:1fr}.footer-inner{align-items:flex-start;flex-direction:column;padding:30px 20px}}
    </style>
</head>
<body>
    <header class="cart-topbar">
        <div class="cart-topbar-inner">
            <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
            <div class="topbar-search" role="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <span>Search for brands, products and more</span>
            </div>
            <nav class="topbar-links" aria-label="Cart navigation">
                <a class="topbar-link" href="{{ route('shop.index') }}">Continue shopping</a>
                <a class="topbar-link cart-link" href="{{ route('buyer.cart') }}" aria-current="page">
                    Cart
                    <span class="cart-count">{{ $cartItems->sum('quantity') }}</span>
                </a>
            </nav>
        </div>
    </header>

    <main class="page">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('shop.index') }}">Home</a>
            <span>/</span>
            <span>Cart</span>
        </nav>

        <section class="page-head" aria-labelledby="cart-heading">
            <h1 id="cart-heading">Shopping Cart</h1>
            <p>You're one step closer to your glow. Review your items and proceed to checkout.</p>
        </section>

        @if(session('success'))<div class="alert" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert" role="alert">{{ session('error') }}</div>@endif

        @if($cartItems->isEmpty())
            <section class="empty-panel" aria-labelledby="empty-cart-heading">
                <div class="bag-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 8h12l-1 13H7L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                </div>
                <h2 id="empty-cart-heading">Your cart is waiting</h2>
                <p>Discover curated beauty products made just for you.</p>
                <a class="continue" href="{{ route('shop.index') }}">Continue Shopping</a>
            </section>
        @else
            <div class="cart-layout">
                <section class="panel" aria-labelledby="cart-items-heading">
                    <div class="panel-head">
                        <h2 class="panel-title" id="cart-items-heading">Cart Items</h2>
                        <div class="cart-toolbar">
                            <label class="select-all">
                                <input type="checkbox" id="select-all" {{ $selectedIds->count() === $cartItems->count() ? 'checked' : '' }}>
                                Select All
                            </label>
                            <button type="button" class="remove-selected" id="remove-selected">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 15H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                Remove Selected
                            </button>
                        </div>
                    </div>

                    <div class="cart-list">
                        @foreach($cartItems as $item)
                            @php
                                $product = $item['product'];
                                $discountLabel = rtrim(rtrim(number_format($item['discount_percent'], 1), '0'), '.');
                            @endphp
                            <article class="cart-item" data-item data-product-id="{{ $item['product_id'] }}" data-price="{{ $item['unit_price'] }}" data-original-price="{{ $item['original_price'] }}" data-stock="{{ $product->stock }}">
                                <input class="item-check" type="checkbox" value="{{ $item['product_id'] }}" {{ $selectedIds->contains($item['product_id']) ? 'checked' : '' }} aria-label="Select {{ $product->name }}">

                                <img class="item-image" src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">

                                <div class="item-copy">
                                    <div class="seller">Lumora seller</div>
                                    <h3 class="item-name">{{ $product->name }}</h3>
                                    <div class="variant">{{ $product->category ?? 'Curated beauty essential' }}</div>
                                    <div class="stock">In Stock ({{ $product->stock }} available)</div>
                                </div>

                                <div class="item-price">
                                    <strong>&#8369;{{ number_format($item['unit_price'], 2) }}</strong>
                                    @if($item['discount_percent'] > 0)
                                        <del>&#8369;{{ number_format($item['original_price'], 2) }}</del>
                                        <span class="sale">{{ $discountLabel }}% OFF</span>
                                    @endif
                                </div>

                                <div class="quantity" aria-label="Quantity for {{ $product->name }}">
                                    <button type="button" data-minus aria-label="Decrease quantity">-</button>
                                    <span data-quantity>{{ $item['quantity'] }}</span>
                                    <button type="button" data-plus aria-label="Increase quantity">+</button>
                                </div>

                                <div class="remove-wrap">
                                    <form class="remove-form" method="POST" action="{{ route('buyer.cart.remove', $product) }}" data-remove-form>
                                        @csrf
                                        @method('DELETE')
                                        <button class="remove" type="submit" aria-label="Remove {{ $product->name }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 15H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <aside class="summary" aria-labelledby="summary-heading">
                    <h2 id="summary-heading">Order Summary</h2>
                    <p class="selected-label">Selected items (<span id="selected-count">{{ $summary['item_count'] }}</span>)</p>
                    <div class="summary-row"><span>Subtotal</span><strong id="subtotal">&#8369;{{ number_format($summary['subtotal'], 2) }}</strong></div>
                    <div class="summary-row"><span>Shipping</span><strong id="shipping">&#8369;{{ number_format($summary['shipping'], 2) }}</strong></div>
                    <div class="summary-row discount"><span>Discount</span><strong id="discount">- &#8369;{{ number_format($summary['discount'], 2) }}</strong></div>

                    <div class="promo-box">
                        <label for="promo-code">Promo Code</label>
                        <div class="promo-row">
                            <input id="promo-code" type="text" placeholder="Enter promo code" autocomplete="off">
                            <button type="button">Apply</button>
                        </div>
                    </div>

                    <div class="total">
                        <span>Total</span>
                        <strong id="total">&#8369;{{ number_format($summary['total'], 2) }}</strong>
                    </div>

                    <form id="selection-form" method="POST" action="{{ route('buyer.cart.select') }}">
                        @csrf
                        <div id="selection-inputs"></div>
                    </form>
                    <form method="GET" action="{{ route('buyer.cart.checkout') }}">
                        <section class="payment-preview" aria-labelledby="cart-payment-heading">
                            <div class="payment-preview-head">
                                <h3 class="payment-preview-title" id="cart-payment-heading">Payment Method</h3>
                                <a class="wallet-link" href="{{ route('buyer.wallet.index') }}">Manage Wallet</a>
                            </div>
                            <div class="payment-preview-options" aria-label="Available payment options">
                                <label class="cart-payment-option">
                                    <input type="radio" name="cart_payment_option" value="cod" @checked($cartPaymentOption === 'cod')>
                                    <span>Cash on Delivery</span>
                                </label>
                                <label class="cart-payment-option">
                                    <input type="radio" name="cart_payment_option" value="wallet" @checked($cartPaymentOption === 'wallet')>
                                    <span>Wallet</span>
                                </label>
                            </div>

                            <div class="wallet-preview" data-wallet-preview @if($cartPaymentOption !== 'wallet') hidden @endif>
                                @if($defaultWallet)
                                    <span class="wallet-preview-label">Saved Wallet</span>
                                    <strong>{{ $walletLabels[$defaultWallet->type] ?? 'Wallet' }}</strong>
                                    <span>{{ $defaultWallet->account_name ?: 'Lumora wallet' }}</span>
                                    <span>{{ $maskWallet($defaultWallet) }}</span>
                                @else
                                    <p class="wallet-empty">No saved wallet yet. <a href="{{ route('buyer.wallet.index') }}">Add Wallet</a></p>
                                @endif
                            </div>

                            <p class="payment-preview-note">Cash on Delivery or Wallet available at checkout.</p>
                        </section>
                        <button class="checkout" id="checkout" type="submit" {{ $summary['item_count'] ? '' : 'disabled' }}>Proceed to Checkout</button>
                    </form>
                    <a class="continue" href="{{ route('shop.index') }}">Continue Shopping</a>
                    <p class="summary-note">Only selected items will be included in checkout. Your cart will keep unchecked items for later.</p>

                    <div class="assurances" aria-label="Shopping assurances">
                        <div class="assurance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 7h11v10H3z"/><path d="M14 11h4l3 3v3h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg><span>Fast Delivery</span></div>
                        <div class="assurance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Secure Payments</span></div>
                        <div class="assurance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m8.5 12 2.2 2.2L16 9"/></svg><span>100% Authentic Products</span></div>
                    </div>
                </aside>
            </div>
        @endif

        @if($recommendedProducts->isNotEmpty())
            <section class="recommendations" aria-labelledby="recommended-heading">
                <div class="recommendations-head">
                    <h2 id="recommended-heading">You May Also Like</h2>
                    <a href="{{ route('shop.index') }}">View all</a>
                </div>
                <div class="recommended-rail">
                    @foreach($recommendedProducts as $product)
                        @php
                            $originalPrice = (float) ($product->price ?? 0);
                            $discountPercent = (float) ($product->discount_percent ?? 0);
                            $finalPrice = $discountPercent > 0 ? $originalPrice * (1 - $discountPercent / 100) : $originalPrice;
                            $discountLabel = rtrim(rtrim(number_format($discountPercent, 1), '0'), '.');
                        @endphp
                        <article class="product-card">
                            <button type="button" class="wish" aria-label="Add {{ $product->name }} to wishlist">&#9825;</button>
                            @if($discountPercent > 0)<span class="sale">{{ $discountLabel }}% OFF</span>@endif
                            <a class="product-image" href="{{ route('shop.product', ['id' => $product->id]) }}" aria-label="View {{ $product->name }}">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                            </a>
                            <div class="product-info">
                                <div class="seller">Lumora seller</div>
                                <h3>{{ $product->name }}</h3>
                                <div class="product-price">
                                    <strong>&#8369;{{ number_format($finalPrice, 2) }}</strong>
                                    @if($discountPercent > 0)<del>&#8369;{{ number_format($originalPrice, 2) }}</del>@endif
                                </div>
                                <div class="product-actions">
                                    <a href="{{ route('shop.product', ['id' => $product->id]) }}">View</a>
                                    <form method="POST" action="{{ route('buyer.cart.add', ['product' => $product->id]) }}">
                                        @csrf
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit">Add to Cart</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </main>

    <footer class="site-footer" aria-label="Lumora footer">
        <div class="footer-inner">
            <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
            <nav class="footer-links" aria-label="Footer navigation">
                <a href="{{ route('shop.index') }}">Shop</a>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('buyer.cart') }}">Cart</a>
            </nav>
            <p>&copy; {{ date('Y') }} Lumora. Beauty lives here.</p>
        </div>
    </footer>

    <script>
        (() => {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const rows = [...document.querySelectorAll('[data-item]')];
            const all = document.querySelector('#select-all');
            const removeSelected = document.querySelector('#remove-selected');
            const cartPaymentOptions = [...document.querySelectorAll('input[name="cart_payment_option"]')];
            const walletPreview = document.querySelector('[data-wallet-preview]');
            const money = (amount) => '&#8369;' + Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            function syncPaymentPreview() {
                const selectedPayment = cartPaymentOptions.find((option) => option.checked)?.value || 'cod';
                if (walletPreview) walletPreview.hidden = selectedPayment !== 'wallet';
            }

            function selected() {
                return rows.filter((row) => row.querySelector('.item-check').checked);
            }

            function sync() {
                if (!rows.length) return;

                const chosen = selected();
                const subtotal = chosen.reduce((sum, row) => sum + Number(row.dataset.originalPrice) * Number(row.querySelector('[data-quantity]').textContent), 0);
                const lineTotals = chosen.reduce((sum, row) => sum + Number(row.dataset.price) * Number(row.querySelector('[data-quantity]').textContent), 0);
                const discount = subtotal - lineTotals;
                const shipping = chosen.length ? 50 : 0;
                const selectedCount = document.querySelector('#selected-count');
                const subtotalEl = document.querySelector('#subtotal');
                const discountEl = document.querySelector('#discount');
                const shippingEl = document.querySelector('#shipping');
                const totalEl = document.querySelector('#total');
                const checkout = document.querySelector('#checkout');
                const inputs = document.querySelector('#selection-inputs');

                if (selectedCount) selectedCount.textContent = chosen.reduce((sum, row) => sum + Number(row.querySelector('[data-quantity]').textContent), 0);
                if (subtotalEl) subtotalEl.innerHTML = money(subtotal);
                if (discountEl) discountEl.innerHTML = '- ' + money(discount);
                if (shippingEl) shippingEl.innerHTML = money(shipping);
                if (totalEl) totalEl.innerHTML = money(lineTotals + shipping);
                if (checkout) checkout.disabled = !chosen.length;
                if (all) {
                    all.checked = chosen.length === rows.length;
                    all.indeterminate = chosen.length > 0 && chosen.length < rows.length;
                }
                if (removeSelected) removeSelected.disabled = !chosen.length;
                if (inputs) inputs.innerHTML = chosen.map((row) => `<input type="hidden" name="selected_ids[]" value="${row.dataset.productId}">`).join('');
            }

            async function saveSelection() {
                const body = new FormData();
                body.append('_token', csrf);
                selected().forEach((row) => body.append('selected_ids[]', row.dataset.productId));
                await fetch('{{ route('buyer.cart.select') }}', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body,
                });
            }

            async function change(row, delta) {
                const quantity = row.querySelector('[data-quantity]');
                const next = Number(quantity.textContent) + delta;
                if (next < 1 || next > Number(row.dataset.stock)) return;

                quantity.textContent = next;
                sync();

                const body = new FormData();
                body.append('_token', csrf);
                body.append('_method', 'PATCH');
                body.append('quantity', next);

                await fetch(`{{ url('/cart') }}/${row.dataset.productId}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body,
                });
            }

            rows.forEach((row) => {
                row.querySelector('.item-check').addEventListener('change', () => {
                    sync();
                    saveSelection();
                });
                row.querySelector('[data-minus]').addEventListener('click', () => change(row, -1));
                row.querySelector('[data-plus]').addEventListener('click', () => change(row, 1));
            });

            all?.addEventListener('change', () => {
                rows.forEach((row) => {
                    row.querySelector('.item-check').checked = all.checked;
                });
                sync();
                saveSelection();
            });

            removeSelected?.addEventListener('click', async () => {
                const chosen = selected();
                if (!chosen.length) return;

                removeSelected.disabled = true;
                for (const row of chosen) {
                    const form = row.querySelector('[data-remove-form]');
                    const body = new FormData(form);
                    await fetch(form.action, {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                        body,
                    });
                }
                window.location.reload();
            });

            cartPaymentOptions.forEach((option) => {
                option.addEventListener('change', syncPaymentPreview);
            });

            sync();
            syncPaymentPreview();
        })();
    </script>
</body>
</html>
