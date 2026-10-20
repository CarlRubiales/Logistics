<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $product->name }} &middot; {{ config('app.name', 'Lumora') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --cream:#F7F1EC; --paper:#FFFDFC; --line:#EAE3DD; --plum:#3B1E34; --rose:#C98F72; --text:#2F2528; --muted:#8B7B78; --green:#6F8F78; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--cream); color:var(--text); font-family:'Inter',ui-sans-serif,system-ui,sans-serif; }
        a { color:inherit; text-decoration:none; }
        .product-page { min-height:100vh; }
        .topbar { min-height:78px; display:flex; align-items:center; background:rgba(255,253,251,.98); border-bottom:1px solid var(--line); }
        .topbar-inner { width:min(1380px,calc(100% - 48px)); margin:auto; display:flex; align-items:center; gap:20px; }
        .search { flex:1; height:44px; padding:0 19px; border:1px solid var(--line); border-radius:25px; background:white; font:inherit; color:var(--plum); }
        .top-action { position:relative; }
    .cart-badge { position:absolute; top:-5px; right:-5px; min-width:18px; height:18px; padding:0 5px; display:flex; align-items:center; justify-content:center; border-radius:999px; background:#C0583A; color:#fff; border:2px solid #fff; box-sizing:content-box; font-size:10px; font-weight:700; line-height:1; }
    .cart-badge.is-empty { display:none; }
    .top-action { width:42px; height:42px; display:grid; place-items:center; border:1px solid var(--line); border-radius:50%; background:white; color:var(--plum); }
        .top-action svg { width:20px; height:20px; }
        .product-shell { width:min(1380px,calc(100% - 48px)); margin:0 auto; padding:22px 0 70px; }
        .flash { margin:0 0 18px; padding:12px 15px; border:1px solid var(--line); border-radius:8px; background:var(--paper); color:var(--rose); font-size:13px; }
        .flash.success { color:#6F8F78; }
        .breadcrumb { margin-bottom:25px; color:var(--muted); font-size:13px; }
        .breadcrumb a:hover { color:var(--rose); }
        .breadcrumb strong { color:var(--plum); }
        .product-detail { display:grid; grid-template-columns:minmax(0,1.18fr) minmax(330px,.92fr) minmax(220px,.55fr); gap:42px; align-items:start; }
        .gallery { display:grid; grid-template-columns:86px minmax(0,1fr); gap:14px; }
        .thumbs { display:flex; flex-direction:column; gap:12px; }
        .thumb { width:86px; height:86px; display:grid; place-items:center; overflow:hidden; border:1px solid var(--line); border-radius:10px; background:#f6eee9; }
        .thumb.active { border:2px solid var(--plum); box-shadow:0 0 0 3px #f2dcd4; }
        .thumb img { width:100%; height:100%; object-fit:cover; }
        .thumb-placeholder { color:#cbbcb8; font-size:23px; }
        .main-photo { position:relative; min-height:560px; display:grid; place-items:center; overflow:hidden; border-radius:16px; background:linear-gradient(135deg,#f1ddd5,#e5c4ba); }
        .main-photo img { width:100%; height:100%; min-height:560px; object-fit:cover; }
        .photo-placeholder { color:rgba(61,27,61,.42); font-family:Georgia,serif; font-size:52px; }
        .zoom { position:absolute; right:16px; bottom:16px; width:38px; height:38px; display:grid; place-items:center; border:0; border-radius:50%; background:rgba(255,253,251,.85); color:var(--plum); }
        .zoom svg { width:19px; height:19px; }
        .eyebrow { color:var(--rose); font-size:11px; font-weight:700; letter-spacing:.15em; text-transform:uppercase; }
        h1 { margin:9px 0 12px; font-family:Georgia,serif; font-size:43px; font-weight:500; line-height:1.05; }
        .rating { margin:0 0 22px; color:var(--plum); font-size:13px; }
        .rating-link { display:inline-flex; align-items:center; gap:8px; color:inherit; }
        .stars { letter-spacing:1px; white-space:nowrap; }
        .stars .filled { color:#C98F72; }
        .stars .empty { color:#EAE3DD; }
        .sold-by { display:flex; align-items:center; gap:9px; flex-wrap:wrap; margin:-3px 0 15px; color:var(--muted); font-size:13px; }
        .sold-by strong { color:var(--plum); }
        .sold-by a { color:var(--rose); font-weight:800; }
        .seller-badge { display:inline-flex; align-items:center; gap:5px; min-height:22px; padding:0 8px; border-radius:999px; background:rgba(102,148,94,.13); color:var(--green); font-size:10px; font-weight:900; text-transform:uppercase; }
        .purchase-row form { flex:1; display:flex; margin:0; }
        .purchase-row .button.primary { width:100%; }
        .muted { color:var(--muted); }
        .price-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin:18px 0 21px; }
        .price { color:var(--rose); font-size:27px; font-weight:700; }
        .old-price { color:#a99b9f; font-size:14px; text-decoration:line-through; }
        .sale-badge { padding:5px 9px; border-radius:999px; background:#b85c3b; color:white; font-size:10px; font-weight:800; }
        .description { padding:18px 0; border-top:1px solid var(--line); color:var(--muted); line-height:1.7; white-space:pre-line; }
        .stock { margin:17px 0 19px; color:var(--green); font-size:13px; font-weight:700; }
        .stock.out { color:#b85c3b; }
        .quantity-label { display:block; margin-bottom:8px; color:var(--plum); font-size:12px; font-weight:600; }
        .purchase-row { display:flex; gap:14px; align-items:center; }
        .quantity { height:48px; display:flex; align-items:center; border:1px solid var(--line); border-radius:9px; background:white; }
        .quantity button { width:40px; height:100%; border:0; background:transparent; color:var(--plum); font-size:20px; cursor:pointer; }
        .quantity output { min-width:38px; text-align:center; font-size:13px; }
        .button { min-height:46px; padding:0 22px; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid var(--plum); border-radius:8px; background:white; color:var(--plum); font:inherit; font-weight:700; cursor:pointer; }
        .button.primary { flex:1; background:var(--green); border-color:var(--green); color:white; }
        .button.primary:hover { background:#567f50; }
        .button.added { background:#4f8050; border-color:#4f8050; }
        .wishlist { width:100%; margin-top:12px; }
        .service-stack { display:flex; flex-direction:column; gap:14px; }
        .service-card { display:grid; grid-template-columns:34px 1fr; gap:12px; padding:22px 18px; border-radius:10px; background:#fff3ed; }
        .service-card svg { width:28px; height:28px; color:var(--rose); }
        .service-card strong { display:block; margin-bottom:5px; font-size:13px; }
        .service-card span { color:var(--muted); font-size:11px; line-height:1.45; }
        .info-panel { margin-top:42px; border:1px solid var(--line); border-radius:14px; background:var(--paper); overflow:hidden; }
        .tabs { display:flex; gap:35px; padding:0 22px; border-bottom:1px solid var(--line); }
        .tab { padding:18px 0 14px; border:0; border-bottom:2px solid transparent; background:transparent; color:var(--muted); font:inherit; font-size:12px; cursor:pointer; }
        .tab.active { border-bottom-color:var(--rose); color:var(--plum); font-weight:700; }
        .tab-panel { display:none; min-height:130px; padding:22px; color:var(--muted); font-size:13px; line-height:1.7; white-space:pre-line; }
        .tab-panel.active { display:block; }
        .shop-profile { position:relative; margin:26px 22px 28px; padding:32px; overflow:hidden; border:1px solid #EAE3DD; border-radius:24px; background:#FFFDFC; box-shadow:0 8px 26px rgba(59,30,52,.045); }
        .shop-profile-title { position:relative; z-index:1; margin:0 0 22px; color:#3B1E34; font-family:'Playfair Display',Georgia,serif; font-size:26px; font-weight:600; line-height:1.15; }
        .shop-profile-top { position:relative; z-index:1; display:grid; grid-template-columns:minmax(0,1.35fr) minmax(420px,.85fr); gap:40px; align-items:center; }
        .shop-identity { min-width:0; }
        .shop-identity-row { display:flex; align-items:center; gap:20px; min-width:0; }
        .shop-avatar { width:92px; height:92px; display:grid; place-items:center; flex:0 0 92px; overflow:hidden; border:1px solid #EAE3DD; border-radius:50%; background:#F8EEE9; color:#3B1E34; font-family:'Playfair Display',Georgia,serif; font-size:31px; font-weight:600; box-shadow:0 8px 22px rgba(59,30,52,.05); }
        .shop-avatar img { width:100%; height:100%; object-fit:cover; }
        .shop-copy { min-width:0; }
        .shop-name-line { display:flex; align-items:center; gap:10px; flex-wrap:wrap; min-width:0; }
        .shop-name { color:#3B1E34; font-family:'Playfair Display',Georgia,serif; font-size:30px; font-weight:600; line-height:1.1; overflow-wrap:anywhere; }
        .shop-description { max-width:520px; margin:9px 0 0; color:#8B7B78; font-size:14px; line-height:1.6; }
        .shop-profile .seller-badge { display:inline-flex; align-items:center; gap:5px; min-height:24px; padding:5px 9px; border-radius:999px; background:rgba(111,143,120,.14); color:#6F8F78; font-size:11px; font-weight:600; line-height:1; text-transform:uppercase; }
        .shop-profile .seller-badge svg { width:13px; height:13px; }
        .shop-meta { display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-top:12px; color:#8B7B78; font-size:13px; font-weight:600; }
        .shop-meta-item { display:inline-flex; align-items:center; gap:7px; }
        .shop-meta-item svg { width:16px; height:16px; color:#C98F72; }
        .shop-meta-divider { width:1px; height:15px; background:#EAE3DD; }
        .shop-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
        .shop-stat { min-height:92px; display:flex; align-items:center; gap:14px; padding:18px 20px; border:1px solid #EAE3DD; border-radius:16px; background:#FFFDFC; }
        .shop-stat-icon { width:42px; height:42px; display:flex; align-items:center; justify-content:center; flex:0 0 42px; margin:0; border-radius:9999px; background:#F7EEE8; color:#B98973; }
        .shop-stat-icon svg { width:18px; height:18px; display:block; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .shop-stat-text { min-width:0; }
        .shop-stat strong { display:block; color:#3B1E34; font-family:'Playfair Display',Georgia,serif; font-size:20px; font-weight:600; line-height:1.1; }
        .shop-stat-label { display:block; margin-top:5px; color:#8A7A76; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
        .shop-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }
        .shop-actions form { margin:0; display:inline-flex; }
        .shop-action { height:46px; padding:0 24px; display:inline-flex; align-items:center; justify-content:center; gap:8px; border:1px solid #3B1E34; border-radius:12px; background:#3B1E34; color:white; font-size:12px; font-weight:700; cursor:pointer; }
        .shop-action svg { width:16px; height:16px; }
        .shop-action.secondary { padding:0 22px; border-color:#C98F72; background:#FFFDFC; color:#3B1E34; }
        .shop-action[disabled] { border-color:#EAE3DD; background:#F8F4F1; color:#8B7B78; cursor:not-allowed; opacity:1; }
        .shop-divider { position:relative; z-index:1; margin:28px 0 26px; border:0; border-top:1px solid #EAE3DD; }
        .shop-about, .shop-products { position:relative; z-index:1; }
        .shop-about { padding-right:210px; }
        .shop-about h3, .shop-products h3 { margin:0; color:#3B1E34; font-family:'Playfair Display',Georgia,serif; font-size:26px; font-weight:600; line-height:1.18; }
        .shop-about p { max-width:760px; margin:12px 0 0; color:#6F6260; font-size:14px; line-height:1.7; }
        .shop-trust { display:flex; align-items:center; gap:40px; flex-wrap:wrap; max-width:760px; margin-top:22px; }
        .shop-trust-item { display:flex; align-items:center; gap:12px; min-width:0; }
        .shop-trust-icon { width:42px; height:42px; display:grid; place-items:center; flex:0 0 42px; border-radius:50%; background:#F8EEE9; color:#C98F72; }
        .shop-trust-icon svg { width:19px; height:19px; }
        .shop-trust-copy { display:block; min-width:0; }
        .shop-trust-copy strong { display:block; color:#3B1E34; font-size:13px; font-weight:600; line-height:1.2; }
        .shop-trust-copy span { display:block; margin-top:3px; color:#8B7B78; font-size:12px; line-height:1.35; }
        .shop-botanical { position:absolute; right:20px; bottom:20px; width:180px; height:190px; color:#C98F72; opacity:.12; pointer-events:none; }
        .shop-botanical svg { width:100%; height:100%; }
        .shop-products { margin-top:28px; padding-top:26px; border-top:1px solid #EAE3DD; }
        .shop-products-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; }
        .shop-view-all { color:#C98F72; font-size:13px; font-weight:600; white-space:nowrap; }
        .shop-product-grid { display:grid; grid-template-columns:repeat(4,250px); justify-content:start; gap:16px; overflow-x:auto; padding-bottom:2px; }
        .shop-product-card { width:250px; min-width:0; overflow:hidden; border:1px solid #EAE3DD; border-radius:16px; background:#FFFDFC; transition:transform .18s ease, box-shadow .18s ease; }
        .shop-product-card:hover { transform:translateY(-2px); box-shadow:0 12px 28px rgba(59,30,52,.07); }
        .shop-product-image { width:100%; height:140px; display:grid; place-items:center; overflow:hidden; background:#F8EEE9; }
        .shop-product-image img { width:100%; height:100%; object-fit:cover; }
        .shop-product-body { position:relative; padding:14px 58px 14px 14px; }
        .shop-product-name { min-height:35px; color:#3B1E34; font-size:14px; font-weight:600; line-height:1.25; }
        .shop-product-rating { margin-top:7px; color:#8B7B78; font-size:11px; }
        .shop-product-foot { margin-top:9px; }
        .shop-product-price { color:#C98F72; font-size:15px; font-weight:700; }
        .shop-product-button { position:absolute; right:14px; bottom:14px; width:36px; height:36px; display:grid; place-items:center; border:1px solid #C98F72; border-radius:50%; background:#FFFDFC; color:#3B1E34; }
        .shop-product-button svg { width:15px; height:15px; }
        .shop-empty { margin:0; color:#8B7B78; font-size:13px; }
        #reviews.tab-panel { white-space:normal; }
        .reviews-summary { display:grid; grid-template-columns:minmax(180px,.4fr) minmax(260px,.6fr); gap:24px; margin-bottom:24px; padding-bottom:22px; border-bottom:1px solid var(--line); }
        .reviews-score { color:var(--plum); font-family:Georgia,serif; font-size:46px; line-height:1; }
        .reviews-based { margin-top:8px; color:var(--muted); }
        .rating-breakdown { display:grid; gap:8px; }
        .rating-bar { display:grid; grid-template-columns:58px 1fr 34px; gap:10px; align-items:center; color:var(--muted); font-size:12px; }
        .rating-track { height:8px; overflow:hidden; border-radius:999px; background:#EAE3DD; }
        .rating-fill { height:100%; border-radius:999px; background:#C98F72; }
        .review-list { display:grid; gap:16px; margin-top:22px; }
        .review-card { padding:16px; border:1px solid var(--line); border-radius:10px; background:#FFFDFC; }
        .review-head { display:flex; justify-content:space-between; gap:14px; margin-bottom:8px; }
        .review-name { color:var(--plum); font-weight:700; }
        .review-date { color:var(--muted); font-size:12px; }
        .verified-badge { display:inline-flex; margin-top:6px; padding:3px 8px; border-radius:999px; background:rgba(111,143,120,.13); color:#6F8F78; font-size:10px; font-weight:800; text-transform:uppercase; }
        .review-text { margin:10px 0 0; color:var(--muted); line-height:1.65; }
        .review-form { margin-top:24px; padding:18px; border:1px solid var(--line); border-radius:10px; background:#FFFDFC; }
        .review-form h3 { margin:0 0 14px; font-family:Georgia,serif; color:var(--plum); font-size:22px; font-weight:500; }
        .star-input { display:inline-flex; flex-direction:row-reverse; gap:4px; margin-bottom:14px; }
        .star-input input { position:absolute; opacity:0; pointer-events:none; }
        .star-input label { color:#EAE3DD; font-size:28px; line-height:1; cursor:pointer; transition:color .2s ease; }
        .star-input input:checked ~ label, .star-input label:hover, .star-input label:hover ~ label { color:#C98F72; }
        .review-textarea { width:100%; min-height:110px; padding:13px; border:1px solid var(--line); border-radius:8px; background:white; color:var(--plum); font:inherit; resize:vertical; }
        .review-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-top:12px; }
        .review-submit, .review-delete { min-height:42px; padding:0 18px; border-radius:8px; font:inherit; font-size:12px; font-weight:800; cursor:pointer; }
        .review-submit { border:1px solid var(--plum); background:var(--plum); color:white; }
        .review-delete { border:1px solid var(--line); background:white; color:var(--muted); }
        .review-note { margin:18px 0 0; color:var(--muted); }
        .related { padding:0 22px 24px; }
        .related h2 { margin:18px 0 14px; font-family:Georgia,serif; font-size:20px; font-weight:500; }
        .related-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .related-card { min-width:0; }
        .related-image { height:135px; display:grid; place-items:center; overflow:hidden; border-radius:9px; background:linear-gradient(135deg,#f2dfd8,#e8c8be); }
        .related-image img { width:100%; height:100%; object-fit:cover; }
        .related-image span { color:rgba(61,27,61,.4); font-family:Georgia,serif; font-size:22px; }
        .related-name { margin-top:8px; font-family:Georgia,serif; font-size:13px; }
        .related-price { margin-top:4px; color:var(--rose); font-size:12px; font-weight:700; }
        @media (max-width:1199px) { .product-detail { grid-template-columns:minmax(0,1fr) minmax(300px,.9fr); } .service-stack { grid-column:1 / -1; display:grid; grid-template-columns:repeat(3,1fr); } .shop-profile-top { grid-template-columns:1fr; align-items:start; } .shop-stats { max-width:620px; } .shop-about { padding-right:0; } .shop-botanical { display:none; } .shop-product-grid { grid-template-columns:repeat(2,250px); } }
        @media (max-width:767px) { .topbar-inner,.product-shell { width:min(100% - 28px,1380px); } .product-detail { display:block; } .gallery { margin-bottom:30px; } .main-photo,.main-photo img { min-height:420px; } .service-stack { display:grid; grid-template-columns:1fr; margin-top:30px; } h1 { font-size:37px; } .tabs { gap:18px; overflow-x:auto; } .tabs .tab { white-space:nowrap; } .related-grid { grid-template-columns:repeat(2,1fr); } .shop-profile { margin:22px 22px 24px; padding:24px 18px; border-radius:22px; } .shop-identity-row { align-items:flex-start; flex-direction:column; gap:14px; } .shop-avatar { width:92px; height:92px; flex-basis:92px; } .shop-name { font-size:27px; } .shop-meta { align-items:flex-start; flex-direction:column; gap:8px; } .shop-meta-divider { display:none; } .shop-actions { display:grid; grid-template-columns:1fr; } .shop-action { width:100%; } .shop-stats { grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; max-width:none; } .shop-stat { align-items:flex-start; flex-direction:column; gap:9px; padding:14px; } .shop-trust { display:grid; grid-template-columns:1fr; gap:14px; } .shop-products-head { align-items:flex-start; flex-direction:column; } .shop-product-grid { display:flex; gap:12px; overflow-x:auto; padding-bottom:4px; scroll-snap-type:x mandatory; } .shop-product-card { width:auto; min-width:230px; scroll-snap-align:start; } }
    </style>
</head>
<body>
<div class="product-page">
    <header class="topbar">
        <div class="topbar-inner">
            <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
            <input class="search" type="search" placeholder="Search skincare, makeup, fragrance..." aria-label="Search products">
            <button class="top-action" type="button" aria-label="Add to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>
            <a class="top-action" href="{{ route('buyer.cart') }}" aria-label="View cart"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg><span class="cart-badge" data-cart-count>{{ session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0 }}</span></a>
        </div>
    </header>

    <main class="product-shell">
        @if (session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif
        @if (session('error') || $errors->any())
            <div class="flash">{{ session('error') ?: $errors->first() }}</div>
        @endif
        <div class="breadcrumb"><a href="{{ route('shop.index') }}">Home</a> <span>&rsaquo;</span> <a href="{{ route('shop.index', ['category' => $product->category]) }}">{{ $categoryTitle }}</a> <span>&rsaquo;</span> <strong>{{ $product->name }}</strong></div>
        <section class="product-detail">
            <div class="gallery">
                <div class="thumbs">
                    @foreach ($product->image_urls as $idx => $photoUrl)
                        <button class="thumb {{ $idx === 0 ? 'active' : '' }}" type="button" data-gallery-thumb="{{ $photoUrl }}" aria-label="Product image {{ $idx + 1 }}">
                            <img src="{{ $photoUrl }}" alt="{{ $product->name }} photo {{ $idx + 1 }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                        </button>
                    @endforeach
                </div>
                <div class="main-photo">
                    <img id="mainProductPhoto" src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                    <button class="zoom" type="button" aria-label="Zoom image"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 5 5M10.8 7.8v6M7.8 10.8h6"/></svg></button>
                </div>
            </div>

            <div class="product-copy">
                @php
                    $averageRating = round((float) ($product->reviews_avg_rating ?? 0), 1);
                    $reviewCount = (int) ($product->reviews_count ?? 0);
                    $roundedRating = (int) round($averageRating);
                    $salesCount = (int) ($product->sales_count ?? 0);
                    $discountPercent = (float) ($product->discount_percent ?? 0);
                    $originalPrice = (float) $product->price;
                    $finalPrice = $discountPercent > 0 ? $originalPrice * (1 - ($discountPercent / 100)) : $originalPrice;
                    $shopProfile = $shopProfile ?? [];
                    $sellerName = $shopProfile['name'] ?? ($product->seller?->shop_name ?: ($product->seller?->name ?: 'Lumora seller'));
                    $stock = (int) ($product->stock ?? 0);
                @endphp
                <div class="eyebrow">{{ $sellerName }}</div>
                <h1>{{ $product->name }}</h1>
                @if (! empty($shopProfile['seller']))
                    <div class="sold-by">
                        <span>Sold by</span>
                        <strong>{{ $sellerName }}</strong>
                        @if (! empty($shopProfile['verified']))
                            <span class="seller-badge">Verified Seller</span>
                        @endif
                        @if (! empty($shopProfile['url']))
                            <a href="{{ $shopProfile['url'] }}">View Shop</a>
                        @endif
                    </div>
                @endif
                <div class="rating">
                    <a href="#reviews" class="rating-link" data-open-reviews>
                        <span class="stars" aria-label="{{ number_format($averageRating, 1) }} out of 5 stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <span class="{{ $star <= $roundedRating ? 'filled' : 'empty' }}">★</span>
                            @endfor
                        </span>
                        <span>{{ number_format($averageRating, 1) }} ({{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }})</span>
                    </a>
                    @if ($salesCount > 0)<span class="muted"> &middot; {{ number_format($salesCount) }} sold</span>@endif
                </div>
                <div class="price-row"><span class="price">&#8369;{{ number_format($finalPrice, 2) }}</span>@if ($discountPercent > 0)<span class="old-price">&#8369;{{ number_format($originalPrice, 2) }}</span><span class="sale-badge">{{ rtrim(rtrim(number_format($discountPercent, 1), '0'), '.') }}% OFF</span>@endif</div>
                <div class="description">{{ $product->description ?: 'Discover more details about this Lumora product.' }}</div>
                <div class="stock {{ $stock < 1 ? 'out' : '' }}">{{ $stock > 0 ? $stock . ' available' : 'Out of stock' }} @if ($stock > 0)<span class="muted"> &middot; In stock and ready to ship</span>@endif</div>
                <label class="quantity-label" for="quantityOutput">Quantity</label>
                <div class="purchase-row">
                    <div class="quantity"><button type="button" id="quantityMinus" aria-label="Decrease quantity">&#8722;</button><output id="quantityOutput">1</output><button type="button" id="quantityPlus" aria-label="Increase quantity">+</button></div>
                    <form method="POST" action="{{ route('buyer.cart.add', ['product' => $product->id]) }}" id="detailCartForm" class="lumora-cart-form" data-cart-product-name="{{ $product->name }}" data-cart-product-price="{{ $finalPrice }}" data-cart-product-image="{{ $product->image_url }}">
                        @csrf
                        <input type="hidden" name="quantity" id="quantityInput" value="1">
                        <button type="submit" class="button primary add-detail-cart" @disabled($stock < 1)>Add to cart</button>
                    </form>
                </div>
                <form method="POST" action="{{ route('buyer.buy-now', ['product' => $product->id]) }}" id="buyNowForm">
                    @csrf
                    <input type="hidden" name="quantity" id="buy-now-quantity" value="1">
                    <button type="submit" class="button wishlist buy-now" id="buyNowButton" @disabled($stock < 1)>Buy now</button>
                </form>
            </div>

            <aside class="service-stack" aria-label="Shopping services">
                <div class="service-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg><div><strong>Free Shipping</strong><span>Shipping details are shown at checkout.</span></div></div>
                <div class="service-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z"/><path d="M5 4h6"/></svg><div><strong>Fast Delivery</strong><span>Delivery options depend on your location.</span></div></div>
                <div class="service-card"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m8 12 2.5 2.5L16 9"/></svg><div><strong>Cash on Delivery</strong><span>Available methods appear at checkout.</span></div></div>
            </aside>
        </section>

        <section class="info-panel">
            <div class="tabs" role="tablist">
                <button class="tab active" type="button" data-tab="description">Description</button>
                <button class="tab" type="button" data-tab="details">Product Details</button>
                <button class="tab" type="button" data-tab="reviews">Reviews</button>
                <button class="tab" type="button" data-tab="shipping">Shipping &amp; Returns</button>
            </div>
            <div class="tab-panel active" id="description">{{ $product->description ?: 'No description has been added for this product yet.' }}</div>
            <div class="tab-panel" id="details">Category: {{ $categoryTitle }}<br>Seller: {{ $sellerName }}<br>Stock: {{ $stock > 0 ? $stock . ' available' : 'Out of stock' }}</div>
            <div class="tab-panel" id="reviews">
                <div class="reviews-summary">
                    <div>
                        <div class="reviews-score">{{ number_format($averageRating, 1) }}</div>
                        <div class="stars" aria-label="{{ number_format($averageRating, 1) }} out of 5 stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <span class="{{ $star <= $roundedRating ? 'filled' : 'empty' }}">★</span>
                            @endfor
                        </div>
                        <div class="reviews-based">Based on {{ $reviewCount }} {{ $reviewCount === 1 ? 'review' : 'reviews' }}</div>
                    </div>
                    <div class="rating-breakdown">
                        @for ($star = 5; $star >= 1; $star--)
                            @php
                                $starTotal = (int) ($ratingBreakdown->get($star, 0));
                                $starPercent = $reviewCount > 0 ? ($starTotal / $reviewCount) * 100 : 0;
                            @endphp
                            <div class="rating-bar">
                                <span>{{ $star }} stars</span>
                                <span class="rating-track"><span class="rating-fill" style="width: {{ $starPercent }}%"></span></span>
                                <span>{{ $starTotal }}</span>
                            </div>
                        @endfor
                    </div>
                </div>

                @if ($reviews->count())
                    <div class="review-list">
                        @foreach ($reviews as $review)
                            <article class="review-card">
                                <div class="review-head">
                                    <div>
                                        <div class="review-name">{{ $review->user?->name ?? 'Lumora buyer' }}</div>
                                        <div class="stars" aria-label="{{ $review->rating }} out of 5 stars">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <span class="{{ $star <= $review->rating ? 'filled' : 'empty' }}">★</span>
                                            @endfor
                                        </div>
                                        @if ($review->order_id)
                                            <span class="verified-badge">Verified Purchase</span>
                                        @endif
                                    </div>
                                    <time class="review-date" datetime="{{ $review->created_at?->toDateString() }}">{{ $review->created_at?->format('M d, Y') }}</time>
                                </div>
                                @if ($review->review)
                                    <p class="review-text">{{ $review->review }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    <div class="pagination">{{ $reviews->links() }}</div>
                @else
                    <p class="review-note">No reviews yet. Be the first to review this product after purchase.</p>
                @endif

                @if ($userReview)
                    <form class="review-form" method="POST" action="{{ route('buyer.products.reviews.update', ['product' => $product->id, 'review' => $userReview->id]) }}">
                        @csrf
                        @method('PATCH')
                        <h3>Edit Review</h3>
                        <div class="star-input" aria-label="Choose a rating">
                            @for ($star = 5; $star >= 1; $star--)
                                <input type="radio" id="rating-edit-{{ $star }}" name="rating" value="{{ $star }}" @checked((int) old('rating', $userReview->rating) === $star)>
                                <label for="rating-edit-{{ $star }}">★</label>
                            @endfor
                        </div>
                        <textarea class="review-textarea" name="review" maxlength="2000" placeholder="Share your experience with this product...">{{ old('review', $userReview->review) }}</textarea>
                        <div class="review-actions">
                            <button class="review-submit" type="submit">Update Review</button>
                        </div>
                    </form>
                    <form method="POST" action="{{ route('buyer.products.reviews.destroy', ['product' => $product->id, 'review' => $userReview->id]) }}" class="review-actions">
                        @csrf
                        @method('DELETE')
                        <button class="review-delete" type="submit">Delete Review</button>
                    </form>
                @elseif ($canReview)
                    <form class="review-form" method="POST" action="{{ route('buyer.products.reviews.store', ['product' => $product->id]) }}">
                        @csrf
                        <h3>Write a Review</h3>
                        <div class="star-input" aria-label="Choose a rating">
                            @for ($star = 5; $star >= 1; $star--)
                                <input type="radio" id="rating-{{ $star }}" name="rating" value="{{ $star }}" @checked((int) old('rating') === $star)>
                                <label for="rating-{{ $star }}">★</label>
                            @endfor
                        </div>
                        <textarea class="review-textarea" name="review" maxlength="2000" placeholder="Share your experience with this product...">{{ old('review') }}</textarea>
                        <div class="review-actions">
                            <button class="review-submit" type="submit">Submit Review</button>
                        </div>
                    </form>
                @else
                    <p class="review-note">Only buyers who have received this product can leave a review.</p>
                @endif
            </div>
            <div class="tab-panel" id="shipping">Shipping and return information will be shown according to the seller and checkout options.</div>
            @if (! empty($shopProfile['seller']))
                <section class="shop-profile" aria-labelledby="shopProfileTitle">
                    @php
                        $shopDescriptionFallback = 'This seller has not added a shop description yet.';
                        $shopDescription = $shopProfile['description'] ?? $shopDescriptionFallback;
                        $hasShopDescription = trim((string) $shopDescription) !== $shopDescriptionFallback;
                        $shopLocation = $shopProfile['location'] ?? null;
                        $shipsFrom = $shopLocation
                            ? trim(\Illuminate\Support\Str::afterLast($shopLocation, ','))
                            : null;
                    @endphp
                    <h2 class="shop-profile-title" id="shopProfileTitle">Shop Profile</h2>
                    <div class="shop-profile-top">
                        <div class="shop-identity">
                            <div class="shop-identity-row">
                                <div class="shop-avatar" aria-hidden="true">
                                    @if (! empty($shopProfile['avatar']))
                                        <img src="{{ $shopProfile['avatar'] }}" alt="">
                                    @else
                                        <span>{{ $shopProfile['initials'] }}</span>
                                    @endif
                                </div>
                                <div class="shop-copy">
                                    <div class="shop-name-line">
                                        <span class="shop-name">{{ $shopProfile['name'] }}</span>
                                        @if (! empty($shopProfile['verified']))
                                            <span class="seller-badge">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m8.8 12 2.1 2.1 4.4-5"/></svg>
                                                Verified Seller
                                            </span>
                                        @endif
                                    </div>
                                    @if ($hasShopDescription)
                                        <p class="shop-description">{{ \Illuminate\Support\Str::limit($shopDescription, 160) }}</p>
                                    @endif
                                    @if ($shopLocation)
                                        <div class="shop-meta" aria-label="Shop location and shipping origin">
                                            <span class="shop-meta-item">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></svg>
                                                {{ $shopLocation }}
                                            </span>
                                            @if ($shipsFrom)
                                                <span class="shop-meta-divider" aria-hidden="true"></span>
                                                <span class="shop-meta-item">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>
                                                    Ships from {{ $shipsFrom }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="shop-actions">
                                @if (! empty($shopProfile['url']))
                                    <a class="shop-action" href="{{ $shopProfile['url'] }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 10.5 12 4l9 6.5"/><path d="M5 10v9h14v-9"/><path d="M9 19v-5h6v5"/></svg>
                                        Visit Shop
                                    </a>
                                @endif
                                @if (Auth::id() && (int) Auth::id() === (int) $product->seller_id)
                                    <button class="shop-action secondary" type="button" disabled title="You cannot chat with your own shop.">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                        Chat Seller
                                    </button>
                                @else
                                    <a class="shop-action secondary" href="{{ route('buyer.chats.start', ['product' => $product->id]) }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                        Chat Seller
                                    </a>
                                @endif
                                @php
                                    $isOwnShop = Auth::id() && (int) Auth::id() === (int) $product->seller_id;
                                    $isFollowing = Auth::check() && ! $isOwnShop
                                        && Auth::user()->followedShops()->where('shop_follows.seller_id', $product->seller_id)->exists();
                                @endphp
                                @if ($isOwnShop)
                                    <button class="shop-action secondary" type="button" disabled title="You cannot follow your own shop.">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                        Follow Shop
                                    </button>
                                @elseif (Auth::check())
                                    <form method="POST" action="{{ route('buyer.shops.follow', $product->seller_id) }}">
                                        @csrf
                                        <button class="shop-action secondary" type="submit" aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
                                            <svg viewBox="0 0 24 24" fill="{{ $isFollowing ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                            {{ $isFollowing ? 'Following' : 'Follow Shop' }}
                                        </button>
                                    </form>
                                @else
                                    <a class="shop-action secondary" href="{{ route('login') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                        Follow Shop
                                    </a>
                                @endif
                            </div>
                        </div>
                        <div class="shop-stats" aria-label="Shop statistics">
                            <div class="shop-stat">
                                <x-shop-stat-icon name="star" />
                                <div class="shop-stat-text">
                                    <strong>{{ $shopProfile['rating_average'] !== null ? number_format($shopProfile['rating_average'], 1) : 'No ratings' }}</strong>
                                    <span class="shop-stat-label">{{ number_format((int) $shopProfile['review_count']) }} {{ (int) $shopProfile['review_count'] === 1 ? 'review' : 'reviews' }}</span>
                                </div>
                            </div>
                            <div class="shop-stat">
                                <x-shop-stat-icon name="package" />
                                <div class="shop-stat-text">
                                    <strong>{{ number_format((int) $shopProfile['active_products']) }}</strong>
                                    <span class="shop-stat-label">Active products</span>
                                </div>
                            </div>
                            <div class="shop-stat">
                                <x-shop-stat-icon name="bag" />
                                <div class="shop-stat-text">
                                    <strong>{{ number_format((int) $shopProfile['sold_count']) }}</strong>
                                    <span class="shop-stat-label">Products sold</span>
                                </div>
                            </div>
                            <div class="shop-stat">
                                <x-shop-stat-icon name="calendar" />
                                <div class="shop-stat-text">
                                    <strong>{{ $shopProfile['joined'] ?? 'Not available' }}</strong>
                                    <span class="shop-stat-label">Seller since</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="shop-divider">
                    <div class="shop-about">
                        <h3>About this shop</h3>
                        <p>{{ $shopDescription }}</p>
                        <div class="shop-trust" aria-label="Shop trust indicators">
                            <div class="shop-trust-item">
                                <span class="shop-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 7 4v5c0 4.3-2.8 7.3-7 9-4.2-1.7-7-4.7-7-9V7l7-4Z"/><path d="m8.8 12 2.1 2.1 4.4-5"/></svg></span>
                                <span class="shop-trust-copy"><strong>Quality Products</strong><span>Trusted seller products</span></span>
                            </div>
                            <div class="shop-trust-item">
                                <span class="shop-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></span>
                                <span class="shop-trust-copy"><strong>Secure Packaging</strong><span>Safe and sealed</span></span>
                            </div>
                            <div class="shop-trust-item">
                                <span class="shop-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></span>
                                <span class="shop-trust-copy"><strong>Fast Shipping</strong><span>Ships from seller location</span></span>
                            </div>
                        </div>
                        <div class="shop-botanical" aria-hidden="true">
                            <svg viewBox="0 0 120 140" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M72 130c-13-28-12-62 8-100"/>
                                <path d="M79 31c-12 1-22-4-29-15 13-2 23 3 29 15Z"/>
                                <path d="M70 53c13-2 24 2 33 13-13 4-24 0-33-13Z"/>
                                <path d="M65 76c-14 0-25-6-34-19 15-2 26 4 34 19Z"/>
                                <path d="M66 101c14-4 27-1 38 9-13 6-26 3-38-9Z"/>
                                <path d="M52 120c-10-12-16-26-18-42"/>
                                <path d="M34 79c10 2 18 8 23 19-11 0-19-6-23-19Z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="shop-products">
                        <div class="shop-products-head">
                            <h3>More from this shop</h3>
                            @if (! empty($shopProfile['url']))
                                <a class="shop-view-all" href="{{ $shopProfile['url'] }}">View all products &rarr;</a>
                            @endif
                        </div>
                        @if (($moreFromSeller ?? collect())->count())
                            <div class="shop-product-grid">
                                @foreach ($moreFromSeller as $sellerProduct)
                                    @php
                                        $sellerProductRating = round((float) ($sellerProduct->reviews_avg_rating ?? 0), 1);
                                        $sellerProductCount = (int) ($sellerProduct->reviews_count ?? 0);
                                        $sellerProductDiscount = (float) ($sellerProduct->discount_percent ?? 0);
                                        $sellerProductPrice = (float) $sellerProduct->price;
                                        $sellerProductFinalPrice = $sellerProductDiscount > 0 ? $sellerProductPrice * (1 - ($sellerProductDiscount / 100)) : $sellerProductPrice;
                                    @endphp
                                    <a class="shop-product-card" href="{{ route('shop.product', ['id' => $sellerProduct->id]) }}">
                                        <div class="shop-product-image"><img src="{{ $sellerProduct->image_url }}" alt="{{ $sellerProduct->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"></div>
                                        <div class="shop-product-body">
                                            <div class="shop-product-name">{{ $sellerProduct->name }}</div>
                                            <div class="shop-product-rating"><span class="stars">@for ($star = 1; $star <= 5; $star++)<span class="{{ $star <= (int) round($sellerProductRating) ? 'filled' : 'empty' }}">&#9733;</span>@endfor</span> <span>{{ number_format($sellerProductRating, 1) }} ({{ $sellerProductCount }})</span></div>
                                            <div class="shop-product-foot">
                                                <div class="shop-product-price">&#8369;{{ number_format($sellerProductFinalPrice, 2) }}</div>
                                                <span class="shop-product-button" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg></span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="shop-empty">No other active products from this shop yet.</p>
                        @endif
                    </div>
                </section>
            @endif
            @if ($relatedProducts->count())
                <div class="related"><h2>You may also like</h2><div class="related-grid">
                    @foreach ($relatedProducts as $related)
                        @php
                            $relatedRating = round((float) ($related->reviews_avg_rating ?? 0), 1);
                            $relatedCount = (int) ($related->reviews_count ?? 0);
                        @endphp
                        <a class="related-card" href="{{ route('shop.product', ['id' => $related->id]) }}"><div class="related-image"><img src="{{ $related->image_url }}" alt="{{ $related->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"></div><div class="related-name">{{ $related->name }}</div><div class="rating"><span class="stars">@for ($star = 1; $star <= 5; $star++)<span class="{{ $star <= (int) round($relatedRating) ? 'filled' : 'empty' }}">★</span>@endfor</span> <span>{{ number_format($relatedRating, 1) }} ({{ $relatedCount }})</span></div><div class="related-price">&#8369;{{ number_format((float) $related->price, 2) }}</div></a>
                    @endforeach
                </div></div>
            @endif
        </section>
    </main>
</div>

@if (view()->exists('components.chat-widget'))
    @include('components.chat-widget')
@endif

@include('components.add-to-cart-success-modal')

<script>
    const output = document.getElementById('quantityOutput');
    const quantityInput = document.getElementById('quantityInput');
    const buyNowQuantityInput = document.getElementById('buy-now-quantity');
    const maxStock = {{ max(1, (int) ($product->stock ?? 0)) }};
    function setQuantity(value) {
        const quantity = Math.max(1, Math.min(maxStock, Number(value) || 1));
        output.textContent = String(quantity);
        quantityInput.value = String(quantity);
        if (buyNowQuantityInput) buyNowQuantityInput.value = String(quantity);
    }
    document.getElementById('quantityMinus')?.addEventListener('click', () => setQuantity(Number(output.textContent) - 1));
    document.getElementById('quantityPlus')?.addEventListener('click', () => setQuantity(Number(output.textContent) + 1));
    document.querySelectorAll('[data-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-tab], .tab-panel').forEach(item => item.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab)?.classList.add('active');
    }));
    document.querySelectorAll('[data-open-reviews]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        document.querySelector('[data-tab="reviews"]')?.click();
        document.getElementById('reviews')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }));
    const mainProductPhoto = document.getElementById('mainProductPhoto');
    document.querySelectorAll('[data-gallery-thumb]').forEach(thumb => {
        thumb.addEventListener('click', () => {
            const url = thumb.dataset.galleryThumb;
            if (mainProductPhoto && url) {
                mainProductPhoto.src = url;
            }
            document.querySelectorAll('[data-gallery-thumb]').forEach(el => el.classList.remove('active'));
            thumb.classList.add('active');
        });
    });
</script>
<script>
(function () {
    document.querySelectorAll('.cart-badge').forEach(function (badge) {
        var sync = function () { badge.classList.toggle('is-empty', (parseInt(badge.textContent, 10) || 0) <= 0); };
        sync();
        new MutationObserver(sync).observe(badge, { childList: true, characterData: true, subtree: true });
    });
})();
</script>
<x-follow-success-modal />
</body>
</html>
