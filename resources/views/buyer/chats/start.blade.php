@php
    $backUrl = $product
        ? route('shop.product', ['id' => $product->id])
        : route('shop.seller', ['seller' => $seller->id]);
    $cartCount = session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat with {{ $sellerProfile['name'] }} &middot; {{ config('app.name', 'Lumora') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--cream:#F7F1EC;--paper:#FFFDFC;--plum:#3B1E34;--rose:#C98F72;--line:#EAE3DD;--text:#2F2528;--muted:#8B7B78;--green:#6F8F78}
        *{box-sizing:border-box}
        body{margin:0;background:var(--cream);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif}
        a{color:inherit;text-decoration:none}
        button,textarea{font:inherit}
        .topbar{min-height:78px;display:flex;align-items:center;border-bottom:1px solid var(--line);background:rgba(255,253,252,.96)}
        .topbar-inner,.start-shell{width:min(1080px,calc(100% - 48px));margin:0 auto}
        .topbar-inner{display:flex;align-items:center;justify-content:space-between;gap:18px}
        .topbar-action svg{width:18px;height:18px;display:block}.topbar-action{position:relative;width:42px;height:42px;display:grid;place-items:center;border:1px solid var(--line);border-radius:999px;background:var(--paper);color:var(--plum)}
        .topbar-badge{position:absolute;top:-5px;right:-5px;min-width:18px;height:18px;display:grid;place-items:center;border-radius:999px;background:var(--rose);color:white;font-size:10px;font-weight:700}
        .start-shell{min-height:calc(100vh - 78px);display:grid;place-items:center;padding:42px 0}
        .modal-card{width:min(620px,100%);border:1px solid var(--line);border-radius:24px;background:var(--paper);box-shadow:0 24px 70px rgba(59,30,52,.14);overflow:hidden}
        .modal-head{padding:26px 28px 18px;border-bottom:1px solid var(--line)}
        .back-link{display:inline-flex;margin-bottom:14px;color:var(--rose);font-size:13px;font-weight:800}
        h1{margin:0;color:var(--plum);font:600 32px/1.1 'Playfair Display',Georgia,serif}
        .modal-body{display:grid;gap:18px;padding:24px 28px 28px}
        .seller-row{display:flex;align-items:center;gap:14px}
        .seller-avatar{width:58px;height:58px;display:grid;place-items:center;overflow:hidden;border:1px solid var(--line);border-radius:50%;background:#F8EEE9;color:var(--plum);font:700 21px 'Playfair Display',Georgia,serif}
        .seller-avatar img{width:100%;height:100%;object-fit:cover}
        .seller-name{display:flex;align-items:center;gap:8px;flex-wrap:wrap;color:var(--plum);font-weight:800}
        .verified{display:inline-flex;align-items:center;min-height:22px;padding:4px 8px;border-radius:999px;background:rgba(111,143,120,.14);color:var(--green);font-size:10px;font-weight:800;text-transform:uppercase}
        .product-card{display:grid;grid-template-columns:72px minmax(0,1fr) auto;align-items:center;gap:13px;border:1px solid var(--line);border-radius:16px;background:#F8F4F1;padding:11px}
        .product-card img{width:72px;height:72px;border-radius:12px;object-fit:cover;background:#F8EEE9}
        .product-card strong{display:block;color:var(--plum);font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .product-card span{display:block;margin-top:5px;color:var(--rose);font-size:13px;font-weight:800}
        .product-card a{color:var(--plum);font-size:12px;font-weight:800}
        textarea{width:100%;min-height:150px;resize:vertical;border:1px solid var(--line);border-radius:16px;background:var(--paper);padding:14px 15px;color:var(--text);outline:none}
        textarea:focus{border-color:var(--rose);box-shadow:0 0 0 3px rgba(201,143,114,.15)}
        .field-error{margin-top:8px;color:#a55252;font-size:13px}
        .start-button{width:100%;min-height:48px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:14px;background:var(--plum);color:white;font-size:14px;font-weight:800;cursor:pointer}
        @media(max-width:640px){.topbar-inner,.start-shell{width:calc(100% - 28px)}.modal-head,.modal-body{padding-left:20px;padding-right:20px}.product-card{grid-template-columns:58px minmax(0,1fr)}.product-card img{width:58px;height:58px}.product-card a{grid-column:1/-1}}
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
        <a class="topbar-action" href="{{ route('buyer.cart') }}" aria-label="View cart">
            <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
            @if ($cartCount > 0)<span class="topbar-badge">{{ $cartCount }}</span>@endif
        </a>
    </div>
</header>

<main class="start-shell">
    <section class="modal-card" role="dialog" aria-labelledby="startChatTitle">
        <div class="modal-head">
            <a class="back-link" href="{{ $backUrl }}">&larr; Back</a>
            <h1 id="startChatTitle">Chat with {{ $sellerProfile['name'] }}</h1>
        </div>
        <form class="modal-body" method="POST" action="{{ route('buyer.chats.begin') }}">
            @csrf
            <input type="hidden" name="seller_id" value="{{ $seller->id }}">
            @if ($product)
                <input type="hidden" name="product_id" value="{{ $product->id }}">
            @endif

            <div class="seller-row">
                <div class="seller-avatar" aria-hidden="true">
                    @if ($sellerProfile['avatar'])
                        <img src="{{ $sellerProfile['avatar'] }}" alt="">
                    @else
                        {{ $sellerProfile['initials'] }}
                    @endif
                </div>
                <div>
                    <div class="seller-name">{{ $sellerProfile['name'] }} @if ($sellerProfile['verified'])<span class="verified">Verified Seller</span>@endif</div>
                </div>
            </div>

            @if ($product)
                <div class="product-card">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                    <div>
                        <strong>{{ $product->name }}</strong>
                        <span>&#8369;{{ number_format((float) $product->price, 2) }}</span>
                    </div>
                    <a href="{{ route('shop.product', ['id' => $product->id]) }}">View Product &rarr;</a>
                </div>
            @endif

            <div>
                <textarea name="message" maxlength="2000" required placeholder="Type your message...">{{ old('message') }}</textarea>
                @error('message')<div class="field-error">{{ $message }}</div>@enderror
            </div>

            <button class="start-button" type="submit">Start Chat</button>
        </form>
    </section>
</main>
</body>
</html>
