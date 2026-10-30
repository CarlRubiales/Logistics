<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#3D1B3D">
    <title>Rider Registration — Lumora Logistics</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="logistics-page">
<main class="auth-shell">
    <section class="auth-card" style="max-width: 580px;">
        <div class="brand-lockup">
            <div class="brand-mark"><span></span><span></span><span></span></div>
            <div>
                <div class="brand-name">LUMORA</div>
                <div class="brand-subtitle">LOGISTICS</div>
            </div>
        </div>
        <div class="eyebrow">RIDER NETWORK</div>
        <h1>Register as a delivery rider</h1>
        <p class="intro">Submit your details and legal vehicle documents to the sorting center. Staff will verify your Driver's License and OR/CR before approving your account.</p>

        @if($errors->any())
            <div class="server-alert error" role="alert"><span>!</span> {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('riders.register.store') }}" enctype="multipart/form-data" class="form-grid">
            @csrf
            <label>Full name *
                <input name="name" value="{{ old('name') }}" placeholder="e.g. Juan Dela Cruz" required>
            </label>
            <label>Email *
                <input type="email" name="email" value="{{ old('email') }}" placeholder="rider@example.com" required>
            </label>
            <label>Mobile phone *
                <input name="phone" type="tel" value="{{ old('phone') }}" placeholder="09171234567" required>
            </label>
            <label>Vehicle type *
                <select name="vehicle_type" required>
                    <option value="Motorcycle" @selected(old('vehicle_type') === 'Motorcycle')>Motorcycle</option>
                    <option value="Tricycle" @selected(old('vehicle_type') === 'Tricycle')>Tricycle</option>
                </select>
            </label>
            <label class="full-span">License plate number
                <input name="license_number" value="{{ old('license_number') }}" placeholder="e.g. ABC 1234">
            </label>
            <label>Password (min 8 chars) *
                <input type="password" name="password" required>
            </label>
            <label>Confirm password *
                <input type="password" name="password_confirmation" required>
            </label>

            <!-- Document Uploads -->
            <div class="full-span" style="margin-top: 10px; margin-bottom: 4px;">
                <div style="font-weight: 700; font-size: 13px; color: #3D1B3D; margin-bottom: 2px;">Required Vehicle & Identity Documents</div>
                <div style="font-size: 11px; color: #8A7B7E;">Upload clear photos or scans of your Driver's License and Vehicle OR/CR (Official Receipt / Certificate of Registration). Accepted formats: JPG, PNG, PDF (Max 10MB each).</div>
            </div>

            <label>Upload Driver's License *
                <input type="file" name="drivers_license" accept="image/*,application/pdf" required style="padding: 6px 10px; height: auto;">
            </label>

            <label>Upload Vehicle OR / CR *
                <input type="file" name="or_cr" accept="image/*,application/pdf" required style="padding: 6px 10px; height: auto;">
            </label>

            <button class="primary-button full-span" type="submit" style="margin-top: 12px;">Submit registration & documents</button>
        </form>

        <p class="auth-footer">
            <a href="{{ route('login') }}">Already registered? Sign in</a>
        </p>
    </section>
</main>
</body>
</html>
