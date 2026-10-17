<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Lumora | Sign Up</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --plum:#351128; --plum-soft:#5a294d; --rose:#b96562; --cream:#fffaf6; --paper:#fffdfb; --beige:#f7eee8; --border:#e8d8d0; --text-dark:#2B1826; --text-muted:#857579; --gold:#c9972b; --maroon:#4A1942; --maroon-dark:#2E1330; --coral:#E2582E; }
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:'Inter',sans-serif; color:var(--plum); min-height:100vh; margin:0; background:var(--cream); }
        .login-shell { min-height:100vh; display:grid; place-items:center; padding:32px; background:radial-gradient(circle at 8% 12%,rgba(236,205,193,.35),transparent 28%),radial-gradient(circle at 92% 88%,rgba(236,213,205,.45),transparent 32%),var(--cream); }
        .login-frame { width:min(1120px,100%); min-height:690px; display:grid; grid-template-columns:.82fr 1.18fr; overflow:hidden; border:1px solid var(--border); border-radius:20px; background:#fffdfb; box-shadow:0 24px 70px rgba(53,17,40,.12); }
        .login-art { position:relative; min-height:690px; display:flex; align-items:flex-start; padding:54px 42px; overflow:hidden; background-image:linear-gradient(180deg,rgba(53,17,40,.04),rgba(53,17,40,.10)),url('{{ asset('images/hero.jpg') }}'); background-size:cover; background-position:center; }
        .login-art::after { content:''; position:absolute; inset:0; background:linear-gradient(180deg,rgba(255,250,246,.06),rgba(53,17,40,.26)); pointer-events:none; }
        .brand-tagline { position:absolute; z-index:1; left:42px; right:42px; bottom:48px; font-family:'Playfair Display',serif; font-size:clamp(30px,4vw,52px); line-height:1.02; color:var(--plum); }
        .brand-tagline small { display:block; margin-top:16px; color:var(--plum-soft); font:14px 'Inter',sans-serif; line-height:1.5; }
        .login-panel { display:flex; align-items:flex-start; justify-content:center; padding:42px clamp(28px,5vw,70px); background:#fffdfb; overflow-y:auto; max-height:90vh; }
        .login-card { width:min(520px,100%); }
        p.subtitle { color:var(--text-muted); font-size:.85rem; margin:0 0 22px; }
        h1.title { color:var(--plum); font-family:'Playfair Display',serif; font-size:clamp(30px,4vw,44px); font-weight:600; line-height:1.05; margin:0 0 8px; }
        .back-home { display:inline-flex; align-items:center; gap:7px; margin:0 0 22px; color:var(--plum); font-size:12px; font-weight:600; text-decoration:none; }
        .back-home:hover { color:var(--rose); text-decoration:underline; text-underline-offset:3px; }

        .error-box { background:#fdece6; color:#b8451f; padding:10px 14px; border-radius:12px; margin-bottom:16px; font-size:.85rem; }
        .error-box ul { margin:4px 0 0 18px; padding:0; }
        .field-error { display:block; margin-top:6px; color:#dc2626; font-size:.74rem; line-height:1.4; }
        .success-box { background:#eef3ec; color:#5C7355; padding:10px 14px; border-radius:12px; margin-bottom:16px; font-size:.85rem; }

        .row-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .row-3 { display:grid; grid-template-columns:1fr 1fr 70px; gap:12px; }
        .row-3-equal { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
        .form-group { margin-bottom:14px; }
        label { display:block; margin-bottom:6px; font-size:.8rem; font-weight:500; color:var(--text-dark); }
        input[type=text], input[type=email], input[type=password], input[type=date], input[type=tel], select {
            width:100%; padding:10px 13px; border:1px solid var(--border); border-radius:12px; font-size:.85rem; font-family:inherit; background:#FBF3F0; color:var(--text-dark);
        }
        input:disabled, select:disabled { color:var(--text-muted); background:#F3E9E6; cursor:not-allowed; }
        input:focus, select:focus { outline:none; border-color:var(--maroon); }

        .sex-options { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; padding-top:0; }
        .sex-option { position:relative; min-width:0; margin-bottom:0; }
        .sex-option input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
        .sex-card { height:48px; display:flex; align-items:center; justify-content:center; gap:8px; border:1px solid var(--border); border-radius:12px; background:#fff; color:var(--text-dark); font-size:.85rem; font-weight:500; transition:background-color 200ms ease,border-color 200ms ease,color 200ms ease,box-shadow 200ms ease; cursor:pointer; }
        .sex-check { width:15px; height:15px; display:none; color:var(--rose); flex:0 0 auto; }
        .sex-option:hover .sex-card { background:var(--beige); border-color:var(--rose); }
        .sex-option input:focus-visible + .sex-card { border-color:var(--rose); box-shadow:0 0 0 3px rgba(185,101,98,.16); }
        .sex-option input:checked + .sex-card { background:#F8F1ED; border-color:var(--rose); color:var(--plum); }
        .sex-option input:checked + .sex-card .sex-check { display:block; }

        .section-label { font-size:.85rem; font-weight:600; color:var(--maroon-dark); margin:18px 0 10px; padding-top:14px; border-top:1px solid var(--border); }

        .email-row { display:flex; gap:8px; }
        .email-row input { flex:1; }
        .verify-btn { padding:0 16px; border-radius:12px; border:1.5px solid var(--maroon); background:#fff; color:var(--maroon); font-size:.82rem; font-weight:600; cursor:pointer; white-space:nowrap; }
        .verify-btn:hover { background:#FBF3F0; }
        .verify-btn.verified { background:#6C8A63; border-color:#6C8A63; color:#fff; cursor:default; }
        .verify-note { font-size:.74rem; color:var(--text-muted); margin-top:5px; }
        .btn-row { display:flex; gap:10px; margin-top:20px; }
        button.submit-btn { flex:1; padding:12px; border-radius:24px; border:none; font-weight:600; font-size:.85rem; font-family:inherit; cursor:pointer; }
        button.buyer-btn { background:var(--maroon-dark); color:#fff; }
        button.buyer-btn:hover { background:#22102a; }
        button.seller-btn { background:#fff; color:var(--maroon); border:1.5px solid var(--maroon) !important; }
        button.seller-btn:hover { background:#FBF3F0; }

        .approval-note { background:var(--beige); border-radius:9px; padding:10px 12px; font-size:.76rem; color:#5C4A52; line-height:1.5; margin-top:16px; }

        .login-link { text-align:center; margin-top:18px; font-size:.82rem; color:var(--text-muted); }
        .login-link a { color:var(--maroon); font-weight:600; text-decoration:none; }
        .back-home { display:inline-flex; align-items:center; gap:6px; margin-bottom:18px; color:var(--maroon); font-size:.78rem; font-weight:600; text-decoration:none; }
        .back-home:hover { color:var(--coral); text-decoration:underline; }
    </style>
</head>
<body>
<main class="login-shell">
<section class="login-frame" aria-labelledby="signup-title">
    <aside class="login-art" aria-label="Lumora brand message">
        <x-logo class="relative z-[1]" />
        <div class="brand-tagline">Create your account<small>Start shopping or selling with Lumora.</small></div>
    </aside>

    <section class="login-panel">
    <div class="login-card">
        <a href="{{ route('home') }}" class="back-home" aria-label="Back to Lumora homepage">&larr; Back to homepage</a>
        <h1 id="signup-title" class="title">Create your account</h1>
        <p class="subtitle">All fields marked required must be filled in.</p>

        @if (session('flash_success'))
            <div class="success-box">{{ session('flash_success') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="registerForm" enctype="multipart/form-data">
            @csrf

            <div class="row-3">
                <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" minlength="2" maxlength="50" required data-no-number-name>
                    <span class="field-error" data-name-error @if (! $errors->has('last_name')) style="display:none;" @endif>{{ $errors->first('last_name') ?: "You can't input a number in your name." }}</span>
                </div>
                <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" minlength="2" maxlength="50" required data-no-number-name>
                    <span class="field-error" data-name-error @if (! $errors->has('first_name')) style="display:none;" @endif>{{ $errors->first('first_name') ?: "You can't input a number in your name." }}</span>
                </div>
                <div class="form-group">
                    <label>M.I.</label>
                    <input type="text" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="4" data-no-number-name>
                    <span class="field-error" data-name-error @if (! $errors->has('middle_initial')) style="display:none;" @endif>{{ $errors->first('middle_initial') ?: "You can't input a number in your name." }}</span>
                </div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Sex</label>
                    <div class="sex-options" role="radiogroup" aria-label="Sex">
                        <label class="sex-option">
                            <input type="radio" name="sex" value="female" {{ old('sex', 'female') === 'female' ? 'checked' : '' }} required>
                            <span class="sex-card">
                                <svg class="sex-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>Female</span>
                            </span>
                        </label>
                        <label class="sex-option">
                            <input type="radio" name="sex" value="male" {{ old('sex') === 'male' ? 'checked' : '' }}>
                            <span class="sex-card">
                                <svg class="sex-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span>Male</span>
                            </span>
                        </label>
                    </div>
                </div>
                <div class="form-group"><label>Contact Number</label><input type="tel" name="contact_number" value="{{ old('contact_number') }}" placeholder="09XX XXX XXXX" required></div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" required onchange="computeAge()" oninput="computeAge()">
                    <span class="field-error" id="ageError" @if (! $errors->has('date_of_birth')) style="display:none;" @endif>{{ $errors->first('date_of_birth') ?: 'You must be at least 18 years old to sign up.' }}</span>
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="text" id="ageDisplay" value="—" disabled>
                    <input type="hidden" name="age" id="ageHidden">
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <div class="email-row">
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required>
                    <button type="button" class="verify-btn" id="verifyBtn" onclick="sendCode()">Verify</button>
                </div>
                <div class="verify-note" id="verifyNote"></div>
                <div class="email-row" id="codeRow" style="display:none;margin-top:8px;">
                    <input type="text" id="codeInput" placeholder="6-digit code" maxlength="6" inputmode="numeric">
                    <button type="button" class="verify-btn" id="confirmBtn" onclick="confirmCode()">Confirm</button>
                </div>
                <input type="hidden" id="emailVerifiedFlag" value="0">
            </div>

            <div class="section-label">Address</div>
            @include('partials.address-fields', [
                'fieldPrefix' => 'registerAddress',
                'selectGridClass' => 'row-3-equal',
                'streetGridClass' => 'row-3-equal',
            ])

            <div class="section-label">Account</div>
            <div class="row-2">
                <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="8"></div>
                <div class="form-group"><label>Confirm Password</label><input type="password" name="password_confirmation" required minlength="8"></div>
            </div>

            <div class="btn-row">
                <button type="submit" name="signup_type" value="buyer" class="submit-btn buyer-btn">Sign Up</button>
                <button type="button" class="submit-btn seller-btn" onclick="openSellerModal()">Sign Up as Seller</button>
            </div>
        </form>
        <div class="login-link">Already have an account? <a href="{{ route('login') }}">Log in</a></div>
    </div>
    </section>
</section>
</main>

<!-- Seller Info Modal -->
<div id="sellerModalOverlay" style="display:none;position:fixed;inset:0;background:rgba(43,24,38,.55);z-index:999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;max-width:440px;width:100%;padding:30px;border-radius:20px;position:relative;max-height:88vh;overflow-y:auto;">
        <button type="button" onclick="closeSellerModal()" style="position:absolute;top:14px;right:18px;background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">&times;</button>
        <h2 style="font-size:1.15rem;font-weight:700;margin-bottom:4px;color:var(--maroon);">Seller Information</h2>
        <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:18px;">Tell us about your business. This is submitted along with the account details you already filled in.</p>

        @include('partials.seller-application-fields', [
            'formId' => 'registerForm',
            'required' => false,
        ])

        <button type="submit" form="registerForm" name="signup_type" value="seller" class="submit-btn buyer-btn" style="width:100%;margin-top:6px;">
            Submit Seller Application
        </button>

        <div class="approval-note" style="margin-top:14px;">
            After submitting your registration, please wait for the administrator's approval, which will be sent to your email.
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// ---------- Age auto-compute & 18+ check ----------
function computeAge() {
    const dobInput = document.getElementById('date_of_birth');
    const dob = dobInput.value;
    const ageErr = document.getElementById('ageError');
    if (!dob) {
        document.getElementById('ageDisplay').value = '—';
        document.getElementById('ageHidden').value = '';
        return false;
    }
    const birth = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birth.getFullYear();
    const m = today.getMonth() - birth.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
    document.getElementById('ageDisplay').value = age;
    document.getElementById('ageHidden').value = age;

    if (age < 18) {
        if (ageErr) {
            ageErr.textContent = 'You must be at least 18 years old to sign up.';
            ageErr.style.display = 'block';
        }
        dobInput.style.borderColor = '#dc2626';
        return false;
    } else {
        if (ageErr) {
            ageErr.style.display = 'none';
        }
        dobInput.style.borderColor = '';
        return true;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('date_of_birth')?.value) {
        computeAge();
    }
});

// ---------- Email verification (unchanged) ----------
function sendCode() {
    const email = document.getElementById('email').value;
    const btn = document.getElementById('verifyBtn');
    const note = document.getElementById('verifyNote');

    if (!email || !email.includes('@')) {
        note.textContent = 'Enter a valid email first.';
        note.style.color = '#b8451f';
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Sending...';

    fetch('{{ route('register.sendCode') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ email })
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Something went wrong.');
        note.textContent = 'Code sent — check your inbox.';
        note.style.color = '#4d6b44';
        btn.textContent = 'Resend code';
        btn.disabled = false;
        document.getElementById('codeRow').style.display = 'flex';
    })
    .catch((err) => {
        note.textContent = err.message;
        note.style.color = '#b8451f';
        btn.textContent = 'Verify';
        btn.disabled = false;
    });
}

function confirmCode() {
    const email = document.getElementById('email').value;
    const code = document.getElementById('codeInput').value;
    const note = document.getElementById('verifyNote');
    const confirmBtn = document.getElementById('confirmBtn');
    const verifyBtn = document.getElementById('verifyBtn');

    if (!code || code.length !== 6) {
        note.textContent = 'Enter the 6-digit code from your email.';
        note.style.color = '#b8451f';
        return;
    }

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Checking...';

    fetch('{{ route('register.verifyCode') }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ email, code })
    })
    .then(async (res) => {
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || 'Incorrect code.');
        note.textContent = 'Email verified ✓';
        note.style.color = '#4d6b44';
        verifyBtn.textContent = 'Verified ✓';
        verifyBtn.classList.add('verified');
        verifyBtn.disabled = true;
        document.getElementById('codeRow').style.display = 'none';
        document.getElementById('emailVerifiedFlag').value = '1';
    })
    .catch((err) => {
        note.textContent = err.message;
        note.style.color = '#b8451f';
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Confirm';
    });
}

const NAME_NUMBER_MSG = "You can't input a number in your name.";
const NAME_SPECIAL_MSG = "You can't input special characters in your name (only ñ and Ñ are allowed).";
const NAME_LENGTH_MSG = "Name and surname must be between 2 and 50 characters.";
const nameInputs = document.querySelectorAll('[data-no-number-name]');

function checkNameInput(input, checkMinLength = false) {
    const val = input.value;
    const trimmed = val.trim();
    const isFullNameField = input.name === 'first_name' || input.name === 'last_name';
    const hasNumber = /\d/.test(val);
    const hasSpecial = /[^A-Za-zñÑ\s\d]/.test(val);
    const tooShortOrLong = isFullNameField && ((checkMinLength && trimmed.length < 2) || (trimmed.length > 0 && trimmed.length < 2) || trimmed.length > 50);
    const err = input.parentElement.querySelector('[data-name-error]');
    if (err) {
        if (hasNumber) {
            err.textContent = NAME_NUMBER_MSG;
            err.style.display = 'block';
            input.style.borderColor = '#dc2626';
        } else if (hasSpecial) {
            err.textContent = NAME_SPECIAL_MSG;
            err.style.display = 'block';
            input.style.borderColor = '#dc2626';
        } else if (tooShortOrLong) {
            err.textContent = NAME_LENGTH_MSG;
            err.style.display = 'block';
            input.style.borderColor = '#dc2626';
        } else {
            err.style.display = 'none';
            input.style.borderColor = '';
        }
    }
    return !hasNumber && !hasSpecial && !tooShortOrLong;
}

nameInputs.forEach((input) => {
    input.addEventListener('input', () => checkNameInput(input, false));
});

function validateSignupFields() {
    let allNamesValid = true;
    nameInputs.forEach((input) => {
        if (!checkNameInput(input, true)) allNamesValid = false;
    });
    if (!allNamesValid) {
        return false;
    }
    if (!computeAge()) {
        const dobInput = document.getElementById('date_of_birth');
        const ageErr = document.getElementById('ageError');
        if (ageErr) {
            ageErr.textContent = dobInput.value
                ? 'You must be at least 18 years old to sign up.'
                : 'Please enter your date of birth.';
            ageErr.style.display = 'block';
        }
        dobInput.style.borderColor = '#dc2626';
        dobInput.focus();
        return false;
    }
    const postalInput = document.querySelector('[data-postal-code]');
    if (postalInput && postalInput.value.trim() !== '') {
        if (!/^\d{1,4}$/.test(postalInput.value.trim())) {
            const postalErr = postalInput.parentElement?.querySelector('[data-postal-error]');
            if (postalErr) {
                postalErr.textContent = 'Postal code must be a maximum of 4 digits.';
                postalErr.style.display = 'block';
            }
            postalInput.style.borderColor = '#dc2626';
            postalInput.focus();
            return false;
        }
    }
    return true;
}

document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (!validateSignupFields()) {
        e.preventDefault();
        return;
    }
    if (document.getElementById('emailVerifiedFlag').value !== '1') {
        e.preventDefault();
        const note = document.getElementById('verifyNote');
        note.textContent = 'Please verify your email before signing up.';
        note.style.color = '#b8451f';
    }
});

function openSellerModal() {
    if (!validateSignupFields()) {
        return;
    }
    document.getElementById('sellerModalOverlay').style.display = 'flex';
    document.getElementById('business_name').required = true;
    document.getElementById('category').required = true;
    document.getElementById('id_document').required = true;
    document.getElementById('business_permit').required = true;
}

function closeSellerModal() {
    document.getElementById('sellerModalOverlay').style.display = 'none';
    document.getElementById('business_name').required = false;
    document.getElementById('category').required = false;
    document.getElementById('id_document').required = false;
    document.getElementById('business_permit').required = false;
}
</script>
</body>
</html>
