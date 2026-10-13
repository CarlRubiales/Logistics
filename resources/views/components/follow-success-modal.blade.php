@props(['stayLabel' => 'Stay in product'])
@if (session('followed_shop'))
    <div class="follow-modal" id="followModal" role="dialog" aria-modal="true" aria-labelledby="followModalTitle">
        <div class="follow-modal-card">
            <div class="follow-modal-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-4.5-9.2-8.5A5.5 5.5 0 0 1 12 6a5.5 5.5 0 0 1 9.2 6.5C19 16.5 12 21 12 21Z"/></svg>
            </div>
            <h2 id="followModalTitle">Shop followed</h2>
            <p>You are now following <strong>{{ session('followed_shop') }}</strong>.</p>
            <div class="follow-modal-actions">
                <button type="button" class="follow-modal-btn secondary" data-follow-modal-close>{{ $stayLabel }}</button>
                <a class="follow-modal-btn primary" href="{{ route('buyer.following.index') }}">View following list</a>
            </div>
        </div>
    </div>
    <style>
        .follow-modal { position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; padding:20px; background:rgba(47,37,40,.55); }
        .follow-modal-card { width:100%; max-width:400px; padding:30px 26px 24px; border-radius:18px; background:#FFFDFC; text-align:center; box-shadow:0 24px 60px rgba(59,30,52,.25); }
        .follow-modal-icon { width:54px; height:54px; margin:0 auto 14px; display:grid; place-items:center; border-radius:50%; background:#F8EEE9; color:#C0583A; }
        .follow-modal-icon svg { width:24px; height:24px; }
        .follow-modal-card h2 { margin:0 0 8px; color:#3B1E34; font:600 26px Georgia,serif; }
        .follow-modal-card p { margin:0 0 22px; color:#8B7B78; font-size:14px; line-height:1.5; }
        .follow-modal-card p strong { color:#3B1E34; }
        .follow-modal-actions { display:flex; gap:10px; flex-wrap:wrap; }
        .follow-modal-btn { flex:1 1 150px; height:44px; padding:0 14px; display:inline-flex; align-items:center; justify-content:center; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; text-decoration:none; }
        .follow-modal-btn.secondary { border:1px solid #C98F72; background:#FFFDFC; color:#3B1E34; }
        .follow-modal-btn.primary { border:1px solid #3B1E34; background:#3B1E34; color:#fff; }
    </style>
    <script>
        (function () {
            var modal = document.getElementById('followModal');
            if (!modal) return;
            var close = function () { modal.remove(); };
            modal.querySelector('[data-follow-modal-close]').addEventListener('click', close);
            modal.addEventListener('click', function (e) { if (e.target === modal) close(); });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
        })();
    </script>
@endif
