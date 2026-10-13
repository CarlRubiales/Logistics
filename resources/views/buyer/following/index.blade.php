@php
    $cartCount = session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lumora | Following</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--background:#F7F1EC;--card:#FFFDFC;--primary:#3B1E34;--accent:#C98F72;--border:#EAE3DD;--text:#2F2528;--muted:#8B7B78;--hover:#F8F4F1;--active:#F5ECE6}
        *{box-sizing:border-box}
        body{margin:0;background:var(--background);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif}
        a{color:inherit;text-decoration:none}
        .profile-topbar{position:sticky;top:0;z-index:30;background:var(--card);border-bottom:1px solid var(--border)}
        .profile-topbar-inner{max-width:1480px;margin:0 auto;padding:15px 40px;display:flex;align-items:center;gap:24px}
        .profile-logo{display:inline-flex;flex-direction:column;color:var(--primary)}
        .profile-logo-brand{font-family:'Playfair Display',Georgia,serif;font-size:31px;line-height:1;text-transform:uppercase;letter-spacing:.32em}.profile-logo-brand span{color:var(--accent)}
        .profile-logo-tagline{margin-top:4px;color:var(--muted);font-size:8px;letter-spacing:.4em;text-transform:uppercase}
        .topbar-actions{margin-left:auto;display:flex;gap:12px}.topbar-action{position:relative;width:38px;height:38px;display:grid;place-items:center;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary)}.topbar-badge{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;display:grid;place-items:center;border-radius:999px;background:var(--accent);color:white;font-size:10px;font-weight:700}
        .profile-page{max-width:1480px;margin:0 auto;padding:48px 40px 72px}.profile-layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:32px;align-items:start}
        .profile-sidebar{position:sticky;top:110px}.profile-sidebar-card{border:1px solid var(--border);border-radius:18px;background:var(--card);padding:10px}.profile-nav{display:grid;gap:6px}.profile-nav-section{display:grid;gap:6px}.profile-nav-section+.profile-nav-section{margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}.profile-nav-section-title{padding:4px 13px;color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}.profile-nav-item,.logout-nav-button{width:100%;height:48px;display:flex;align-items:center;gap:11px;border:0;border-left:3px solid transparent;border-radius:12px;background:transparent;padding:0 13px;color:var(--muted);font-size:14px;font-weight:500;text-align:left}.profile-nav-item.active{border-left-color:var(--accent);background:var(--active);color:var(--primary)}.profile-nav-icon{width:18px;height:18px;display:grid;place-items:center;flex:0 0 auto}.profile-nav-icon svg{width:17px;height:17px}.profile-nav-label{min-width:0;flex:1}.profile-nav-badge{min-width:20px;height:20px;display:grid;place-items:center;border-radius:999px;background:var(--accent);padding:0 6px;color:white;font-size:11px;font-weight:700}.logout-nav-form{margin:8px 0 0;padding-top:10px;border-top:1px solid var(--border)}
        h1{margin:0;color:var(--primary);font:600 46px/1.05 'Playfair Display',Georgia,serif}.page-copy{margin:10px 0 24px;color:var(--muted);font-size:15px}
        .list-card{border:1px solid var(--border);border-radius:18px;background:var(--card);overflow:hidden}
        .shop-row{display:grid;grid-template-columns:54px minmax(0,1fr) auto;gap:13px;align-items:center;padding:16px 18px;border-bottom:1px solid var(--border)}.shop-row:last-child{border-bottom:0}.shop-row:hover{background:var(--hover)}
        .shop-avatar{width:54px;height:54px;display:grid;place-items:center;overflow:hidden;border:1px solid var(--border);border-radius:50%;background:#F8EEE9;color:var(--primary);font:700 20px 'Playfair Display',Georgia,serif}.shop-avatar img{width:100%;height:100%;object-fit:cover}
        .shop-name{color:var(--primary);font-weight:800}.shop-sub{margin-top:4px;color:var(--muted);font-size:13px}
        .row-actions{display:flex;gap:8px;align-items:center}.row-actions form{margin:0}
        .row-btn{height:38px;padding:0 16px;display:inline-flex;align-items:center;border:1px solid var(--accent);border-radius:10px;background:var(--card);color:var(--primary);font-size:12px;font-weight:700;cursor:pointer}
        .row-btn.primary{border-color:var(--primary);background:var(--primary);color:#fff}
        .empty-state{padding:54px 24px;text-align:center;color:var(--muted)}.empty-state strong{display:block;margin-bottom:7px;color:var(--primary);font:600 25px 'Playfair Display',Georgia,serif}
        .flash{margin-bottom:16px;padding:12px 16px;border:1px solid var(--border);border-radius:12px;background:var(--card);color:var(--primary);font-size:14px}
        @media(max-width:900px){.profile-page{padding:32px 20px}.profile-layout{display:block}.profile-sidebar{position:static;margin-bottom:20px}.profile-sidebar-card{overflow-x:auto}.profile-nav{display:flex;width:max-content}.profile-nav-section{display:flex}.profile-nav-section-title{display:none}.profile-nav-section+.profile-nav-section{margin:0 0 0 8px;padding:0 0 0 12px;border-top:0;border-left:1px solid var(--border)}.logout-nav-form{margin:0;padding:0;border:0}.profile-nav-item,.logout-nav-button{width:auto}.profile-topbar-inner{padding:14px 20px}.profile-logo-brand{font-size:24px}.shop-row{grid-template-columns:46px minmax(0,1fr)}.row-actions{grid-column:2}h1{font-size:34px}}
    </style>
</head>
<body>
<header class="profile-topbar">
    <div class="profile-topbar-inner">
        <a href="{{ route('shop.index') }}" class="profile-logo" aria-label="Lumora shop"><span class="profile-logo-brand">LUM<span>O</span>RA</span><span class="profile-logo-tagline">Beauty lives here</span></a>
        <div class="topbar-actions">
            <a href="{{ route('buyer.cart') }}" class="topbar-action" aria-label="Cart"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.1 2.1h3l2.7 12.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6"/></svg>@if ($cartCount > 0)<span class="topbar-badge">{{ $cartCount }}</span>@endif</a>
        </div>
    </div>
</header>

<main class="profile-page">
    <div class="profile-layout">
        @include('profile.partials.account-sidebar', ['active' => 'following'])
        <section>
            <h1>Following</h1>
            <p class="page-copy">Shops you follow on Lumora.</p>
            @if (session('success'))
                <div class="flash">{{ session('success') }}</div>
            @endif
            <div class="list-card">
                @forelse ($shops as $shop)
                    @php
                        $application = $shop->approvedSellerApplication ?: $shop->sellerApplication;
                        $name = $shop->shop_name ?: ($application?->business_name ?: $shop->name);
                        $initials = collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') ?: 'LS';
                    @endphp
                    <div class="shop-row">
                        <div class="shop-avatar">@if ($shop->avatar)<img src="{{ $shop->avatar }}" alt="">@else{{ mb_strtoupper($initials) }}@endif</div>
                        <div>
                            <div class="shop-name">{{ $name }}</div>
                            <div class="shop-sub">Followed {{ $shop->pivot->created_at?->diffForHumans() }}</div>
                        </div>
                        <div class="row-actions">
                            <a class="row-btn primary" href="{{ route('shop.seller', $shop->id) }}">Visit Shop</a>
                            <form method="POST" action="{{ route('buyer.shops.follow', $shop->id) }}">
                                @csrf
                                <button type="submit" class="row-btn">Unfollow</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <strong>No followed shops yet</strong>
                        Tap "Follow Shop" on a seller's page to see it here.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</main>
</body>
</html>

