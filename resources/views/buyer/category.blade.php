<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $categoryTitle }} · {{ config('app.name', 'Lumora') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --cream:#fffaf7; --paper:#fffdfb; --line:#eadfe0; --plum:#3d1b3d; --rose:#b96562; --muted:#766a70; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--cream); color:var(--plum); font-family:Inter,ui-sans-serif,system-ui,sans-serif; }
        a { color:inherit; text-decoration:none; }
        .category-page { min-height:100vh; }
        .category-topbar { height:92px; background:rgba(255,253,251,.96); border-bottom:1px solid var(--line); display:flex; align-items:center; }
        .category-topbar-inner { width:min(1180px,calc(100% - 48px)); margin:auto; display:flex; align-items:center; gap:24px; }
        .search { flex:1; height:48px; border:1px solid var(--line); border-radius:26px; padding:0 20px; background:white; color:var(--muted); font:inherit; }
        .top-action { position:relative; width:42px; height:42px; border:1px solid var(--line); border-radius:50%; background:white; color:var(--plum); display:grid; place-items:center; }
        .top-action svg { width:20px; height:20px; }
        .cart-badge { position:absolute; top:-5px; right:-5px; min-width:18px; height:18px; padding:0 5px; display:flex; align-items:center; justify-content:center; border-radius:999px; background:#C0583A; color:#fff; border:2px solid #fff; box-sizing:content-box; font-size:10px; font-weight:700; line-height:1; }
        .cart-badge.is-empty { display:none; }
        .category-shell { width:min(1360px,calc(100% - 48px)); margin:0 auto; display:grid; grid-template-columns:245px minmax(0,1fr); gap:34px; align-items:start; }
        .category-sidebar { position:sticky; top:20px; margin-top:34px; overflow:hidden; border:1px solid var(--line); border-radius:14px; background:var(--paper); box-shadow:0 8px 22px rgba(61,27,61,.05); }
        .category-sidebar-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:16px 14px; border-bottom:1px solid var(--line); font-family:Georgia,serif; font-size:17px; }
        .category-sidebar-collapse, .mobile-category-trigger { display:grid; place-items:center; border:1px solid var(--line); border-radius:8px; background:white; color:var(--plum); cursor:pointer; }
        .category-sidebar-collapse { width:30px; height:30px; font-size:18px; }
        .category-sidebar-reset { display:flex; align-items:center; gap:8px; padding:12px 14px; border-bottom:1px solid var(--line); color:var(--plum); font-size:12px; font-weight:600; }
        .category-sidebar-reset:hover { color:var(--rose); background:#fcf3f0; }
        .category-sidebar-search { margin:12px 14px; display:flex; align-items:center; gap:7px; padding:8px 10px; border:1px solid var(--line); border-radius:999px; background:#fff; }
        .category-sidebar-search svg { width:14px; height:14px; color:var(--muted); flex:0 0 14px; }
        .category-sidebar-search input { min-width:0; width:100%; border:0; outline:0; background:transparent; color:var(--plum); font:inherit; font-size:11px; }
        .category-sidebar-search input::placeholder { color:#a99b9f; }
        .category-sidebar-path { margin:0 14px 10px; color:var(--rose); font-size:11px; font-weight:600; }
        .category-sidebar-list { padding:0; }
        .category-sidebar-count { min-width:19px; padding:3px 5px; border-radius:999px; background:#f8e8e2; color:var(--rose); font-size:10px; line-height:1; text-align:center; }
        .category-sidebar-parent .category-sidebar-count { margin-left:auto; }
        .category-sidebar-parent, .category-sidebar-leaf { width:100%; display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 14px; border:0; border-bottom:1px solid var(--line); background:transparent; color:var(--plum); font:inherit; font-size:13px; text-align:left; text-decoration:none; cursor:pointer; }
        .category-sidebar-parent:hover, .category-sidebar-leaf:hover { background:#fcf3f0; }
        .category-sidebar-parent { font-weight:600; }
        .category-sidebar-chevron { color:var(--rose); transition:transform .2s ease; }
        .category-sidebar-group.is-active-branch > .category-sidebar-parent { background:var(--plum); color:white; }
        .category-sidebar-group.is-active-branch > .category-sidebar-parent .category-sidebar-chevron { color:white; transform:rotate(180deg); }
        .category-sidebar-children { display:none; padding:4px 0; background:#fffdfb; }
        .category-sidebar-group.is-active-branch > .category-sidebar-children { display:block; }
        .category-sidebar-children .category-sidebar-parent, .category-sidebar-children .category-sidebar-leaf { padding-left:27px; font-size:12px; }
        .category-sidebar-children .category-sidebar-children .category-sidebar-parent, .category-sidebar-children .category-sidebar-children .category-sidebar-leaf { padding-left:40px; }
        .category-sidebar-leaf.is-current { border-left:3px solid var(--rose); background:#fbefea; color:var(--rose); font-weight:700; }
        .category-sidebar-collapsed { grid-template-columns:62px minmax(0,1fr); }
        .category-sidebar-collapsed .category-sidebar { width:62px; }
        .category-sidebar-collapsed .category-sidebar-title, .category-sidebar-collapsed .category-sidebar-list { display:none; }
        .category-sidebar-collapsed .category-sidebar-head { justify-content:center; }
        .mobile-category-trigger { display:none; width:42px; height:38px; margin-bottom:16px; font-size:18px; }
        .category-main { min-width:0; margin:0; padding:50px 0 70px; }
        .breadcrumb { color:var(--muted); font-size:13px; margin-bottom:18px; }
        .breadcrumb strong { color:var(--plum); }
        h1 { margin:0; font-family:Georgia,serif; font-size:44px; font-weight:500; letter-spacing:-1px; }
        .category-description { margin:10px 0 12px; color:var(--muted); }
        .filter-form { position:relative; }
        .sort-tabs { display:flex; align-items:center; gap:5px; }
        .sort-tab { display:inline-flex; align-items:center; min-height:38px; padding:0 13px; border:1px solid var(--line); border-radius:8px; background:white; color:var(--muted); font:inherit; font-size:12px; text-decoration:none; transition:background .2s ease, color .2s ease, border-color .2s ease; }
        .sort-tab:hover { border-color:var(--rose); color:var(--rose); }
        .sort-tab.is-active { border-color:var(--plum); background:var(--plum); color:white; }
        @media (max-width:640px) { .sort-tabs { width:100%; overflow-x:auto; padding-bottom:2px; } .sort-tab { white-space:nowrap; } }
        .active-filters { display:flex; gap:8px; flex-wrap:wrap; margin:0 0 12px; }
        .filter-chip { display:inline-flex; align-items:center; gap:7px; padding:7px 10px; border:1px solid #e5c9c0; border-radius:999px; background:#fff6f2; color:var(--rose); font-size:11px; }
        .filter-chip a { font-size:15px; line-height:10px; }
        .filter-panel { display:none; width:min(360px,100%); margin:-16px 0 24px; padding:18px; border:1px solid var(--line); border-radius:12px; background:white; box-shadow:0 14px 30px rgba(61,27,61,.12); }
        .filter-panel.is-open { display:block; }
        .filter-panel h2 { margin:0 0 15px; font-family:Georgia,serif; font-size:18px; font-weight:500; }
        .filter-field { margin-bottom:15px; }
        .filter-field legend, .filter-field label { display:block; margin-bottom:8px; color:var(--plum); font-size:12px; font-weight:600; }
        .price-fields { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .filter-input { width:100%; height:36px; padding:0 10px; border:1px solid var(--line); border-radius:7px; color:var(--plum); font:inherit; font-size:12px; }
        .filter-check { display:flex !important; align-items:center; gap:8px; margin:8px 0 !important; font-weight:400 !important; }
        .filter-check input { accent-color:var(--plum); }
        .discount-options { display:flex; gap:8px; flex-wrap:wrap; }
        .discount-option { display:flex; align-items:center; gap:5px; padding:7px 9px; border:1px solid var(--line); border-radius:7px; color:var(--muted); font-size:11px; font-weight:400 !important; }
        .filter-actions { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-top:4px; }
        .apply-filters { padding:9px 14px; border:0; border-radius:7px; background:var(--plum); color:white; font:inherit; font-size:12px; font-weight:600; cursor:pointer; }
        .clear-filters { color:var(--rose); font-size:12px; }
        .category-meta { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:28px; color:var(--plum); font-size:13px; }
        .back-link { color:var(--rose); font-weight:600; }
        .back-link:hover { text-decoration:underline; }
        .category-count { font-weight:600; }
        .product-actions { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-top:14px; }
        .product-actions form { margin:0; display:contents; }
        .product-action { width:100%; min-height:34px; display:grid; place-items:center; border:1px solid var(--plum); border-radius:8px; background:white; color:var(--plum); font:inherit; font-size:11px; font-weight:600; cursor:pointer; transition:background .2s ease, color .2s ease, transform .2s ease; }
        .product-action:hover { transform:translateY(-1px); }
        .product-action.primary { background:var(--plum); color:white; }
        .product-action.primary:hover { background:#54264f; }
        .product-action.added { background:#6b9b61; border-color:#6b9b61; }
        .related-section { margin-top:42px; padding-top:24px; border-top:1px solid var(--line); }
        .related-section h2 { margin:0 0 15px; font-family:Georgia,serif; font-size:21px; font-weight:500; }
        .related-links { display:flex; gap:10px; flex-wrap:wrap; }
        .related-link { display:inline-flex; align-items:center; gap:12px; padding:10px 15px; border:1px solid var(--line); border-radius:999px; background:white; color:var(--plum); font-size:12px; transition:border-color .2s ease, transform .2s ease; }
        .related-link:hover { border-color:var(--rose); transform:translateY(-1px); }
        .coming-soon { margin-left:auto; color:var(--muted); font-size:12px; }
        .empty strong { color:var(--plum); }
        .toolbar { display:flex; align-items:center; gap:10px; margin-bottom:28px; }
        .filter-button, .sort-select, .view-button { height:38px; padding:0 14px; border:1px solid var(--line); border-radius:8px; background:white; color:var(--plum); font:inherit; }
        .sort-wrap { margin-left:auto; display:flex; align-items:center; gap:10px; color:var(--muted); font-size:13px; }
        .view-button { width:38px; padding:0; display:grid; place-items:center; }
        .product-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; }
        .product-card { position:relative; background:var(--paper); border:1px solid var(--line); border-radius:12px; overflow:hidden; transition:transform .25s ease,box-shadow .25s ease; }
        .product-card:hover { transform:translateY(-4px); box-shadow:0 14px 32px rgba(61,27,61,.10); }
        .product-image { height:260px; background:linear-gradient(135deg,#f4ded7,#e7c5bb); display:grid; place-items:center; overflow:hidden; }
        .product-image img { width:100%; height:100%; object-fit:cover; }
        .product-placeholder { color:rgba(61,27,61,.48); font-family:Georgia,serif; font-size:30px; }
        .heart { position:absolute; top:12px; right:12px; width:30px; height:30px; display:grid; place-items:center; border:0; border-radius:50%; background:rgba(255,253,251,.82); color:var(--plum); }
        .heart svg { width:17px; height:17px; }
        .product-content { padding:15px; }
        .seller { color:#988b8e; font-size:11px; text-transform:uppercase; letter-spacing:.08em; }
        .product-name { margin:7px 0; font-family:Georgia,serif; font-size:17px; }
        .rating { color:var(--plum); font-size:12px; margin:8px 0; }
        .price { color:var(--rose); font-weight:700; font-size:16px; }
        .price-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
        .old-price { color:#a99b9f; font-size:12px; text-decoration:line-through; font-weight:500; }
        .sale-badge { display:inline-block; margin-top:8px; padding:4px 8px; border-radius:999px; background:#b85c3b; color:#fff; font-size:10px; font-weight:800; letter-spacing:.04em; }
        .empty { padding:70px 20px; text-align:center; border:1px dashed var(--line); border-radius:14px; color:var(--muted); background:white; }
        .pagination { margin-top:30px; }
        @media (max-width:900px) { .product-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .category-shell { width:min(100% - 32px,1180px); grid-template-columns:1fr; display:block; } .category-sidebar { position:fixed; inset:0 auto 0 0; z-index:50; width:min(310px,88vw); margin:0; border-radius:0 14px 14px 0; transform:translateX(-105%); transition:transform .25s ease; } .category-sidebar.mobile-open { transform:translateX(0); } .mobile-category-trigger { display:grid; } }
        @media (max-width:640px) { .category-topbar-inner,.category-main { width:min(100% - 28px,1180px); } .category-topbar { height:76px; } .search { min-width:0; } .product-grid { grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; } .product-image { height:190px; } h1 { font-size:34px; } .toolbar { flex-wrap:wrap; } .sort-wrap { width:100%; margin-left:0; } .category-meta { align-items:flex-start; flex-direction:column; gap:8px; } }
    </style>
</head>
<body>
<div class="category-page">
    @php
        $cartCount = session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0;
    @endphp
    <header class="category-topbar">
        <div class="category-topbar-inner">
            <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
            <input class="search" type="search" placeholder="Search skincare, makeup, fragrance..." aria-label="Search products">
            <button class="top-action" aria-label="Wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>
            <a class="top-action" href="{{ route('buyer.cart') }}" aria-label="View cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h7.9a2 2 0 0 0 1.9-1.4L21 8H7"/><circle cx="10" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
                <span class="cart-badge {{ $cartCount <= 0 ? 'is-empty' : '' }}" data-cart-count>{{ $cartCount }}</span>
            </a>
        </div>
    </header>

    @php
        $categoryTree = \App\Support\CategoryCatalog::tree();
        $filters = $filters ?? ['min_price' => null, 'max_price' => null, 'discount' => null, 'in_stock' => false, 'on_sale' => false, 'sort' => 'newest'];
    @endphp
    <div class="category-shell" id="categoryShell">
        <aside class="category-sidebar" id="categorySidebar" aria-label="Shop by category">
            <div class="category-sidebar-head">
                <span class="category-sidebar-title">Shop by category</span>
                <button type="button" class="category-sidebar-collapse" id="categorySidebarCollapse" aria-label="Collapse category menu">&lsaquo;</button>
            </div>
            <a class="category-sidebar-reset" href="{{ route('shop.index') }}"><span aria-hidden="true">▦</span><span>All Products</span></a>
            <label class="category-sidebar-search" for="categorySidebarSearch">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="6.5"/><path d="m16 16 5 5"/></svg>
                <input id="categorySidebarSearch" type="search" placeholder="Search categories..." autocomplete="off">
            </label>
            <div class="category-sidebar-path">{{ $categoryTitle ? 'Current: ' . $categoryTitle : 'Browse all categories' }}</div>
            <div class="category-sidebar-list" id="categorySidebarList">
                @include('components.category-sidebar-node', ['nodes' => $categoryTree, 'activeCategory' => $category, 'categoryCounts' => $categoryCounts ?? []])
            </div>
        </aside>

        <main class="category-main">
            <button type="button" class="mobile-category-trigger" id="mobileCategoryTrigger" aria-label="Open categories">☰</button>
        <div class="breadcrumb"><a href="{{ route('shop.index') }}">Home</a> <span>/</span> <span>{{ $categoryTitle }}</span></div>
        <h1>{{ $categoryTitle }}</h1>
        <p class="category-description">Explore selected styles and products from this collection.</p>
        <div class="category-meta">
            <span class="category-count">{{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }} found in {{ $categoryTitle }}</span>
            <a class="back-link" href="{{ route('shop.index') }}">&larr; Back to all products</a>
        </div>
        @php
            $hasFilters = $filters['min_price'] !== null || $filters['max_price'] !== null || $filters['discount'] !== null || $filters['in_stock'] || $filters['on_sale'];
        @endphp
        @if ($hasFilters)
            <div class="active-filters">
                @if ($filters['on_sale']) <span class="filter-chip">On sale <a href="{{ route('shop.index', ['category' => $category]) }}" aria-label="Clear filters">&times;</a></span> @endif
                @if ($filters['in_stock']) <span class="filter-chip">In stock <a href="{{ route('shop.index', ['category' => $category]) }}" aria-label="Clear filters">&times;</a></span> @endif
                @if ($filters['discount'] !== null) <span class="filter-chip">{{ $filters['discount'] }}% or more <a href="{{ route('shop.index', ['category' => $category]) }}" aria-label="Clear filters">&times;</a></span> @endif
                @if ($filters['min_price'] !== null || $filters['max_price'] !== null) <span class="filter-chip">Price range <a href="{{ route('shop.index', ['category' => $category]) }}" aria-label="Clear filters">&times;</a></span> @endif
            </div>
        @endif
        <form class="filter-form" method="GET" action="{{ route('shop.index') }}">
            <input type="hidden" name="category" value="{{ $category }}">
            <div class="toolbar">
                <button type="button" class="filter-button" id="filterToggle">Filter</button>
                <div class="sort-tabs" aria-label="Product sorting">
                    <a class="sort-tab {{ in_array($filters['sort'], ['popular'], true) ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => 'popular']) }}">Popular</a>
                    <a class="sort-tab {{ $filters['sort'] === 'top_sales' ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => 'top_sales']) }}">Top Sales</a>
                    <a class="sort-tab {{ in_array($filters['sort'], ['newest', 'latest'], true) ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['sort' => 'latest']) }}">Latest</a>
                </div>
                <div class="sort-wrap"><label for="sortSelect">More:</label><select class="sort-select" id="sortSelect" name="sort" onchange="this.form.submit()"><option value="newest" @selected(in_array($filters['sort'], ['newest', 'latest'], true))>Newest</option><option value="price_asc" @selected($filters['sort'] === 'price_asc')>Price: Low to high</option><option value="price_desc" @selected($filters['sort'] === 'price_desc')>Price: High to low</option><option value="discount_desc" @selected($filters['sort'] === 'discount_desc')>Highest discount</option></select><button type="button" class="view-button" aria-label="Grid view">▦</button></div>
            </div>
            <div class="filter-panel" id="filterPanel">
                <h2>Filter products</h2>
                <fieldset class="filter-field"><legend>Price range</legend><div class="price-fields"><input class="filter-input" type="number" min="0" step="0.01" name="min_price" value="{{ $filters['min_price'] }}" placeholder="₱0"><input class="filter-input" type="number" min="0" step="0.01" name="max_price" value="{{ $filters['max_price'] }}" placeholder="₱5,000"></div></fieldset>
                <fieldset class="filter-field"><legend>Availability</legend><label class="filter-check"><input type="checkbox" name="in_stock" value="1" @checked($filters['in_stock'])> In stock</label><label class="filter-check"><input type="checkbox" name="on_sale" value="1" @checked($filters['on_sale'])> On sale</label></fieldset>
                <fieldset class="filter-field"><legend>Discount</legend><div class="discount-options"><label class="discount-option"><input type="radio" name="discount" value="10" @checked((string) $filters['discount'] === '10')> 10% or more</label><label class="discount-option"><input type="radio" name="discount" value="20" @checked((string) $filters['discount'] === '20')> 20% or more</label></div></fieldset>
                <div class="filter-actions"><a class="clear-filters" href="{{ route('shop.index', ['category' => $category]) }}">Clear all</a><button class="apply-filters" type="submit">Apply filters</button></div>
            </div>
        </form>

        @if ($products->count())
            <div class="product-grid">
                @foreach ($products as $product)
                    <article class="product-card">
                        <button class="heart" aria-label="Add {{ $product->name }} to wishlist"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg></button>
                        <div class="product-image">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                        </div>
                        <div class="product-content">
                            <div class="seller">Lumora seller</div>
                            <h2 class="product-name">{{ $product->name }}</h2>
                            @php $rating = round((float) ($product->reviews_avg_rating ?? 0), 1); $reviewCount = (int) ($product->reviews_count ?? 0); $salesCount = (int) ($product->sales_count ?? 0); @endphp
                            <div class="rating">{!! str_repeat('&#9733;', (int) round($rating)) !!}{!! str_repeat('&#9734;', 5 - (int) round($rating)) !!} <span>{{ number_format($rating, 1) }} ({{ $reviewCount }})</span></div>
                            @if (($filters['sort'] ?? '') === 'top_sales' && $salesCount > 0)<div class="sales-note">{{ number_format($salesCount) }} sold</div>@endif
                            @php
                                $discountPercent = (float) ($product->discount_percent ?? 0);
                                $originalPrice = (float) $product->price;
                                $finalPrice = $discountPercent > 0
                                    ? $originalPrice * (1 - ($discountPercent / 100))
                                    : $originalPrice;
                            @endphp
                            <div class="price-row">
                                <div class="price">₱{{ number_format($finalPrice, 2) }}</div>
                                @if ($discountPercent > 0)
                                    <div class="old-price">₱{{ number_format($originalPrice, 2) }}</div>
                                @endif
                            </div>
                            @if ($discountPercent > 0)
                                <span class="sale-badge">{{ rtrim(rtrim(number_format($discountPercent, 1), '0'), '.') }}% OFF</span>
                            @endif
                            <div class="product-actions">
                                <a class="product-action" href="{{ route('shop.product', ['id' => $product->id]) }}">View product</a>
                                <form method="POST" action="{{ route('buyer.cart.add', ['product' => $product->id]) }}" class="lumora-cart-form" data-cart-product-name="{{ $product->name }}" data-cart-product-price="{{ $finalPrice }}" data-cart-product-image="{{ $product->image_url }}">
                                    @csrf
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="product-action primary">Add to cart</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="pagination">{{ $products->links() }}</div>
        @else
            <div class="empty">No products were found in <strong>{{ $categoryTitle }}</strong> yet.</div>
        @endif

        @php
            $relatedCategories = match ($category) {
                'mens-shoes' => [
                    ['label' => "Men's Clothing", 'slug' => 'mens-clothing'],
                    ['label' => "Men's Accessories", 'slug' => 'mens-accessories'],
                    ['label' => "Men's Skincare", 'slug' => 'mens-skincare'],
                ],
                'womens-shoes' => [
                    ['label' => "Women's Clothing", 'slug' => 'womens-clothing'],
                    ['label' => "Women's Bags", 'slug' => 'womens-bags'],
                    ['label' => "Women's Accessories", 'slug' => 'womens-accessories'],
                ],
                default => [
                    ['label' => "Men's Clothing", 'slug' => 'mens-clothing'],
                    ['label' => "Women's Clothing", 'slug' => 'womens-clothing'],
                    ['label' => 'Makeup', 'slug' => 'makeup'],
                ],
            };
        @endphp
        <section class="related-section" aria-label="Related categories">
            <h2>Explore related categories</h2>
            <div class="related-links">
                @foreach ($relatedCategories as $related)
                    <a class="related-link" href="{{ route('shop.index', ['category' => $related['slug']]) }}">
                        <span>{{ $related['label'] }}</span><span aria-hidden="true">&rarr;</span>
                    </a>
                @endforeach
                @if (!$products->count())
                    <span class="coming-soon">More styles are coming soon.</span>
                @endif
            </div>
        </section>
        </main>
    </div>
    @include('components.add-to-cart-success-modal')
    <script>
        const categorySidebar = document.getElementById('categorySidebar');
        const categorySidebarCollapse = document.getElementById('categorySidebarCollapse');
        const mobileCategoryTrigger = document.getElementById('mobileCategoryTrigger');
        document.getElementById('filterToggle')?.addEventListener('click', function () {
            document.getElementById('filterPanel')?.classList.toggle('is-open');
        });
        categorySidebarCollapse?.addEventListener('click', function () {
            document.getElementById('categoryShell').classList.toggle('category-sidebar-collapsed');
        });
        mobileCategoryTrigger?.addEventListener('click', function () {
            categorySidebar.classList.add('mobile-open');
        });
        const categorySearch = document.getElementById('categorySidebarSearch');
        categorySearch?.addEventListener('input', function () {
            const query = categorySearch.value.trim().toLowerCase();
            document.querySelectorAll('[data-sidebar-group], .category-sidebar-leaf').forEach(function (item) {
                const text = item.textContent.toLowerCase();
                const hasMatchingChild = item.querySelector && Array.from(item.querySelectorAll('.category-sidebar-leaf, [data-sidebar-group]')).some(function (child) {
                    return child.textContent.toLowerCase().includes(query);
                });
                const matches = !query || text.includes(query) || hasMatchingChild;
                item.style.display = matches ? '' : 'none';
                if (query && hasMatchingChild) item.classList.add('is-active-branch');
            });
        });
        document.querySelectorAll('[data-sidebar-toggle]').forEach(function (toggle) {
            toggle.addEventListener('click', function () {
                const group = toggle.closest('[data-sidebar-group]');
                if (!group) return;

                // Only one category branch may be open at each menu level.
                const siblings = Array.from(group.parentElement.children).filter(function (element) {
                    return element !== group && element.hasAttribute('data-sidebar-group');
                });

                siblings.forEach(function (sibling) {
                    sibling.classList.remove('is-active-branch');
                    const siblingToggle = sibling.querySelector(':scope > .category-sidebar-parent');
                    if (siblingToggle) siblingToggle.setAttribute('aria-expanded', 'false');
                });

                const expanded = group.classList.toggle('is-active-branch');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        });
        categorySidebar?.querySelectorAll('.category-sidebar-leaf').forEach(function (leaf) {
            leaf.addEventListener('click', function () {
                if (window.innerWidth <= 900) categorySidebar.classList.remove('mobile-open');
            });
        });
        document.addEventListener('click', function (event) {
            if (window.innerWidth <= 900 && categorySidebar?.classList.contains('mobile-open') && !categorySidebar.contains(event.target) && event.target !== mobileCategoryTrigger) {
                categorySidebar.classList.remove('mobile-open');
            }
        });
    </script>
</div>
</body>
</html>
