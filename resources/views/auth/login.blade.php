<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Sign in — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="logistics-page login-page">
    <main class="login-shell">
        <section class="login-art" aria-label="Lumora Logistics brand panel">
            <div class="login-art-glow"></div>
            <div class="login-brand"><div class="brand-mark"><span></span><span></span><span></span></div><div><div class="brand-name">LUMORA</div><div class="brand-subtitle">LOGISTICS</div></div></div>
            <div class="login-art-copy"><div class="eyebrow light">OPERATIONS CONSOLE</div><h1>Move beautifully.<br><em>Deliver calmly.</em></h1><p>A considered workspace for every order, parcel, and customer promise.</p></div>
            <div class="login-art-footer"><span class="login-status"><b></b> All systems operational</span><span>v1.0.0</span></div>
        </section>
        <section class="login-card-wrap">
            <div class="login-card">
                <div class="login-mobile-brand"><div class="brand-mark"><span></span><span></span><span></span></div><strong>LUMORA</strong></div>
                <div class="section-kicker">WELCOME BACK</div>
                <h2>Sign in to your workspace</h2>
                <p class="login-intro">Use your operations account to continue to Lumora Logistics.</p>
                @if ($errors->any())
                    <div class="server-alert error" role="alert"><span>!</span>{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('login.store') }}" class="login-form">
                    @csrf
                    <label for="email">Work email<input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="you@company.com" required autofocus></label>
                    <label for="password">Password<div class="password-field"><input id="password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required><button type="button" id="toggle-password" aria-label="Show password">Show</button></div></label>
                    <div class="login-options"><label class="remember-label"><input type="checkbox" name="remember" value="1"> <span>Remember me</span></label><a href="#forgot-password" data-toast="Password reset will be connected to your mail provider.">Forgot password?</a></div>
                    <button class="primary-button login-submit" type="submit">Sign in <span>→</span></button>
                </form>
                <p class="login-help">Need access? <a href="mailto:ops@lumora.example">Contact your administrator</a></p>
            </div>
            <div class="login-legal">© 2024 Lumora Logistics <span>•</span> Secure operations access</div>
        </section>
    </main>
</body>
</html>
