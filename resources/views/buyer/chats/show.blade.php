@php
    $cartCount = session('lumora_cart') ? collect(session('lumora_cart'))->sum('quantity') : 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Messages with {{ $sellerProfile['name'] }} &middot; {{ config('app.name', 'Lumora') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--background:#F7F1EC;--card:#FFFDFC;--primary:#3B1E34;--accent:#C98F72;--border:#EAE3DD;--text:#2F2528;--muted:#8B7B78;--success:#6F8F78;--hover:#F8F4F1;--active:#F5ECE6}
        *{box-sizing:border-box}body{margin:0;background:var(--background);color:var(--text);font-family:Inter,ui-sans-serif,system-ui,sans-serif}a{color:inherit;text-decoration:none}button,textarea{font:inherit}
        .profile-topbar{position:sticky;top:0;z-index:30;background:var(--card);border-bottom:1px solid var(--border)}.profile-topbar-inner{max-width:1480px;margin:0 auto;padding:15px 40px;display:flex;align-items:center;gap:24px}.profile-logo{display:inline-flex;flex-direction:column;color:var(--primary)}.profile-logo-brand{font-family:'Playfair Display',Georgia,serif;font-size:31px;line-height:1;text-transform:uppercase;letter-spacing:.32em}.profile-logo-brand span{color:var(--accent)}.profile-logo-tagline{margin-top:4px;color:var(--muted);font-size:8px;letter-spacing:.4em;text-transform:uppercase}.topbar-actions{margin-left:auto;display:flex;gap:12px}.topbar-action svg{width:18px;height:18px;display:block}.topbar-action{position:relative;width:38px;height:38px;display:grid;place-items:center;border:1px solid var(--border);border-radius:999px;background:var(--card);color:var(--primary)}.topbar-badge{position:absolute;top:-5px;right:-5px;min-width:17px;height:17px;display:grid;place-items:center;border-radius:999px;background:var(--accent);color:white;font-size:10px;font-weight:700}
        .profile-page{max-width:1480px;margin:0 auto;padding:34px 40px 60px}.profile-layout{display:grid;grid-template-columns:230px minmax(0,1fr);gap:24px;align-items:start}.profile-sidebar{position:sticky;top:104px}.profile-sidebar-card{border:1px solid var(--border);border-radius:18px;background:var(--card);padding:10px}.profile-nav{display:grid;gap:6px}.profile-nav-section{display:grid;gap:6px}.profile-nav-section+.profile-nav-section{margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}.profile-nav-section-title{padding:4px 13px;color:var(--muted);font-size:11px;font-weight:700;letter-spacing:.16em;text-transform:uppercase}.profile-nav-item,.logout-nav-button{width:100%;height:48px;display:flex;align-items:center;gap:11px;border:0;border-left:3px solid transparent;border-radius:12px;background:transparent;padding:0 13px;color:var(--muted);font-size:14px;font-weight:500;text-align:left}.profile-nav-item.active{border-left-color:var(--accent);background:var(--active);color:var(--primary)}.profile-nav-icon{width:18px;height:18px;display:grid;place-items:center;flex:0 0 auto}.profile-nav-icon svg{width:17px;height:17px}.profile-nav-label{min-width:0;flex:1}.profile-nav-badge{min-width:20px;height:20px;display:grid;place-items:center;border-radius:999px;background:var(--accent);padding:0 6px;color:white;font-size:11px;font-weight:700}.logout-nav-form{margin:8px 0 0;padding-top:10px;border-top:1px solid var(--border)}
        .chat-wrap{min-height:calc(100vh - 172px);display:grid;grid-template-columns:300px minmax(0,1fr) 260px;border:1px solid var(--border);border-radius:20px;background:var(--card);overflow:hidden}.conversation-list{border-right:1px solid var(--border);overflow-y:auto}.chat-title{padding:18px;border-bottom:1px solid var(--border)}.chat-title h1{margin:0;color:var(--primary);font:600 28px/1.1 'Playfair Display',Georgia,serif}.chat-title p{margin:6px 0 0;color:var(--muted);font-size:13px}.conversation-row{display:grid;grid-template-columns:44px minmax(0,1fr) auto;gap:10px;align-items:center;padding:13px 14px;border-bottom:1px solid var(--border)}.conversation-row:hover,.conversation-row.active{background:var(--hover)}.avatar{width:44px;height:44px;display:grid;place-items:center;overflow:hidden;border:1px solid var(--border);border-radius:50%;background:#F8EEE9;color:var(--primary);font:700 17px 'Playfair Display',Georgia,serif}.avatar img{width:100%;height:100%;object-fit:cover}.row-name{font-size:13px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.row-preview{margin-top:3px;color:var(--muted);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.row-meta{display:grid;justify-items:end;gap:6px;color:var(--muted);font-size:11px}.unread-pill{min-width:20px;height:20px;display:grid;place-items:center;border-radius:999px;background:var(--accent);color:white;font-size:11px;font-weight:800}
        .chat-main{display:flex;min-width:0;min-height:620px;flex-direction:column}.chat-header{display:flex;align-items:center;gap:13px;padding:16px 18px;border-bottom:1px solid var(--border)}.back-link{display:none;color:var(--accent);font-size:13px;font-weight:800}.chat-header h2{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0;color:var(--primary);font:600 24px/1.1 'Playfair Display',Georgia,serif}.verified{display:inline-flex;min-height:22px;align-items:center;padding:4px 8px;border-radius:999px;background:rgba(111,143,120,.14);color:var(--success);font-size:10px;font-weight:800;text-transform:uppercase}.chat-header span{color:var(--muted);font-size:12px}
        .messages{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:12px;padding:20px;background:#FFFDFC}.message-row{display:flex}.message-row.mine{justify-content:flex-end}.message-bubble{max-width:70%;padding:11px 13px;border-radius:14px 14px 14px 4px;background:#F7F1EC;color:var(--text);font-size:13.5px;line-height:1.55}.message-row.mine .message-bubble{border-radius:14px 14px 4px 14px;background:var(--primary);color:white}.message-time{margin-top:5px;color:var(--muted);font-size:10.5px}.message-row.mine .message-time{color:rgba(255,255,255,.72);text-align:right}.composer{display:flex;align-items:flex-end;gap:10px;padding:14px;border-top:1px solid var(--border);background:var(--card)}.composer textarea{flex:1;min-height:48px;max-height:120px;resize:vertical;border:1px solid var(--border);border-radius:14px;padding:13px 14px;color:var(--text);outline:none}.send-btn{width:48px;height:48px;display:grid;place-items:center;border:0;border-radius:14px;background:var(--primary);color:white;cursor:pointer}.send-btn svg{width:19px;height:19px}
        .context-panel{border-left:1px solid var(--border);padding:18px;overflow-y:auto}.context-panel h3{margin:0 0 14px;color:var(--primary);font-size:15px;font-weight:800}.context-card{border:1px solid var(--border);border-radius:14px;padding:12px}.product-card img{width:100%;aspect-ratio:4/3;object-fit:cover;border-radius:10px;background:#F8EEE9}.context-name{margin-top:10px;color:var(--primary);font-size:13px;font-weight:800}.context-muted{margin-top:5px;color:var(--muted);font-size:12px;line-height:1.5}.context-price{margin-top:5px;color:var(--accent);font-size:13px;font-weight:800}.context-link{min-height:38px;display:flex;align-items:center;justify-content:center;margin-top:10px;border-radius:10px;background:var(--primary);color:white;font-size:12px;font-weight:800}.empty-note{padding:18px;color:var(--muted);font-size:13px;line-height:1.6}
        @media(max-width:1180px){.chat-wrap{grid-template-columns:280px minmax(0,1fr)}.context-panel{display:none}}@media(max-width:900px){.profile-page{padding:24px 16px}.profile-layout{display:block}.profile-sidebar{position:static;margin-bottom:18px}.profile-sidebar-card{overflow-x:auto}.profile-nav{display:flex;width:max-content}.profile-nav-section{display:flex}.profile-nav-section-title{display:none}.profile-nav-section+.profile-nav-section{margin:0 0 0 8px;padding:0 0 0 12px;border-top:0;border-left:1px solid var(--border)}.logout-nav-form{margin:0;padding:0;border:0}.profile-nav-item,.logout-nav-button{width:auto}.chat-wrap{grid-template-columns:1fr}.conversation-list{border-right:0}.conversation-row:not(.active){display:none}.back-link{display:inline-flex}.chat-main{min-height:calc(100vh - 290px)}.message-bubble{max-width:86%}.profile-topbar-inner{padding:14px 20px}.profile-logo-brand{font-size:24px}}
    </style>
</head>
<body>
<header class="profile-topbar">
    <div class="profile-topbar-inner">
        <a href="{{ route('shop.index') }}" class="profile-logo" aria-label="Lumora shop"><span class="profile-logo-brand">LUM<span>O</span>RA</span><span class="profile-logo-tagline">Beauty lives here</span></a>
        <div class="topbar-actions">
            <a href="{{ route('buyer.cart') }}" class="topbar-action" aria-label="Cart"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.1 2.1h3l2.7 12.4a2 2 0 0 0 2 1.6h7.8a2 2 0 0 0 2-1.6L21 7H6"/></svg>@if ($cartCount > 0)<span class="topbar-badge">{{ $cartCount }}</span>@endif</a>
        </div>
    </div>
</header>

<main class="profile-page">
    <div class="profile-layout">
        @include('profile.partials.account-sidebar', ['active' => 'messages'])
        <section class="chat-wrap">
            <aside class="conversation-list">
                <div class="chat-title"><h1>Messages</h1><p>Your seller conversations.</p></div>
                @foreach ($conversations as $item)
                    @php
                        $seller = $item->seller;
                        $application = $seller?->approvedSellerApplication ?: $seller?->sellerApplication;
                        $name = $seller?->shop_name ?: ($application?->business_name ?: ($seller?->name ?: 'Lumora seller'));
                        $initials = collect(preg_split('/\s+/', trim($name)))->filter()->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') ?: 'LS';
                        $last = $item->latestMessage;
                    @endphp
                    <a class="conversation-row {{ $conversation->id === $item->id ? 'active' : '' }}" href="{{ route('buyer.chats.show', $item) }}">
                        <div class="avatar">@if ($seller?->avatar)<img src="{{ $seller->avatar }}" alt="">@else{{ mb_strtoupper($initials) }}@endif</div>
                        <div>
                            <div class="row-name">{{ $name }}</div>
                            <div class="row-preview">{{ $last?->body ?? 'No messages yet.' }}</div>
                        </div>
                        <div class="row-meta">
                            <span>{{ $last?->created_at?->diffForHumans(null, true) ?? '' }}</span>
                            @if ((int) $item->unread_count > 0)<span class="unread-pill">{{ $item->unread_count }}</span>@endif
                        </div>
                    </a>
                @endforeach
            </aside>

            <section class="chat-main">
                <div class="chat-header">
                    <a class="back-link" href="{{ route('buyer.chats.index') }}">&larr; Back</a>
                    <div class="avatar" aria-hidden="true">@if ($sellerProfile['avatar'])<img src="{{ $sellerProfile['avatar'] }}" alt="">@else{{ $sellerProfile['initials'] }}@endif</div>
                    <div>
                        <h2>{{ $sellerProfile['name'] }} @if ($sellerProfile['verified'])<span class="verified">Verified Seller</span>@endif</h2>
                        <span>Seller conversation</span>
                    </div>
                </div>

                <div class="messages" data-message-list>
                    @forelse ($conversation->messages->sortBy('created_at') as $message)
                        <div class="message-row {{ (int) $message->sender_id === (int) Auth::id() ? 'mine' : '' }}">
                            <div class="message-bubble">
                                <div>{{ $message->body }}</div>
                                <div class="message-time">{{ $message->created_at?->format('M d, g:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-note">No messages yet. Send a message to start this conversation.</div>
                    @endforelse
                </div>

                <form class="composer" method="POST" action="{{ route('buyer.chats.messages.store', $conversation) }}">
                    @csrf
                    <textarea name="message" placeholder="Type a message..." required maxlength="2000">{{ old('message') }}</textarea>
                    <button class="send-btn" type="submit" aria-label="Send message"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7Z"/><path d="M22 2 11 13"/></svg></button>
                </form>
            </section>

            <aside class="context-panel">
                <h3>Product Context</h3>
                @if ($conversation->product)
                    <div class="context-card product-card">
                        <img src="{{ $conversation->product->image_url }}" alt="{{ $conversation->product->name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                        <div class="context-name">{{ $conversation->product->name }}</div>
                        <div class="context-price">&#8369;{{ number_format((float) $conversation->product->price, 2) }}</div>
                        <a class="context-link" href="{{ route('shop.product', ['id' => $conversation->product->id]) }}">View Product</a>
                    </div>
                @else
                    <p class="context-muted">No product was attached to this conversation.</p>
                @endif

                <h3 style="margin-top:22px;">Seller Information</h3>
                <div class="context-card">
                    <div class="context-name">{{ $sellerProfile['name'] }}</div>
                    <div class="context-muted">{{ $sellerProfile['verified'] ? 'Verified Seller' : 'Lumora seller' }}</div>
                </div>
            </aside>
        </section>
    </div>
</main>
<script>
    const messageList = document.querySelector('[data-message-list]');
    if (messageList) messageList.scrollTop = messageList.scrollHeight;
</script>
</body>
</html>
