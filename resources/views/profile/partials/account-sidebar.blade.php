@props([
    'active' => 'profile',
])

@php
    if (request()->routeIs('buyer.wallet.*') || request()->routeIs('buyer.payment-methods.*')) {
        $active = 'payment-methods';
    } elseif (request()->routeIs('buyer.orders.*')) {
        $active = 'orders';
    } elseif (request()->routeIs('buyer.chats.*')) {
        $active = 'messages';
    } elseif (request()->routeIs('buyer.following.*')) {
        $active = 'following';
    }

    $onProfilePage = $active === 'profile';
    $profileHref = $onProfilePage ? '#profile' : route('profile.edit');
    $wishlistHref = $onProfilePage ? '#wishlist' : route('profile.edit') . '#wishlist';
    $shippingHref = $onProfilePage ? '#shipping-address' : route('profile.edit') . '#shipping-address';
    $settingsHref = $onProfilePage ? '#account-settings' : route('profile.edit') . '#account-settings';
@endphp

<aside class="profile-sidebar" data-profile-sidebar aria-label="Account menu">
    <div class="profile-sidebar-card">
        <nav class="profile-nav">
            <div class="profile-nav-section">
                <div class="profile-nav-section-title">Account</div>
                <x-buyer-profile.nav-item :href="$profileHref" label="Profile" :active="$active === 'profile'">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="7" r="4"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <x-buyer-profile.nav-item :href="$settingsHref" label="Account Settings">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12.2 2h-.4a2 2 0 0 0-2 2v.2a2 2 0 0 1-1 1.7l-.4.2a2 2 0 0 1-2 0l-.2-.1a2 2 0 0 0-2.7.7l-.2.3a2 2 0 0 0 .7 2.7l.2.1a2 2 0 0 1 1 1.7v.5a2 2 0 0 1-1 1.7l-.2.1a2 2 0 0 0-.7 2.7l.2.3a2 2 0 0 0 2.7.7l.2-.1a2 2 0 0 1 2 0l.4.2a2 2 0 0 1 1 1.7v.2a2 2 0 0 0 2 2h.4a2 2 0 0 0 2-2v-.2a2 2 0 0 1 1-1.7l.4-.2a2 2 0 0 1 2 0l.2.1a2 2 0 0 0 2.7-.7l.2-.3a2 2 0 0 0-.7-2.7l-.2-.1a2 2 0 0 1-1-1.7v-.5a2 2 0 0 1 1-1.7l.2-.1a2 2 0 0 0 .7-2.7l-.2-.3a2 2 0 0 0-2.7-.7l-.2.1a2 2 0 0 1-2 0l-.4-.2a2 2 0 0 1-1-1.7V4a2 2 0 0 0-2-2Z"/><circle cx="12" cy="12" r="3"/></svg></x-slot>
                </x-buyer-profile.nav-item>
            </div>

            <div class="profile-nav-section">
                <div class="profile-nav-section-title">Shopping</div>
                <x-buyer-profile.nav-item :href="route('buyer.orders.index')" label="My Orders" :active="$active === 'orders'">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2Z"/><path d="M9 8h6"/><path d="M9 12h6"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <a href="{{ route('buyer.chats.index') }}" class="profile-nav-item {{ $active === 'messages' ? 'active' : '' }}" data-profile-nav-item @if ($active === 'messages') aria-current="page" @endif>
                    <span class="profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg></span>
                    <span class="profile-nav-label">Messages</span>
                    @if (($buyerUnreadChatCount ?? 0) > 0)
                        <span class="profile-nav-badge">{{ $buyerUnreadChatCount }}</span>
                    @endif
                </a>
                <x-buyer-profile.nav-item :href="route('buyer.following.index')" label="Following" :active="$active === 'following'">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 10.5 12 4l9 6.5"/><path d="M5 10v9h14v-9"/><path d="M9 19v-5h6v5"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <x-buyer-profile.nav-item :href="$wishlistHref" label="Wishlist">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <x-buyer-profile.nav-item :href="$shippingHref" label="Shipping Address">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <x-buyer-profile.nav-item :href="route('buyer.wallet.index')" label="Wallet" :active="$active === 'payment-methods'">
                    <x-slot name="icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></x-slot>
                </x-buyer-profile.nav-item>
                <form method="POST" action="{{ route('logout') }}" class="logout-nav-form">
                    @csrf
                    <button type="submit" class="logout-nav-button">
                        <span class="profile-nav-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></span>
                        <span class="profile-nav-label">Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</aside>
