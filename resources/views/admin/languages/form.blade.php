@php
    $isEditing = isset($language);
    $countryCode = strtoupper((string) old('country_code', $isEditing ? $language->country_code : ''));
    $countryCodePreview = preg_match('/^[A-Z]{2}$/', $countryCode) === 1 ? strtolower($countryCode) : 'us';
    $isActive = (bool) old('is_active', $isEditing ? $language->is_active : true);
@endphp

<div class="dashboard-panel premium-shadow">
    <div class="panel-body">
        <form method="POST" action="{{ $formAction }}" class="row g-3">
            @csrf
            @if($isEditing)
                @method('PUT')
            @endif

            <div class="col-lg-6">
                <label for="name" class="form-label">{{ __('Name (Native)') }}</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $isEditing ? $language->name : '') }}"
                    maxlength="255"
                    required
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-lg-6">
                <label for="english_name" class="form-label">{{ __('English Name') }}</label>
                <input
                    type="text"
                    id="english_name"
                    name="english_name"
                    class="form-control @error('english_name') is-invalid @enderror"
                    value="{{ old('english_name', $isEditing ? $language->english_name : '') }}"
                    maxlength="255"
                    required
                >
                @error('english_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-lg-3">
                <label for="code" class="form-label">{{ __('Language Code (ISO 639-1)') }}</label>
                <input
                    type="text"
                    id="code"
                    name="code"
                    class="form-control text-lowercase @error('code') is-invalid @enderror"
                    value="{{ old('code', $isEditing ? $language->code : '') }}"
                    minlength="2"
                    maxlength="2"
                    required
                >
                @error('code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-lg-3">
                <label for="country_code" class="form-label">{{ __('Country Code (ISO 3166-1)') }}</label>
                <input
                    type="text"
                    id="country_code"
                    name="country_code"
                    class="form-control text-uppercase @error('country_code') is-invalid @enderror"
                    value="{{ $countryCode }}"
                    minlength="2"
                    maxlength="2"
                    required
                >
                @error('country_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-lg-3">
                <label for="direction" class="form-label">{{ __('Direction') }}</label>
                <select id="direction" name="direction" class="form-select @error('direction') is-invalid @enderror" required>
                    <option value="ltr" {{ old('direction', $isEditing ? $language->direction : 'ltr') === 'ltr' ? 'selected' : '' }}>
                        {{ __('LTR') }}
                    </option>
                    <option value="rtl" {{ old('direction', $isEditing ? $language->direction : 'ltr') === 'rtl' ? 'selected' : '' }}>
                        {{ __('RTL') }}
                    </option>
                </select>
                @error('direction')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-lg-3 d-flex align-items-end">
                <div class="w-100 border rounded-3 px-3 py-2 bg-light-soft">
                    <div class="small text-muted mb-1">{{ __('Flag Preview') }}</div>
                    <div class="d-flex align-items-center gap-2">
                        <span id="countryFlagPreview" class="fi fi-{{ $countryCodePreview }} language-flag"></span>
                        <span id="countryCodePreviewText" class="fw-semibold text-muted">{{ $countryCode ?: 'US' }}</span>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input
                        type="hidden"
                        name="is_active"
                        value="0"
                    >
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="is_active"
                        name="is_active"
                        value="1"
                        {{ $isActive ? 'checked' : '' }}
                    >
                    <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-primary px-4">
                    {{ $submitLabel }}
                </button>
                <a href="{{ route('admin.languages.index') }}" class="btn btn-light-soft px-4">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@push('styles')
<style>
.language-flag {
    width: 1.35rem;
    height: 1rem;
    border-radius: 2px;
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const countryCodeInput = document.getElementById('country_code');
    const flagPreview = document.getElementById('countryFlagPreview');
    const codePreviewText = document.getElementById('countryCodePreviewText');

    if (!countryCodeInput || !flagPreview || !codePreviewText) {
        return;
    }

    const renderFlag = () => {
        const code = countryCodeInput.value.trim().toUpperCase().slice(0, 2);
        countryCodeInput.value = code;
        const normalized = /^[A-Z]{2}$/.test(code) ? code.toLowerCase() : 'us';

        flagPreview.className = `fi fi-${normalized} language-flag`;
        codePreviewText.textContent = code !== '' ? code : 'US';
    };

    countryCodeInput.addEventListener('input', renderFlag);
    renderFlag();
});
</script>
@endpush
