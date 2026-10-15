<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Rider Messages & Support — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .chat-thread-container {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 520px;
            overflow-y: auto;
            padding: 16px;
            background: #FFFDFB;
            border-radius: 16px;
            border: 1px solid #EBDDD8;
            margin-bottom: 20px;
        }
        .chat-row {
            display: flex;
            flex-direction: column;
            max-width: 78%;
        }
        .chat-row.mine {
            align-self: flex-end;
            align-items: flex-end;
        }
        .chat-row.theirs {
            align-self: flex-start;
            align-items: flex-start;
        }
        .chat-msg-bubble {
            padding: 12px 16px;
            border-radius: 16px;
            font-size: 13px;
            line-height: 1.5;
            word-break: break-word;
        }
        .chat-msg-bubble.mine {
            background: #3D1B3D;
            color: #FFFDFB;
            border-bottom-right-radius: 4px;
        }
        .chat-msg-bubble.theirs {
            background: #FBEFEA;
            color: #3A2E30;
            border-bottom-left-radius: 4px;
            border: 1px solid #EBDDD8;
        }
        .chat-meta {
            font-size: 10px;
            color: #8A7B7E;
            margin-top: 4px;
            padding: 0 4px;
        }
        .parcel-tag {
            display: inline-block;
            font-size: 10px;
            font-family: monospace;
            background: rgba(226, 112, 58, 0.12);
            color: #E2703A;
            padding: 2px 6px;
            border-radius: 4px;
            margin-bottom: 4px;
            font-weight: 700;
        }
    </style>
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">
                <a href="{{ route('riders.dashboard') }}" style="color: inherit; text-decoration: none;">Rider App</a>
                <span class="crumb-divider">/</span>
                <strong>Operations Messages</strong>
            </div>
            <div class="topbar-actions">
                <a href="{{ route('riders.dashboard') }}" class="action-chip-btn btn-secondary">
                    ← Back to Deliveries & Scanner
                </a>
            </div>
        </header>

        <div class="content-wrap">
            @if(session('success'))
                <div class="server-alert" role="status"><span>✓</span> {{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
            @endif

            <section class="welcome-row">
                <div>
                    <div class="eyebrow">COMMUNICATION & DISPATCH SUPPORT</div>
                    <h1>Operations Chat</h1>
                    <p class="intro">
                        Direct channel between you and the sorting center desk for parcel coordination, delivery exception reports, and address clarifications.
                    </p>
                </div>
            </section>

            <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 24px; align-items: start;">
                <!-- Chat Feed -->
                <section class="panel" style="margin-bottom: 0;">
                    <div class="panel-heading">
                        <div>
                            <div class="section-kicker">CONVERSATION THREAD</div>
                            <h2>Sorting center dispatch desk</h2>
                        </div>
                        @if(request('parcel_id'))
                            <a href="{{ route('riders.messages') }}" style="font-size: 11px; color: #E2703A; font-weight: 700; text-decoration: none;">
                                Clear parcel filter (Show all)
                            </a>
                        @endif
                    </div>

                    <div class="chat-thread-container">
                        @forelse($messages as $msg)
                            @php
                                $isMine = ($msg->sender_id === auth()->id());
                            @endphp
                            <div class="chat-row {{ $isMine ? 'mine' : 'theirs' }}">
                                @if($msg->parcel)
                                    <span class="parcel-tag">
                                        📦 Parcel #{{ $msg->parcel->parcel_number }} · {{ $msg->parcel->recipient_name }}
                                    </span>
                                @endif
                                <div class="chat-msg-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                    {{ $msg->message }}
                                </div>
                                <div class="chat-meta">
                                    <strong>{{ $isMine ? 'You' : ($msg->sender?->name ?: 'Operations Manager') }}</strong>
                                    · {{ $msg->created_at->format('M j, g:i A') }} ({{ $msg->created_at->diffForHumans() }})
                                </div>
                            </div>
                        @empty
                            <div style="text-align: center; padding: 40px; color: #8A7B7E;">
                                <p style="font-size: 14px; font-weight: 600; margin-bottom: 4px;">No messages yet</p>
                                <p style="font-size: 12px; margin: 0;">Send a message to operations using the form on the right.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="table-footer">{{ $messages->links() }}</div>
                </section>

                <!-- New Message Composer -->
                <section class="panel" style="margin-bottom: 0;">
                    <div class="panel-heading">
                        <div>
                            <div class="section-kicker">COMPOSE MESSAGE</div>
                            <h2>Send update to staff</h2>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('riders.messages.send') }}" class="form-grid">
                        @csrf
                        <label class="full-span">
                            Parcel Reference (Optional)
                            <select name="parcel_id">
                                <option value="">General inquiry / Fleet update</option>
                                @foreach($assignedParcels as $p)
                                    <option value="{{ $p->id }}" @selected(request('parcel_id') == $p->id)>
                                        {{ $p->parcel_number }} — {{ $p->recipient_name }} ({{ $p->city }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="full-span">
                            Message
                            <div style="display: flex; gap: 6px; overflow-x: auto; padding-bottom: 6px; margin-bottom: 8px;">
                                <button type="button" class="preset-chip" style="background: #FAF6F0; border: 1px solid #EBDDD8; padding: 4px 8px; border-radius: 999px; font-size: 11px; cursor: pointer; white-space: nowrap;" onclick="insertRiderPreset('📍 Arrived at delivery address. Waiting for recipient.')">
                                    📍 Arrived at address
                                </button>
                                <button type="button" class="preset-chip" style="background: #FAF6F0; border: 1px solid #EBDDD8; padding: 4px 8px; border-radius: 999px; font-size: 11px; cursor: pointer; white-space: nowrap;" onclick="insertRiderPreset('📞 Recipient phone is unreachable. Please verify contact number.')">
                                    📞 Unreachable
                                </button>
                                <button type="button" class="preset-chip" style="background: #FAF6F0; border: 1px solid #EBDDD8; padding: 4px 8px; border-radius: 999px; font-size: 11px; cursor: pointer; white-space: nowrap;" onclick="insertRiderPreset('🏢 Heading back to sorting center for parcel handover.')">
                                    🏢 Heading to hub
                                </button>
                            </div>
                            <textarea
                                name="message"
                                id="riderMessageInput"
                                rows="4"
                                required
                                placeholder="Type your message to operations staff..."
                                style="width: 100%; border: 1px solid #EBDDD8; border-radius: 10px; padding: 12px; font-size: 13px; resize: vertical; background: #FFFDFB;"
                            ></textarea>
                        </label>

                        <button class="primary-button full-span" type="submit" style="padding: 12px; font-size: 13px;">
                            ✉ Send Message
                        </button>
                    </form>
                </section>
            </div>
        </div>
    </main>
</div>
<script>
    const threadContainer = document.querySelector('.chat-thread-container');
    if (threadContainer) {
        threadContainer.scrollTop = threadContainer.scrollHeight;
    }

    function insertRiderPreset(text) {
        const input = document.getElementById('riderMessageInput');
        if (input) {
            input.value = text;
            input.focus();
        }
    }
</script>
</body>
</html>
