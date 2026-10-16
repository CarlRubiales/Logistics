@php
    $unreadTotal = 0;

    if (Auth::check()) {
        $unreadTotal = (int) \App\Models\Message::query()
            ->whereHas('conversation', fn ($query) => $query->where('buyer_id', Auth::id()))
            ->where('sender_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->count();
    }
@endphp

<div class="account-menu-divider"></div>
<a href="{{ route('buyer.chats.index') }}" class="account-menu-link" role="menuitem">
    <span class="account-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg></span>
    <span style="flex:1">Messages</span>
    @if ($unreadTotal > 0)
        <span class="recent-chat-unread">{{ $unreadTotal }}</span>
    @endif
</a>

<style>
    .recent-chat-unread { min-width:18px; height:18px; padding:0 5px; display:inline-grid; place-items:center; border-radius:999px; background:#C0583A; color:#fff; font-size:10px; font-weight:700; }
</style>
