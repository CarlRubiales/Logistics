<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $shopProfile['name'] }} &middot; {{ config('app.name', 'Lumora') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --cream:#F7F1EC; --paper:#FFFDFC; --plum:#3B1E34; --rose:#C98F72; --line:#EAE3DD; --text:#2F2528; --muted:#8B7B78; --green:#6F8F78; --icon:#B98973; --blush:#F8EEE9; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--cream); color:var(--text); font-family:'Inter',ui-sans-serif,system-ui,sans-serif; }
        a { color:inherit; text-decoration:none; }
        button, input, select { font:inherit; }
        .store-page { min-height:100vh; }
        .topbar { position:sticky; top:0; z-index:30; border-bottom:1px solid var(--line); background:rgba(255,253,252,.96); }
        .topbar-inner, .store-shell { width:min(1260px,calc(100% - 48px)); margin:0 auto; }
        .topbar-inner { min-height:78px; display:flex; align-items:center; gap:20px; }
        .search { flex:1; max-width:540px; height:44px; display:flex; align-items:center; gap:9px; padding:0 17px; border:1px solid var(--line); border-radius:999px; background:var(--paper); }
        .search svg { width:17px; height:17px; color:var(--muted); }
        .search input { width:100%; border:0; outline:0; background:transparent; color:var(--text); font-size:14px; }
        .nav-actions { display:flex; align-items:center; gap:11px; margin-left:auto; }
        .icon-btn { position:relative; width:40px; height:40px; display:inline-grid; place-items:center; padding:0; line-height:0; border:1px solid var(--line); border-radius:50%; background:var(--paper); color:var(--plum); }
        .icon-btn svg { width:18px; height:18px; display:block; position:static; transform:none; margin:0; }
        .nav-badge { position:absolute; top:-5px; right:-5px; min-width:17px; height:17px; display:grid; place-items:center; padding:0 4px; border-radius:999px; background:var(--rose); color:white; font-size:10px; font-weight:800; }
        .account-menu-wrap { position:relative; }
        .account-trigger { height:44px; display:flex; align-items:center; gap:10px; padding:0 10px 0 6px; border:1px solid var(--line); border-radius:999px; background:var(--paper); color:var(--text); cursor:pointer; }
        .account-avatar { width:32px; height:32px; display:grid; place-items:center; border-radius:50%; background:var(--plum); color:white; font-size:12px; font-weight:800; }
        .account-name { max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--text); font-size:13px; font-weight:700; }
        .account-dropdown { position:absolute; top:calc(100% + 10px); right:0; width:220px; display:none; padding:10px; border:1px solid var(--line); border-radius:14px; background:var(--paper); box-shadow:0 18px 38px rgba(59,30,52,.12); }
        .account-dropdown.open { display:block; }
        .account-dropdown a, .account-dropdown button { width:100%; display:block; padding:10px 11px; border:0; border-radius:10px; background:transparent; color:var(--text); text-align:left; font-size:13px; font-weight:700; cursor:pointer; }
        .account-dropdown a:hover, .account-dropdown button:hover { background:#F8F4F1; }
        .auth-link { min-height:40px; display:inline-flex; align-items:center; padding:0 16px; border:1px solid var(--plum); border-radius:999px; color:var(--plum); font-size:13px; font-weight:800; }
        .auth-link.primary { background:var(--plum); color:white; }
        .store-shell { padding:24px 0 76px; }
        .breadcrumb { margin-bottom:20px; color:var(--muted); font-size:13px; }
        .breadcrumb a:hover { color:var(--rose); }
        .breadcrumb strong { color:var(--plum); }
        .cover { position:relative; height:160px; min-height:0; overflow:hidden; border-radius:22px; background-color:#F8EEE9; background-image:var(--shop-cover-image, radial-gradient(circle at 18% 20%,rgba(255,253,252,.9),rgba(255,253,252,0) 30%),radial-gradient(circle at 82% 20%,rgba(201,143,114,.22),rgba(201,143,114,0) 32%),linear-gradient(135deg,#FFFDFC 0%,#F8EEE9 50%,#EFE1DC 100%)); background-position:center; background-size:cover; background-repeat:no-repeat; }
        .cover::before { content:""; position:absolute; inset:0; background:linear-gradient(90deg,rgba(255,253,252,.76),rgba(255,253,252,.42) 56%,rgba(255,253,252,.12)); }
        .cover.has-image::before { background:linear-gradient(90deg,rgba(59,30,52,.58),rgba(59,30,52,.16) 58%,rgba(59,30,52,.04)); }
        .cover::after { content:""; position:absolute; right:34px; bottom:-34px; width:190px; height:145px; border:1px solid rgba(185,137,115,.18); border-radius:62% 38% 72% 28%; transform:rotate(-12deg); }
        .cover-content { position:relative; z-index:1; max-width:560px; padding:28px 38px; color:var(--plum); }
        .cover.has-image .cover-content { color:white; }
        .cover-eyebrow { margin:0 0 8px; color:var(--rose); font-size:11px; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
        .cover.has-image .cover-eyebrow { color:rgba(255,255,255,.82); }
        .cover h1 { margin:0; font-family:'Playfair Display',Georgia,serif; font-size:clamp(38px,3.2vw,46px); font-weight:600; line-height:1; }
        .cover p { max-width:450px; margin:11px 0 0; color:#6F6260; font-size:13.5px; line-height:1.6; }
        .cover.has-image p { color:rgba(255,255,255,.86); }
        .profile-card { position:relative; z-index:2; margin:-24px 0 0; padding:24px 28px; border:1px solid var(--line); border-radius:22px; background:var(--paper); box-shadow:0 10px 26px rgba(59,30,52,.045); }
        .profile-grid { display:grid; grid-template-columns:minmax(0,1.2fr) minmax(360px,.8fr); gap:28px; align-items:center; }
        .identity-row { display:flex; align-items:center; gap:16px; min-width:0; }
        .store-avatar { width:78px; height:78px; display:grid; place-items:center; flex:0 0 78px; overflow:hidden; border:1px solid var(--line); border-radius:50%; background:var(--blush); color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:28px; font-weight:600; }
        .store-avatar img { width:100%; height:100%; object-fit:cover; }
        .name-line { display:flex; align-items:center; gap:10px; flex-wrap:wrap; min-width:0; }
        .shop-name { min-width:0; margin:0; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:30px; font-weight:600; line-height:1.1; overflow-wrap:anywhere; }
        .seller-badge { display:inline-flex; align-items:center; gap:5px; min-height:24px; padding:5px 9px; flex:0 0 auto; border-radius:999px; background:rgba(111,143,120,.14); color:var(--green); font-size:11px; font-weight:700; line-height:1; text-transform:uppercase; }
        .seller-badge svg { width:13px; height:13px; }
        .description { max-width:600px; margin:7px 0 0; color:var(--muted); font-size:13.5px; line-height:1.55; }
        .meta-row { display:flex; align-items:center; flex-wrap:wrap; gap:10px; margin-top:10px; color:var(--muted); font-size:13px; font-weight:600; }
        .meta-item { display:inline-flex; align-items:center; gap:7px; }
        .meta-item svg { width:16px; height:16px; color:var(--rose); }
        .meta-divider { width:1px; height:15px; background:var(--line); }
        .store-actions { display:flex; gap:11px; flex-wrap:wrap; margin-top:15px; }
        .store-actions form { margin:0; display:inline-flex; }
        .store-action { height:44px; display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:0 20px; border:1px solid var(--rose); border-radius:11px; background:var(--paper); color:var(--plum); font-size:12px; font-weight:800; cursor:pointer; }
        .store-action.primary { border-color:var(--plum); background:var(--plum); color:white; }
        .store-action[disabled] { border-color:var(--line); background:#F8F4F1; color:var(--muted); cursor:not-allowed; opacity:1; }
        .store-action svg { width:16px; height:16px; display:block; flex:0 0 16px; position:static; transform:none; margin:0; }
        .stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; align-self:stretch; }
        .stat { min-width:0; min-height:78px; display:grid; grid-template-columns:40px minmax(0,1fr); align-items:center; gap:12px; padding:14px 16px; border:1px solid var(--line); border-radius:15px; background:var(--paper); }
        .stats .stat .shop-stat-icon.stat-icon { width:40px; height:40px; display:inline-grid !important; place-items:center !important; align-self:center; justify-self:center; flex:0 0 40px; margin:0 !important; padding:0 !important; line-height:0 !important; position:static !important; inset:auto !important; transform:none !important; border-radius:9999px; background:#F7EEE8; color:var(--icon); overflow:hidden; }
        .stats .stat .shop-stat-icon.stat-icon > .shop-stat-icon-svg { width:18px !important; height:18px !important; display:block !important; flex:0 0 18px; margin:0 !important; position:static !important; inset:auto !important; transform:none !important; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; overflow:visible; }
        .stat strong { display:block; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:18px; font-weight:600; line-height:1.1; }
        .stat span { display:block; margin-top:4px; color:var(--muted); font-size:10.5px; font-weight:800; letter-spacing:.075em; text-transform:uppercase; }
        .stat > div { min-width:0; }
        .tabs { display:flex; gap:32px; margin:18px 0 0; border-bottom:1px solid var(--line); overflow-x:auto; }
        .tab { padding:0 0 12px; border:0; border-bottom:2px solid transparent; background:transparent; color:var(--muted); font-size:13px; font-weight:800; cursor:pointer; white-space:nowrap; }
        .tab.active { border-bottom-color:var(--rose); color:var(--plum); }
        .tab-panel { display:none; padding-top:20px; }
        .tab-panel.active { display:block; }
        .storefront { display:grid; grid-template-columns:220px minmax(0,1fr); gap:24px; align-items:start; }
        .filters { position:sticky; top:98px; padding:18px; border:1px solid var(--line); border-radius:16px; background:var(--paper); }
        .filter-section + .filter-section { margin-top:18px; padding-top:18px; border-top:1px solid var(--line); }
        .filter-section h3 { margin:0 0 12px; color:var(--plum); font-size:13px; font-weight:800; text-transform:uppercase; letter-spacing:.08em; }
        .category-link { min-height:36px; display:flex; align-items:center; justify-content:space-between; gap:12px; padding:0 10px; border-radius:10px; color:var(--muted); font-size:13px; font-weight:700; }
        .category-link.active, .category-link:hover { background:var(--blush); color:var(--plum); }
        .category-link span:last-child { color:var(--rose); font-size:12px; }
        .field { display:grid; gap:6px; margin-bottom:10px; }
        .field label { color:var(--muted); font-size:12px; font-weight:700; }
        .field input, .field select, .shop-search input, .view-select { width:100%; height:44px; padding:0 12px; border:1px solid var(--line); border-radius:11px; background:white; color:var(--text); }
        .filter-button, .clear-link { width:100%; min-height:42px; display:inline-flex; align-items:center; justify-content:center; border-radius:11px; font-size:12px; font-weight:800; }
        .filter-button { border:1px solid var(--plum); background:var(--plum); color:white; cursor:pointer; }
        .clear-link { margin-top:9px; border:1px solid var(--line); background:var(--paper); color:var(--muted); }
        .products-head { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:16px; }
        .products-head h2 { margin:0; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:26px; font-weight:600; }
        .product-count { color:var(--muted); font-size:13px; }
        .toolbar { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
        .shop-search { display:flex; flex:1 1 auto; min-width:0; gap:8px; }
        .shop-search button { width:46px; height:44px; flex:0 0 46px; display:grid; place-items:center; padding:0; line-height:0; border:1px solid var(--plum); border-radius:11px; background:var(--plum); color:white; cursor:pointer; }
        .shop-search svg, .toggle svg { width:17px; height:17px; display:block; position:static; transform:none; margin:0; }
        .view-select { flex:0 0 170px; }
        .view-toggle { display:flex; gap:6px; }
        .toggle { width:44px; height:44px; display:grid; place-items:center; padding:0; line-height:0; border:1px solid var(--line); border-radius:11px; background:var(--paper); color:var(--muted); }
        .toggle.active { border-color:var(--rose); color:var(--plum); }
        .product-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:20px; }
        .product-grid.list { grid-template-columns:1fr; }
        .product-card { position:relative; overflow:hidden; border:1px solid var(--line); border-radius:16px; background:var(--paper); }
        .product-image { width:100%; aspect-ratio:4/3; display:block; overflow:hidden; background:var(--blush); }
        .product-image img { width:100%; height:100%; display:block; object-fit:cover; object-position:center; }
        .wish { position:absolute; top:12px; right:12px; width:36px; height:36px; display:grid; place-items:center; padding:0; line-height:0; border:1px solid rgba(234,227,221,.9); border-radius:50%; background:rgba(255,253,252,.92); color:var(--plum); }
        .wish svg { width:16px; height:16px; display:block; position:static; transform:none; margin:0; }
        .sale { position:absolute; top:12px; left:12px; padding:5px 8px; border-radius:999px; background:var(--plum); color:white; font-size:10px; font-weight:900; }
        .product-body { padding:14px 15px; }
        .product-category { margin-bottom:5px; color:var(--muted); font-size:11px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .product-name { min-height:40px; margin:0; color:var(--plum); font-size:15px; font-weight:700; line-height:1.35; }
        .product-rating { margin-top:8px; color:var(--muted); font-size:12px; }
        .stars .filled { color:var(--rose); }
        .stars .empty { color:var(--line); }
        .price-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:11px; }
        .price-row strong { color:var(--rose); font-size:16px; font-weight:800; }
        .price-row del { color:#A99793; font-size:12px; }
        .product-actions { display:flex; align-items:center; gap:10px; margin-top:14px; }
        .product-actions a, .cart-form button { min-height:36px; display:inline-flex; align-items:center; justify-content:center; border-radius:11px; font-size:12px; font-weight:800; }
        .product-actions a { flex:1; border:1px solid var(--line); color:var(--plum); }
        .cart-form { margin:0; }
        .cart-form button { width:36px; height:36px; padding:0; line-height:0; border:1px solid var(--plum); border-radius:50%; background:var(--plum); color:white; cursor:pointer; }
        .cart-form button svg { display:block; position:static; transform:none; margin:0; }
        .cart-form button:disabled { border-color:var(--line); background:#F8F4F1; color:var(--muted); cursor:not-allowed; }
        .product-grid.list .product-card { display:grid; grid-template-columns:220px minmax(0,1fr); }
        .product-grid.list .product-image { aspect-ratio:4/3; min-height:0; }
        .empty-state { padding:32px; border:1px solid var(--line); border-radius:18px; background:var(--paper); color:var(--muted); text-align:center; }
        .empty-state strong { display:block; margin-bottom:8px; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:24px; }
        .pagination { margin-top:24px; }
        .info-card { position:relative; overflow:hidden; padding:28px; border:1px solid var(--line); border-radius:20px; background:var(--paper); }
        .info-card h2 { margin:0 0 12px; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:28px; font-weight:600; }
        .info-card p { max-width:760px; margin:0; color:#6F6260; font-size:14px; line-height:1.7; }
        .trust { display:flex; gap:36px; flex-wrap:wrap; margin-top:24px; }
        .trust-item { display:flex; align-items:center; gap:12px; }
        .trust-icon { width:42px; height:42px; display:grid; place-items:center; border-radius:50%; background:var(--blush); color:var(--icon); }
        .trust-icon svg { width:19px; height:19px; fill:none; stroke:currentColor; stroke-width:1.8; stroke-linecap:round; stroke-linejoin:round; }
        .trust-item strong { display:block; color:var(--plum); font-size:13px; font-weight:700; }
        .trust-item span { display:block; margin-top:3px; color:var(--muted); font-size:12px; }
        .details-list { display:grid; gap:10px; max-width:720px; margin-top:22px; color:var(--muted); font-size:14px; }
        .details-list strong { color:var(--plum); }
        .botanical { position:absolute; right:24px; bottom:14px; width:180px; color:var(--rose); opacity:.12; pointer-events:none; }
        .reviews-layout { display:grid; grid-template-columns:260px minmax(0,1fr); gap:24px; }
        .review-score { padding:24px; border:1px solid var(--line); border-radius:18px; background:var(--paper); }
        .review-score strong { display:block; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:42px; line-height:1; }
        .review-score span { color:var(--muted); font-size:13px; }
        .breakdown { display:grid; gap:8px; margin-top:18px; }
        .break-row { display:grid; grid-template-columns:44px 1fr 28px; gap:8px; align-items:center; color:var(--muted); font-size:12px; }
        .track { height:7px; overflow:hidden; border-radius:999px; background:var(--line); }
        .fill { height:100%; border-radius:999px; background:var(--rose); }
        .review-list { display:grid; gap:14px; }
        .review-card { padding:18px; border:1px solid var(--line); border-radius:16px; background:var(--paper); }
        .review-card-head { display:flex; justify-content:space-between; gap:12px; margin-bottom:8px; }
        .review-name { color:var(--plum); font-weight:800; }
        .review-date, .review-product { color:var(--muted); font-size:12px; }
        .review-text { margin:9px 0 0; color:#6F6260; font-size:14px; line-height:1.65; }
        .policies { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:16px; }
        .policy { padding:22px; border:1px solid var(--line); border-radius:18px; background:var(--paper); }
        .policy h3 { margin:0 0 8px; color:var(--plum); font-family:'Playfair Display',Georgia,serif; font-size:22px; font-weight:600; }
        .policy p { margin:0; color:var(--muted); font-size:13px; line-height:1.65; }
        .share-note { min-height:18px; margin-top:8px; color:var(--green); font-size:12px; font-weight:700; }
        @media (max-width:1050px) { .profile-grid, .storefront, .reviews-layout { grid-template-columns:1fr; } .filters { position:static; } .stats { max-width:640px; } .product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; } .policies { grid-template-columns:1fr; } .botanical { display:none; } }
        @media (max-width:720px) { html, body, .store-page { max-width:100%; overflow-x:hidden; } .topbar-inner, .store-shell { width:calc(100vw - 28px); max-width:1260px; } .topbar-inner { flex-wrap:wrap; gap:10px; padding:14px 0; } .topbar-inner > a[aria-label="Lumora shop"] span:first-child { font-size:22px; letter-spacing:.24em; } .topbar-inner > a[aria-label="Lumora shop"] span:last-child { font-size:5px; letter-spacing:.42em; } .nav-actions { gap:8px; } .auth-link { display:none; } .search { order:3; max-width:none; flex-basis:100%; } .account-name { display:none; } .cover { height:130px; max-width:100%; } .cover::after { width:150px; height:120px; right:10px; } .cover-content { padding:22px; } .cover h1 { font-size:32px; } .cover p { display:none; } .profile-card { max-width:100%; margin:0; padding:22px 18px; overflow:hidden; border-radius:18px; } .profile-grid { min-width:0; } .identity-row { align-items:flex-start; flex-direction:column; gap:14px; } .name-line { align-items:flex-start; flex-direction:column; gap:8px; } .shop-name { font-size:30px; } .store-actions { display:grid; grid-template-columns:1fr; } .store-actions form, .store-action { width:100%; } .stats { width:100%; grid-template-columns:repeat(2,minmax(0,1fr)); max-width:none; gap:14px; } .stat { grid-template-columns:1fr; align-items:start; gap:9px; padding:14px 12px; } .stats .stat .shop-stat-icon.stat-icon { justify-self:start; } .stat strong { font-size:18px; overflow-wrap:anywhere; } .stat span { font-size:10px; letter-spacing:.045em; overflow-wrap:anywhere; } .toolbar { flex-wrap:wrap; } .shop-search { flex-basis:100%; } .view-select { flex:1 1 calc(100% - 98px); } .view-toggle { flex:0 0 auto; } .product-grid, .product-grid.list { grid-template-columns:1fr; gap:18px; } .product-grid.list .product-card { display:block; } .product-grid.list .product-image { aspect-ratio:4/3; } .tabs { gap:22px; } .trust { display:grid; gap:14px; } }
    </style>
</head>
<body>
@php
    $descriptionFallback = 'This seller has not added a shop description yet.';
    $shopDescription = $shopProfile['description'] ?? $descriptionFallback;
    $hasShopDescription = trim((string) $shopDescription) !== $descriptionFallback;
    $shopLocation = $shopProfile['location'] ?? null;
    $shipsFrom = $shopLocation ? trim(\Illuminate\Support\Str::afterLast($shopLocation, ',')) : null;
    $storeUrl = route('shop.seller', ['seller' => $seller->id]);
    $shopCoverPath = collect(['shop_cover', 'cover_image', 'banner_image', 'store_cover', 'shop_banner'])
        ->map(fn ($field) => $seller->getAttribute($field))
        ->first(fn ($value) => filled($value));
    $shopCoverUrl = null;

    if (filled($shopCoverPath)) {
        $coverPath = trim(str_replace('\\', '/', (string) $shopCoverPath));
        $isLegacyJewelryHero = \Illuminate\Support\Str::contains($coverPath, [
            'images/hero/hero-1.jpg',
            'hero/hero-1.jpg',
            'hero-1.jpg',
        ]);

        if ($isLegacyJewelryHero) {
            $shopCoverPath = null;
        } elseif (\Illuminate\Support\Str::startsWith($coverPath, ['http://', 'https://'])) {
            $shopCoverUrl = $coverPath;
        } else {
            $coverPath = ltrim($coverPath, '/');
            $coverCandidates = array_filter(array_unique([
                $coverPath,
                \Illuminate\Support\Str::startsWith($coverPath, 'storage/') ? $coverPath : 'storage/'.$coverPath,
            ]));

            foreach ($coverCandidates as $publicPath) {
                if (file_exists(public_path($publicPath))) {
                    $shopCoverUrl = asset($publicPath);
                    break;
                }
            }

            if (! $shopCoverUrl && \Illuminate\Support\Facades\Storage::disk('public')->exists($coverPath)) {
                $shopCoverUrl = asset(\Illuminate\Support\Facades\Storage::disk('public')->url($coverPath));
            }
        }
    }

    $sortLabels = [
        'newest' => 'Newest First',
        'price_asc' => 'Price: Low to High',
        'price_desc' => 'Price: High to Low',
        'best_selling' => 'Best Selling',
        'top_rated' => 'Top Rated',
    ];
@endphp
<div class="store-page">
    <header class="topbar">
        <div class="topbar-inner">
            <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
            <form action="{{ route('shop.index') }}" method="GET" class="search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" placeholder="Search skincare, makeup, fragrance..." value="{{ request('q') }}">
            </form>
            <div class="nav-actions">
                <a href="{{ route('profile.edit') }}#wishlist" class="icon-btn" aria-label="Wishlist">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
                </a>
                @auth
                    <a href="{{ route('buyer.cart') }}" class="icon-btn" aria-label="Cart">
                @else
                    <button type="button" class="icon-btn" data-open-guest-cart aria-label="Sign in to view cart">
                @endauth
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.5 3h2l2.7 12.4a2 2 0 0 0 2 1.6h8.6a2 2 0 0 0 2-1.6L21.5 7H6"/></svg>
                    <span class="nav-badge" data-cart-count>{{ session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0 }}</span>
                @auth
                    </a>
                @else
                    </button>
                @endauth
                @auth
                    <div class="account-menu-wrap">
                        <button type="button" class="account-trigger" data-account-trigger aria-expanded="false">
                            <span class="account-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                            <span class="account-name">{{ Auth::user()->name }}</span>
                        </button>
                        <div class="account-dropdown" data-account-dropdown>
                            <a href="{{ route('profile.edit') }}">Profile</a>
                            @if (Auth::user()->role === 'seller')
                                <a href="{{ route('seller.dashboard') }}">Seller dashboard</a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit">Log out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a class="auth-link" href="{{ route('login') }}">Log in</a>
                    <a class="auth-link primary" href="{{ route('register') }}">Join</a>
                @endauth
            </div>
        </div>
    </header>

    <main class="store-shell">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('shop.index') }}">Home</a> <span>&rsaquo;</span> <a href="{{ route('shop.index') }}">Shops</a> <span>&rsaquo;</span> <strong>{{ $shopProfile['name'] }}</strong>
        </nav>

        <section class="cover {{ $shopCoverUrl ? 'has-image' : 'is-fallback' }}" @if ($shopCoverUrl) style="--shop-cover-image:url('{{ $shopCoverUrl }}')" @endif aria-label="{{ $shopProfile['name'] }} cover">
            <div class="cover-content">
                @if (! empty($businessCategory))
                    <p class="cover-eyebrow">{{ $businessCategory }}</p>
                @endif
                <h1>{{ $shopProfile['name'] }}</h1>
            </div>
        </section>

        <section class="profile-card" aria-labelledby="storeTitle">
            <div class="profile-grid">
                <div>
                    <div class="identity-row">
                        <div class="store-avatar" aria-hidden="true">
                            @if (! empty($shopProfile['avatar']))
                                <img src="{{ $shopProfile['avatar'] }}" alt="">
                            @else
                                <span>{{ $shopProfile['initials'] }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="name-line">
                                <h2 class="shop-name" id="storeTitle">{{ $shopProfile['name'] }}</h2>
                                @if (! empty($shopProfile['verified']))
                                    <span class="seller-badge">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3 4 7v5c0 5 3.5 8 8 9 4.5-1 8-4 8-9V7l-8-4Z"/><path d="m8.8 12 2.1 2.1 4.4-5"/></svg>
                                        Verified Seller
                                    </span>
                                @endif
                            </div>
                            <p class="description">{{ $shopDescription }}</p>
                            @if ($shopLocation)
                                <div class="meta-row">
                                    <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></svg>{{ $shopLocation }}</span>
                                    @if ($shipsFrom)
                                        <span class="meta-divider" aria-hidden="true"></span>
                                        <span class="meta-item"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg>Ships from {{ $shipsFrom }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="store-actions">
                        @php
                            $isOwnShop = Auth::id() && (int) Auth::id() === (int) $seller->id;
                            $isFollowing = Auth::check() && ! $isOwnShop
                                && Auth::user()->followedShops()->where('shop_follows.seller_id', $seller->id)->exists();
                        @endphp
                        @if ($isOwnShop)
                            <button class="store-action primary" type="button" disabled title="You cannot follow your own shop.">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                Follow Shop
                            </button>
                        @elseif (Auth::check())
                            <form method="POST" action="{{ route('buyer.shops.follow', $seller->id) }}">
                                @csrf
                                <button class="store-action {{ $isFollowing ? '' : 'primary' }}" type="submit" aria-pressed="{{ $isFollowing ? 'true' : 'false' }}">
                                    <svg viewBox="0 0 24 24" fill="{{ $isFollowing ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                    {{ $isFollowing ? 'Following' : 'Follow Shop' }}
                                </button>
                            </form>
                        @else
                            <a class="store-action primary" href="{{ route('login') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
                                Follow Shop
                            </a>
                        @endif
                        @if (Auth::id() && (int) Auth::id() === (int) $seller->id)
                            <button class="store-action" type="button" disabled title="You cannot chat with your own shop.">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                Chat Seller
                            </button>
                        @else
                            <a class="store-action" href="{{ route('buyer.chats.start', ['seller' => $seller->id]) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>
                                Chat Seller
                            </a>
                        @endif
                        <button class="store-action" type="button" data-share-shop data-share-url="{{ $storeUrl }}" data-share-title="{{ $shopProfile['name'] }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.6 6.8-4.2M8.6 13.4l6.8 4.2"/></svg>
                            Share Shop
                        </button>
                    </div>
                    <div class="share-note" data-share-note></div>
                </div>
                <div class="stats" aria-label="Shop statistics">
                    <div class="stat"><x-shop-stat-icon name="star" class="stat-icon" /><div><strong>{{ $shopProfile['rating_average'] !== null ? number_format($shopProfile['rating_average'], 1) : 'No ratings' }}</strong><span>{{ number_format((int) $shopProfile['review_count']) }} {{ (int) $shopProfile['review_count'] === 1 ? 'review' : 'reviews' }}</span></div></div>
                    <div class="stat"><x-shop-stat-icon name="package" class="stat-icon" /><div><strong>{{ number_format((int) $shopProfile['active_products']) }}</strong><span>Active Products</span></div></div>
                    <div class="stat"><x-shop-stat-icon name="bag" class="stat-icon" /><div><strong>{{ number_format((int) $shopProfile['sold_count']) }}</strong><span>Products Sold</span></div></div>
                    <div class="stat"><x-shop-stat-icon name="calendar" class="stat-icon" /><div><strong>{{ $shopProfile['joined'] ?? 'Not available' }}</strong><span>Seller Since</span></div></div>
                </div>
            </div>
        </section>

        <div class="tabs" role="tablist" aria-label="Shop sections">
            <button class="tab active" type="button" data-tab="products">Products</button>
            <button class="tab" type="button" data-tab="about">About</button>
            <button class="tab" type="button" data-tab="reviews">Reviews</button>
            <button class="tab" type="button" data-tab="policies">Policies</button>
        </div>

        <section class="tab-panel active" id="products" aria-label="Products">
            <div class="storefront">
                <aside class="filters" aria-label="Shop filters">
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <a class="category-link {{ empty($filters['category']) ? 'active' : '' }}" href="{{ $storeUrl }}"><span>All Products</span><span>{{ number_format((int) $totalProductCount) }}</span></a>
                        @foreach ($categoryCounts as $categorySlug => $count)
                            <a class="category-link {{ ($filters['category'] ?? '') === $categorySlug ? 'active' : '' }}" href="{{ $storeUrl }}?{{ http_build_query(array_merge(request()->except(['category', 'page']), ['category' => $categorySlug])) }}">
                                <span>{{ \App\Support\CategoryCatalog::label((string) $categorySlug) }}</span>
                                <span>{{ number_format((int) $count) }}</span>
                            </a>
                        @endforeach
                    </div>
                    <form method="GET" action="{{ $storeUrl }}">
                        <input type="hidden" name="search" value="{{ $filters['search'] }}">
                        <input type="hidden" name="category" value="{{ $filters['category'] }}">
                        <input type="hidden" name="view" value="{{ $filters['view'] }}">
                        <div class="filter-section">
                            <h3>Price Range</h3>
                            <div class="field"><label for="min_price">Min Price</label><input id="min_price" name="min_price" type="number" min="0" step="0.01" placeholder="{{ $priceBounds['min'] !== null ? number_format($priceBounds['min'], 2, '.', '') : '0.00' }}" value="{{ $filters['min_price'] !== null ? number_format($filters['min_price'], 2, '.', '') : '' }}"></div>
                            <div class="field"><label for="max_price">Max Price</label><input id="max_price" name="max_price" type="number" min="0" step="0.01" placeholder="{{ $priceBounds['max'] !== null ? number_format($priceBounds['max'], 2, '.', '') : '0.00' }}" value="{{ $filters['max_price'] !== null ? number_format($filters['max_price'], 2, '.', '') : '' }}"></div>
                        </div>
                        <div class="filter-section">
                            <h3>Sort By</h3>
                            <div class="field">
                                <label for="sort">Sort products</label>
                                <select id="sort" name="sort">
                                    @foreach ($sortLabels as $value => $label)
                                        <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="filter-button" type="submit">Apply filters</button>
                            <a class="clear-link" href="{{ $storeUrl }}">Clear filters</a>
                        </div>
                    </form>
                </aside>

                <div>
                    <div class="products-head">
                        <div>
                            <h2>Products</h2>
                            <div class="product-count">{{ number_format($products->total()) }} {{ $products->total() === 1 ? 'product' : 'products' }} found</div>
                        </div>
                    </div>
                    <div class="toolbar">
                        <form action="{{ $storeUrl }}" method="GET" class="shop-search">
                            <input type="hidden" name="category" value="{{ $filters['category'] }}">
                            <input type="hidden" name="min_price" value="{{ $filters['min_price'] }}">
                            <input type="hidden" name="max_price" value="{{ $filters['max_price'] }}">
                            <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                            <input type="hidden" name="view" value="{{ $filters['view'] }}">
                            <input type="search" name="search" placeholder="Search in this shop..." value="{{ $filters['search'] }}">
                            <button type="submit" aria-label="Search this shop"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg></button>
                        </form>
                        <select class="view-select" onchange="window.location.href=this.value" aria-label="Sort products">
                            @foreach ($sortLabels as $value => $label)
                                <option value="{{ $storeUrl }}?{{ http_build_query(array_merge(request()->except(['sort', 'page']), ['sort' => $value])) }}" @selected(($filters['sort'] ?? 'newest') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="view-toggle" aria-label="Product view">
                            <a class="toggle {{ ($filters['view'] ?? 'grid') === 'grid' ? 'active' : '' }}" href="{{ $storeUrl }}?{{ http_build_query(array_merge(request()->except(['view', 'page']), ['view' => 'grid'])) }}" aria-label="Grid view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></a>
                            <a class="toggle {{ ($filters['view'] ?? 'grid') === 'list' ? 'active' : '' }}" href="{{ $storeUrl }}?{{ http_build_query(array_merge(request()->except(['view', 'page']), ['view' => 'list'])) }}" aria-label="List view"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></a>
                        </div>
                    </div>

                    @if ($products->count())
                        <div class="product-grid {{ ($filters['view'] ?? 'grid') === 'list' ? 'list' : '' }}">
                            @foreach ($products as $storeProduct)
                                @php
                                    $productRating = round((float) ($storeProduct->reviews_avg_rating ?? 0), 1);
                                    $productCount = (int) ($storeProduct->reviews_count ?? 0);
                                    $discountPercent = (float) ($storeProduct->discount_percent ?? 0);
                                    $originalPrice = (float) $storeProduct->price;
                                    $finalPrice = $discountPercent > 0 ? $originalPrice * (1 - ($discountPercent / 100)) : $originalPrice;
                                @endphp
                                <article class="product-card">
                                    <button type="button" class="wish" aria-label="Add {{ $storeProduct->name }} to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>
                                    @if ($discountPercent > 0)<span class="sale">{{ rtrim(rtrim(number_format($discountPercent, 1), '0'), '.') }}% OFF</span>@endif
                                    <a class="product-image" href="{{ route('shop.product', ['id' => $storeProduct->id]) }}"><img src="{{ $storeProduct->image_url }}" alt="{{ $storeProduct->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';"></a>
                                    <div class="product-body">
                                        @if ($storeProduct->category)<div class="product-category">{{ \App\Support\CategoryCatalog::label((string) $storeProduct->category) }}</div>@endif
                                        <h3 class="product-name"><a href="{{ route('shop.product', ['id' => $storeProduct->id]) }}">{{ $storeProduct->name }}</a></h3>
                                        <div class="product-rating"><span class="stars">@for ($star = 1; $star <= 5; $star++)<span class="{{ $star <= (int) round($productRating) ? 'filled' : 'empty' }}">&#9733;</span>@endfor</span> <span>{{ number_format($productRating, 1) }} ({{ $productCount }})</span></div>
                                        <div class="price-row"><strong>&#8369;{{ number_format($finalPrice, 2) }}</strong>@if ($discountPercent > 0)<del>&#8369;{{ number_format($originalPrice, 2) }}</del>@endif</div>
                                        <div class="product-actions">
                                            <a href="{{ route('shop.product', ['id' => $storeProduct->id]) }}">View product</a>
                                            <form method="POST" action="{{ route('buyer.cart.add', ['product' => $storeProduct->id]) }}" class="cart-form lumora-cart-form" data-cart-product-name="{{ $storeProduct->name }}" data-cart-product-price="{{ $finalPrice }}" data-cart-product-image="{{ $storeProduct->image_url }}">
                                                @csrf
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" aria-label="Add {{ $storeProduct->name }} to cart" @disabled((int) ($storeProduct->stock ?? 0) < 1)><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l-1 12H7L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg></button>
                                            </form>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div class="pagination">{{ $products->links() }}</div>
                    @else
                        <div class="empty-state"><strong>No products found.</strong><span>Try adjusting the search or filters.</span><a class="clear-link" href="{{ $storeUrl }}">Clear filters</a></div>
                    @endif
                </div>
            </div>
        </section>

        <section class="tab-panel" id="about" aria-label="About this shop">
            <div class="info-card">
                <h2>About this shop</h2>
                <p>{{ $shopDescription }}</p>
                <div class="details-list">
                    @if ($shopLocation)<div><strong>Location:</strong> {{ $shopLocation }}</div>@endif
                    @if ($shipsFrom)<div><strong>Ships from:</strong> {{ $shipsFrom }}</div>@endif
                    <div><strong>Seller since:</strong> {{ $shopProfile['joined'] ?? 'Not available' }}</div>
                    @if (! empty($businessCategory))<div><strong>Business category:</strong> {{ $businessCategory }}</div>@endif
                </div>
                <div class="trust">
                    <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24"><path d="m12 3 7 4v5c0 4.3-2.8 7.3-7 9-4.2-1.7-7-4.7-7-9V7l7-4Z"/><path d="m8.8 12 2.1 2.1 4.4-5"/></svg></span><div><strong>Quality Products</strong><span>Trusted seller products</span></div></div>
                    <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg></span><div><strong>Secure Packaging</strong><span>Safe and sealed</span></div></div>
                    <div class="trust-item"><span class="trust-icon"><svg viewBox="0 0 24 24"><path d="M3 7h11v10H3z"/><path d="M14 10h4l3 3v4h-7z"/><circle cx="7" cy="19" r="2"/><circle cx="18" cy="19" r="2"/></svg></span><div><strong>Fast Shipping</strong><span>Ships from seller location</span></div></div>
                </div>
                <svg class="botanical" viewBox="0 0 120 140" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M72 130c-13-28-12-62 8-100"/><path d="M79 31c-12 1-22-4-29-15 13-2 23 3 29 15Z"/><path d="M70 53c13-2 24 2 33 13-13 4-24 0-33-13Z"/><path d="M65 76c-14 0-25-6-34-19 15-2 26 4 34 19Z"/><path d="M66 101c14-4 27-1 38 9-13 6-26 3-38-9Z"/></svg>
            </div>
        </section>

        <section class="tab-panel" id="reviews" aria-label="Shop reviews">
            @if ((int) $shopProfile['review_count'] > 0)
                <div class="reviews-layout">
                    <div class="review-score">
                        <strong>{{ $shopProfile['rating_average'] !== null ? number_format($shopProfile['rating_average'], 1) : 'No ratings' }}</strong>
                        <span>{{ number_format((int) $shopProfile['review_count']) }} {{ (int) $shopProfile['review_count'] === 1 ? 'review' : 'reviews' }}</span>
                        <div class="breakdown">
                            @for ($rating = 5; $rating >= 1; $rating--)
                                @php $ratingTotal = (int) ($ratingBreakdown[$rating] ?? 0); $percent = (int) $shopProfile['review_count'] > 0 ? ($ratingTotal / (int) $shopProfile['review_count']) * 100 : 0; @endphp
                                <div class="break-row"><span>{{ $rating }} star</span><div class="track"><div class="fill" style="width:{{ $percent }}%"></div></div><span>{{ $ratingTotal }}</span></div>
                            @endfor
                        </div>
                    </div>
                    <div class="review-list">
                        @forelse ($recentReviews as $review)
                            <article class="review-card">
                                <div class="review-card-head"><div><div class="review-name">{{ $review->user?->name ?? 'Lumora buyer' }}</div><div class="review-product">{{ $review->product?->name }}</div></div><div class="review-date">{{ $review->created_at?->format('M d, Y') }}</div></div>
                                <div class="product-rating"><span class="stars">@for ($star = 1; $star <= 5; $star++)<span class="{{ $star <= (int) $review->rating ? 'filled' : 'empty' }}">&#9733;</span>@endfor</span></div>
                                @if (filled($review->review))<p class="review-text">{{ $review->review }}</p>@endif
                            </article>
                        @empty
                            <div class="empty-state"><strong>No shop reviews yet.</strong><span>Reviews will appear after buyers review products from this shop.</span></div>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="empty-state"><strong>No shop reviews yet.</strong><span>Reviews will appear after buyers review products from this shop.</span></div>
            @endif
        </section>

        <section class="tab-panel" id="policies" aria-label="Shop policies">
            <div class="policies">
                <article class="policy"><h3>Shipping</h3><p>Shipping options and delivery fees are shown during Lumora checkout based on the order and seller location.</p></article>
                <article class="policy"><h3>Returns</h3><p>Return requests are handled through Lumora order support according to marketplace order status and product condition.</p></article>
                <article class="policy"><h3>Payment Methods</h3><p>Available payment methods are shown at checkout. Supported wallet and cash options depend on your account and order.</p></article>
            </div>
        </section>
    </main>
</div>

@if (view()->exists('components.chat-widget'))
    @include('components.chat-widget')
@endif
@include('components.add-to-cart-success-modal')
@include('components.guest-cart-login-modal')

<script>
    document.querySelectorAll('[data-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-tab], .tab-panel').forEach(item => item.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById(tab.dataset.tab)?.classList.add('active');
    }));

    const trigger = document.querySelector('[data-account-trigger]');
    const dropdown = document.querySelector('[data-account-dropdown]');
    trigger?.addEventListener('click', () => {
        const isOpen = dropdown?.classList.toggle('open');
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.account-menu-wrap')) {
            dropdown?.classList.remove('open');
            trigger?.setAttribute('aria-expanded', 'false');
        }
    });

    document.querySelector('[data-share-shop]')?.addEventListener('click', async event => {
        const button = event.currentTarget;
        const note = document.querySelector('[data-share-note]');
        const shareData = { title: button.dataset.shareTitle, url: button.dataset.shareUrl };
        try {
            if (navigator.share) {
                await navigator.share(shareData);
                if (note) note.textContent = 'Shop shared.';
            } else if (navigator.clipboard) {
                await navigator.clipboard.writeText(shareData.url);
                if (note) note.textContent = 'Shop link copied.';
            }
        } catch (error) {
            if (note) note.textContent = '';
        }
    });
</script>
<x-follow-success-modal stay-label="Stay in shop" />
</body>
</html>
