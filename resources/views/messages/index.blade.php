<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Messages — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page">
<div class="app-shell">
    @include('partials.sidebar')
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">
                <span>Workspace</span>
                <span class="crumb-divider">/</span>
                <strong>Messages & Rider Comms</strong>
            </div>
            <div class="topbar-actions">
                <span class="user-name">{{ auth()->user()->name ?? 'Admin' }}</span>
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
                    <div class="eyebrow">OPERATIONS COMMUNICATION</div>
                    <h1>Rider & Operations Dispatch</h1>
                    <p class="intro">
                        Real-time message exchange between sorting-center operations staff and delivery couriers on the road.
                    </p>
                </div>
            </section>

            <section class="dashboard-grid">
                <!-- Compose Message -->
                <article class="panel">
                    <div class="section-kicker">NEW DISPATCH</div>
                    <h2>Contact a courier</h2>
                    <form method="POST" action="{{ route('messages.send') }}" class="form-grid">
                        @csrf
                        <label class="full-span">
                            Select Courier
                            <select name="recipient_id" id="recipientSelect" required>
                                <option value="">Choose an approved rider</option>
                                @foreach($riders as $rider)
                                    <option value="{{ $rider->user_id }}" @selected(request('rider_user_id') == $rider->user_id)>
                                        {{ $rider->user->name }} — {{ $rider->areas->pluck('name')->join(', ') ?: 'General Area' }} ({{ strtoupper($rider->availability) }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="full-span">
                            Parcel Reference (Optional)
                            <select name="parcel_id">
                                <option value="">General announcement / Area query</option>
                                @foreach($activeParcels ?? [] as $parcel)
                                    <option value="{{ $parcel->id }}" @selected(request('parcel_id') == $parcel->id)>
                                        {{ $parcel->parcel_number }} — {{ $parcel->recipient_name }} ({{ $parcel->city }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="full-span">
                            Message
                            <textarea
                                name="message"
                                rows="4"
                                required
                                placeholder="Write operational instructions or delivery updates for the courier..."
                                style="width: 100%; border: 1px solid #EBDDD8; border-radius: 10px; padding: 12px; font-size: 13px; resize: vertical; background: #FFFDFB;"
                            ></textarea>
                        </label>

                        <button class="primary-button full-span" type="submit" style="padding: 12px;">
                            ✉ Send to Rider
                        </button>
                    </form>
                </article>

                <!-- Message History Feed -->
                <article class="panel">
                    <div class="panel-heading" style="margin-bottom: 12px;">
                        <div>
                            <div class="section-kicker">MESSAGE HISTORY</div>
                            <h2>Recent communication</h2>
                        </div>
                        @if(request('rider_user_id') || request('parcel_id'))
                            <a href="{{ route('messages.index') }}" style="font-size: 11px; color: #E2703A; font-weight: 700; text-decoration: none;">Show all</a>
                        @endif
                    </div>

                    <div class="exception-list" style="max-height: 520px; overflow-y: auto; padding-right: 4px;">
                        @forelse($messages as $message)
                            @php
                                $isFromMe = ($message->sender_id === auth()->id());
                                $counterpart = $isFromMe ? $message->recipient : $message->sender;
                            @endphp
                            <div class="exception-item" style="align-items: flex-start; padding: 12px; border-bottom: 1px solid #EBDDD8;">
                                <span class="exception-symbol {{ $isFromMe ? 'plum-bg' : 'coral-bg' }}" style="flex-shrink: 0; margin-top: 2px;">
                                    {{ $isFromMe ? '↗' : '↙' }}
                                </span>
                                <span class="exception-copy" style="flex: 1;">
                                    <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                        <strong>
                                            {{ $isFromMe ? 'To: ' : 'From: ' }} {{ $counterpart?->name ?: 'Rider Courier' }}
                                        </strong>
                                        <small style="color: #8A7B7E;">{{ $message->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div style="margin: 4px 0; font-size: 13px; color: #3A2E30; line-height: 1.4;">
                                        {{ $message->message }}
                                    </div>
                                    @if($message->parcel)
                                        <div style="margin-top: 4px;">
                                            <span style="font-size: 10px; font-family: monospace; background: #FBEFEA; color: #E2703A; padding: 1px 5px; border-radius: 4px; font-weight: 700;">
                                                📦 Parcel: {{ $message->parcel->parcel_number }} · {{ $message->parcel->recipient_name }}
                                            </span>
                                        </div>
                                    @endif
                                </span>
                                @if(! $isFromMe && $message->sender_id)
                                    <button
                                        type="button"
                                        class="action-chip-btn btn-secondary"
                                        style="font-size: 10px; padding: 3px 8px; margin-left: 6px;"
                                        onclick="replyTo({{ $message->sender_id }}, {{ $message->parcel_id ?? 'null' }})"
                                    >
                                        Reply
                                    </button>
                                @endif
                            </div>
                        @empty
                            <div class="intro" style="padding: 20px; text-align: center; color: #8A7B7E;">
                                No messages exchanged yet.
                            </div>
                        @endforelse
                    </div>

                    <div class="table-footer">{{ $messages->links() }}</div>
                </article>
            </section>
        </div>
    </main>
</div>

<script>
    function replyTo(riderUserId, parcelId) {
        const select = document.getElementById('recipientSelect');
        if (select) {
            select.value = riderUserId;
            select.scrollIntoView({ behavior: 'smooth' });
            select.focus();
        }
    }
</script>
</body>
</html>
