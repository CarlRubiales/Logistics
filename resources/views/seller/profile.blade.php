@extends('layouts.seller')

@section('title', 'Profile / Settings')

@section('content')
    @php
        $birthDate = old('date_of_birth', optional($user->date_of_birth)->format('Y-m-d'));
    @endphp

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('seller.profile.update') }}" method="POST" class="profile-form">
        @csrf
        @method('PATCH')

        <div class="panel profile-panel">
            <div class="section-heading">
                <div>
                    <h2>Profile Information</h2>
                    <p>Keep your personal, contact, address, and shop information up to date.</p>
                </div>
            </div>

            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="profile-grid basic-grid">
                    <div class="form-field">
                        <label for="first_name">First Name</label>
                        <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                        @error('first_name') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="last_name">Last Name</label>
                        <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                        @error('last_name') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="middle_initial">M.I.</label>
                        <input id="middle_initial" type="text" name="middle_initial" value="{{ old('middle_initial', $user->middle_initial) }}" maxlength="4">
                        @error('middle_initial') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label>Sex</label>
                        <div class="radio-row">
                            <label><input type="radio" name="sex" value="female" @checked(old('sex', $user->sex) === 'female') required> Female</label>
                            <label><input type="radio" name="sex" value="male" @checked(old('sex', $user->sex) === 'male')> Male</label>
                        </div>
                        @error('sex') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="contact_number">Contact Number</label>
                        <input id="contact_number" type="tel" name="contact_number" value="{{ old('contact_number', $user->contact_number) }}" required>
                        @error('contact_number') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="date_of_birth">Date of Birth</label>
                        <input id="date_of_birth" type="date" name="date_of_birth" value="{{ $birthDate }}" required>
                        @error('date_of_birth') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="age">Age</label>
                        <input id="age" type="text" value="{{ $birthDate ? \Carbon\Carbon::parse($birthDate)->age : '' }}" readonly>
                    </div>
                    <div class="form-field email-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        <div class="verification-note">
                            @if ($user->email_verified_at)
                                <span class="verified">Verified</span>
                            @else
                                <span class="not-verified">Not verified</span>
                            @endif
                            — changing your email will mark it as unverified again.
                        </div>
                        @error('email') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Address</h3>
                <div class="profile-grid address-grid">
                    <div class="form-field">
                        <label for="province">Province</label>
                        <select id="province" name="province" required>
                            <option value="">Loading provinces...</option>
                        </select>
                        @error('province') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="municipality">Municipality</label>
                        <select id="municipality" name="municipality" required disabled>
                            <option value="">Select province first</option>
                        </select>
                        @error('municipality') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="barangay">Barangay</label>
                        <select id="barangay" name="barangay" required disabled>
                            <option value="">Select municipality first</option>
                        </select>
                        @error('barangay') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field address-wide">
                        <label for="street">Street</label>
                        <input id="street" type="text" name="street" value="{{ old('street', $user->street) }}">
                        @error('street') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="house_number">House / Unit No.</label>
                        <input id="house_number" type="text" name="house_number" value="{{ old('house_number', $user->house_number) }}">
                        @error('house_number') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="form-section seller-section">
                <h3>Seller Information</h3>
                <div class="profile-grid seller-grid">
                    <div class="form-field">
                        <label for="shop_name">Shop / Store Name</label>
                        <input id="shop_name" type="text" name="shop_name" value="{{ old('shop_name', $user->shop_name) }}" placeholder="e.g. Skincare Products PH">
                        @error('shop_name') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                    <div class="form-field">
                        <label for="shop_description">Shop Description</label>
                        <textarea id="shop_description" name="shop_description" rows="4" placeholder="Tell buyers about your shop">{{ old('shop_description', $user->shop_description) }}</textarea>
                        @error('shop_description') <small class="field-error">{{ $message }}</small> @enderror
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <button type="submit" class="btn-solid">Save Changes</button>
            </div>
        </div>
    </form>

    <div class="panel password-panel">
        <h2>Change Password</h2>
        <p class="panel-description">Use a strong password to keep your seller account secure.</p>
        <form action="{{ route('seller.profile.updatePassword') }}" method="POST" class="password-form">
            @csrf
            @method('PATCH')
            <div class="password-grid">
                <div class="form-field">
                    <label for="current_password">Current Password</label>
                    <input id="current_password" type="password" name="current_password" required>
                </div>
                <div class="form-field">
                    <label for="password">New Password</label>
                    <input id="password" type="password" name="password" required>
                </div>
                <div class="form-field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
            </div>
            <button type="submit" class="btn-solid">Update Password</button>
        </form>
    </div>

    <style>
        .profile-panel { margin-bottom: 22px; }
        .section-heading { display:flex; justify-content:space-between; align-items:flex-start; }
        .section-heading h2, .password-panel h2 { margin-bottom:5px; }
        .section-heading p, .panel-description { color:var(--text-muted); font-size:13px; margin-bottom:0; }
        .form-section { padding:22px 0 4px; border-top:1px solid var(--border); margin-top:22px; }
        .form-section h3 { font-size:15px; color:var(--maroon); margin-bottom:16px; font-weight:700; }
        .profile-grid { display:grid; gap:16px 18px; }
        .basic-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
        .address-grid { grid-template-columns:repeat(3, minmax(0, 1fr)); }
        .seller-grid { grid-template-columns:1fr 1fr; }
        .form-field { min-width:0; }
        .email-field { grid-column:span 2; }
        .address-wide { grid-column:span 2; }
        .form-field label { display:block; color:var(--text-muted); font-size:13px; font-weight:600; margin-bottom:7px; }
        .form-field input:not([type="radio"]), .form-field select, .form-field textarea { width:100%; box-sizing:border-box; min-height:42px; padding:10px 12px; border:1px solid var(--border); border-radius:9px; background:#fffaf9; color:var(--text-dark); font:inherit; outline:none; }
        .form-field textarea { min-height:88px; resize:vertical; }
        .form-field input[readonly] { background:#f7eeee; color:var(--text-muted); }
        .form-field input:focus, .form-field select:focus, .form-field textarea:focus { border-color:var(--maroon); box-shadow:0 0 0 3px rgba(91,26,53,.10); }
        .form-field select:disabled { opacity:.65; cursor:not-allowed; }
        .radio-row { display:flex; align-items:center; gap:18px; min-height:42px; }
        .radio-row label { display:flex; align-items:center; gap:6px; margin:0; color:var(--text-dark); font-weight:400; }
        .radio-row input { width:auto; accent-color:var(--maroon); }
        .verification-note { color:var(--text-muted); font-size:12px; margin-top:6px; }
        .verified { color:var(--sage-2); }
        .not-verified, .field-error { color:var(--coral); }
        .field-error { display:block; font-size:12px; margin-top:5px; }
        .profile-actions { display:flex; justify-content:flex-end; padding-top:24px; margin-top:22px; border-top:1px solid var(--border); }
        .password-panel { margin-bottom:22px; }
        .password-panel .panel-description { margin-bottom:18px; }
        .password-form { display:flex; flex-direction:column; gap:16px; }
        .password-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:16px 18px; }
        .password-form button { align-self:flex-start; }
        @media (max-width:900px) { .basic-grid, .address-grid, .password-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } .seller-grid { grid-template-columns:1fr; } }
        @media (max-width:600px) { .basic-grid, .address-grid, .seller-grid, .password-grid { grid-template-columns:1fr; } .email-field, .address-wide { grid-column:auto; } .profile-actions { justify-content:stretch; } .profile-actions .btn-solid { width:100%; } }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const province = document.getElementById('province');
        const municipality = document.getElementById('municipality');
        const barangay = document.getElementById('barangay');
        const selectedProvince = @json(old('province', $user->province));
        const selectedMunicipality = @json(old('municipality', $user->municipality));
        const selectedBarangay = @json(old('barangay', $user->barangay));

        function itemName(item) { return item.name ?? item.label ?? item.description ?? ''; }
        function itemCode(item) { return item.code ?? item.id ?? item.key ?? ''; }

        function fillSelect(select, items, selectedValue, placeholder) {
            select.innerHTML = '';
            select.add(new Option(placeholder, ''));
            items.forEach(function (item) {
                const name = itemName(item);
                const option = new Option(name, name, false, name === selectedValue);
                option.dataset.code = itemCode(item);
                select.add(option);
            });
            select.disabled = items.length === 0;
        }

        async function getAddress(url) {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Address request failed: ' + response.status);
            const payload = await response.json();
            const items = Array.isArray(payload) ? payload : payload.data;
            if (!Array.isArray(items)) throw new Error('Invalid address response.');
            return items;
        }

        async function loadProvinces() {
            try {
                const items = await getAddress('{{ url('/address/provinces') }}');
                fillSelect(province, items, selectedProvince, 'Select province');
                await loadMunicipalities();
            } catch (error) {
                console.error(error);
                province.innerHTML = '<option value="">Could not load provinces</option>';
            }
        }

        async function loadMunicipalities() {
            const option = province.options[province.selectedIndex];
            const code = option?.dataset.code;
            municipality.innerHTML = '<option value="">Select municipality</option>';
            barangay.innerHTML = '<option value="">Select barangay</option>';
            municipality.disabled = true;
            barangay.disabled = true;
            if (!code) return;
            try {
                const items = await getAddress('{{ url('/address/provinces') }}/' + encodeURIComponent(code) + '/municipalities');
                fillSelect(municipality, items, selectedMunicipality, 'Select municipality');
                await loadBarangays();
            } catch (error) { console.error(error); }
        }

        async function loadBarangays() {
            const option = municipality.options[municipality.selectedIndex];
            const code = option?.dataset.code;
            barangay.innerHTML = '<option value="">Select barangay</option>';
            barangay.disabled = true;
            if (!code) return;
            try {
                const items = await getAddress('{{ url('/address/municipalities') }}/' + encodeURIComponent(code) + '/barangays');
                fillSelect(barangay, items, selectedBarangay, 'Select barangay');
            } catch (error) { console.error(error); }
        }

        province.addEventListener('change', loadMunicipalities);
        municipality.addEventListener('change', loadBarangays);
        loadProvinces();

        const nameInputs = ['first_name', 'last_name', 'middle_initial']
            .map((id) => document.getElementById(id))
            .filter(Boolean);
        const NAME_NUMBER_MSG = "You can't input a number in your name.";
        const NAME_SPECIAL_MSG = "You can't input special characters in your name (only ñ and Ñ are allowed).";

        function checkNameInput(input) {
            const val = input.value;
            const hasNumber = /\d/.test(val);
            const hasSpecial = /[^A-Za-zñÑ\s\d]/.test(val);
            const fieldWrap = input.closest('.form-field');
            let err = fieldWrap?.querySelector('.field-error');

            if (hasNumber || hasSpecial) {
                if (!err && fieldWrap) {
                    err = document.createElement('small');
                    err.className = 'field-error';
                    fieldWrap.appendChild(err);
                }
                if (err) {
                    err.textContent = hasNumber ? NAME_NUMBER_MSG : NAME_SPECIAL_MSG;
                    err.style.display = 'block';
                    err.style.color = '#dc2626';
                }
                input.style.borderColor = '#dc2626';
            } else {
                if (err && (err.textContent === NAME_NUMBER_MSG || err.textContent === NAME_SPECIAL_MSG)) {
                    err.style.display = 'none';
                }
                input.style.borderColor = '';
            }
            return !hasNumber && !hasSpecial;
        }

        nameInputs.forEach((input) => {
            input.addEventListener('input', () => checkNameInput(input));
        });

        const profileForm = document.querySelector('.profile-form');
        profileForm?.addEventListener('submit', function (e) {
            let valid = true;
            nameInputs.forEach((input) => {
                if (!checkNameInput(input)) valid = false;
            });
            if (!valid) e.preventDefault();
        });
    });
    </script>
@endsection
