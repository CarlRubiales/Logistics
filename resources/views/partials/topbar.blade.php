<header class="topbar topbar-modern" style="min-height: 74px; padding: 0 36px; border-bottom: 1px solid rgba(235, 221, 216, 0.85); background: rgba(255, 253, 251, 0.94); backdrop-filter: blur(14px); display: flex; align-items: center; justify-content: space-between; gap: 20px; position: sticky; top: 0; z-index: 40;">
    <div class="topbar-left" style="display: flex; align-items: center; gap: 12px;">
        <div class="breadcrumb topbar-breadcrumb" style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #8A7B7E;">
            <span class="crumb-category" style="font-weight: 500; color: #8A7B7E;">{{ $category ?? (auth()->user()?->isMasterAdmin() ? 'Master Administration' : 'Branch Workspace') }}</span>
            <span class="crumb-slash" style="color: #D3C4BE;">/</span>
            <strong class="crumb-current" style="font-weight: 700; color: #3D1B3D;">{{ $title ?? 'Dashboard' }}</strong>
        </div>
    </div>

    <!-- Live Hub Operational Status Indicator -->
    <div class="topbar-center" style="display: flex; align-items: center; justify-content: center;">
        @if(auth()->check() && auth()->user()->isMasterAdmin())
            <div class="hub-status-pill hq" style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 14px; border-radius: 999px; font-size: 11.5px; font-weight: 600; background: rgba(61, 27, 61, 0.05); border: 1px solid rgba(61, 27, 61, 0.15); color: #3D1B3D;">
                <span class="live-pulse-dot" style="width: 7px; height: 7px; border-radius: 50%; background: #1E6B37; display: inline-block;"></span>
                <span>Central Logistics HQ · Global Command</span>
            </div>
        @elseif(auth()->check())
            <div class="hub-status-pill branch" style="display: inline-flex; align-items: center; gap: 8px; padding: 5px 14px; border-radius: 999px; font-size: 11.5px; font-weight: 600; background: rgba(30, 107, 55, 0.08); border: 1px solid rgba(30, 107, 55, 0.22); color: #1E6B37;">
                <span class="live-pulse-dot branch" style="width: 7px; height: 7px; border-radius: 50%; background: #E2703A; display: inline-block;"></span>
                <span>{{ auth()->user()->getBranchName() ? auth()->user()->getBranchName() . ' Hub' : 'Branch Hub' }} · Active Operations</span>
            </div>
        @endif
    </div>

    <!-- Right-Hand Profile and Actions (Settings & Sign Out placed on Sidebar Only) -->
    <div class="topbar-actions topbar-right" style="display: flex; align-items: center; gap: 14px; margin-left: auto;">
        @auth
            @php
                $unreadMessagesCount = \App\Models\ErpMessage::where('recipient_id', auth()->id())->whereNull('read_at')->count();
            @endphp
            <!-- Messages Link with Unread Counter -->
            <a href="{{ route('messages.index') }}" class="topbar-icon-pill" title="Messages & Courier Communications" style="width: 36px; height: 36px; border-radius: 10px; border: 1px solid #EBDDD8; background: #FFFDFB; display: grid; place-items: center; color: #3D1B3D; text-decoration: none; position: relative;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                @if($unreadMessagesCount > 0)
                    <span class="topbar-unread-badge" style="position: absolute; top: -4px; right: -4px; background: #E2703A; color: #FFFDFB; font-size: 9.5px; font-weight: 800; min-width: 17px; height: 17px; border-radius: 999px; display: grid; place-items: center; padding: 0 4px;">{{ $unreadMessagesCount }}</span>
                @endif
            </a>

            <!-- Clean User Profile Pill (No Sign Out on Toolbar) -->
            <div class="topbar-user-pill" style="display: inline-flex; align-items: center; gap: 10px; padding: 4px 14px 4px 6px; border-radius: 999px; background: #FFFDFB; border: 1px solid #EBDDD8; box-shadow: 0 2px 6px rgba(61, 27, 61, 0.04);">
                <div class="topbar-user-avatar" style="width: 30px; height: 30px; min-width: 30px; border-radius: 50%; color: #FFFDFB; font-weight: 800; font-size: 11px; display: grid; place-items: center; flex-shrink: 0; {{ auth()->user()->isMasterAdmin() ? 'background: linear-gradient(135deg, #3D1B3D, #793779);' : 'background: linear-gradient(135deg, #E2703A, #B96562);' }}">
                    {{ strtoupper(substr(auth()->user()->isMasterAdmin() ? 'HQ' : (auth()->user()->getBranchName() ?? auth()->user()->name), 0, 2)) }}
                </div>
                <div class="topbar-user-info" style="display: flex; flex-direction: column; min-width: 0;">
                    <span class="topbar-user-name" title="{{ auth()->user()->name }}" style="font-size: 12.5px; font-weight: 800; color: #3D1B3D; white-space: nowrap; line-height: 1.2; text-transform: uppercase;">
                        {{ auth()->user()->getBranchName() ? strtoupper(auth()->user()->getBranchName()) : (auth()->user()->isMasterAdmin() ? 'CENTRAL HQ' : strtoupper(auth()->user()->name)) }}
                    </span>
                    <span class="topbar-user-role" style="font-size: 9.5px; font-weight: 700; color: #8A7B7E; text-transform: uppercase; letter-spacing: 0.4px; line-height: 1;">
                        {{ auth()->user()->isMasterAdmin() ? 'SUPER ADMIN' : (auth()->user()->getBranchName() ? strtoupper(auth()->user()->getBranchName()) . ' ADMIN' : 'ADMIN') }}
                    </span>
                </div>
            </div>
        @endauth
    </div>
</header>
