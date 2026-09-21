@php
    $translateLabel = app()->getLocale() === 'ka' ? 'თარგმნე ენა' : 'Translate Locale';
    $seoLabel = app()->getLocale() === 'ka' ? 'SEO-ის გენერაცია' : 'Generate SEO';
@endphp

<div class="d-flex justify-content-end gap-2 mb-3 ai-locale-tools"
     data-translate-url="{{ $translateUrl }}"
     data-seo-url="{{ $seoUrl }}"
     data-target-locale="{{ $localeCode }}"
     data-draft-mode="{{ !empty($draftMode) ? 'true' : 'false' }}">
    <button type="button" class="btn btn-sm btn-outline-primary ai-translate-locale-btn">
        <i class="bi bi-magic me-1"></i>{{ $translateLabel }}
    </button>
</div>
