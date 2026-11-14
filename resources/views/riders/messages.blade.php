<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Rider Messages & Hub Dispatch — Lumora Logistics</title>
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
            background: #FFFDFB;
            border-left: 4px solid #E2703A;
            color: inherit;
            text-decoration: none;
        }
        .roster-avatar-wrap {
            position: relative;
            flex-shrink: 0;
        }
        .roster-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #E2703A;
            color: #FFFDFB;
            display: grid;
            place-items: center;
            font-size: 13px;
            font-weight: 700;
        }
        .status-dot {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            border: 2px solid #FFFDFB;
            background: #1E6B37;
        }
        .roster-info {
            flex: 1;
            min-width: 0;
        }
        .roster-name {
            font-size: 13px;
            font-weight: 700;
            color: #3D1B3D;
        }
        .roster-snippet {
            font-size: 11.5px;
            color: #8A7B7E;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
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
            .messenger-layout { grid-template-columns: 280px 1fr; }
            .intel-pane { display: none; }
        }
        @media (max-width: 768px) {
            .messenger-layout { grid-template-columns: 1fr; height: auto; max-height: none; }
        }
    </style>
</head>
<body class="logistics-page">
@php
    $hubLabel = ($riderBranch ?? 'Pila') . ' Hub';
    $hubInitials = strtoupper(substr($riderBranch ?? 'PI', 0, 2));
    $allThread = isset($threadMessages) ? $threadMessages : $messages->reverse();
    $lastMsg = $allThread->last();
@endphp
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        @include('partials.topbar', [
            'category' => 'Courier Comms',
            'title' => 'Direct Messages with ' . $hubLabel
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
                    <div class="eyebrow" style="color: #E2703A;">COURIER TO BRANCH HUB MESSAGES</div>
                    <h1 style="font-size: 26px;">Direct Messages with {{ $hubLabel }}</h1>
                    <p class="intro">
                        Instant two-way communication channel between your courier account and {{ $hubLabel }} operations & dispatch.
                    </p>
                </div>
            </section>

            <div class="messenger-layout">
                <!-- 1. LEFT: Hub Channel Roster -->
                <aside class="roster-pane">
                    <div class="roster-header">
                        <div style="display: flex; justify-content: space-between; align-items: baseline;">
                            <strong style="color: #3D1B3D; font-size: 14px;">Assigned Hub Channel</strong>
                            <small style="color: #8A7B7E;">1 hub</small>
                        </div>
                    </div>
                    <ul class="roster-list">
                        <li>
                            <div class="roster-item">
                                <div class="roster-avatar-wrap">
                                    <div class="roster-avatar">{{ $hubInitials }}</div>
                                    <span class="status-dot" title="Active Hub"></span>
                                </div>
                                <div class="roster-info">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                        <span class="roster-name">{{ $hubLabel }} Operations</span>
                                        @if($lastMsg)
                                            <span style="font-size: 10px; color: #8A7B7E;">{{ $lastMsg->created_at->diffForHumans(null, true, true) }}</span>
                                        @endif
                                    </div>
                                    <div class="roster-snippet">
                                        @if($lastMsg)
                                            {{ $lastMsg->message }}
                                        @else
                                            {{ $hubAdmin?->name ?? 'Branch Operations Desk' }} · {{ $hubAdmin?->email ?? 'hub@lumora.party' }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </aside>

                <!-- 2. CENTER: Live Conversation Thread -->
                <section class="chat-pane">
                    <div class="chat-header">
                        <div class="chat-contact-title">
                            <div class="roster-avatar-wrap">
                                <div class="roster-avatar" style="background: #3D1B3D;">{{ $hubInitials }}</div>
                                <span class="status-dot"></span>
                            </div>
                            <div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <h2 style="margin: 0; font-size: 16px; color: #3D1B3D;">
                                        {{ $hubLabel }} ({{ $hubAdmin?->name ?? 'Operations Desk' }})
                                    </h2>
                                    <span style="font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: rgba(30,107,55,0.1); color: #1E6B37;">
                                        ● CONNECTED
                                    </span>
                                </div>
                                <small style="color: #8A7B7E; font-size: 11px;">
                                    ✉ {{ $hubAdmin?->email ?? 'operations@lumora.party' }} · 🏢 {{ $hubLabel }}
                                </small>
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 11px; color: #E2703A; font-weight: 700; background: rgba(226,112,58,0.08); padding: 4px 10px; border-radius: 8px;">
                                📦 {{ $assignedParcels->count() }} assigned parcels
                            </span>
                        </div>
                    </div>

                    <div class="chat-stream" id="chatStream">
                        @forelse($allThread as $msg)
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
                                    <span>{{ $isMine ? 'You' : ($msg->sender?->name ?: ($hubLabel . ' Staff')) }}</span>
                                    <span>·</span>
                                    <time>{{ $msg->created_at->format('g:i A') }}</time>
                                    @if($isMine)
                                        <span style="color: {{ $msg->read_at ? '#1E6B37' : '#8A7B7E' }};">
                                            {{ $msg->read_at ? '✓✓' : '✓' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; margin: auto; padding: 40px; color: #8A7B7E;">
                                <div style="font-size: 32px; margin-bottom: 8px;">💬</div>
                                <strong style="color: #3D1B3D; display: block; font-size: 14px;">
                                    No messages with {{ $hubLabel }} yet
                                </strong>
                                <p style="font-size: 12px; margin: 4px 0 0;">
                                    Send delivery updates, exception reports, or assistance requests to your branch hub below.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    <div class="chat-composer-wrap">
                        <div class="quick-presets">
                            <button type="button" class="preset-chip" onclick="insertPreset('📍 Arrived at customer delivery address. Waiting for recipient.')">
                                📍 Arrived at address
                            </button>
                            <button type="button" class="preset-chip" onclick="insertPreset('📞 Receiver phone is unreachable. Please verify contact details.')">
                                📞 Receiver unreachable
                            </button>
                            <button type="button" class="preset-chip" onclick="insertPreset('🏠 Address is incomplete or cannot be located. Requesting guidance.')">
                                🏠 Address issue
                            </button>
                            <button type="button" class="preset-chip" onclick="insertPreset('🏢 Heading back to {{ $hubLabel }} for parcel & COD handover.')">
                                🏢 Heading to hub
                            </button>
                        </div>

                        <form method="POST" action="{{ route('riders.messages.send') }}" id="chatForm" onsubmit="handleSend(event)">
                            @csrf
                            @if($hubAdmin)
                                <input type="hidden" name="recipient_id" value="{{ $hubAdmin->id }}">
                            @endif

                            @if($assignedParcels->isNotEmpty())
                                <div class="composer-attach-bar">
                                    <span>Reference Parcel:</span>
                                    <select name="parcel_id" style="padding: 2px 8px; border-radius: 6px; border: 1px solid #EBDDD8; font-size: 11px; background: #FAF6F0; max-width: 320px;">
                                        <option value="">None (General Hub Message)</option>
                                        @foreach($assignedParcels as $p)
                                            <option value="{{ $p->id }}" @selected(request('parcel_id') == $p->id)>
                                                {{ $p->parcel_number }} — {{ $p->recipient_name }} ({{ $p->city }})
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
                                    placeholder="Type message for {{ $hubLabel }}... (Press Enter to send, Shift+Enter for new line)"
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
                </section>

                <!-- 3. RIGHT: Hub Intel Pane -->
                <aside class="intel-pane">
                    <div class="intel-card">
                        <div class="intel-avatar-lg">{{ $hubInitials }}</div>
                        <h3 style="text-align: center; margin: 0 0 4px; font-size: 15px; color: #3D1B3D;">
                            {{ $hubLabel }}
                        </h3>
                        <div style="text-align: center; margin-bottom: 14px;">
                            <span style="font-size: 11px; font-weight: 700; color: #E2703A; background: #FAF6F0; border: 1px solid #EBDDD8; padding: 2px 8px; border-radius: 999px;">
                                Branch Sorting Hub
                            </span>
                        </div>
                        <div class="intel-detail-row">
                            <span class="intel-detail-label">Dispatcher</span>
                            <span class="intel-detail-val">{{ $hubAdmin?->name ?? 'Hub Admin' }}</span>
                        </div>
                        <div class="intel-detail-row">
                            <span class="intel-detail-label">Email</span>
                            <span class="intel-detail-val" style="font-size: 11px;">{{ $hubAdmin?->email ?? 'hub@lumora.party' }}</span>
                        </div>
                        <div class="intel-detail-row">
                            <span class="intel-detail-label">Branch</span>
                            <span class="intel-detail-val">{{ $riderBranch ?? 'Pila' }}</span>
                        </div>
                        <div class="intel-detail-row">
                            <span class="intel-detail-label">Assigned Queue</span>
                            <span class="intel-detail-val" style="color: #E2703A;">{{ $assignedParcels->count() }} parcels</span>
                        </div>
                    </div>

                    @if($assignedParcels->isNotEmpty())
                        <div class="intel-card" style="padding: 12px;">
                            <strong style="display: block; font-size: 12px; color: #3D1B3D; margin-bottom: 8px;">
                                Your Parcel Queue
                            </strong>
                            <div style="display: flex; flex-direction: column; gap: 6px; max-height: 240px; overflow-y: auto;">
                                @foreach($assignedParcels as $p)
                                    <div style="font-size: 11px; padding: 6px; background: #FAF6F0; border: 1px solid #EBDDD8; border-radius: 6px;">
                                        <div style="font-weight: 700; color: #3D1B3D;">{{ $p->parcel_number }}</div>
                                        <div style="color: #8A7B7E; font-size: 10px;">{{ $p->recipient_name }} · {{ $p->city }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </main>
</div>
<script>
    const chatStream = document.getElementById('chatStream');
    function scrollToBottom() {
        if (chatStream) chatStream.scrollTop = chatStream.scrollHeight;
    }
    scrollToBottom();

    function insertPreset(text) {
        const input = document.getElementById('messageInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }

    function handleKeyDown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            document.getElementById('chatForm').requestSubmit();
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.innerText = text;
        return div.innerHTML;
    }

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
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble-row mine';
                let parcelHtml = '';
                if (data.data && data.data.parcel) {
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

    (function() {
        let lastKnownMsgId = {{ $allThread->last()?->id ?? 0 }};
        async function pollNewMessages() {
            try {
                const res = await fetch(`{{ route('riders.messages') }}?format=json`, {
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
        setInterval(pollNewMessages, 3500);
    })();
</script>
</body>
</html>
