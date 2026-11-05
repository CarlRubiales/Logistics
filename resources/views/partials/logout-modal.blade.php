@once
<div id="logout-confirm-modal" class="logout-modal-backdrop" onclick="if(event.target === this) closeLogoutModal();" style="display: none; position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; z-index: 99999999 !important; background: rgba(30, 10, 30, 0.58) !important; backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important; align-items: center !important; justify-content: center !important; padding: 20px !important; box-sizing: border-box !important;">
    <div class="logout-modal-card" style="background: #FFFDFB !important; border-radius: 22px !important; border: 1px solid #EBDDD8 !important; box-shadow: 0 25px 70px rgba(0, 0, 0, 0.45) !important; max-width: 420px !important; width: 100% !important; padding: 28px 24px !important; text-align: center !important; box-sizing: border-box !important; position: relative !important; z-index: 100000000 !important;">
        
        <!-- Red Warning Icon Badge -->
        <div style="width: 54px; height: 54px; border-radius: 50%; background: #FEEAEA; border: 1px solid #F8B4B4; color: #C62828; display: grid; place-items: center; margin: 0 auto 16px auto;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
        </div>

        <h3 style="margin: 0 0 8px 0; font-size: 19px; font-weight: 800; color: #3D1B3D; font-family: Georgia, 'Playfair Display', serif;">
            Are you sure you want to log out?
        </h3>
        
        <p style="margin: 0 0 24px 0; font-size: 13.5px; color: #8A7B7E; line-height: 1.5;">
            You will be signed out of <strong>{{ auth()->user()?->getBranchName() ? strtoupper(auth()->user()->getBranchName()) . ' HUB' : (auth()->user()?->isMasterAdmin() ? 'CENTRAL HQ' : 'LUMORA LOGISTICS') }}</strong>. You will need your password to log in again.
        </p>

        <!-- Actions -->
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" onclick="closeLogoutModal()" style="flex: 1; height: 44px; border-radius: 12px; border: 1px solid #EBDDD8; background: #FFFDFB; color: #3D1B3D; font-size: 13.5px; font-weight: 700; cursor: pointer; transition: all 0.15s;">
                Cancel
            </button>
            
            <form id="global-logout-modal-form" action="{{ route('logout') }}" method="POST" style="flex: 1; margin: 0; padding: 0;">
                @csrf
                <button type="submit" style="width: 100%; height: 44px; border-radius: 12px; border: 0; background: #C62828; color: #FFFDFB; font-size: 13.5px; font-weight: 700; cursor: pointer; box-shadow: 0 4px 14px rgba(198, 40, 40, 0.32); transition: all 0.15s;">
                    Yes, Log Out
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function ensureLogoutModalInBody() {
        var modal = document.getElementById('logout-confirm-modal');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureLogoutModalInBody);
    } else {
        ensureLogoutModalInBody();
    }

    function openLogoutModal() {
        ensureLogoutModalInBody();
        var modal = document.getElementById('logout-confirm-modal');
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        } else {
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = "{{ route('logout') }}";
            }
        }
    }

    function closeLogoutModal() {
        var modal = document.getElementById('logout-confirm-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            closeLogoutModal();
        }
    });
</script>
@endonce
