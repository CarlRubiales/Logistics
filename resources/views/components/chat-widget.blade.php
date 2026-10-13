<div x-data="chatWidget()" x-init="init()" class="lumora-chat-widget">
    <div class="lumora-chat-panel" x-show="open" x-cloak x-transition:enter="chat-enter" x-transition:enter-start="chat-enter-start" x-transition:enter-end="chat-enter-end" x-transition:leave="chat-leave" x-transition:leave-start="chat-leave-start" x-transition:leave-end="chat-leave-end">
        <header class="lumora-chat-header">
            <div>
                <h2>Customer Support</h2>
                <span>Here to help with your Lumora journey</span>
            </div>
            <button type="button" class="lumora-chat-close" @click="open = false" aria-label="Minimize chat">&minus;</button>
        </header>

        <div class="lumora-chat-status">
            <span class="lumora-online-dot" aria-hidden="true"></span>
            <span>We're here to assist you!</span>
        </div>

        <div x-ref="messageList" class="lumora-chat-messages" aria-live="polite">
            <div class="lumora-welcome-message" x-show="messages.length === 0">
                <div class="lumora-welcome-icon" aria-hidden="true">L</div>
                <p>{{ config('support_bot.greeting') }}</p>
            </div>

            <template x-for="msg in messages" :key="msg.id">
                <div class="lumora-message" :class="msg.sender_id === userId ? 'lumora-message-user' : 'lumora-message-support'">
                    <span x-text="msg.body" x-show="msg.body"></span>
                    <template x-if="msg.attachment_url">
                        <a :href="msg.attachment_url" target="_blank" rel="noopener" class="lumora-attach">
                            <template x-if="(msg.attachment_mime || '').startsWith('image/')"><img :src="msg.attachment_url" alt=""></template>
                            <template x-if="!(msg.attachment_mime || '').startsWith('image/')"><span x-text="'📎 ' + (msg.attachment_name || 'Attachment')"></span></template>
                        </a>
                    </template>
                </div>
            </template>

            <div class="lumora-quick" x-show="!reportMode && (buttons.length || path.length)" x-cloak>
                <template x-for="(btn, i) in buttons" :key="'b' + i">
                    <button type="button" class="lumora-chip" @click="pick(btn)" x-text="btn.label"></button>
                </template>
                <button type="button" class="lumora-chip lumora-chip-back" x-show="path.length" x-cloak @click="resetMenu()">&larr; Main menu</button>
            </div>

            <div class="lumora-message lumora-message-support lumora-typing" x-show="typing" x-cloak aria-label="Assistant is typing"><i></i><i></i><i></i></div>
        </div>

        <form class="lumora-chat-form" @submit.prevent="send()">
            <div class="lumora-report-banner" x-show="reportMode" x-cloak>
                <span>Reporting a product: describe the problem and attach a photo or file.</span>
                <button type="button" @click="reportMode = false; file = null">Cancel</button>
            </div>
            <div class="lumora-file-chip" x-show="file" x-cloak>
                <span x-text="file ? '📎 ' + file.name : ''"></span>
                <button type="button" @click="file = null; $refs.fileInput.value = ''" aria-label="Remove file">&times;</button>
            </div>
            <div class="lumora-input-wrap">
                <input type="file" x-ref="fileInput" accept="image/*,.pdf" hidden @change="pickFile($event)">
                <button type="button" class="lumora-attach-btn" @click="$refs.fileInput.click()" aria-label="Attach a file" title="Attach a photo or PDF">&#128206;</button>
                <input x-model="newMessage" type="text" :placeholder="reportMode ? 'Describe the problem...' : 'Type a message...'" autocomplete="off" aria-label="Type a message">
                <button type="submit" class="lumora-send-button" aria-label="Send message">Send</button>
            </div>
        </form>
    </div>

    <button type="button" class="lumora-chat-fab" @click="open = !open" :aria-label="open ? 'Close customer support' : 'Open customer support'" :aria-expanded="open.toString()">
        <svg x-show="!open" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
            <path d="M20 11.5a7.5 7.5 0 0 1-8 7.5 8.9 8.9 0 0 1-3.3-.6L4 20l1.6-3.5A7.3 7.3 0 0 1 4 11.5 7.5 7.5 0 0 1 12 4a7.5 7.5 0 0 1 8 7.5Z"/>
        </svg>
        <svg x-show="open" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="m6 6 12 12M18 6 6 18"/>
        </svg>
    </button>
</div>

<style>
    [x-cloak] { display:none !important; }
    .lumora-chat-widget { position:fixed; right:24px; bottom:24px; z-index:999; font-family:'Inter','Poppins',sans-serif; color:#333; }
    .lumora-chat-panel { position:absolute; right:0; bottom:70px; width:350px; height:460px; display:flex; flex-direction:column; overflow:hidden; background:#fff; border:1px solid #eadbd6; border-radius:18px; box-shadow:0 18px 45px rgba(61,27,61,.18); transform-origin:bottom right; }
    .lumora-chat-header { min-height:68px; display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px 13px 18px; background:#641b3a; color:#fff; }
    .lumora-chat-header h2 { margin:0; font-size:16px; line-height:1.2; font-weight:700; letter-spacing:.01em; }
    .lumora-chat-header span { display:block; margin-top:5px; color:rgba(255,255,255,.72); font-size:10.5px; }
    .lumora-chat-close { width:30px; height:30px; display:grid; place-items:center; border:0; border-radius:50%; background:rgba(255,255,255,.12); color:#fff; font-size:22px; line-height:1; cursor:pointer; transition:background .2s ease, transform .2s ease; }
    .lumora-chat-close:hover { background:rgba(255,255,255,.22); transform:scale(1.06); }
    .lumora-chat-status { min-height:42px; display:flex; align-items:center; gap:8px; padding:0 18px; border-bottom:1px solid #eadbd6; background:#f8f1ed; color:#777; font-size:11.5px; }
    .lumora-online-dot { width:8px; height:8px; flex:0 0 8px; border-radius:50%; background:#79c267; box-shadow:0 0 0 3px rgba(121,194,103,.14); }
    .lumora-chat-messages { flex:1; overflow-y:auto; display:flex; flex-direction:column; gap:10px; padding:18px 15px; background:#fff; }
    .lumora-welcome-message { margin:auto 10px; color:#777; text-align:center; font-size:12px; }
    .lumora-welcome-message p { margin:9px 0 0; }
    .lumora-welcome-icon { width:36px; height:36px; display:grid; place-items:center; margin:auto; border-radius:50%; background:#f8f1ed; color:#641b3a; font-family:Georgia,serif; font-size:18px; font-weight:700; }
    .lumora-message { max-width:78%; padding:10px 13px; font-size:12.5px; line-height:1.45; word-break:break-word; }
    .lumora-message-support { align-self:flex-start; border:1px solid #eadbd6; border-radius:14px 14px 14px 3px; background:#f8f1ed; color:#333; }
    .lumora-typing { display:flex; gap:4px; align-items:center; padding:12px 14px; }
    .lumora-typing i { width:6px; height:6px; border-radius:50%; background:#b9a3a8; animation:lumora-dot 1s infinite ease-in-out; }
    .lumora-typing i:nth-child(2) { animation-delay:.15s; } .lumora-typing i:nth-child(3) { animation-delay:.3s; }
    @keyframes lumora-dot { 0%,80%,100% { opacity:.3; transform:translateY(0); } 40% { opacity:1; transform:translateY(-3px); } }
    .lumora-quick { display:flex; flex-wrap:wrap; gap:7px; padding:2px 0 4px; }
    .lumora-chip { padding:8px 13px; border:1px solid #641b3a; border-radius:18px; background:#fff; color:#641b3a; font:inherit; font-size:12px; font-weight:600; cursor:pointer; transition:background .2s ease, color .2s ease; text-align:left; }
    .lumora-chip:hover { background:#641b3a; color:#fff; }
    .lumora-chip-back { border-color:#eadbd6; color:#777; }
    .lumora-chip-back:hover { background:#f8f1ed; color:#641b3a; }
    .lumora-attach { display:block; margin-top:6px; color:inherit; font-size:12px; text-decoration:underline; }
    .lumora-attach img { display:block; max-width:100%; max-height:150px; border-radius:8px; }
    .lumora-attach-btn { flex:0 0 auto; width:30px; height:30px; border:0; border-radius:50%; background:transparent; font-size:16px; cursor:pointer; }
    .lumora-attach-btn:hover { background:#f8f1ed; }
    .lumora-report-banner { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding:7px 10px; border-radius:10px; background:#fdecea; color:#a8321f; font-size:11px; }
    .lumora-report-banner button, .lumora-file-chip button { border:0; background:transparent; color:inherit; font:inherit; font-weight:700; cursor:pointer; }
    .lumora-file-chip { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:8px; padding:6px 10px; border-radius:10px; background:#f8f1ed; color:#641b3a; font-size:11px; }
    .lumora-message-user { align-self:flex-end; border-radius:14px 14px 3px 14px; background:#641b3a; color:#fff; }
    .lumora-chat-form { padding:12px; border-top:1px solid #eadbd6; background:#fff; }
    .lumora-input-wrap { display:flex; align-items:center; gap:8px; min-height:44px; padding:4px 5px 4px 8px; border:1px solid #eadbd6; border-radius:25px; background:#fff; transition:border-color .2s ease, box-shadow .2s ease; }
    .lumora-input-wrap:focus-within { border-color:#7a294b; box-shadow:0 0 0 3px rgba(122,41,75,.08); }
    .lumora-input-wrap input[type="text"] { min-width:0; flex:1; border:0; outline:0; background:transparent; color:#333; font:inherit; font-size:12.5px; }
    .lumora-input-wrap input::placeholder { color:#999; }
    .lumora-send-button { flex:0 0 auto; padding:9px 16px; border:0; border-radius:20px; background:#641b3a; color:#fff; font:inherit; font-size:12px; font-weight:600; cursor:pointer; transition:background .2s ease, transform .2s ease, box-shadow .2s ease; }
    .lumora-send-button:hover { background:#7a294b; transform:translateY(-1px); box-shadow:0 4px 10px rgba(100,27,58,.18); }
    .lumora-chat-fab { width:54px; height:54px; display:grid; place-items:center; margin-left:auto; padding:0; line-height:0; border:0; border-radius:50%; background:#641b3a; color:#fff; box-shadow:0 8px 22px rgba(100,27,58,.28); cursor:pointer; transition:transform .22s ease, background .22s ease, box-shadow .22s ease; }
    .lumora-chat-fab:hover { background:#7a294b; transform:scale(1.06); box-shadow:0 10px 26px rgba(100,27,58,.34); }
    .lumora-chat-fab svg { width:22px; height:22px; display:block; position:static; transform:none; margin:0; }
    .chat-enter { transition:opacity .24s ease, transform .28s cubic-bezier(.22,.61,.36,1); }
    .chat-enter-start { opacity:0; transform:translateY(15px) scale(.96); }
    .chat-enter-end { opacity:1; transform:translateY(0) scale(1); }
    .chat-leave { transition:opacity .18s ease, transform .2s ease; }
    .chat-leave-start { opacity:1; transform:translateY(0) scale(1); }
    .chat-leave-end { opacity:0; transform:translateY(10px) scale(.97); }
    @media (max-width:520px) { .lumora-chat-widget { right:14px; bottom:14px; } .lumora-chat-panel { right:-2px; bottom:66px; width:min(350px,calc(100vw - 28px)); height:min(460px,calc(100vh - 105px)); } }
    @media (prefers-reduced-motion:reduce) { .chat-enter,.chat-leave,.lumora-chat-fab,.lumora-chat-close,.lumora-send-button { transition:none; } }
</style>

<script>
function chatWidget() {
    return {
        open: false,
        conversationId: null,
        userId: {{ auth()->id() ?? 'null' }},
        messages: [],
        newMessage: '',
        typing: false,
        reportMode: false,
        file: null,
        rootMenu: @json(config('support_bot.menu', [])),
        buttons: [],
        path: [],
        localId: 0,
        realtimeAvailable: false,
        echoChannel: null,

        pickFile(event) {
            const f = event.target.files[0];
            if (!f) return;
            if (f.size > 5 * 1024 * 1024) { alert('File is too large (max 5 MB).'); event.target.value = ''; return; }
            this.file = f;
        },

        pick(btn) {
            if (btn.action === 'report') this.reportMode = true;
            this.messages.push({ id: 'q' + (++this.localId), sender_id: this.userId, body: btn.label });
            this.messages.push({ id: 'a' + (++this.localId), sender_id: null, is_bot: true, body: btn.answer || '' });
            if (btn.buttons && btn.buttons.length) {
                this.path.push(btn.label);
                this.buttons = btn.buttons;
            } else {
                this.resetMenu();
            }
            if (btn.action === 'human' || btn.action === 'report') {
                this.$nextTick(() => { const i = this.$el.querySelector('.lumora-input-wrap input[type="text"]'); if (i) i.focus(); });
            }
            this.$nextTick(() => this.scrollToBottom());
        },

        resetMenu() {
            this.path = [];
            this.buttons = this.rootMenu;
            this.$nextTick(() => this.scrollToBottom());
        },

        async init() {
            this.buttons = this.rootMenu;
            try {
                const res = await fetch('{{ route('chat.mine') }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Unable to load conversation.');
                const data = await res.json();
                this.conversationId = data.conversation_id;
                this.messages = data.messages || [];

                if (window.Echo && typeof window.Echo.private === 'function' && this.conversationId) {
                    this.realtimeAvailable = true;
                    this.echoChannel = window.Echo.private('conversation.' + this.conversationId);
                    this.echoChannel.listen('.message.sent', (event) => {
                        if (!this.messages.some((message) => message.id === event.id)) {
                            this.messages.push(event);
                        }
                        this.$nextTick(() => this.scrollToBottom());
                    });
                }
            } catch (error) {
                console.error('Chat initialization failed:', error);
            }
            this.$nextTick(() => this.scrollToBottom());
        },

        async send() {
            const body = this.newMessage.trim();
            const file = this.file;
            if ((!body && !file) || !this.conversationId) return;
            const wasReport = this.reportMode;

            this.newMessage = '';
            this.file = null;
            if (this.$refs.fileInput) this.$refs.fileInput.value = '';

            const tmp = {
                id: 'tmp-' + Date.now(), sender_id: this.userId,
                body: (wasReport ? '[Product report] ' : '') + body,
                attachment_url: file && file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
                attachment_mime: file ? file.type : null,
                attachment_name: file ? file.name : null,
            };
            this.messages.push(tmp);
            const tmpIndex = this.messages.length - 1;
            this.typing = !wasReport;
            this.$nextTick(() => this.scrollToBottom());

            const form = new FormData();
            form.append('conversation_id', this.conversationId);
            form.append('body', body);
            if (file) form.append('attachment', file);
            if (wasReport) form.append('is_report', '1');

            try {
                const res = await fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: form,
                });
                if (!res.ok) {
                    let msg = 'Unable to send message.';
                    try { const err = await res.json(); msg = (err.errors && Object.values(err.errors)[0][0]) || err.message || msg; } catch (e) {}
                    throw new Error(msg);
                }
                const data = await res.json();
                this.messages[tmpIndex] = data.message || Object.assign(tmp, { id: data.message_id });
                if (data.bot) this.messages.push(data.bot);
                if (wasReport) {
                    this.reportMode = false;
                    this.messages.push({ id: 'a' + (++this.localId), sender_id: null, is_bot: true, body: 'Thank you! Your report was sent to the Lumora team. An admin will review it and reply here.' });
                }
            } catch (error) {
                this.messages.splice(tmpIndex, 1);
                this.newMessage = body;
                this.file = file;
                alert(error.message);
                console.error('Message sending failed:', error);
            } finally {
                this.typing = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            if (this.$refs.messageList) {
                this.$refs.messageList.scrollTop = this.$refs.messageList.scrollHeight;
            }
        }
    };
}
</script>
