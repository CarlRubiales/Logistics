@php
    $formId = $formId ?? null;
    $includeReason = $includeReason ?? false;
    $required = $required ?? true;
@endphp

<div class="form-group seller-application-field">
    <label for="business_name">Business Name</label>
    <input type="text" name="business_name" id="business_name" value="{{ old('business_name') }}" @if($formId) form="{{ $formId }}" @endif @required($required)>
    @error('business_name')<span class="field-error">{{ $message }}</span>@enderror
</div>

<div class="form-group seller-application-field">
    <label for="category">Line of Business (Category)</label>
    <select name="category" id="category" @if($formId) form="{{ $formId }}" @endif @required($required)>
        <option value="">Select a category</option>
        @foreach (config('categories') as $cat)
            <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
        @endforeach
    </select>
    @error('category')<span class="field-error">{{ $message }}</span>@enderror
</div>

<div class="form-group seller-application-field">
    <label for="id_document">Upload Valid ID <small style="font-weight:400;color:#857579;">(JPG, PNG, PDF · Max 5MB)</small></label>
    <input type="file" name="id_document" id="id_document" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" data-seller-file @if($formId) form="{{ $formId }}" @endif @required($required)>
    <span class="field-error" data-seller-file-error @if (! $errors->has('id_document')) style="display:none;" @endif>{{ $errors->first('id_document') ?: 'Only JPG, PNG, and PDF files up to 5MB are allowed.' }}</span>
</div>

<div class="form-group seller-application-field">
    <label for="business_permit">Upload Business Permit <small style="font-weight:400;color:#857579;">(JPG, PNG, PDF · Max 5MB)</small></label>
    <input type="file" name="business_permit" id="business_permit" accept=".jpg,.jpeg,.png,.pdf,image/jpeg,image/png,application/pdf" data-seller-file @if($formId) form="{{ $formId }}" @endif @required($required)>
    <span class="field-error" data-seller-file-error @if (! $errors->has('business_permit')) style="display:none;" @endif>{{ $errors->first('business_permit') ?: 'Only JPG, PNG, and PDF files up to 5MB are allowed.' }}</span>
</div>

@if ($includeReason)
    <div class="form-group seller-application-field">
        <label for="reason">Application Details</label>
        <textarea name="reason" id="reason" maxlength="2000" placeholder="Share the products you plan to offer and anything the admin should know." @if($formId) form="{{ $formId }}" @endif>{{ old('reason') }}</textarea>
        @error('reason')<span class="field-error">{{ $message }}</span>@enderror
    </div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
    const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'application/pdf'];

    function validateSellerFile(input) {
        const err = input.parentElement?.querySelector('[data-seller-file-error]');
        const file = input.files && input.files[0];
        if (!file) {
            if (err) err.style.display = 'none';
            input.style.borderColor = '';
            return true;
        }

        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const validExt = ALLOWED_EXTENSIONS.includes(ext);
        const validMime = !file.type || ALLOWED_MIMES.includes(file.type.toLowerCase());

        if (!validExt || !validMime) {
            input.value = '';
            if (err) {
                err.textContent = 'Only JPG, PNG, and PDF files are allowed.';
                err.style.display = 'block';
            }
            input.style.borderColor = '#dc2626';
            return false;
        }

        if (file.size > MAX_FILE_SIZE) {
            input.value = '';
            if (err) {
                err.textContent = 'File size must not exceed 5MB.';
                err.style.display = 'block';
            }
            input.style.borderColor = '#dc2626';
            return false;
        }

        if (err) err.style.display = 'none';
        input.style.borderColor = '';
        return true;
    }

    document.querySelectorAll('[data-seller-file]').forEach(function (input) {
        input.addEventListener('change', function () {
            validateSellerFile(input);
        });
    });
});
</script>
