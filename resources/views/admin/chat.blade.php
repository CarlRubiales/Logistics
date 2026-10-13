<x-admin-layout title="Support Chat">
    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="display:flex;height:70vh;min-height:480px;">

            {{-- Conversation list --}}
            <div style="width:300px;border-right:1px solid var(--border);overflow-y:auto;flex-shrink:0;">
                <div style="padding:16px;border-bottom:1px solid var(--border);font-weight:700;">
                    Conversations
                </div>

                @forelse ($conversations as $c)
                    <div
                        class="conversation-item"
                        data-id="{{ $c->id }}"
                        onclick="loadConversation({{ $c->id }}, '{{ addslashes($c->user->name) }}')"
                        style="padding:14px 16px;border-bottom:1px solid var(--border);cursor:pointer;"
                    >
                        <div style="font-weight:600;font-size:14px;">{{ $c->user->name }}</div>
                        <div style="font-size:12px;color:var(--text-muted);margin-top:2px;">
                            {{ $c->last_message_at ? \Carbon\Carbon::parse($c->last_message_at)->diffForHumans() : 'No messages yet' }}
                        </div>
                    </div>
                @empty
                    <div style="padding:16px;color:var(--text-muted);font-size:13px;">
                        No conversations yet.
                    </div>
                @endforelse
            </div>

            {{-- Active conversation --}}
            <div style="flex:1;display:flex;flex-direction:column;">
                <div id="chat-header" style="padding:16px;border-bottom:1px solid var(--border);font-weight:700;">
                    Select a conversation
                </div>

                <div id="chat-messages" style="flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:8px;background:#FBF1EC;">
                    <div style="color:var(--text-muted);font-size:13px;">
                        Pick a conversation on the left to view its messages.
                    </div>
                </div>

                <form id="chat-send-form" style="display:none;gap:8px;padding:12px;border-top:1px solid var(--border);">
                    @csrf
                    <input id="chat-input" type="text" placeholder="Type a reply..." autocomplete="off"
                        style="flex:1;border:1px solid var(--border);border-radius:20px;padding:10px 14px;font-size:13px;font-family:inherit;">
                    <button type="submit" class="btn btn-dark" style="border-radius:20px;padding:10px 18px;">Send</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .conversation-item:hover { background: #FBF1EC; }
        .conversation-item.active { background: #F0E2DA; }
    </style>

    <script>
        let activeConversationId = null;
        const adminUserId = {{ auth()->id() }};

        async function loadConversation(id, userName) {
            activeConversationId = id;

            document.querySelectorAll('.conversation-item').forEach(el => {
                el.classList.toggle('active', el.dataset.id == id);
            });

            document.getElementById('chat-header').textContent = userName;
            document.getElementById('chat-messages').innerHTML = '<div style="color:#999;font-size:13px;">Loading...</div>';
            document.getElementById('chat-send-form').style.display = 'flex';

            const res = await fetch(`/admin/chat/${id}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            renderMessages(data.messages);
        }

        function renderMessages(messages) {
            const container = document.getElementById('chat-messages');
            container.innerHTML = '';

            if (messages.length === 0) {
                container.innerHTML = '<div style="color:#999;font-size:13px;">No messages yet.</div>';
                return;
            }

            messages.forEach(msg => {
                const bubble = document.createElement('div');
                const mine = msg.sender_id === adminUserId;
                bubble.style.cssText = mine
                    ? 'align-self:flex-end;background:#5B1A35;color:#fff;padding:8px 12px;border-radius:12px 12px 2px 12px;max-width:70%;font-size:13px;'
                    : 'align-self:flex-start;background:#fff;border:1px solid #F0E2DA;padding:8px 12px;border-radius:12px 12px 12px 2px;max-width:70%;font-size:13px;';
                bubble.textContent = msg.body;
                if (msg.attachment_url) {
                    const link = document.createElement('a');
                    link.href = msg.attachment_url; link.target = '_blank'; link.rel = 'noopener';
                    link.style.cssText = 'display:block;margin-top:6px;color:inherit;font-size:12px;text-decoration:underline;';
                    if ((msg.attachment_mime || '').startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = msg.attachment_url; img.style.cssText = 'display:block;max-width:100%;max-height:180px;border-radius:8px;';
                        link.appendChild(img);
                    } else {
                        link.textContent = '\uD83D\uDCCE ' + (msg.attachment_name || 'Attachment');
                    }
                    bubble.appendChild(link);
                }
                container.appendChild(bubble);
            });

            container.scrollTop = container.scrollHeight;
        }

        document.getElementById('chat-send-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!activeConversationId) return;

            const input = document.getElementById('chat-input');
            const body = input.value.trim();
            if (!body) return;

            input.value = '';

            const res = await fetch('{{ route('chat.send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ conversation_id: activeConversationId, body: body }),
            });

            const data = await res.json();

            const container = document.getElementById('chat-messages');
            const bubble = document.createElement('div');
            bubble.style.cssText = 'align-self:flex-end;background:#5B1A35;color:#fff;padding:8px 12px;border-radius:12px 12px 2px 12px;max-width:70%;font-size:13px;';
            bubble.textContent = body;
            container.appendChild(bubble);
            container.scrollTop = container.scrollHeight;
        });
    </script>
</x-admin-layout>