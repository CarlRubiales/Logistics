<div id="lumoraCartSuccess" class="lumora-cart-success" aria-hidden="true">
    <div class="lumora-cart-success-backdrop" data-cart-success-close></div>
    <section class="lumora-cart-success-card" role="dialog" aria-modal="true" aria-labelledby="cartSuccessTitle">
        <button type="button" class="lumora-cart-success-close" aria-label="Close" data-cart-success-close>&times;</button>
        <div class="lumora-cart-success-check" aria-hidden="true">&#10003;</div>
        <h2 id="cartSuccessTitle">Added to cart</h2>
        <p class="lumora-cart-success-message">The product has been added to your cart.</p>
        <div class="lumora-cart-success-product">
            <div class="lumora-cart-success-thumb" data-cart-success-thumb aria-hidden="true">L</div>
            <div>
                <strong data-cart-success-name>Product added</strong>
                <span data-cart-success-price></span>
            </div>
        </div>
        <div class="lumora-cart-success-actions">
            <button type="button" class="lumora-cart-continue" data-cart-success-close>Continue shopping</button>
            <a href="{{ route('buyer.cart') }}" class="lumora-cart-view">View cart</a>
        </div>
    </section>
</div>

<style>
    .lumora-cart-success{position:fixed;inset:0;z-index:1000;display:none;align-items:center;justify-content:center;padding:20px}
    .lumora-cart-success.is-open{display:flex}
    .lumora-cart-success-backdrop{position:absolute;inset:0;background:rgba(53,17,40,.34);backdrop-filter:blur(3px)}
    .lumora-cart-success-card{position:relative;width:min(440px,100%);padding:34px 30px 28px;border:1px solid #e8d8d0;border-radius:16px;background:#fffdfb;box-shadow:0 24px 70px rgba(53,17,40,.22);text-align:center;color:#351128;font-family:Inter,system-ui,sans-serif}
    .lumora-cart-success-close{position:absolute;top:12px;right:15px;border:0;background:transparent;color:#857579;font-size:25px;line-height:1;cursor:pointer}
    .lumora-cart-success-check{width:48px;height:48px;margin:0 auto 14px;display:grid;place-items:center;border:1px solid #b96562;border-radius:50%;color:#b96562;font-size:26px}
    .lumora-cart-success-card h2{margin:0;color:#351128;font:600 28px/1.15 'Playfair Display',Georgia,serif}
    .lumora-cart-success-message{margin:8px 0 22px;color:#857579;font-size:13px}
    .lumora-cart-success-product{display:flex;align-items:center;gap:14px;padding:12px;text-align:left;border-top:1px solid #e8d8d0;border-bottom:1px solid #e8d8d0}
    .lumora-cart-success-thumb{width:62px;height:62px;display:grid;place-items:center;overflow:hidden;border-radius:8px;background:#f2e3dc;color:#b96562;font:600 22px 'Playfair Display',Georgia,serif}
    .lumora-cart-success-thumb img{width:100%;height:100%;object-fit:cover}
    .lumora-cart-success-product strong{display:block;color:#351128;font-size:13px}
    .lumora-cart-success-product span{display:block;margin-top:5px;color:#b96562;font-size:12px;font-weight:700}
    .lumora-cart-success-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:24px}
    .lumora-cart-success-actions button,.lumora-cart-success-actions a{min-height:44px;display:grid;place-items:center;border-radius:8px;font:600 12px Inter,system-ui,sans-serif;text-decoration:none;cursor:pointer}
    .lumora-cart-continue{border:1px solid #b96562;background:#fffdfb;color:#351128}
    .lumora-cart-view{border:1px solid #351128;background:#351128;color:#fff}
    .lumora-cart-continue:hover{background:#f7eee8}.lumora-cart-view:hover{background:#5a294d}
    @media(max-width:520px){.lumora-cart-success{align-items:flex-end;padding:0}.lumora-cart-success-card{width:100%;border-radius:18px 18px 0 0;padding:30px 20px 24px}.lumora-cart-success-actions{grid-template-columns:1fr}.lumora-cart-view{order:-1}}
</style>

<script>
(() => {
    const modal = document.getElementById('lumoraCartSuccess');
    if (!modal) return;
    const nameEl = modal.querySelector('[data-cart-success-name]');
    const priceEl = modal.querySelector('[data-cart-success-price]');
    const thumbEl = modal.querySelector('[data-cart-success-thumb]');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let closeTimer;

    function closeModal(){
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden','true');
        clearTimeout(closeTimer);
    }
    function openModal(form, payload){
        const name = form.dataset.cartProductName || payload?.product_name || 'Product added';
        const price = form.dataset.cartProductPrice || payload?.price || '';
        const image = form.dataset.cartProductImage || '';
        nameEl.textContent = name;
        priceEl.textContent = price ? '₱' + Number(price).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}) : '';
        thumbEl.innerHTML = image ? `<img src="${image}" alt="" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">` : 'L';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden','false');
        closeTimer = setTimeout(closeModal, 5000);
    }
    modal.querySelectorAll('[data-cart-success-close]').forEach(el => el.addEventListener('click', closeModal));
    document.addEventListener('keydown', event => { if(event.key === 'Escape') closeModal(); });

    document.querySelectorAll('form.lumora-cart-form').forEach(form => {
        form.addEventListener('submit', async event => {
            event.preventDefault();
            const button = form.querySelector('button[type="submit"]');
            if (button?.disabled) return;
            const original = button?.textContent;
            if (button) { button.disabled = true; button.textContent = 'Adding...'; }
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
                    body: new FormData(form)
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Unable to add this product to your cart.');
                document.querySelectorAll('[data-cart-count]').forEach(el => {
                    if (payload.cart_count !== undefined) {
                        el.textContent = payload.cart_count;
                        el.classList.toggle('is-empty', Number(payload.cart_count) <= 0);
                    }
                });
                openModal(form, payload);
            } catch (error) {
                window.alert(error.message);
            } finally {
                if (button) { button.disabled = false; button.textContent = original || 'Add to cart'; }
            }
        });
    });
})();
</script> 
