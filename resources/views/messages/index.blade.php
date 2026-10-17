<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Messages & Hub Communications — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .messenger-layout {
            display: grid;
            grid-template-columns: 320px 1fr 300px;
            gap: 16px;
            height: calc(100vh - 190px);
            min-height: 640px;
            max-height: 840px;
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(61, 27, 61, 0.04);
        }

        /* Left: Roster Pane */
        .roster-pane {
            display: flex;
            flex-direction: column;
            border-right: 1px solid #EBDDD8;
            background: #FAF6F0;
            overflow: hidden;
        }
        .roster-header {
            padding: 16px;
            border-bottom: 1px solid #EBDDD8;
            background: #FFFDFB;
        }
        .roster-search {
            width: 100%;
            height: 36px;
            padding: 0 12px;
            font-size: 12px;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            background: #FAF6F0;
            color: #3A2E30;
            margin-top: 10px;
        }
        .roster-tabs {
            display: flex;
            gap: 6px;
            padding: 10px 16px;
            background: #FAF6F0;
            border-bottom: 1px solid #EBDDD8;
        }
        .roster-tab {
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
            border: none;
            background: transparent;
            color: #8A7B7E;
            cursor: pointer;
            transition: all 0.15s;
        }
        .roster-tab.active {
            background: #3D1B3D;
            color: #FFFDFB;
        }
        .roster-list {
            flex: 1;
            overflow-y: auto;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .roster-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid rgba(235, 221, 216, 0.6);
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            transition: background 0.15s;
            position: relative;
        }
        .roster-item:hover {
            background: #F4EAE4;
        }
        .roster-item.active {
            background: #FFFDFB;
            border-left: 4px solid #E2703A;
        }
        .roster-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .roster-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #3D1B3D;
            color: #FFFDFB;
            display: grid;
            place-items: center;
            font-size: 13px;
            font-weight: 700;
        }
        .roster-avatar.active-contact {
            background: #E2703A;
        }
        .status-dot {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #FFFDFB;
        }
        .status-dot.online { background: #1E6B37; }
        .status-dot.offline { background: #8A7B7E; }
        .roster-info {
            flex: 1;
            min-width: 0;
        }
        .roster-top {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2px;
        }
        .roster-name {
            font-size: 13px;
            font-weight: 700;
            color: #3D1B3D;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .roster-time {
            font-size: 10px;
            color: #8A7B7E;
            flex-shrink: 0;
            margin-left: 6px;
        }
        .roster-snippet {
            font-size: 11.5px;
            color: #8A7B7E;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .roster-badge {
            background: #E2703A;
            color: #FFFDFB;
            font-size: 10px;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        /* Center: Chat Pane */
        .chat-pane {
            display: flex;
            flex-direction: column;
            background: #FFFDFB;
            overflow: hidden;
        }
        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 20px;
            border-bottom: 1px solid #EBDDD8;
            background: #FFFDFB;
        }
        .chat-contact-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .chat-stream {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            background: #FAF6F0;
        }
        .chat-bubble-row {
            display: flex;
            flex-direction: column;
            max-width: 72%;
        }
        .chat-bubble-row.mine {
            align-self: flex-end;
            align-items: flex-end;
        }
        .chat-bubble-row.theirs {
            align-self: flex-start;
            align-items: flex-start;
        }
        .chat-bubble {
            padding: 11px 16px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.45;
            position: relative;
            word-break: break-word;
        }
        .chat-bubble.mine {
            background: #3D1B3D;
            color: #FFFDFB;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble.theirs {
            background: #FFFDFB;
            color: #3A2E30;
            border: 1px solid #EBDDD8;
            border-bottom-left-radius: 4px;
        }
        .chat-parcel-card {
            background: #FAF6F0;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            padding: 8px 12px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-size: 11.5px;
            text-decoration: none;
            color: inherit;
        }
        .chat-parcel-card:hover {
            border-color: #E2703A;
        }
        .chat-parcel-number {
            font-weight: 800;
            color: #3D1B3D;
        }
        .chat-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 4px;
            font-size: 10px;
            color: #8A7B7E;
        }
        .chat-composer-wrap {
            padding: 14px 20px 16px;
            border-top: 1px solid #EBDDD8;
            background: #FFFDFB;
        }
        .quick-presets {
            display: flex;
            gap: 6px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .preset-chip {
            background: #FAF6F0;
            border: 1px solid #EBDDD8;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #3D1B3D;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.15s;
        }
        .preset-chip:hover {
            background: #3D1B3D;
            color: #FFFDFB;
            border-color: #3D1B3D;
        }
        .composer-bar {
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        .composer-input {
            flex: 1;
            min-height: 44px;
            max-height: 120px;
            border: 1px solid #EBDDD8;
            border-radius: 12px;
            padding: 10px 14px;
            font-size: 13px;
            resize: none;
            background: #FAF6F0;
            color: #3A2E30;
            outline: none;
            transition: all 0.15s;
        }
        .composer-input:focus {
            border-color: #E2703A;
            background: #FFFDFB;
            box-shadow: 0 0 0 3px rgba(226, 112, 58, 0.15);
        }
        .composer-attach-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 11px;
            color: #8A7B7E;
        }

        /* Right: Contact Intel Pane */
        .intel-pane {
            display: flex;
            flex-direction: column;
            border-left: 1px solid #EBDDD8;
            background: #FAF6F0;
            padding: 20px;
            overflow-y: auto;
        }
        .intel-card {
            background: #FFFDFB;
            border: 1px solid #EBDDD8;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .intel-avatar-lg {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #3D1B3D;
            color: #FFFDFB;
            display: grid;
            place-items: center;
            font-size: 18px;
            font-weight: 700;
            margin: 0 auto 10px;
        }
        .intel-detail-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            padding: 6px 0;
            border-bottom: 1px solid #FAF6F0;
        }
        .intel-detail-label { color: #8A7B7E; }
        .intel-detail-val { font-weight: 600; color: #3A2E30; }

        @media (max-width: 1024px) {
            .messenger-layout {
                grid-template-columns: 280px 1fr;
            }
            .intel-pane {
                display: none;
            }
        }
        @media (max-width: 768px) {
            .messenger-layout {
                grid-template-columns: 1fr;
                height: auto;
                max-height: none;
            }
            .roster-pane {
                max-height: 280px;
            }
            .chat-stream {
                min-height: 380px;
            }
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => ($isMasterAdmin ? 'Master Administration' : 'Hub Comms'),
            'title' => ($isMasterAdmin ? 'Branch & Dispatch Messages' : 'Direct Messages with Master Admin')
        ])

        <div class="content-wrap" style="padding-bottom: 24px;">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <section class="welcome-row" style="margin-bottom: 16px;">
                <div>
                    <div class="eyebrow" style="color: #E2703A;">
                        {{ $isMasterAdmin ? 'HEADQUARTERS DISPATCH & BRANCH COMMS' : 'BRANCH TO HEADQUARTERS MESSAGES' }}
                    </div>
                    <h1 style="font-size: 26px;">
                        {{ $isMasterAdmin ? 'Direct Branch & Courier Messages' : 'Direct Messages with Headquarters Admin' }}
                    </h1>
                    <p class="intro">
                        {{ $isMasterAdmin
                            ? 'Instant two-way messaging between Central Logistics Command and operating branches (Pila, Santa Cruz, etc.).'
                            : 'Direct communication channel between your branch hub and the Master Logistics Headquarters.' }}
                    </p>
                </div>
            </section>

            <!-- Multi-Pane Messenger -->
            <div class="messenger-layout">
                <!-- 1. LEFT: Roster Pane -->
                <aside class="roster-pane">
                    <div class="roster-header">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <strong style="color: #3D1B3D; font-size: 14px;">
                                {{ $isMasterAdmin ? 'Branch Hubs & Couriers' : 'Comms Channels' }}
                            </strong>
                            <small style="color: #8A7B7E;">
                                {{ $branchSummaries->count() }} branches
                            </small>
                        </div>
                        <input
                            type="search"
                            id="rosterSearchInput"
                            class="roster-search"
                            placeholder="⌕ Filter by name, hub, or email..."
                            oninput="filterRosters(this.value)"
                        >
                    </div>

                    <div class="roster-tabs">
                        <button type="button" class="roster-tab active" onclick="switchCategory('branch', this)">
                            {{ $isMasterAdmin ? 'Branches (' . $branchSummaries->count() . ')' : 'Headquarters' }}
                        </button>
                        <button type="button" class="roster-tab" onclick="switchCategory('rider', this)">
                            Couriers ({{ $riderSummaries->count() }})
                        </button>
                    </div>

                    <ul class="roster-list" id="rosterList">
                        <!-- BRANCH CONTACTS -->
                        @forelse($branchSummaries as $item)
                            @php
                                $bUser = $item['user'];
                                $isSelected = ($selectedContact && $selectedContact->id === $bUser->id);
                                $initials = strtoupper(substr($item['branch'], 0, 2));
                            @endphp
                            <li class="contact-entry" data-category="branch">
                                <a
                                    href="{{ route('messages.index', ['contact_user_id' => $bUser->id]) }}"
                                    class="roster-item {{ $isSelected ? 'active' : '' }}"
                                    data-name="{{ strtolower($item['name']) }}"
                                    data-sub="{{ strtolower($item['sub_label']) }}"
                                    data-branch="{{ strtolower($item['branch']) }}"
                                    data-unread="{{ $item['unread_count'] }}"
                                >
                                    <div class="roster-avatar-wrap">
                                        <div class="roster-avatar {{ $isSelected ? 'active-contact' : '' }}" style="{{ $bUser->isMasterAdmin() ? 'background: linear-gradient(135deg, #3D1B3D, #793779);' : '' }}">
                                            {{ $initials }}
                                        </div>
                                        <span class="status-dot online" title="Active Hub"></span>
                                    </div>

                                    <div class="roster-info">
                                        <div class="roster-top">
                                            <span class="roster-name">{{ $item['name'] }}</span>
                                            @if($item['latest_message'])
                                                <span class="roster-time">{{ $item['latest_message']->created_at->diffForHumans(null, true, true) }}</span>
                                            @endif
                                        </div>
                                        <div class="roster-snippet">
                                            @if($item['latest_message'])
                                                {{ $item['latest_message']->message }}
                                            @else
                                                <span style="font-style: italic;">{{ $item['sub_label'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($item['unread_count'] > 0)
                                        <span class="roster-badge">{{ $item['unread_count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @empty
                            <li class="contact-entry" data-category="branch" style="padding: 24px 16px; text-align: center; color: #8A7B7E; font-size: 12px;">
                                No branch accounts registered yet.
                            </li>
                        @endforelse

                        <!-- COURIER CONTACTS -->
                        @forelse($riderSummaries as $item)
                            @php
                                $rUser = $item['user'];
                                $rider = $item['rider'];
                                $isSelected = ($selectedContact && $selectedContact->id === $rUser->id);
                                $isOnline = in_array($rider->availability, ['online', 'available']);
                                $initials = strtoupper(substr($rUser->name, 0, 2));
                            @endphp
                            <li class="contact-entry" data-category="rider" style="display: none;">
                                <a
                                    href="{{ route('messages.index', ['contact_user_id' => $rUser->id]) }}"
                                    class="roster-item {{ $isSelected ? 'active' : '' }}"
                                    data-name="{{ strtolower($item['name']) }}"
                                    data-sub="{{ strtolower($item['sub_label']) }}"
                                    data-branch="{{ strtolower($item['branch']) }}"
                                    data-unread="{{ $item['unread_count'] }}"
                                >
                                    <div class="roster-avatar-wrap">
                                        <div class="roster-avatar {{ $isSelected ? 'active-contact' : '' }}">
                                            {{ $initials }}
                                        </div>
                                        <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}" title="{{ ucfirst($rider->availability) }}"></span>
                                    </div>

                                    <div class="roster-info">
                                        <div class="roster-top">
                                            <span class="roster-name">{{ $item['name'] }}</span>
                                            @if($item['latest_message'])
                                                <span class="roster-time">{{ $item['latest_message']->created_at->diffForHumans(null, true, true) }}</span>
                                            @endif
                                        </div>
                                        <div class="roster-snippet">
                                            @if($item['latest_message'])
                                                {{ $item['latest_message']->message }}
                                            @else
                                                <span style="font-style: italic;">{{ $item['sub_label'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($item['unread_count'] > 0)
                                        <span class="roster-badge">{{ $item['unread_count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @empty
                            <li class="contact-entry" data-category="rider" style="display: none; padding: 24px 16px; text-align: center; color: #8A7B7E; font-size: 12px;">
                                No couriers available in this workspace.
                            </li>
                        @endforelse
                    </ul>
                </aside>

                <!-- 2. CENTER: Active Conversation Thread -->
                <section class="chat-pane">
                    @if($selectedContact)
                        @php
                            $contactInitials = strtoupper(substr($selectedContact->branch ?: $selectedContact->name, 0, 2));
                            $displayName = $selectedContact->isMasterAdmin()
                                ? 'Central Logistics HQ (Master Admin)'
                                : ($selectedContact->branch ? ($selectedContact->branch . ' Hub (' . $selectedContact->name . ')') : $selectedContact->name);
                        @endphp

                        <!-- Header -->
                        <div class="chat-header">
                            <div class="chat-contact-title">
                                <div class="roster-avatar-wrap">
                                    <div class="roster-avatar" style="background: #3D1B3D;">
                                        {{ $contactInitials }}
                                    </div>
                                    <span class="status-dot online"></span>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <h2 style="margin: 0; font-size: 16px; color: #3D1B3D;">
                                            {{ $displayName }}
                                        </h2>
                                        <span style="font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: rgba(30,107,55,0.1); color: #1E6B37;">
                                            ● CONNECTED
                                        </span>
                                    </div>
                                    <small style="color: #8A7B7E; font-size: 11px;">
                                        ✉ {{ $selectedContact->email }}
                                        @if($selectedContact->branch) · 🏢 {{ $selectedContact->branch }} Hub @endif
                                    </small>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                @if($selectedContactParcels->isNotEmpty())
                                    <span style="font-size: 11px; color: #E2703A; font-weight: 700; background: rgba(226,112,58,0.08); padding: 4px 10px; border-radius: 8px;">
                                        📦 {{ $selectedContactParcels->count() }} active parcels
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Chat Messages Stream -->
                        <div class="chat-stream" id="chatStream">
                            @forelse($threadMessages as $msg)
                                @php
                                    $isMine = ($msg->sender_id === auth()->id());
                                @endphp
                                <div class="chat-bubble-row {{ $isMine ? 'mine' : 'theirs' }}" id="msg-{{ $msg->id }}">
                                    @if($msg->parcel)
                                        <div class="chat-parcel-card">
                                            <div>
                                                <span class="chat-parcel-number">📦 {{ $msg->parcel->parcel_number }}</span>
                                                <div style="font-size: 10.5px; color: #8A7B7E; margin-top: 2px;">
                                                    {{ $msg->parcel->recipient_name }} · {{ $msg->parcel->city }}
                                                </div>
                                            </div>
                                            <span style="font-size: 9.5px; font-weight: 700; background: #FFFDFB; border: 1px solid #EBDDD8; padding: 2px 6px; border-radius: 4px;">
                                                {{ ucwords(str_replace('_', ' ', $msg->parcel->status)) }}
                                            </span>
                                        </div>
                                    @endif

                                    <div class="chat-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                        {{ $msg->message }}
                                    </div>

                                    <div class="chat-meta">
                                        <span>{{ $isMine ? 'You' : ($msg->sender?->name ?? 'Partner') }}</span>
                                        <span>·</span>
                                        <time>{{ $msg->created_at->format('g:i A') }}</time>
                                        @if($isMine)
                                            <span style="color: {{ $msg->read_at ? '#1E6B37' : '#8A7B7E' }};" title="{{ $msg->read_at ? 'Read by recipient' : 'Delivered' }}">
                                                {{ $msg->read_at ? '✓✓' : '✓' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="text-align: center; margin: auto; padding: 40px; color: #8A7B7E;">
                                    <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                                    <strong style="color: #3D1B3D; display: block; font-size: 14px;">
                                        No messages with {{ $selectedContact->name }} yet
                                    </strong>
                                    <p style="font-size: 12px; margin: 4px 0 0;">
                                        {{ $isMasterAdmin ? 'Send operational updates, sorting alerts, or instructions to this branch below.' : 'Send inquiries, delivery reports, or assistance requests to Master Admin below.' }}
                                    </p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Composer & Quick Presets -->
                        <div class="chat-composer-wrap">
                            <!-- Quick Presets -->
                            <div class="quick-presets">
                                @if($isMasterAdmin)
                                    <button type="button" class="preset-chip" onclick="insertPreset('📦 Please prioritize intake and sorting for the incoming manifest.')">
                                        📦 Prioritize intake
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('📍 Dispatch report requested for your active branch delivery areas.')">
                                        📍 Dispatch report
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('⚡ Courier credentials verified and approved for your branch.')">
                                        ⚡ Courier approved
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('✓ Received branch end-of-day reconciliation. Thank you.')">
                                        ✓ EOD confirmed
                                    </button>
                                @else
                                    <button type="button" class="preset-chip" onclick="insertPreset('📦 Inbound manifest arrived and sorting is now underway at the hub.')">
                                        📦 Inbound arrived
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('📍 Requesting additional courier assignment for high volume zones.')">
                                        📍 Request couriers
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('⚡ Delivery exception flagged for parcel review.')">
                                        ⚡ Exception flagged
                                    </button>
                                    <button type="button" class="preset-chip" onclick="insertPreset('✓ All sorted parcels assigned to couriers for delivery.')">
                                        ✓ Dispatched
                                    </button>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('messages.send') }}" id="chatForm" onsubmit="handleSend(event)">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $selectedContact->id }}">

                                <!-- Parcel Reference Selector -->
                                @if($selectedContactParcels->isNotEmpty())
                                    <div class="composer-attach-bar">
                                        <span>Reference Parcel:</span>
                                        <select name="parcel_id" id="parcelSelect" style="padding: 2px 8px; border-radius: 6px; border: 1px solid #EBDDD8; font-size: 11px; background: #FAF6F0; max-width: 320px;">
                                            <option value="">None (General Message)</option>
                                            @foreach($selectedContactParcels as $p)
                                                <option value="{{ $p->id }}" @selected(request('parcel_id') == $p->id)>
                                                    {{ $p->parcel_number }} — {{ $p->recipient_name }} ({{ $p->city }}) [{{ ucwords(str_replace('_', ' ', $p->status)) }}]
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="composer-bar">
                                    <textarea
                                        name="message"
                                        id="messageInput"
                                        class="composer-input"
                                        rows="2"
                                        placeholder="Type message for {{ $selectedContact->name }}... (Press Enter to send, Shift+Enter for new line)"
                                        required
                                        onkeydown="handleKeyDown(event)"
                                    ></textarea>

                                    <button
                                        type="submit"
                                        class="primary-button"
                                        id="sendBtn"
                                        style="height: 44px; padding: 0 20px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;"
                                    >
                                        <span>Send</span>
                                        <span>➤</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    @else
                        <!-- No Contact Selected State -->
                        <div style="margin: auto; text-align: center; padding: 60px 20px; color: #8A7B7E;">
                            <div style="font-size: 42px; margin-bottom: 12px;">📨</div>
                            <h2 style="font-family: 'Playfair Display', Georgia, serif; color: #3D1B3D; font-size: 22px; margin-bottom: 6px;">
                                Select a Conversation
                            </h2>
                            <p style="font-size: 13px; max-width: 380px; margin: 0 auto;">
                                Choose a branch hub or courier from the roster to start exchanging messages.
                            </p>
                        </div>
                    @endif
                </section>

                <!-- 3. RIGHT: Intel Pane -->
                @if($selectedContact)
                    <aside class="intel-pane">
                        <div class="intel-card">
                            <div class="intel-avatar-lg">
                                {{ strtoupper(substr($selectedContact->branch ?: $selectedContact->name, 0, 2)) }}
                            </div>
                            <h3 style="text-align: center; margin: 0 0 4px; font-size: 15px; color: #3D1B3D;">
                                {{ $selectedContact->name }}
                            </h3>
                            <div style="text-align: center; margin-bottom: 14px;">
                                <span style="font-size: 11px; font-weight: 700; color: #E2703A; background: #FAF6F0; border: 1px solid #EBDDD8; padding: 2px 8px; border-radius: 999px;">
                                    {{ $selectedContact->isMasterAdmin() ? 'Central Command' : ($selectedContact->branch ? $selectedContact->branch . ' Hub' : 'Courier') }}
                                </span>
                            </div>

                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Email</span>
                                <span class="intel-detail-val" style="font-size: 11px;">{{ $selectedContact->email }}</span>
                            </div>
                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Role</span>
                                <span class="intel-detail-val">{{ str_replace('_', ' ', ucfirst($selectedContact->role)) }}</span>
                            </div>
                            @if($selectedContact->branch)
                                <div class="intel-detail-row">
                                    <span class="intel-detail-label">Branch Hub</span>
                                    <span class="intel-detail-val">{{ $selectedContact->branch }}</span>
                                </div>
                            @endif
                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Active Queue</span>
                                <span class="intel-detail-val" style="color: #E2703A;">
                                    {{ $selectedContactParcels->count() }} parcels
                                </span>
                            </div>
                        </div>

                        <!-- Active Parcels for this Contact -->
                        @if($selectedContactParcels->isNotEmpty())
                            <div class="intel-card" style="padding: 12px;">
                                <strong style="display: block; font-size: 12px; color: #3D1B3D; margin-bottom: 8px;">
                                    Live Parcel Queue
                                </strong>
                                <div style="display: flex; flex-direction: column; gap: 6px; max-height: 240px; overflow-y: auto;">
                                    @foreach($selectedContactParcels as $p)
                                        <div style="font-size: 11px; padding: 6px; background: #FAF6F0; border: 1px solid #EBDDD8; border-radius: 6px;">
                                            <div style="font-weight: 700; color: #3D1B3D;">{{ $p->parcel_number }}</div>
                                            <div style="color: #8A7B7E; font-size: 10px;">{{ $p->recipient_name }} · {{ $p->city }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </aside>
                @endif
            </div>
        </div>
    </main>
</div>

<script>
    // Scroll chat stream to bottom on load
    const chatStream = document.getElementById('chatStream');
    function scrollToBottom() {
        if (chatStream) {
            chatStream.scrollTop = chatStream.scrollHeight;
        }
    }
    scrollToBottom();

    // Insert preset message
    function insertPreset(text) {
        const input = document.getElementById('messageInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    // Keyboard shortcut: Enter to submit, Shift+Enter for new line
    function handleKeyDown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            document.getElementById('chatForm').requestSubmit();
        }
    }

    // Switch Category Tabs: Branch vs Rider
    function switchCategory(category, btn) {
        document.querySelectorAll('.roster-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelectorAll('.contact-entry').forEach(el => {
            if (el.getAttribute('data-category') === category) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });
    }

    // Filter Contacts in Left Roster
    function filterRosters(query) {
        const q = query.toLowerCase().trim();
        const items = document.querySelectorAll('#rosterList .roster-item');
        items.forEach(el => {
            const name = el.getAttribute('data-name') || '';
            const sub = el.getAttribute('data-sub') || '';
            const branch = el.getAttribute('data-branch') || '';
            if (name.includes(q) || sub.includes(q) || branch.includes(q)) {
                el.parentElement.style.display = '';
            } else {
                el.parentElement.style.display = 'none';
            }
        });
    }

    // AJAX Send for instantaneous chat delivery
    async function handleSend(e) {
        e.preventDefault();
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const msgText = input.value.trim();
        if (!msgText) return;

        const formData = new FormData(form);
        sendBtn.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            if (response.ok) {
                const data = await response.json();
                input.value = '';

                // Append sent bubble
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble-row mine';

                let parcelHtml = '';
                if (data.data.parcel) {
                    parcelHtml = `
                        <div class="chat-parcel-card">
                            <span class="chat-parcel-number">📦 ${data.data.parcel.number}</span>
                            <span style="font-size: 10px; color: #8A7B7E;">${data.data.parcel.recipient}</span>
                        </div>
                    `;
                }

                bubble.innerHTML = `
                    ${parcelHtml}
                    <div class="chat-bubble mine">${escapeHtml(data.data.message)}</div>
                    <div class="chat-meta">
                        <span>You</span>
                        <span>·</span>
                        <time>${data.data.created_at_time}</time>
                        <span style="color: #8A7B7E;">✓</span>
                    </div>
                `;
                chatStream.appendChild(bubble);
                scrollToBottom();
            } else {
                form.submit();
            }
        } catch (err) {
            form.submit();
        } finally {
            sendBtn.disabled = false;
            input.focus();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

    // Live Auto-Polling for incoming replies
    @if($selectedContact)
    (function() {
        const contactId = {{ $selectedContact->id }};
        const pollInterval = 3500;
        let lastKnownMsgId = {{ $threadMessages->last()?->id ?? 0 }};

        async function pollNewMessages() {
            try {
                const res = await fetch(`/messages?contact_user_id=${contactId}&format=json`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();

                if (data.messages && data.messages.length > 0) {
                    const newMessages = data.messages.filter(m => m.id > lastKnownMsgId);
                    if (newMessages.length > 0) {
                        newMessages.forEach(m => {
                            if (!document.getElementById(`msg-${m.id}`)) {
                                const bubble = document.createElement('div');
                                bubble.id = `msg-${m.id}`;
                                bubble.className = `chat-bubble-row ${m.is_mine ? 'mine' : 'theirs'}`;

                                let parcelHtml = '';
                                if (m.parcel) {
                                    parcelHtml = `
                                        <div class="chat-parcel-card">
                                            <span class="chat-parcel-number">📦 ${m.parcel.number}</span>
                                            <span style="font-size: 10px; color: #8A7B7E;">${m.parcel.recipient} · ${m.parcel.city}</span>
                                        </div>
                                    `;
                                }

                                bubble.innerHTML = `
                                    ${parcelHtml}
                                    <div class="chat-bubble ${m.is_mine ? 'mine' : 'theirs'}">${escapeHtml(m.message)}</div>
                                    <div class="chat-meta">
                                        <span>${escapeHtml(m.sender_name)}</span>
                                        <span>·</span>
                                        <time>${m.created_at_time}</time>
                                    </div>
                                `;
                                chatStream.appendChild(bubble);
                            }
                        });
                        lastKnownMsgId = data.last_message_id;
                        scrollToBottom();
                    }
                }
            } catch (e) {}
        }

        setInterval(pollNewMessages, pollInterval);
    })();
    @endif
</script>
</body>
</html>
