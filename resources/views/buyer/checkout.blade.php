<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumora | Checkout</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --plum: #3d1b3d;
            --plum-dark: #2c102c;
            --rose: #b96562;
            --rose-soft: #d99a96;
            --gold: #c9972b;
            --cream: #fffdfb;
            --blush: #fbefea;
            --blush-deep: #f3d8de;
            --ink: #3a2e30;
            --muted: #8a7b7e;
            --line: #ebddd8;
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            color: var(--ink);
            font-family: Inter, Arial, sans-serif;
            background: linear-gradient(135deg, #fffdfb 0%, #fbefea 44%, #f3d8de 100%);
        }
        a { color: inherit; text-decoration: none; }
        h1, h2, h3 { margin: 0; color: var(--plum); font-family: 'Playfair Display', Georgia, serif; font-weight: 600; }

        .checkout-header {
            height: 106px;
            background: rgba(255, 253, 251, .96);
            border-bottom: 1px solid var(--line);
        }
        .header-inner {
            width: min(1380px, calc(100% - 96px));
            height: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .steps {
            display: flex;
            align-items: center;
            gap: 23px;
            color: var(--muted);
            font-size: 15px;
        }
        .step { display: flex; align-items: center; gap: 18px; white-space: nowrap; }
        .step.active { color: var(--plum); font-weight: 600; }
        .step.active::after {
            content: '';
            position: absolute;
            margin-top: 55px;
            margin-left: 10px;
            width: 58px;
            height: 2px;
            background: var(--gold);
        }
        .chevron { color: #b9a7a7; font-size: 25px; font-weight: 300; }
        .secure { display: flex; align-items: center; gap: 10px; color: var(--ink); font-size: 14px; }
        .lock { display: inline-flex; color: var(--gold); }

        .page {
            width: min(1380px, calc(100% - 96px));
            margin: 0 auto;
            padding: 54px 0 100px;
        }
        .page-title { margin-bottom: 29px; font-size: 47px; line-height: 1; }
        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(360px, .85fr);
            gap: 62px;
            align-items: start;
        }
        .left-column { min-width: 0; }
        .card {
            background: rgba(255, 253, 251, .91);
            border: 1px solid var(--line);
            border-radius: 13px;
            box-shadow: 0 8px 30px rgba(61, 27, 61, .035);
        }
        .delivery-card { padding: 29px 34px 27px; }
        .card-title { font-size: 29px; line-height: 1; margin-bottom: 25px; }
        .address-row { display: flex; align-items: center; gap: 31px; }
        .pin {
            width: 90px;
            height: 90px;
            flex: 0 0 90px;
            display: grid;
            place-items: center;
            color: var(--rose);
            background: #fcedeb;
            border-radius: 50%;
            font-size: 38px;
        }
        .address-copy {
            min-height: 90px;
            padding-left: 31px;
            border-left: 1px solid var(--line);
            font-size: 15px;
            line-height: 1.52;
        }
        .address-name { margin-bottom: 4px; color: var(--ink); font-weight: 600; font-size: 16px; }
        .contact { margin-top: 6px; color: var(--rose); }
        .items-card { margin-top: 16px; padding: 27px 34px 17px; }
        .items-card .card-title { margin-bottom: 14px; }
        .item {
            min-height: 113px;
            display: flex;
            align-items: center;
            gap: 29px;
            border-top: 1px solid var(--line);
        }
        .item-image {
            width: 160px;
            height: 96px;
            flex: 0 0 160px;
            border-radius: 8px;
            object-fit: cover;
            background: #f2e8e3;
        }
        .item-details { flex: 1; min-width: 0; }
        .item-name { color: var(--ink); font: 600 17px/1.2 Inter, Arial, sans-serif; }
        .item-meta { margin-top: 8px; color: var(--muted); font-size: 14px; }
        .item-qty { width: 75px; color: var(--ink); font-size: 14px; white-space: nowrap; }
        .item-price { width: 117px; color: var(--plum); font-size: 16px; font-weight: 600; text-align: right; white-space: nowrap; }
        .return-link { display: inline-flex; gap: 11px; margin-top: 24px; color: var(--rose); font-size: 14px; text-decoration: underline; text-underline-offset: 3px; }

        .summary-card {
            position: sticky;
            top: 25px;
            min-height: 610px;
            padding: 34px 42px 31px;
            overflow: hidden;
        }
        .summary-card::after {
            content: '';
            position: absolute;
            width: 250px;
            height: 180px;
            right: -88px;
            bottom: -55px;
            border: 1px solid rgba(201, 151, 43, .45);
            border-radius: 50% 40% 0 50%;
            transform: rotate(-24deg);
            opacity: .45;
        }
        .summary-title { padding-bottom: 23px; border-bottom: 1px solid #d8c9bd; font-size: 30px; }
        .summary-title::after { content: '✦'; float: right; color: var(--gold); font: 20px Georgia, serif; }
        .summary-row { display: flex; justify-content: space-between; gap: 20px; margin-top: 23px; color: var(--ink); font-size: 15px; }
        .summary-row strong { font-weight: 500; }
        .summary-row.discount strong { color: var(--rose); }
        .voucher-box { margin-top: 24px; padding-top: 20px; border-top: 1px dashed #d8c9bd; position: relative; z-index: 1; }
        .voucher-label { display: flex; align-items: center; justify-content: space-between; margin-bottom: 9px; color: var(--plum); font-size: 13px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        .voucher-input-group { display: flex; gap: 8px; align-items: stretch; }
        .voucher-input { flex: 1; min-width: 0; height: 44px; padding: 0 14px; border: 1px solid var(--line); border-radius: 8px; background: #fffdfb; color: var(--plum); font: 600 13px Inter, Arial, sans-serif; letter-spacing: .04em; text-transform: uppercase; outline: none; transition: border-color .2s ease, box-shadow .2s ease; }
        .voucher-input::placeholder { color: var(--muted); font-weight: 400; letter-spacing: normal; text-transform: none; }
        .voucher-input:focus { border-color: var(--rose); box-shadow: 0 0 0 3px rgba(185, 101, 98, .12); }
        .voucher-apply-btn { height: 44px; padding: 0 18px; border: 1px solid var(--plum); border-radius: 8px; background: var(--plum); color: #fff; font: 600 13px Inter, Arial, sans-serif; cursor: pointer; white-space: nowrap; transition: background .2s ease, transform .15s ease; }
        .voucher-apply-btn:hover { background: var(--plum-dark); transform: translateY(-1px); }
        .voucher-feedback { margin-top: 8px; font-size: 12px; line-height: 1.4; display: none; }
        .voucher-feedback.success { display: flex; align-items: center; justify-content: space-between; gap: 8px; color: #4f7a5a; font-weight: 600; }
        .voucher-feedback.error { display: block; color: var(--rose); font-weight: 500; }
        .voucher-remove-btn { border: 0; background: transparent; color: var(--rose); font-size: 11px; font-weight: 700; text-decoration: underline; cursor: pointer; padding: 0; }
        .summary-total { display: flex; justify-content: space-between; margin-top: 22px; padding-top: 24px; border-top: 1px solid #d8c9bd; color: var(--plum); font: 600 29px 'Playfair Display', Georgia, serif; }
        .payment-label { display: block; margin-top: 39px; margin-bottom: 14px; color: var(--plum); font: 600 21px 'Playfair Display', Georgia, serif; }
        .payment-option {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
            min-height: 61px;
            padding: 0 17px;
            border: 1px solid var(--rose-soft);
            border-radius: 8px;
            color: var(--ink);
            font-size: 15px;
        }
        .payment-option input { width: 21px; height: 21px; accent-color: var(--plum); }
        .payment-option span small { display: block; margin-top: 3px; color: var(--muted); font-size: 12px; }
        .payment-card {
            margin-top: 36px;
            padding-top: 27px;
            border-top: 1px solid #d8c9bd;
        }
        .payment-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 16px;
        }
        .payment-card-title {
            margin: 0;
            color: var(--plum);
            font: 600 26px/1 'Playfair Display', Georgia, serif;
        }
        .payment-card-subtitle {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }
        .manage-wallet {
            flex: 0 0 auto;
            color: var(--rose);
            font-size: 12px;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .payment-options,
        .wallet-list {
            display: grid;
            gap: 10px;
        }
        .payment-radio-card,
        .wallet-card {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 12px;
            align-items: flex-start;
            min-height: 62px;
            margin: 0;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: rgba(255, 253, 251, .78);
            padding: 13px 14px;
            color: var(--ink);
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease;
        }
        .payment-radio-card:has(input:checked),
        .wallet-card:has(input:checked) {
            border-color: var(--rose-soft);
            background: #fff7f3;
        }
        .payment-radio-card input,
        .wallet-card input {
            width: 19px;
            height: 19px;
            margin-top: 2px;
            accent-color: var(--plum);
        }
        .payment-radio-card strong,
        .wallet-card strong {
            display: block;
            color: var(--plum);
            font-size: 14px;
            font-weight: 700;
        }
        .payment-radio-card span,
        .wallet-card span {
            display: block;
            margin-top: 4px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .wallet-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .wallet-section-title {
            margin: 19px 0 10px;
            color: var(--plum);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        .wallet-badge {
            display: inline-flex;
            align-items: center;
            min-height: 20px;
            border-radius: 999px;
            background: rgba(111, 143, 120, .13);
            padding: 0 8px;
            color: #6F8F78;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .payment-note {
            margin: 8px 0 0 31px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.45;
        }
        .payment-note a {
            color: var(--rose);
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
        .payment-error {
            display: block;
            margin-top: 10px;
            color: var(--rose);
            font-size: 12px;
            line-height: 1.45;
        }
        .place-order {
            width: 100%;
            height: 69px;
            margin-top: 31px;
            border: 0;
            border-radius: 8px;
            background: var(--plum);
            color: white;
            font: 500 20px 'Playfair Display', Georgia, serif;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }
        .place-order:hover { background: var(--plum-dark); transform: translateY(-1px); }
        .error { margin-bottom: 18px; padding: 12px 15px; border-radius: 8px; color: var(--rose); background: #fff3e9; font-size: 13px; }

        @media (max-width: 1050px) {
            .header-inner, .page { width: min(100% - 44px, 850px); }
            .steps { display: none; }
            .layout { grid-template-columns: 1fr; gap: 24px; }
            .summary-card { position: static; min-height: auto; }
        }
        @media (max-width: 620px) {
            .checkout-header { height: 78px; }
            .header-inner, .page { width: calc(100% - 32px); }
            .secure { font-size: 12px; }
            .page { padding-top: 31px; }
            .page-title { font-size: 38px; }
            .delivery-card, .items-card, .summary-card { padding: 22px 18px; }
            .card-title, .summary-title { font-size: 25px; }
            .address-row { gap: 16px; align-items: flex-start; }
            .pin { width: 58px; height: 58px; flex-basis: 58px; font-size: 25px; }
            .address-copy { padding-left: 16px; font-size: 12px; }
            .item { gap: 12px; }
            .item-image { width: 76px; height: 68px; flex-basis: 76px; }
            .item-name { font-size: 13px; }
            .item-meta, .item-qty, .item-price { font-size: 11px; }
            .item-qty { width: 45px; }
            .item-price { width: 73px; }
            .payment-card-head { flex-direction: column; gap: 8px; }
            .manage-wallet { align-self: flex-start; }
        }
    </style>
</head>
<body>
<header class="checkout-header">
    <div class="header-inner">
        <x-logo :href="route('shop.index')" aria-label="Lumora shop" />
        <nav class="steps" aria-label="Checkout progress">
            <span class="step">Cart <span class="chevron">›</span></span>
            <span class="step active">Checkout <span class="chevron">›</span></span>
            <span class="step">Confirmation</span>
        </nav>
        <div class="secure"><span class="lock" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg></span> Secure checkout</div>
    </div>
</header>

<main class="page">
    <h1 class="page-title">Checkout</h1>

    @if($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="layout">
        <section class="left-column">
            <div class="card delivery-card">
                <h2 class="card-title">Delivery information</h2>
                <div class="address-row">
                    <div class="pin" aria-hidden="true"><svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.7"></circle></svg></div>
                    <div class="address-copy">
                        <div class="address-name">{{ $buyer->name }}</div>
                        @if($buyer->house_number || $buyer->street)
                            {{ $buyer->house_number }} {{ $buyer->street }}<br>
                        @endif
                        {{ $buyer->barangay }}@if($buyer->barangay && $buyer->municipality), @endif{{ $buyer->municipality }}<br>
                        {{ $buyer->province }}<br>
                        <div class="contact">☎ {{ $buyer->contact_number }}</div>
                    </div>
                </div>
            </div>

            <div class="card items-card">
                <h2 class="card-title">Items to purchase</h2>
                @foreach($cartItems as $item)
                    @php
                        $product = $item['product'];
                    @endphp
                    <div class="item">
                        <img class="item-image" src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                        <div class="item-details">
                            <div class="item-name">{{ $product->name }}</div>
                            <div class="item-meta">Quantity: {{ $item['quantity'] }}</div>
                        </div>
                        <div class="item-qty">Qty: {{ $item['quantity'] }}</div>
                        <div class="item-price">₱{{ number_format($item['line_total'], 2) }}</div>
                    </div>
                @endforeach
            </div>
            <a class="return-link" href="{{ $returnUrl ?? route('buyer.cart') }}">← &nbsp;{{ ($checkoutMode ?? 'cart') === 'buy_now' ? 'Return to product' : 'Return to cart' }}</a>
        </section>

        <aside class="card summary-card">
            <h2 class="summary-title">Order summary</h2>
            <div class="summary-row"><span>Subtotal</span><strong>₱{{ number_format($summary['subtotal'], 2) }}</strong></div>
            <div class="summary-row discount"><span>Discount</span><strong>- ₱{{ number_format($summary['discount'], 2) }}</strong></div>
            <div class="summary-row"><span>Shipping</span><strong>₱{{ number_format($summary['shipping'], 2) }}</strong></div>
            <div class="summary-row discount" id="voucherSummaryRow" style="display:none;"><span>Voucher (<span id="voucherSummaryCode"></span>)</span><strong id="voucherSummaryAmount">- ₱0.00</strong></div>
            <div class="voucher-box">
                <label class="voucher-label" for="voucherCodeInput">Voucher Code</label>
                <div class="voucher-input-group">
                    <input type="text" id="voucherCodeInput" class="voucher-input" placeholder="Enter voucher code" value="{{ old('voucher_code', '') }}" autocomplete="off">
                    <button type="button" id="applyVoucherBtn" class="voucher-apply-btn">Apply</button>
                </div>
                <div id="voucherFeedback" class="voucher-feedback" aria-live="polite"></div>
            </div>
            <div class="summary-total"><span>Total</span><strong id="summaryTotalAmount" data-base-total="{{ (float) $summary['total'] }}" data-net-items="{{ round((float) $summary['subtotal'] - (float) $summary['discount'], 2) }}" data-shipping="{{ (float) $summary['shipping'] }}">₱{{ number_format($summary['total'], 2) }}</strong></div>

            <form method="POST" action="{{ route('buyer.checkout.store') }}">
                @csrf
                <input type="hidden" name="checkout_mode" value="{{ $checkoutMode ?? 'cart' }}">
                <input type="hidden" name="voucher_code" id="appliedVoucherHidden" value="{{ old('voucher_code', '') }}">
                @php
                    $wallets = collect($paymentMethods ?? [])
                        ->whereIn('type', ['gcash', 'maya', 'bank_transfer'])
                        ->values();
                    $walletsByType = $wallets->groupBy('type');
                    $defaultWallet = $wallets->firstWhere('is_default', true);
                    $cartPaymentOption = in_array($cartPaymentOption ?? 'cod', ['cod', 'wallet'], true) ? $cartPaymentOption : 'cod';
                    $initialWallet = $cartPaymentOption === 'wallet' ? $defaultWallet : null;
                    $selectedWalletId = old('payment_method_id', $initialWallet?->id);
                    $selectedPaymentType = old('payment_method', $initialWallet?->type ?? 'cod');
                    $paymentOptions = [
                        'cod' => [
                            'label' => 'Cash on Delivery',
                            'description' => 'Pay when your order arrives.',
                            'empty' => null,
                        ],
                        'gcash' => [
                            'label' => 'GCash',
                            'description' => 'Use a saved GCash wallet at checkout.',
                            'empty' => 'No saved GCash wallet yet. You can add one from Wallet.',
                        ],
                        'maya' => [
                            'label' => 'Maya',
                            'description' => 'Use a saved Maya wallet at checkout.',
                            'empty' => 'No saved Maya wallet yet. You can add one from Wallet.',
                        ],
                        'bank_transfer' => [
                            'label' => 'Bank Account',
                            'description' => 'Use a saved bank account wallet at checkout.',
                            'empty' => 'No saved bank account yet. You can add one from Wallet.',
                        ],
                    ];

                    $maskWallet = function ($wallet): string {
                        $identifier = (string) $wallet->account_identifier;
                        $digits = preg_replace('/\D+/', '', $identifier);
                        $tail = substr($digits ?: $identifier, -4);

                        if (in_array($wallet->type, ['gcash', 'maya'], true)) {
                            return substr($digits ?: $identifier, 0, 4) . ' *** ' . $tail;
                        }

                        return '**** **** ' . $tail;
                    };
                @endphp

                <section class="payment-card" aria-labelledby="checkout-payment-heading">
                    <div class="payment-card-head">
                        <div>
                            <h3 class="payment-card-title" id="checkout-payment-heading">Payment Method</h3>
                            <p class="payment-card-subtitle">Choose how you want to pay for your order.</p>
                        </div>
                        <a class="manage-wallet" href="{{ route('buyer.wallet.index') }}">Manage Wallet</a>
                    </div>

                    <div class="payment-options">
                        @foreach($paymentOptions as $value => $option)
                            @php
                                $savedCount = $walletsByType->get($value, collect())->count();
                            @endphp
                            <div>
                                <label class="payment-radio-card">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $value }}"
                                        data-payment-method
                                        @checked($selectedPaymentType === $value)
                                    >
                                    <span>
                                        <strong>{{ $option['label'] }}</strong>
                                        <span>{{ $option['description'] }}</span>
                                    </span>
                                </label>

                                @if($value !== 'cod' && $savedCount === 0)
                                    <p class="payment-note">
                                        {{ $option['empty'] }}
                                        <a href="{{ route('buyer.wallet.index') }}">Add Wallet</a>
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($wallets->isNotEmpty())
                        <h4 class="wallet-section-title">Saved Wallets</h4>
                        <div class="wallet-list">
                            @foreach($wallets as $wallet)
                                <label class="wallet-card">
                                    <input
                                        type="radio"
                                        name="payment_method_id"
                                        value="{{ $wallet->id }}"
                                        data-wallet-radio
                                        data-wallet-type="{{ $wallet->type }}"
                                        @checked((string) $selectedWalletId === (string) $wallet->id)
                                    >
                                    <span>
                                        <span class="wallet-title-row">
                                            <strong>{{ $paymentOptions[$wallet->type]['label'] ?? 'Wallet' }}</strong>
                                            @if($wallet->is_default)
                                                <span class="wallet-badge">Default</span>
                                            @endif
                                        </span>
                                        <span>{{ $wallet->account_name ?: 'Lumora wallet' }}</span>
                                        <span>{{ $maskWallet($wallet) }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="payment-note">No saved wallets yet. <a href="{{ route('buyer.wallet.index') }}">Add Wallet</a></p>
                    @endif

                    @error('payment_method')<span class="payment-error">{{ $message }}</span>@enderror
                    @error('payment_method_id')<span class="payment-error">{{ $message }}</span>@enderror
                </section>

                <button class="place-order" type="submit">Place order</button>
            </form>
        </aside>
    </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const methodRadios = [...document.querySelectorAll('[data-payment-method]')];
        const walletRadios = [...document.querySelectorAll('[data-wallet-radio]')];

        const walletForType = (type) => walletRadios.find((radio) => radio.dataset.walletType === type);

        walletRadios.forEach((walletRadio) => {
            walletRadio.addEventListener('change', () => {
                if (!walletRadio.checked) return;

                const matchingMethod = methodRadios.find((radio) => radio.value === walletRadio.dataset.walletType);
                if (matchingMethod) matchingMethod.checked = true;
            });
        });

        methodRadios.forEach((methodRadio) => {
            methodRadio.addEventListener('change', () => {
                if (!methodRadio.checked) return;

                if (methodRadio.value === 'cod') {
                    walletRadios.forEach((radio) => {
                        radio.checked = false;
                    });
                    return;
                }

                const currentWallet = walletRadios.find((radio) => radio.checked);
                if (currentWallet?.dataset.walletType === methodRadio.value) return;

                walletRadios.forEach((radio) => {
                    radio.checked = false;
                });

                const firstMatchingWallet = walletForType(methodRadio.value);
                if (firstMatchingWallet) firstMatchingWallet.checked = true;
            });
        });

        // Voucher Code Input Logic
        const voucherInput = document.getElementById('voucherCodeInput');
        const applyVoucherBtn = document.getElementById('applyVoucherBtn');
        const voucherFeedback = document.getElementById('voucherFeedback');
        const voucherSummaryRow = document.getElementById('voucherSummaryRow');
        const voucherSummaryCode = document.getElementById('voucherSummaryCode');
        const voucherSummaryAmount = document.getElementById('voucherSummaryAmount');
        const summaryTotalAmount = document.getElementById('summaryTotalAmount');
        const appliedVoucherHidden = document.getElementById('appliedVoucherHidden');

        const formatCurrency = (val) => '₱' + Number(val).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const builtInVouchers = {
            'LUMORA10': { type: 'percent', value: 10, label: '10% OFF' },
            'LUMORA20': { type: 'percent', value: 20, label: '20% OFF' },
            'WELCOME50': { type: 'fixed', value: 50, label: '₱50 OFF' },
            'LESS100': { type: 'fixed', value: 100, label: '₱100 OFF' },
            'FREESHIP': { type: 'shipping', value: 15, label: 'Free Shipping' },
        };

        function clearVoucher() {
            const baseTotal = parseFloat(summaryTotalAmount?.dataset.baseTotal || '0');
            if (voucherSummaryRow) voucherSummaryRow.style.display = 'none';
            if (summaryTotalAmount) summaryTotalAmount.textContent = formatCurrency(baseTotal);
            if (appliedVoucherHidden) appliedVoucherHidden.value = '';
            if (voucherFeedback) {
                voucherFeedback.className = 'voucher-feedback';
                voucherFeedback.innerHTML = '';
            }
        }

        function applyVoucherCode() {
            const rawCode = (voucherInput?.value || '').trim().toUpperCase();
            if (!rawCode) {
                clearVoucher();
                if (voucherFeedback) {
                    voucherFeedback.className = 'voucher-feedback error';
                    voucherFeedback.textContent = 'Please enter a voucher code.';
                }
                return;
            }

            const baseTotal = parseFloat(summaryTotalAmount?.dataset.baseTotal || '0');
            const netItems = parseFloat(summaryTotalAmount?.dataset.netItems || '0');
            const shipping = parseFloat(summaryTotalAmount?.dataset.shipping || '0');
            const rule = builtInVouchers[rawCode];

            if (!rule) {
                clearVoucher();
                if (voucherFeedback) {
                    voucherFeedback.className = 'voucher-feedback error';
                    voucherFeedback.textContent = 'Invalid or expired voucher code.';
                }
                return;
            }

            let voucherSavings = 0;
            if (rule.type === 'percent') {
                voucherSavings = Math.round(netItems * (rule.value / 100) * 100) / 100;
            } else if (rule.type === 'shipping') {
                voucherSavings = Math.min(shipping, rule.value);
            } else {
                voucherSavings = Math.min(netItems, rule.value);
            }

            const newTotal = Math.max(0, Math.round((baseTotal - voucherSavings) * 100) / 100);

            if (voucherSummaryCode) voucherSummaryCode.textContent = rawCode;
            if (voucherSummaryAmount) voucherSummaryAmount.textContent = '- ' + formatCurrency(voucherSavings);
            if (voucherSummaryRow) voucherSummaryRow.style.display = 'flex';
            if (summaryTotalAmount) summaryTotalAmount.textContent = formatCurrency(newTotal);
            if (appliedVoucherHidden) appliedVoucherHidden.value = rawCode;

            if (voucherFeedback) {
                voucherFeedback.className = 'voucher-feedback success';
                voucherFeedback.innerHTML = `<span>Voucher <strong>${rawCode}</strong> (${rule.label}) applied!</span><button type="button" class="voucher-remove-btn" id="removeVoucherBtn">Remove</button>`;
                document.getElementById('removeVoucherBtn')?.addEventListener('click', () => {
                    if (voucherInput) voucherInput.value = '';
                    clearVoucher();
                });
            }
        }

        applyVoucherBtn?.addEventListener('click', applyVoucherCode);
        voucherInput?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyVoucherCode();
            }
        });
    });
</script>
</body>
</html>
