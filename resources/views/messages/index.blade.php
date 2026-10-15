<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Messages & Courier Dispatch — Lumora Logistics</title>
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

        /* Left: Courier Roster */
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
            font-weight: 700;
            font-size: 13px;
        }
        .roster-avatar.active-rider {
            background: #E2703A;
        }
        .status-dot {
            width: 11px;
            height: 11px;
            border-radius: 50%;
            position: absolute;
            bottom: -1px;
            right: -1px;
            border: 2px solid #FAF6F0;
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
            margin-bottom: 3px;
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
        }
        .roster-snippet {
            font-size: 11px;
            color: #8A7B7E;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }
        .roster-badge {
            background: #E2703A;
            color: #FFFDFB;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 999px;
            margin-left: 6px;
            flex-shrink: 0;
        }

        /* Center: Chat Thread */
        .chat-pane {
            display: flex;
            flex-direction: column;
            background: #FFFDFB;
            overflow: hidden;
        }
        .chat-header {
            padding: 14px 20px;
            border-bottom: 1px solid #EBDDD8;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FFFDFB;
        }
        .chat-courier-title {
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
            max-width: 76%;
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
            padding: 12px 18px;
            border-radius: 18px;
            font-size: 13.5px;
            line-height: 1.5;
            word-break: break-word;
            box-shadow: 0 1px 3px rgba(61, 27, 61, 0.05);
        }
        .chat-bubble.mine {
            background: #3D1B3D;
            color: #FFFDFB;
            border-bottom-right-radius: 4px;
        }
        .chat-bubble.theirs {
            background: #FFFDFB;
            color: #3A2E30;
            border-bottom-left-radius: 4px;
            border: 1px solid #EBDDD8;
        }
        .chat-meta {
            font-size: 10px;
            color: #8A7B7E;
            margin-top: 4px;
            padding: 0 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .chat-parcel-card {
            background: #FBEFEA;
            border: 1px solid #EBDDD8;
            border-radius: 10px;
            padding: 8px 12px;
            margin-bottom: 6px;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: #3A2E30;
            text-decoration: none;
        }
        .chat-parcel-card:hover {
            border-color: #E2703A;
        }
        .chat-parcel-number {
            font-family: monospace;
            font-weight: 700;
            color: #E2703A;
        }

        /* Composer */
        .chat-composer-wrap {
            padding: 14px 20px;
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
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            color: #3D1B3D;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s;
        }
        .preset-chip:hover {
            background: #FBEFEA;
            border-color: #E2703A;
            color: #E2703A;
        }
        .composer-bar {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        .composer-input {
            flex: 1;
            min-height: 44px;
            max-height: 100px;
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

        /* Right: Courier Intel Card */
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
        <header class="topbar">
            <div class="breadcrumb">
                <span>Operations</span>
                <span class="crumb-divider">/</span>
                <strong>Courier Dispatch Messenger</strong>
            </div>
            <div class="topbar-actions">
                <span class="user-name">{{ auth()->user()->name ?? 'Admin Dispatch' }}</span>
            </div>
        </header>

        <div class="content-wrap" style="padding-bottom: 24px;">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <section class="welcome-row" style="margin-bottom: 16px;">
                <div>
                    <div class="eyebrow" style="color: #E2703A;">OPERATIONS DISPATCH DESK</div>
                    <h1 style="font-size: 26px;">Live Courier Communications</h1>
                    <p class="intro">
                        Real-time coordination, address verification, and delivery dispatch with field couriers.
                    </p>
                </div>
            </section>

            <!-- Multi-Pane Dispatch Messenger -->
            <div class="messenger-layout">
                <!-- 1. LEFT: Courier Roster List -->
                <aside class="roster-pane">
                    <div class="roster-header">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <strong style="color: #3D1B3D; font-size: 14px;">Courier Roster</strong>
                            <small style="color: #8A7B7E;">{{ $riderSummaries->count() }} drivers</small>
                        </div>
                        <input
                            type="search"
                            id="rosterSearchInput"
                            class="roster-search"
                            placeholder="⌕ Filter by name, vehicle, or zone..."
                            oninput="filterRosters(this.value)"
                        >
                    </div>

                    <div class="roster-tabs">
                        <button type="button" class="roster-tab active" onclick="setRosterTab('all', this)">All</button>
                        <button type="button" class="roster-tab" onclick="setRosterTab('online', this)">Online</button>
                        <button type="button" class="roster-tab" onclick="setRosterTab('unread', this)">Unread</button>
                    </div>

                    <ul class="roster-list" id="rosterList">
                        @forelse($riderSummaries as $item)
                            @php
                                $rider = $item['rider'];
                                $isSelected = ($selectedRider && $selectedRider->id === $rider->id);
                                $isOnline = ($rider->availability === 'online' || $rider->availability === 'available');
                                $initials = strtoupper(substr($rider->user->name, 0, 2));
                                $zone = $rider->areas->pluck('name')->join(', ') ?: ($rider->areas->first()?->province ?? 'General Zone');
                            @endphp
                            <li>
                                <a
                                    href="{{ route('messages.index', ['rider_user_id' => $rider->user_id]) }}"
                                    class="roster-item {{ $isSelected ? 'active' : '' }}"
                                    data-name="{{ strtolower($rider->user->name) }}"
                                    data-zone="{{ strtolower($zone) }}"
                                    data-vehicle="{{ strtolower($rider->vehicle_type ?? '') }}"
                                    data-online="{{ $isOnline ? '1' : '0' }}"
                                    data-unread="{{ $item['unread_count'] }}"
                                >
                                    <div class="roster-avatar-wrap">
                                        <div class="roster-avatar {{ $isSelected ? 'active-rider' : '' }}">
                                            {{ $initials }}
                                        </div>
                                        <span class="status-dot {{ $isOnline ? 'online' : 'offline' }}" title="{{ ucfirst($rider->availability) }}"></span>
                                    </div>

                                    <div class="roster-info">
                                        <div class="roster-top">
                                            <span class="roster-name">{{ $rider->user->name }}</span>
                                            @if($item['latest_message'])
                                                <span class="roster-time">{{ $item['latest_message']->created_at->diffForHumans(null, true, true) }}</span>
                                            @endif
                                        </div>
                                        <div class="roster-snippet">
                                            @if($item['latest_message'])
                                                {{ $item['latest_message']->message }}
                                            @else
                                                <span style="font-style: italic;">{{ $rider->vehicle_type ?: 'Motorcycle' }} · {{ $zone }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($item['unread_count'] > 0)
                                        <span class="roster-badge">{{ $item['unread_count'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @empty
                            <li style="padding: 32px 16px; text-align: center; color: #8A7B7E; font-size: 12px;">
                                No approved riders registered yet.
                            </li>
                        @endforelse
                    </ul>
                </aside>

                <!-- 2. CENTER: Active Conversation Thread -->
                <section class="chat-pane">
                    @if($selectedRider)
                        @php
                            $selectedIsOnline = ($selectedRider->availability === 'online' || $selectedRider->availability === 'available');
                            $selectedInitials = strtoupper(substr($selectedRider->user->name, 0, 2));
                            $selectedZone = $selectedRider->areas->pluck('name')->join(', ') ?: ($selectedRider->areas->first()?->province ?? 'General Zone');
                        @endphp

                        <!-- Header -->
                        <div class="chat-header">
                            <div class="chat-courier-title">
                                <div class="roster-avatar-wrap">
                                    <div class="roster-avatar" style="background: #3D1B3D;">
                                        {{ $selectedInitials }}
                                    </div>
                                    <span class="status-dot {{ $selectedIsOnline ? 'online' : 'offline' }}"></span>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <h2 style="margin: 0; font-size: 16px; color: #3D1B3D;">{{ $selectedRider->user->name }}</h2>
                                        <span style="font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: {{ $selectedIsOnline ? 'rgba(30,107,55,0.1)' : '#EBDDD8' }}; color: {{ $selectedIsOnline ? '#1E6B37' : '#8A7B7E' }};">
                                            ● {{ strtoupper($selectedRider->availability) }}
                                        </span>
                                    </div>
                                    <small style="color: #8A7B7E; font-size: 11px;">
                                        {{ $selectedRider->vehicle_type ?: 'Motorcycle' }} · 📍 {{ $selectedZone }}
                                    </small>
                                </div>
                            </div>

                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 11px; color: #1E6B37; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; background: rgba(30,107,55,0.08); padding: 4px 10px; border-radius: 8px;">
                                    ⛟ {{ $selectedRiderParcels->count() }} active delivery queue
                                </span>
                                @if(request('parcel_id'))
                                    <a href="{{ route('messages.index', ['rider_user_id' => $selectedRider->user_id]) }}" class="text-button" style="font-size: 11px; color: #E2703A;">
                                        Clear parcel filter (Show all)
                                    </a>
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
                                        <a href="{{ route('shipments.show', $msg->parcel->shipment ?? $msg->parcel_id) }}" target="_blank" class="chat-parcel-card" title="Open shipment details">
                                            <div>
                                                <span class="chat-parcel-number">📦 {{ $msg->parcel->parcel_number }}</span>
                                                <div style="font-size: 10.5px; color: #8A7B7E; margin-top: 2px;">
                                                    {{ $msg->parcel->recipient_name }} · {{ $msg->parcel->city }}
                                                </div>
                                            </div>
                                            <span style="font-size: 9.5px; font-weight: 700; background: #FFFDFB; border: 1px solid #EBDDD8; padding: 2px 6px; border-radius: 4px;">
                                                {{ ucwords(str_replace('_', ' ', $msg->parcel->status)) }}
                                            </span>
                                        </a>
                                    @endif

                                    <div class="chat-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                        {{ $msg->message }}
                                    </div>

                                    <div class="chat-meta">
                                        <span>{{ $isMine ? 'Dispatch' : $selectedRider->user->name }}</span>
                                        <span>·</span>
                                        <time>{{ $msg->created_at->format('g:i A') }}</time>
                                        @if($isMine)
                                            <span style="color: {{ $msg->read_at ? '#1E6B37' : '#8A7B7E' }};" title="{{ $msg->read_at ? 'Read by courier' : 'Delivered to device' }}">
                                                {{ $msg->read_at ? '✓✓' : '✓' }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div style="text-align: center; margin: auto; padding: 40px; color: #8A7B7E;">
                                    <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                                    <strong style="color: #3D1B3D; display: block; font-size: 14px;">No messages with {{ $selectedRider->user->name }} yet</strong>
                                    <p style="font-size: 12px; margin: 4px 0 0;">Send operational instructions, delivery questions, or address notes below.</p>
                                </div>
                            @endforelse
                        </div>

                        <!-- Composer & Quick Presets -->
                        <div class="chat-composer-wrap">
                            <!-- Quick Presets -->
                            <div class="quick-presets">
                                <button type="button" class="preset-chip" onclick="insertPreset('📍 Please report your current delivery location and ETA.')">
                                    📍 Location update
                                </button>
                                <button type="button" class="preset-chip" onclick="insertPreset('⚡ Customer requested priority delivery for this parcel.')">
                                    ⚡ Rush delivery
                                </button>
                                <button type="button" class="preset-chip" onclick="insertPreset('📞 Recipient is currently unreachable. Please retry delivery or leave note.')">
                                    📞 Unreachable
                                </button>
                                <button type="button" class="preset-chip" onclick="insertPreset('🏢 Sorting center handover is ready. Please proceed to intake hub.')">
                                    🏢 Hub return
                                </button>
                                <button type="button" class="preset-chip" onclick="insertPreset('✓ Delivery confirmed. Thank you!')">
                                    ✓ Completed
                                </button>
                            </div>

                            <form method="POST" action="{{ route('messages.send') }}" id="chatForm" onsubmit="handleSend(event)">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $selectedRider->user_id }}">

                                <!-- Parcel Reference Selector -->
                                <div class="composer-attach-bar">
                                    <span>Attach Parcel:</span>
                                    <select name="parcel_id" id="parcelSelect" style="padding: 2px 8px; border-radius: 6px; border: 1px solid #EBDDD8; font-size: 11px; background: #FAF6F0; max-width: 320px;">
                                        <option value="">None (General Message)</option>
                                        @foreach($selectedRiderParcels as $p)
                                            <option value="{{ $p->id }}" @selected(request('parcel_id') == $p->id)>
                                                {{ $p->parcel_number }} — {{ $p->recipient_name }} ({{ $p->city }}) [{{ ucwords(str_replace('_', ' ', $p->status)) }}]
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="composer-bar">
                                    <textarea
                                        name="message"
                                        id="messageInput"
                                        class="composer-input"
                                        rows="2"
                                        placeholder="Type dispatch instructions for {{ $selectedRider->user->name }}... (Press Enter to send, Shift+Enter for new line)"
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
                        <!-- No Rider Selected State -->
                        <div style="margin: auto; text-align: center; padding: 60px 20px; color: #8A7B7E;">
                            <div style="font-size: 42px; margin-bottom: 12px;">📨</div>
                            <h2 style="font-family: 'Playfair Display', Georgia, serif; color: #3D1B3D; font-size: 22px; margin-bottom: 6px;">Select a Courier</h2>
                            <p style="font-size: 13px; max-width: 380px; margin: 0 auto; line-height: 1.6;">
                                Select an approved courier from the roster on the left to review message history or send live delivery dispatches.
                            </p>
                        </div>
                    @endif
                </section>

                <!-- 3. RIGHT: Courier Intel & Active Deliveries Inspector -->
                @if($selectedRider)
                    <aside class="intel-pane">
                        <div class="intel-card" style="text-align: center;">
                            <div class="intel-avatar-lg">
                                {{ $selectedInitials }}
                            </div>
                            <strong style="color: #3D1B3D; font-size: 15px; display: block;">{{ $selectedRider->user->name }}</strong>
                            <small style="color: #8A7B7E; font-size: 11px;">{{ $selectedRider->vehicle_type ?: 'Motorcycle Courier' }}</small>

                            <div style="margin-top: 10px;">
                                <span style="font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: {{ $selectedIsOnline ? 'rgba(30,107,55,0.1)' : '#EBDDD8' }}; color: {{ $selectedIsOnline ? '#1E6B37' : '#8A7B7E' }};">
                                    ● {{ strtoupper($selectedRider->availability) }}
                                </span>
                            </div>

                            <div class="detail-divider" style="margin: 14px 0;"></div>

                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Phone</span>
                                <span class="intel-detail-val">
                                    @if($selectedRider->phone)
                                        <a href="tel:{{ $selectedRider->phone }}" style="color: #E2703A; text-decoration: none;">{{ $selectedRider->phone }}</a>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>

                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Email</span>
                                <span class="intel-detail-val" style="font-size: 11px; max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $selectedRider->user->email }}
                                </span>
                            </div>

                            <div class="intel-detail-row">
                                <span class="intel-detail-label">License</span>
                                <span class="intel-detail-val">{{ $selectedRider->license_number ?: 'On File' }}</span>
                            </div>

                            <div class="intel-detail-row">
                                <span class="intel-detail-label">Assigned Area</span>
                                <span class="intel-detail-val" style="color: #3D1B3D;">📍 {{ $selectedZone }}</span>
                            </div>
                        </div>

                        <!-- Active Deliveries Widget -->
                        <div class="intel-card" style="flex: 1; display: flex; flex-direction: column;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 10px;">
                                <strong style="color: #3D1B3D; font-size: 13px;">Active Parcels</strong>
                                <small style="color: #8A7B7E; font-weight: 700;">{{ $selectedRiderParcels->count() }}</small>
                            </div>

                            <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                                @forelse($selectedRiderParcels as $parcel)
                                    @php
                                        $chipClass = match($parcel->status) {
                                            'delivered' => 'sage-wash',
                                            'in_transit', 'out_for_delivery', 'rider_received' => 'in-transit',
                                            'exception', 'failed' => 'exception',
                                            default => 'packed',
                                        };
                                    @endphp
                                    <div style="background: #FAF6F0; border: 1px solid #EBDDD8; border-radius: 8px; padding: 8px 10px; font-size: 11px;">
                                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                            <a href="{{ route('shipments.show', $parcel->shipment ?? $parcel->id) }}" target="_blank" style="font-family: monospace; font-weight: 700; color: #3D1B3D; text-decoration: none;">
                                                {{ $parcel->parcel_number }}
                                            </a>
                                            <span class="status-chip {{ $chipClass }}" style="font-size: 9px; padding: 2px 6px;">
                                                {{ ucwords(str_replace('_', ' ', $parcel->status)) }}
                                            </span>
                                        </div>
                                        <div style="color: #8A7B7E; font-size: 10.5px; margin-top: 2px;">
                                            {{ $parcel->recipient_name }} · {{ $parcel->city }}
                                        </div>
                                        <button
                                            type="button"
                                            class="text-button"
                                            style="margin-top: 4px; font-size: 10px; color: #E2703A; padding: 0;"
                                            onclick="selectParcelForChat({{ $parcel->id }}, '{{ $parcel->parcel_number }}')"
                                        >
                                            ＋ Mention in chat
                                        </button>
                                    </div>
                                @empty
                                    <div style="padding: 20px 0; text-align: center; color: #8A7B7E; font-size: 11px;">
                                        No active deliveries currently in transit with this driver.
                                    </div>
                                @endforelse
                            </div>
                        </div>
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

    // Attach parcel from inspector to composer
    function selectParcelForChat(parcelId, parcelNumber) {
        const select = document.getElementById('parcelSelect');
        if (select) {
            select.value = parcelId;
        }
        const input = document.getElementById('messageInput');
        if (input) {
            input.value = `Regarding parcel ${parcelNumber}: `;
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

    // Filter Couriers in Left Roster
    function filterRosters(query) {
        const q = query.toLowerCase().trim();
        const items = document.querySelectorAll('#rosterList .roster-item');
        items.forEach(el => {
            const name = el.getAttribute('data-name') || '';
            const zone = el.getAttribute('data-zone') || '';
            const vehicle = el.getAttribute('data-vehicle') || '';
            if (name.includes(q) || zone.includes(q) || vehicle.includes(q)) {
                el.parentElement.style.display = '';
            } else {
                el.parentElement.style.display = 'none';
            }
        });
    }

    // Filter Tabs: All / Online / Unread
    function setRosterTab(type, btn) {
        document.querySelectorAll('.roster-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const items = document.querySelectorAll('#rosterList .roster-item');
        items.forEach(el => {
            const isOnline = el.getAttribute('data-online') === '1';
            const unread = parseInt(el.getAttribute('data-unread') || '0', 10);

            if (type === 'all') {
                el.parentElement.style.display = '';
            } else if (type === 'online') {
                el.parentElement.style.display = isOnline ? '' : 'none';
            } else if (type === 'unread') {
                el.parentElement.style.display = unread > 0 ? '' : 'none';
            }
        });
    }

    // AJAX Send for seamless instantaneous chat delivery
    async function handleSend(e) {
        e.preventDefault();
        const form = document.getElementById('chatForm');
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const msgText = input.value.trim();
        if (!msgText) return;

        const formData = new FormData(form);

        // Optimistically show sending or disable button briefly
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
                        <span>Dispatch</span>
                        <span>·</span>
                        <time>${data.data.created_at_time}</time>
                        <span style="color: #8A7B7E;">✓</span>
                    </div>
                `;
                chatStream.appendChild(bubble);
                scrollToBottom();
            } else {
                // Fallback to normal submission on error
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

    // Live Auto-Polling for incoming rider replies
    @if($selectedRider)
    (function() {
        const riderUserId = {{ $selectedRider->user_id }};
        const pollInterval = 3500;
        let lastKnownMsgId = {{ $threadMessages->last()?->id ?? 0 }};

        async function pollNewMessages() {
            try {
                const res = await fetch(`/messages?rider_user_id=${riderUserId}&format=json`, {
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
