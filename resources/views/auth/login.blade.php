<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Sign in — Lumora Logistics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        html {
            height: 100%;
        }

        /* Centered viewport layout with soft beige background */
        body.login-page {
            min-height: 100vh;
            min-height: 100dvh;
            width: 100%;
            margin: 0;
            padding: clamp(16px, 3vh, 40px);
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #F5EDE4;
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Main Container: Fixed dimensions on desktop, guaranteed to never stretch on browser zoom-out */
        .login-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1080px !important;
            height: 720px !important;
            min-height: 0 !important;
            max-height: min(720px, calc(100vh - 48px));
            margin: auto;
            border-radius: 18px;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(360px, .92fr) minmax(420px, 1.08fr);
            box-shadow: 
                0 32px 85px -15px rgba(50, 18, 42, 0.24),
                0 12px 35px -8px rgba(50, 18, 42, 0.12),
                0 0 0 1px rgba(224, 198, 186, 0.65);
            background: #FFFFFF;
            transition: box-shadow 0.3s ease;
        }

        /* Left brand art panel */
        .login-art {
            height: 100%;
            box-sizing: border-box;
            padding: 44px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: linear-gradient(160deg, #2A1124 0%, #3D1B3D 55%, #4C1D40 100%);
            position: relative;
            overflow: hidden;
        }

        .login-art-copy {
            margin: auto 0;
            max-width: 380px;
        }

        .login-art h1 {
            font-size: clamp(32px, 3.2vw, 46px);
            margin: 14px 0;
            line-height: 1.08;
        }

        /* Right form container */
        .login-card-wrap {
            height: 100%;
            box-sizing: border-box;
            padding: 40px clamp(24px, 4vw, 52px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: #FFFFFF;
            overflow-y: auto;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            padding: 0;
            margin: auto 0;
        }

        .login-legal {
            margin-top: 24px;
            text-align: center;
        }

        /* Responsive handling for tablets & mobile */
        @media (max-width: 860px) {
            body.login-page {
                padding: 16px;
                align-items: flex-start;
            }

            .login-shell {
                display: block;
                height: auto !important;
                max-height: none !important;
                max-width: 460px !important;
                margin: 20px auto;
                border-radius: 18px;
            }

            .login-art {
                display: none;
            }

            .login-card-wrap {
                min-height: auto;
                padding: 36px 24px;
            }
        }
    </style>
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
