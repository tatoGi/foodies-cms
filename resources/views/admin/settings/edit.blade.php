@extends('admin.layouts.app')

@section('title', __('General Settings'))
@section('page_title', __('General Settings'))

@section('content')
    <div class="row mb-5">
        <div class="col-md-8">
            <h2 class="welcome-title mb-1">{{ __('General Settings') }}</h2>
            <p class="text-muted mb-0">{{ __('Configure site logos, contact information, and other site-wide settings.') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Site Identity: Logos --}}
        <div class="dashboard-panel premium-shadow mb-4">
            <div class="panel-header">
                <div class="panel-header-title">
                    <i class="bi bi-image me-2 text-primary"></i>
                    <span>{{ __('Site Identity') }}</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-4">

                    {{-- Header Logo --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                            {{ __('Header Logo') }}
                        </label>
                        @if(old('header_logo', $headerLogo))
                            <div class="mb-2">
                                <img src="{{ old('header_logo', $headerLogo) }}" alt=""
                                     class="rounded-2" style="max-height:70px;object-fit:contain;background:#f8f9fa;padding:4px;">
                            </div>
                        @endif
                        <input type="text" name="header_logo" id="header_logo_input"
                               class="form-control mb-2 @error('header_logo') is-invalid @enderror"
                               value="{{ old('header_logo', $headerLogo) }}"
                               placeholder="{{ __('Stored path') }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker"
                                data-picker-mode="image"
                                data-target-input="header_logo_input"
                                data-target-selector="#header_logo_input">
                            <i class="bi bi-image me-1"></i>{{ __('Choose from Media') }}
                        </button>
                        @error('header_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    {{-- Footer Logo --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                            {{ __('Footer Logo') }}
                        </label>
                        @if(old('footer_logo', $footerLogo))
                            <div class="mb-2">
                                <img src="{{ old('footer_logo', $footerLogo) }}" alt=""
                                     class="rounded-2" style="max-height:70px;object-fit:contain;background:#f8f9fa;padding:4px;">
                            </div>
                        @endif
                        <input type="text" name="footer_logo" id="footer_logo_input"
                               class="form-control mb-2 @error('footer_logo') is-invalid @enderror"
                               value="{{ old('footer_logo', $footerLogo) }}"
                               placeholder="{{ __('Stored path') }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker"
                                data-picker-mode="image"
                                data-target-input="footer_logo_input"
                                data-target-selector="#footer_logo_input">
                            <i class="bi bi-image me-1"></i>{{ __('Choose from Media') }}
                        </button>
                        @error('footer_logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                            {{ __('Breadcrumb banner') }}
                        </label>
                        <p class="small text-muted">{{ __('Background photo behind the page title. White title text sits on top of it.') }}</p>
                        @if(old('breadcrumb_image', $breadcrumbImage))
                            <div class="mb-2">
                                <img src="{{ old('breadcrumb_image', $breadcrumbImage) }}" alt=""
                                     class="rounded-2" style="max-height:90px;width:100%;object-fit:cover;">
                            </div>
                        @endif
                        <input type="text" name="breadcrumb_image" id="breadcrumb_image_input"
                               class="form-control mb-2 @error('breadcrumb_image') is-invalid @enderror"
                               value="{{ old('breadcrumb_image', $breadcrumbImage) }}"
                               placeholder="{{ __('Stored path') }}">
                        <button type="button" class="btn btn-outline-secondary btn-sm open-media-picker"
                                data-picker-mode="image"
                                data-target-input="breadcrumb_image_input"
                                data-target-selector="#breadcrumb_image_input">
                            <i class="bi bi-image me-1"></i>{{ __('Choose from Media') }}
                        </button>
                        @error('breadcrumb_image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                            {{ __('Breadcrumb color') }}
                        </label>
                        <p class="small text-muted">{{ __('Used when no photo is set, and behind the photo so the title stays readable.') }}</p>
                        <input type="color" name="breadcrumb_color" id="breadcrumb_color_input"
                               class="form-control form-control-color @error('breadcrumb_color') is-invalid @enderror"
                               value="{{ old('breadcrumb_color', $breadcrumbColor) }}">
                        @error('breadcrumb_color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Social Media Links --}}
        <div class="dashboard-panel premium-shadow mb-4">
            <div class="panel-header">
                <div class="panel-header-title">
                    <i class="bi bi-share me-2 text-primary"></i>
                    <span>{{ __('Social Media Links') }}</span>
                </div>
            </div>
            <div class="panel-body">
                <div class="row g-3">
                    @foreach([
                        'facebook'  => ['icon' => 'facebook',   'label' => 'Facebook',  'ph' => 'https://facebook.com/yourpage'],
                        'instagram' => ['icon' => 'instagram',  'label' => 'Instagram', 'ph' => 'https://instagram.com/yourprofile'],
                        'youtube'   => ['icon' => 'youtube',    'label' => 'YouTube',   'ph' => 'https://youtube.com/@yourchannel'],
                        'tiktok'    => ['icon' => 'tiktok',     'label' => 'TikTok',    'ph' => 'https://tiktok.com/@yourprofile'],
                        'linkedin'  => ['icon' => 'linkedin',   'label' => 'LinkedIn',  'ph' => 'https://linkedin.com/company/yourcompany'],
                        'twitter'   => ['icon' => 'twitter-x',  'label' => 'X / Twitter', 'ph' => 'https://x.com/yourhandle'],
                    ] as $platform => $info)
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                <i class="bi bi-{{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
                            </label>
                            <input type="url" name="social_links[{{ $platform }}]"
                                   class="form-control @error('social_links.'.$platform) is-invalid @enderror"
                                   value="{{ old('social_links.'.$platform, $socialLinks[$platform] ?? '') }}"
                                   placeholder="{{ $info['ph'] }}">
                            @error('social_links.'.$platform)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Contact Information (multilingual) --}}
        <div class="dashboard-panel premium-shadow mb-4">
            <div class="panel-header border-bottom-0 pb-0">
                <div class="panel-header-title mb-3">
                    <i class="bi bi-telephone me-2 text-primary"></i>
                    <span>{{ __('Contact Information') }}</span>
                </div>
                @include('admin.partials.lang-tabs', ['isMaster' => false, 'tabId' => 'contact-lang-tabs'])
            </div>
            <div class="panel-body pt-0">
                <div class="tab-content tab-content-premium" id="contactLangTabsContent">
                    @foreach($locales as $locale)
                        <div class="tab-pane fade {{ $locale['code'] === $defaultLocale ? 'show active' : '' }}"
                             id="contact-lang-panel-{{ $locale['code'] }}"
                             data-locale-code="{{ $locale['code'] }}"
                             role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                        {{ __('Phone') }} ({{ strtoupper($locale['code']) }})
                                    </label>
                                    <input type="text" name="contact_phone[{{ $locale['code'] }}]"
                                           class="form-control @error('contact_phone.'.$locale['code']) is-invalid @enderror"
                                           value="{{ old('contact_phone.'.$locale['code'], $contactPhone[$locale['code']] ?? '') }}"
                                           placeholder="+995 555 12 34 56">
                                    @error('contact_phone.'.$locale['code'])
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                        {{ __('Email') }} ({{ strtoupper($locale['code']) }})
                                    </label>
                                    <input type="text" name="contact_email[{{ $locale['code'] }}]"
                                           class="form-control @error('contact_email.'.$locale['code']) is-invalid @enderror"
                                           value="{{ old('contact_email.'.$locale['code'], $contactEmail[$locale['code']] ?? '') }}"
                                           placeholder="info@homespace.ge">
                                    @error('contact_email.'.$locale['code'])
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                        {{ __('Address') }} ({{ strtoupper($locale['code']) }})
                                    </label>
                                    <input type="text" name="contact_address[{{ $locale['code'] }}]"
                                           class="form-control @error('contact_address.'.$locale['code']) is-invalid @enderror"
                                           value="{{ old('contact_address.'.$locale['code'], $contactAddress[$locale['code']] ?? '') }}"
                                           placeholder="{{ __('Street, City') }}">
                                    @error('contact_address.'.$locale['code'])
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Footer Content (multilingual) --}}
        <div class="dashboard-panel premium-shadow mb-4">
            <div class="panel-header border-bottom-0 pb-0">
                <div class="panel-header-title mb-3">
                    <i class="bi bi-layout-text-sidebar me-2 text-primary"></i>
                    <span>{{ __('Footer Content') }}</span>
                </div>
                @include('admin.partials.lang-tabs', ['isMaster' => false])
            </div>

            <div class="panel-body pt-0">
                <div class="tab-content tab-content-premium" id="langTabsContent">
                    @foreach($locales as $locale)
                        <div class="tab-pane fade {{ $locale['code'] === $defaultLocale ? 'show active' : '' }}"
                             id="lang-panel-{{ $locale['code'] }}"
                             data-locale-code="{{ $locale['code'] }}"
                             role="tabpanel">

                            <div class="mb-4">
                                <label class="form-label small fw-bold text-muted uppercase letter-spacing-1">
                                    {{ __('Contact / Footer Text') }} ({{ strtoupper($locale['code']) }})
                                </label>
                                <textarea name="footer_contact[{{ $locale['code'] }}]"
                                          rows="6"
                                          class="form-control @error('footer_contact.'.$locale['code']) is-invalid @enderror"
                                          placeholder="{{ __('Address, phone, email, working hours...') }}">{{ old('footer_contact.'.$locale['code'], $footerContact[$locale['code']] ?? '') }}</textarea>
                                @error('footer_contact.'.$locale['code'])
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                        </div>
                    @endforeach
                </div>

                <hr class="my-4 opacity-10">

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-5 py-3 rounded-3 shadow-premium d-flex align-items-center gap-2">
                        <i class="bi bi-check-lg"></i>
                        <span class="fw-bold">{{ __('Save Settings') }}</span>
                    </button>
                </div>
            </div>
        </div>

    </form>
@endsection
