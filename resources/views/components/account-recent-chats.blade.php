@php
    $recentChats = collect();
    $unreadTotal = 0;

    if (Auth::check()) {
        $recentChats = \App\Models\Conversation::query()
            ->where('buyer_id', Auth::id())
            ->whereHas('messages')
            ->with(['seller.approvedSellerApplication', 'seller.sellerApplication', 'latestMessage'])
            ->withCount([
                'messages as unread_count' => fn ($messages) => $messages
                    ->where('sender_id', '!=', Auth::id())
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->take(4)
            ->get();

        $unreadTotal = (int) $recentChats->sum('unread_count');
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

@if ($recentChats->isNotEmpty())
    <div class="recent-chats" aria-label="Recent conversations">
        <div class="recent-chats-title">Recent chats</div>
        @foreach ($recentChats as $chat)
            @php
                $seller = $chat->seller;
                $application = $seller?->approvedSellerApplication ?: $seller?->sellerApplication;
                $chatName = $seller?->shop_name ?: ($application?->business_name ?: ($seller?->name ?: 'Lumora seller'));
                $chatInitial = mb_strtoupper(mb_substr(trim($chatName), 0, 1)) ?: 'L';
            @endphp
            <a href="{{ route('buyer.chats.show', $chat) }}" class="recent-chat" role="menuitem">
                <span class="recent-chat-avatar">
                    @if ($seller?->avatar)<img src="{{ $seller->avatar }}" alt="">@else{{ $chatInitial }}@endif
                </span>
                <span class="recent-chat-body">
                    <span class="recent-chat-name">{{ $chatName }}</span>
                    <span class="recent-chat-preview">{{ \Illuminate\Support\Str::limit($chat->latestMessage?->body ?? 'No messages yet.', 34) }}</span>
                </span>
                @if ($chat->unread_count > 0)
                    <span class="recent-chat-unread">{{ $chat->unread_count }}</span>
                @endif
            </a>
        @endforeach
    </div>
@endif

<style>
    .recent-chats { padding:2px 0 4px; }
    .recent-chats-title { padding:4px 10px; color:#8B7B78; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; }
    .recent-chat { display:flex; align-items:center; gap:10px; padding:8px 10px; border-radius:10px; color:inherit; text-decoration:none; }
    .recent-chat:hover { background:#F8EEE9; }
    .recent-chat-avatar { flex:0 0 auto; width:32px; height:32px; display:grid; place-items:center; overflow:hidden; border:1px solid #EAE3DD; border-radius:50%; background:#F8EEE9; color:#3B1E34; font:700 13px Georgia,serif; }
    .recent-chat-avatar img { width:100%; height:100%; object-fit:cover; }
    .recent-chat-body { flex:1; min-width:0; display:grid; }
    .recent-chat-name { overflow:hidden; white-space:nowrap; text-overflow:ellipsis; color:#3B1E34; font-size:12px; font-weight:700; }
    .recent-chat-preview { overflow:hidden; white-space:nowrap; text-overflow:ellipsis; color:#8B7B78; font-size:11px; }
    .recent-chat-unread { min-width:18px; height:18px; padding:0 5px; display:inline-grid; place-items:center; border-radius:999px; background:#C0583A; color:#fff; font-size:10px; font-weight:700; }
</style>

