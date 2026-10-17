@php
    $user = $user ?? null;
    $fieldPrefix = $fieldPrefix ?? 'address';
    $selectGridClass = $selectGridClass ?? 'grid';
    $streetGridClass = $streetGridClass ?? 'grid';
    $inputClass = $inputClass ?? '';
    $selectClass = $selectClass ?? $inputClass;
    $required = $required ?? true;
    $streetRequired = $streetRequired ?? false;

    $provinceId = $fieldPrefix.'Province';
    $municipalityId = $fieldPrefix.'Municipality';
    $barangayId = $fieldPrefix.'Barangay';
    $streetId = $fieldPrefix.'Street';
    $houseNumberId = $fieldPrefix.'HouseNumber';
    $postalCodeId = $fieldPrefix.'PostalCode';

    $selectedProvince = old('province', $user?->province);
    $selectedMunicipality = old('municipality', $user?->municipality);
    $selectedBarangay = old('barangay', $user?->barangay);
@endphp

<div class="{{ $selectGridClass }}">
    <div class="form-group">
        <label for="{{ $provinceId }}">Province</label>
        <select id="{{ $provinceId }}" name="province" class="{{ $selectClass }}" @required($required)>
            <option value="">Loading provinces...</option>
        </select>
        @error('province')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="{{ $municipalityId }}">Municipality</label>
        <select id="{{ $municipalityId }}" name="municipality" class="{{ $selectClass }}" disabled @required($required)>
            <option value="">Select province first</option>
        </select>
        @error('municipality')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="{{ $barangayId }}">Barangay</label>
        <select id="{{ $barangayId }}" name="barangay" class="{{ $selectClass }}" disabled @required($required)>
            <option value="">Select municipality first</option>
        </select>
        @error('barangay')<span class="field-error">{{ $message }}</span>@enderror
    </div>
</div>

<div class="{{ $streetGridClass }}">
    <div class="form-group">
        <label for="{{ $streetId }}">Street</label>
        <input id="{{ $streetId }}" name="street" type="text" class="{{ $inputClass }}" value="{{ old('street', $user?->street) }}" autocomplete="address-line1" @required($streetRequired)>
        @error('street')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="{{ $houseNumberId }}">House / Unit No.</label>
        <input id="{{ $houseNumberId }}" name="house_number" type="text" class="{{ $inputClass }}" value="{{ old('house_number', $user?->house_number) }}" autocomplete="address-line2">
        @error('house_number')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    <div class="form-group">
        <label for="{{ $postalCodeId }}">Postal Code</label>
        <input id="{{ $postalCodeId }}" name="postal_code" type="text" class="{{ $inputClass }}" value="{{ old('postal_code', $user?->postal_code) }}" maxlength="4" inputmode="numeric" pattern="\d{1,4}" placeholder="4 digits" autocomplete="postal-code" data-postal-code>
        <span class="field-error" data-postal-error @if (! $errors->has('postal_code')) style="display:none;" @endif>{{ $errors->first('postal_code') ?: 'Postal code must be a maximum of 4 digits.' }}</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const province = document.getElementById(@json($provinceId));
    const municipality = document.getElementById(@json($municipalityId));
    const barangay = document.getElementById(@json($barangayId));
    const postalCode = document.getElementById(@json($postalCodeId));
    const selectedProvince = @json($selectedProvince);
    const selectedMunicipality = @json($selectedMunicipality);
    const selectedBarangay = @json($selectedBarangay);

    if (postalCode) {
        postalCode.addEventListener('input', function () {
            const raw = postalCode.value;
            const hasNonDigit = /\D/.test(raw);
            const digitsOnly = raw.replace(/\D/g, '').slice(0, 4);
            if (raw !== digitsOnly) {
                postalCode.value = digitsOnly;
            }
            const err = postalCode.parentElement?.querySelector('[data-postal-error]');
            if (err) {
                if (hasNonDigit) {
                    err.textContent = 'Postal code must contain numbers only (maximum 4 digits).';
                    err.style.display = 'block';
                    postalCode.style.borderColor = '#dc2626';
                } else {
                    err.style.display = 'none';
                    postalCode.style.borderColor = '';
                }
            }
        });
    }

    if (!province || !municipality || !barangay) return;

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
            const items = await getAddress(@json(url('/address/provinces')));
            fillSelect(province, items, selectedProvince, 'Select province');
            await loadMunicipalities(selectedMunicipality, selectedBarangay);
        } catch (error) {
            console.error(error);
            province.innerHTML = '<option value="">Unable to load locations. Please try again.</option>';
        }
    }

    async function loadMunicipalities(selectedMunicipalityValue = '', selectedBarangayValue = '') {
        const option = province.options[province.selectedIndex];
        const code = option?.dataset.code;
        municipality.innerHTML = '<option value="">Select province first</option>';
        barangay.innerHTML = '<option value="">Select municipality first</option>';
        municipality.disabled = true;
        barangay.disabled = true;
        if (!code) return;

        municipality.innerHTML = '<option value="">Loading municipalities...</option>';

        try {
            const items = await getAddress(@json(url('/address/provinces')).replace(/\/$/, '') + '/' + encodeURIComponent(code) + '/municipalities');
            fillSelect(municipality, items, selectedMunicipalityValue, 'Select municipality');
            await loadBarangays(selectedBarangayValue);
        } catch (error) {
            console.error(error);
            municipality.innerHTML = '<option value="">Unable to load locations. Please try again.</option>';
        }
    }

    async function loadBarangays(selectedValue = '') {
        const option = municipality.options[municipality.selectedIndex];
        const code = option?.dataset.code;
        barangay.innerHTML = '<option value="">Select municipality first</option>';
        barangay.disabled = true;
        if (!code) return;

        barangay.innerHTML = '<option value="">Loading barangays...</option>';

        try {
            const items = await getAddress(@json(url('/address/municipalities')).replace(/\/$/, '') + '/' + encodeURIComponent(code) + '/barangays');
            fillSelect(barangay, items, selectedValue, 'Select barangay');
        } catch (error) {
            console.error(error);
            barangay.innerHTML = '<option value="">Unable to load locations. Please try again.</option>';
        }
    }

    province.addEventListener('change', function () {
        loadMunicipalities();
    });

    municipality.addEventListener('change', function () {
        loadBarangays();
    });

    loadProvinces();
});
</script>
