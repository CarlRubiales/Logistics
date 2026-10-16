@php
    $fullName = trim($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')));
    $initials = collect(explode(' ', $fullName ?: 'Lumora Buyer'))
        ->filter()
        ->take(2)
        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
        ->implode('');
    $memberSince = optional($user->created_at)->format('F Y') ?: 'Recently';
    $isGoogleAccount = $user->provider === 'google' || filled($user->google_id);
    $cartCount = session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumora | My Profile</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root{--background:#F7F1EC;--card:#FFFDFC;--primary:#3B1E34;--accent:#C98F72;--border:#EAE3DD;--text:#2F2528;--muted:#8B7B78;--success:#6F8F78;--hover:#F8F4F1;--active:#F5ECE6}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--background);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif;font-size:16px;letter-spacing:0}
        h1,h2,h3{font-family:'Playfair Display',Georgia,serif;color:var(--primary);font-weight:600;letter-spacing:0}
        a{text-decoration:none;color:inherit}
        button,input,select{font:inherit}
        button{cursor:pointer}
        .profile-topbar{position:sticky;top:0;z-index:30;background:var(--card);border-bottom:1px solid var(--border)}
        .profile-topbar-inner{max-width:1480px;margin:0 auto;padding:15px 40px;display:flex;align-items:center;gap:24px}
        .topbar-search{flex:1;max-width:520px;min-height:40px;display:flex;align-items:center;gap:9px;border:1px solid var(--border);border-radius:999px;background:var(--card);padding:0 16px;color:var(--muted);font-size:13px}
        .topbar-search input{width:100%;border:0;background:transparent;outline:0;color:var(--text)}
        .topbar-search svg,.topbar-action svg{width:17px;height:17px}
        .topbar-actions{margin-left:auto;display:flex;align-items:center;gap:12px}
        .topbar-action{position:relative;width:38px;height:38px;display:grid;place-items:center;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary);transition:background-color .15s ease,border-color .15s ease,color .15s ease}
        .topbar-badge{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;display:grid;place-items:center;border-radius:999px;background:var(--accent);color:var(--card);font-size:10px;font-weight:600}
        .mobile-menu-button{display:none;width:40px;height:40px;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary)}
        .profile-logo{display:inline-flex;flex-direction:column;align-items:flex-start;justify-content:center;flex-shrink:0;color:var(--primary);transition:opacity .15s ease}
        .profile-logo-brand{font-family:'Playfair Display',Georgia,serif;font-size:31px;font-weight:500;line-height:1;text-transform:uppercase;letter-spacing:.32em;color:var(--primary)}
        .profile-logo-brand span{color:var(--accent)}
        .profile-logo-tagline{margin-top:4px;font-size:8px;font-weight:500;line-height:1;text-transform:uppercase;letter-spacing:.4em;color:var(--muted)}
        .profile-page{max-width:1480px;margin:0 auto;padding:48px 40px 72px}
        .profile-layout{display:grid;grid-template-columns:260px minmax(0,1fr);gap:32px;align-items:start}
        .profile-sidebar{position:sticky;top:110px;height:fit-content}
        .profile-sidebar-card{border:1px solid var(--border);border-radius:18px;background:var(--card);padding:10px}
        .profile-nav{display:grid;gap:6px}
        .profile-nav-section{display:grid;gap:6px}
        .profile-nav-section + .profile-nav-section{margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
        .profile-nav-section-title{padding:4px 13px;color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}
        .profile-nav-item,.logout-nav-button{width:100%;height:48px;display:flex;align-items:center;gap:11px;border:0;border-left:3px solid transparent;border-radius:12px;background:transparent;padding:0 13px;color:var(--muted);font-size:14px;font-weight:500;text-align:left;transition:background-color .2s ease,border-color .2s ease,color .2s ease}
        .profile-nav-item.active{border-left-color:var(--accent);background:var(--active);color:var(--primary)}
        .profile-nav-icon{width:18px;height:18px;display:grid;place-items:center;flex:0 0 auto}
        .profile-nav-icon svg{width:17px;height:17px}
        .profile-nav-label{min-width:0;flex:1}
        .profile-nav-badge{min-width:20px;height:20px;display:grid;place-items:center;border-radius:999px;background:var(--accent);padding:0 6px;color:var(--card);font-size:11px;font-weight:700}
        .logout-nav-form{margin:8px 0 0;padding-top:10px;border-top:1px solid var(--border)}
        .profile-content{min-width:0}
        .profile-scroll-section{scroll-margin-top:130px}
        .profile-scroll-anchor{display:block;height:1px;overflow:hidden;scroll-margin-top:130px}
        .profile-heading{margin:0;color:var(--primary);font-size:56px;line-height:1}
        .profile-description{max-width:560px;margin:13px 0 24px;color:var(--muted);font-size:16px;line-height:1.7}
        .profile-summary-card{height:110px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:24px;border:1px solid var(--border);border-radius:18px;background:var(--card);padding:19px 28px;animation:fadeUp .2s ease both}
        .summary-person{display:flex;align-items:center;gap:18px;min-width:0}
        .summary-avatar{width:72px;height:72px;display:grid;place-items:center;flex:0 0 auto;overflow:hidden;border:1px solid var(--border);border-radius:999px;background:var(--background);color:var(--primary);font:600 24px 'Playfair Display',Georgia,serif}
        .summary-avatar img{width:100%;height:100%;object-fit:cover}
        .summary-name{margin:0;color:var(--primary);font:600 24px/1.15 'Playfair Display',Georgia,serif}
        .summary-meta{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin-top:10px;color:var(--muted);font-size:12px}
        .buyer-badge{display:inline-flex;align-items:center;min-height:25px;border:1px solid var(--border);border-radius:999px;background:var(--card);padding:0 10px;color:var(--primary);font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase}
        .profile-actions-top{display:flex;align-items:center;gap:10px;flex:0 0 auto}
        .btn-primary,.btn-secondary{min-width:150px;min-height:40px;display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:999px;padding:0 18px;font-size:13px;font-weight:600;letter-spacing:0;box-shadow:none;transition:background-color .15s ease,border-color .15s ease,color .15s ease,filter .15s ease}
        .btn-primary{border:1px solid var(--primary);background:var(--primary);color:var(--card)}
        .btn-secondary{border:1px solid var(--border);background:var(--card);color:var(--primary)}
        .btn-primary svg,.btn-secondary svg{width:16px;height:16px}
        .profile-form-stack{display:grid;gap:24px}
        .profile-section-card{border:1px solid var(--border);border-radius:18px;background:var(--card);padding:28px;animation:fadeUp .2s ease both}
        .profile-section-card:nth-child(2){animation-delay:.03s}
        .profile-section-card:nth-child(3){animation-delay:.06s}
        .profile-section-card:nth-child(4){animation-delay:.09s}
        .section-card-head{margin-bottom:24px;padding-bottom:18px;border-bottom:1px solid var(--border)}
        .section-card-head h2{margin:0;font-size:30px;line-height:1.15}
        .section-card-head p{margin:7px 0 0;color:var(--muted);font-size:13px;line-height:1.6}
        .profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px}
        .profile-grid.address-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
        .profile-field{min-width:0}
        .profile-field label{display:block;margin-bottom:8px;color:var(--muted);font-size:13px;font-weight:500}
        .profile-input,.profile-select{width:100%;height:48px;border:1px solid var(--border);border-radius:14px;background:var(--card);padding:0 14px;color:var(--text);font-size:16px;outline:none;transition:border-color .15s ease,box-shadow .15s ease,background-color .15s ease}
        .profile-input:focus,.profile-select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(201,143,114,.16);background:var(--card)}
        .profile-input[readonly],.profile-select:disabled{background:var(--background);color:var(--muted);cursor:not-allowed}
        .profile-field-error{display:block;margin-top:6px;color:#dc2626;font-size:13px;line-height:1.4}
        .profile-radio-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0;border:1px solid var(--border);border-radius:14px;background:var(--card);padding:3px}
        .profile-radio{position:relative;display:block;margin:0}
        .profile-radio input{position:absolute;opacity:0;pointer-events:none}
        .profile-radio + .profile-radio{border-left:1px solid var(--border)}
        .profile-radio span{height:40px;display:flex;align-items:center;justify-content:center;border:1px solid transparent;border-radius:11px;background:transparent;color:var(--text);font-size:13px;font-weight:500;transition:background-color .15s ease,border-color .15s ease,color .15s ease,box-shadow .15s ease}
        .profile-radio input:checked + span{border-color:var(--accent);background:var(--active);color:var(--primary)}
        .profile-radio input:focus-visible + span{box-shadow:0 0 0 3px rgba(201,143,114,.16)}
        .contact-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:24px;align-items:start}
        .google-badge{display:inline-flex;align-items:center;gap:6px;width:max-content;margin-top:10px;border:0;border-radius:999px;background:rgba(111,143,120,.12);padding:5px 10px;color:var(--success);font-size:13px;font-weight:600}
        .google-badge svg{width:13px;height:13px}
        .email-note{margin-top:9px;color:var(--muted);font-size:13px;line-height:1.5}
        .email-note button{border:0;background:transparent;color:var(--primary);padding:0;text-decoration:underline;text-underline-offset:3px}
        .form-footer{display:flex;align-items:center;justify-content:flex-end;gap:14px;margin-top:24px;padding-top:22px;border-top:1px solid var(--border)}
        .saved-message{color:var(--success);font-size:13px}
        .password-actions{display:flex;align-items:center;justify-content:flex-end;gap:14px;margin-top:22px}
        .alert{margin-bottom:18px;border:1px solid var(--border);border-radius:14px;background:var(--card);padding:13px 16px;color:var(--muted);font-size:13px;line-height:1.5}
        .alert-success{border-color:var(--border);background:rgba(111,143,120,.12);color:var(--success)}
        .section-empty-note{margin:0;color:var(--muted);font-size:14px;line-height:1.7}
        .drawer-backdrop{position:fixed;inset:0;z-index:40;display:none;background:rgba(47,37,40,.25)}
        .drawer-backdrop.open{display:block}
        .profile-page svg,.profile-topbar svg{stroke-width:1.75}
        @keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
        @media(hover:hover) and (pointer:fine){.profile-logo:hover{opacity:.9}.topbar-action:hover{background:var(--hover);border-color:var(--accent);color:var(--primary)}.profile-nav-item:hover,.logout-nav-button:hover{background:var(--hover);color:var(--primary)}.btn-primary:hover{filter:brightness(1.12)}.btn-secondary:hover{border-color:var(--border);background:var(--hover);color:var(--primary)}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.profile-nav-item,.logout-nav-button{transition:none}}
        @media(max-width:1279px) and (min-width:1024px){.profile-topbar-inner{padding-right:32px;padding-left:32px}.profile-page{padding:48px 32px 72px}.profile-layout{grid-template-columns:220px minmax(0,1fr);gap:24px}.profile-heading{font-size:48px}.profile-summary-card{padding-right:24px;padding-left:24px}.summary-avatar{width:64px;height:64px;font-size:22px}.profile-grid,.contact-row{gap:20px}.btn-primary,.btn-secondary{min-width:180px}}
        @media(max-width:1023px) and (min-width:768px){body{font-size:15px}.profile-topbar-inner{padding:14px 24px}.profile-page{padding:40px 24px 64px}.profile-layout{display:block}.profile-sidebar{position:static;width:100%;height:auto;margin-bottom:24px}.profile-sidebar-card{display:flex;align-items:center;overflow-x:auto;padding:8px;border-radius:18px}.profile-nav{display:flex;flex:0 0 auto;gap:8px;width:max-content}.profile-nav-section{display:flex;align-items:center;gap:8px}.profile-nav-section + .profile-nav-section{margin-top:0;margin-left:8px;padding-top:0;padding-left:12px;border-top:0;border-left:1px solid var(--border)}.profile-nav-section-title{display:none}.logout-nav-form{flex:0 0 auto;margin:0;padding:0;border:0}.profile-nav-item,.logout-nav-button{width:auto;min-width:max-content;padding:0 14px;border-left:0}.profile-nav-item.active{box-shadow:inset 0 -3px 0 var(--accent)}.profile-heading{font-size:42px}.profile-description{font-size:15px}.profile-summary-card{height:auto;align-items:flex-start;flex-direction:column;gap:18px;padding:24px;margin-bottom:20px}.summary-avatar{width:64px;height:64px;font-size:22px}.summary-name{font-size:22px}.profile-actions-top{width:auto}.profile-form-stack{gap:20px}.profile-section-card{padding:24px}.section-card-head{margin-bottom:20px}.section-card-head h2{font-size:26px}.profile-grid,.profile-grid.address-grid,.contact-row{grid-template-columns:1fr;gap:20px}.profile-input,.profile-select{font-size:15px}.btn-primary,.btn-secondary{min-width:180px}.form-footer,.password-actions{align-items:flex-end}.drawer-backdrop{display:none!important}}
        @media(max-width:767px){body{font-size:15px}.mobile-menu-button{display:grid;place-items:center}.topbar-search{display:none}.profile-topbar-inner{padding:13px 20px;gap:16px}.profile-logo-brand{font-size:24px;letter-spacing:.28em}.profile-logo-tagline{font-size:7px;letter-spacing:.32em}.profile-page{padding:24px 20px 48px}.profile-layout{display:block}.profile-sidebar{position:fixed;top:0;left:0;bottom:0;z-index:50;width:280px;height:100%;padding:0;background:var(--card);border-right:1px solid var(--border);transform:translateX(-105%);transition:transform .25s ease-out}.profile-sidebar.open{transform:translateX(0)}.profile-sidebar-card{height:100%;overflow-y:auto;border:0;border-radius:0;background:var(--card);padding:20px 12px}.profile-nav-label{display:inline}.profile-nav-item,.logout-nav-button{justify-content:flex-start;padding:0 13px;border-left:3px solid transparent}.profile-nav-item.active{box-shadow:none}.profile-heading{font-size:36px}.profile-description{font-size:15px;margin-bottom:20px}.profile-summary-card{width:100%;height:auto;min-height:0;align-items:center;flex-direction:column;gap:18px;margin-bottom:20px;padding:20px;text-align:center}.summary-person{align-items:center;flex-direction:column;gap:12px}.summary-avatar{width:60px;height:60px;font-size:20px}.summary-name{font-size:20px}.summary-meta{align-items:center;flex-direction:column;justify-content:center;gap:7px}.profile-actions-top{width:100%;justify-content:center}.profile-form-stack{gap:20px}.profile-section-card{width:100%;padding:20px;border-radius:18px}.section-card-head{margin-bottom:18px;padding-bottom:16px}.section-card-head h2{font-size:24px}.profile-grid,.contact-row,.profile-grid.address-grid{grid-template-columns:1fr;gap:18px}.profile-input,.profile-select{font-size:15px}.form-footer,.password-actions{align-items:stretch;flex-direction:column-reverse;margin-top:20px;padding-top:18px}.btn-primary,.btn-secondary{width:100%;max-width:100%;min-width:0;min-height:48px}.topbar-action:active,.btn-primary:active,.btn-secondary:active,.profile-nav-item:active,.logout-nav-button:active{background:var(--active)}}
        @media(max-width:420px){.profile-topbar-inner{gap:12px}.profile-logo-brand{font-size:21px;letter-spacing:.24em}.profile-logo-tagline{letter-spacing:.26em}.topbar-actions{gap:8px}.topbar-action,.mobile-menu-button{width:38px;height:38px}.profile-radio-row{grid-template-columns:1fr}.profile-radio + .profile-radio{border-left:0;border-top:1px solid var(--border)}}
    </style>
</head>
<body>
<header class="profile-topbar">
    <div class="profile-topbar-inner">
        <button type="button" class="mobile-menu-button" data-profile-menu aria-label="Open profile menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
        </button>
        <a href="{{ route('shop.index') }}" class="profile-logo" aria-label="Lumora shop">
            <span class="profile-logo-brand">LUM<span>O</span>RA</span>
            <span class="profile-logo-tagline">Beauty lives here</span>
        </a>
        <form action="{{ route('shop.index') }}" method="GET" class="topbar-search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m21 21-4.3-4.3"/><circle cx="11" cy="11" r="7"/></svg>
            <input type="search" name="search" placeholder="Search Lumora" aria-label="Search Lumora">
        </form>
        <div class="topbar-actions">
            <a href="#" class="topbar-action" aria-label="Wishlist">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
            </a>
            <a href="{{ route('buyer.cart') }}" class="topbar-action" aria-label="Cart">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.1 2.1h3l2.7 12.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6"/></svg>
                @if ($cartCount > 0)<span class="topbar-badge">{{ $cartCount }}</span>@endif
            </a>
        </div>
    </div>
</header>

<div class="drawer-backdrop" data-profile-backdrop></div>

<main class="profile-page" id="profile-shell">
    <div class="profile-layout">
        @include('profile.partials.account-sidebar', ['active' => 'profile'])

        <section class="profile-content">
            <section id="profile" class="profile-scroll-section" data-profile-section>
                <h1 class="profile-heading">My Profile</h1>
                <p class="profile-description">Manage your personal information and account preferences.</p>

                @if (session('status') === 'profile-updated')
                    <div class="alert alert-success">Your profile has been updated.</div>
                @endif

                <article class="profile-summary-card">
                    <div class="summary-person">
                        <div class="summary-avatar">
                            @if ($user->avatar)
                                <img src="{{ $user->avatar }}" alt="{{ $fullName ?: 'Lumora Buyer' }}">
                            @else
                                <span>{{ $initials ?: 'LB' }}</span>
                            @endif
                        </div>
                        <div>
                            <h2 class="summary-name">{{ $fullName ?: 'Lumora Buyer' }}</h2>
                            <div class="summary-meta">
                                <span class="buyer-badge">Buyer</span>
                                <span>Member since {{ $memberSince }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-actions-top">
                        <a href="#personal-information" class="btn-secondary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                            Edit Profile
                        </a>
                    </div>
                </article>
            </section>

            <section id="wishlist" class="profile-scroll-anchor" data-profile-section tabindex="-1" aria-label="Wishlist"></section>

            @include('profile.partials.update-profile-information-form', ['isGoogleAccount' => $isGoogleAccount])
            @include('profile.partials.update-shipping-address-form')
            @include('profile.partials.update-password-form')
        </section>
    </div>
</main>

@auth
    @include('components.chat-widget')
@endauth

</body>
</html>
