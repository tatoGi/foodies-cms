@php
    $selectedLocaleCodes = collect($selectedLocaleCodes ?? [])
        ->map(static fn ($code): string => (string) $code)
        ->values();
    $selectedLocales = collect($locales)
        ->filter(static fn (array $locale): bool => $selectedLocaleCodes->contains((string) $locale['code']))
        ->values();
    $hiddenLocales = collect($locales)
        ->reject(static fn (array $locale): bool => $selectedLocaleCodes->contains((string) $locale['code']))
        ->values();
    
    $prefix = $prefix ?? 'lang';
    $targetPrefix = $targetPrefix ?? 'lang-panel';
    $isMaster = $isMaster ?? true;
    $activeLocaleCode = (string) (($selectedLocales->first()['code'] ?? '') ?: '');
    $tabsLocales = $isMaster ? $selectedLocales : collect($locales);
@endphp

<div class="lang-tabs-container {{ $prefix }}-tabs-container mb-4">
    <ul class="nav nav-tabs nav-tabs-premium border-0 gap-2" id="{{ $prefix }}Tabs" role="tablist">
        @foreach($tabsLocales as $locale)
            @php
                $localeCode = (string) $locale['code'];
                $isSelectedLocale = $selectedLocaleCodes->contains($localeCode);
            @endphp
            <li class="nav-item position-relative {{ $prefix }}-tab-item-{{ $localeCode }} {{ !$isMaster && !$isSelectedLocale ? 'd-none' : '' }}" id="{{ $prefix }}-tab-item-{{ $localeCode }}" role="presentation">
                <button class="nav-link {{ $localeCode === $activeLocaleCode ? 'active' : '' }} d-flex align-items-center gap-2 {{ $isMaster ? 'pe-4' : '' }}"
                        id="{{ $prefix }}-tab-{{ $locale['code'] }}"
                        data-bs-toggle="tab"
                        data-bs-target="#{{ $targetPrefix }}-{{ $locale['code'] }}"
                        type="button" role="tab">
                    <span class="lang-flag-mini">{{ $locale['flag'] ?: 'GL' }}</span>
                    <span class="lang-name-mini">{{ $locale['name'] }}</span>
                    <span class="badge bg-light-soft text-muted border ms-1 small">{{ strtoupper($locale['code']) }}</span>
                </button>
                @if($isMaster)
                    <button type="button"
                            class="btn-close-tab"
                            onclick="clearLocaleData(event, '{{ $locale['code'] }}')"
                            title="{{ __('Clear Locale') }}">
                        <i class="bi bi-x"></i>
                    </button>
                @endif
            </li>
        @endforeach

        @if($isMaster && ($showSeoTab ?? false))
            <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-2"
                        id="seo-main-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#seo-main-panel"
                        type="button" role="tab">
                    <i class="bi bi-search small text-muted"></i>
                    <span class="lang-name-mini text-uppercase letter-spacing-1 fw-bold">{{ __('SEO Settings') }}</span>
                </button>
            </li>
        @endif

        @if($isMaster)
            <li class="nav-item ms-auto" id="add-locale-wrapper">
                <div class="dropdown">
                    <button class="btn btn-sm btn-light-soft h-100 d-flex align-items-center gap-2 px-3 rounded-3 dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="bi bi-plus-lg"></i>
                        <span class="d-none d-md-inline">{{ __('Add Locale') }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end premium-dropdown" id="add-locale-menu">
                        @forelse($hiddenLocales as $locale)
                            <li>
                                <button type="button"
                                        class="dropdown-item add-locale-item"
                                        data-locale-code="{{ $locale['code'] }}"
                                        data-locale-name="{{ $locale['name'] }}"
                                        data-locale-flag="{{ $locale['flag'] }}">
                                    <span class="me-2">{{ $locale['flag'] ?: 'GL' }}</span>
                                    {{ $locale['name'] }} ({{ strtoupper($locale['code']) }})
                                </button>
                            </li>
                        @empty
                            <li class="add-locale-empty"><span class="dropdown-item-text text-muted small">{{ __('All locales are already added.') }}</span></li>
                        @endforelse
                    </ul>
                </div>
            </li>
        @endif
    </ul>
</div>

<script>
if (!window.__localeTabsService) {
    window.__localeTabsService = {};

    window.refreshAddLocaleMenuState = function() {
        const menu = document.getElementById('add-locale-menu');
        if (!menu) {
            return;
        }

        const hasItems = menu.querySelectorAll('.add-locale-item').length > 0;
        const emptyItem = menu.querySelector('.add-locale-empty');

        if (!hasItems && !emptyItem) {
            const li = document.createElement('li');
            li.className = 'add-locale-empty';
            li.innerHTML = '<span class="dropdown-item-text text-muted small">{{ __('All locales are already added.') }}</span>';
            menu.appendChild(li);
        }

        if (hasItems && emptyItem) {
            emptyItem.remove();
        }
    };

    window.ensureSeoMainTabOrder = function() {
        const seoMainTab = document.getElementById('seo-main-tab');
        const seoMainItem = seoMainTab ? seoMainTab.closest('.nav-item') : null;
        const langTabs = document.getElementById('langTabs');
        if (!seoMainItem || !langTabs) {
            return;
        }

        const addWrapper = document.getElementById('add-locale-wrapper');
        if (addWrapper && addWrapper.parentElement === langTabs) {
            langTabs.insertBefore(seoMainItem, addWrapper);
            return;
        }

        langTabs.appendChild(seoMainItem);
    };

    window.activateFirstLocaleTab = function() {
        const firstLocaleTab = document.querySelector('#langTabs [id^="lang-tab-"]');
        if (firstLocaleTab && window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(firstLocaleTab).show();
        }
    };

    window.activateFirstVisibleSeoTab = function() {
        const firstSeoTab = document.querySelector('#seoLangTabs .nav-item:not(.d-none) [id^="seo-tab-"]');
        if (firstSeoTab && window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(firstSeoTab).show();
        }
    };

    window.setSeoLocaleVisibility = function(localeCode, isVisible, clearValues = false) {
        const seoTabItem = document.getElementById('seo-tab-item-' + localeCode);
        const seoTabButton = document.getElementById('seo-tab-' + localeCode);
        const seoPanel = document.getElementById('seo-panel-' + localeCode);

        if (seoTabItem) {
            seoTabItem.classList.toggle('d-none', !isVisible);
        }

        if (seoPanel) {
            seoPanel.classList.toggle('d-none', !isVisible);
            if (!isVisible) {
                seoPanel.classList.remove('show', 'active');
            }

            seoPanel.querySelectorAll('input, textarea, select').forEach((field) => {
                field.disabled = !isVisible;
                if (!isVisible && clearValues) {
                    if (field instanceof HTMLInputElement) {
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            field.checked = false;
                        } else {
                            field.value = '';
                        }
                    } else if (field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
                        field.value = '';
                    }
                }
            });
        }

        if (!isVisible && seoTabButton && seoTabButton.classList.contains('active')) {
            window.activateFirstVisibleSeoTab();
        }
    };

    window.syncAllSeoLocaleTabs = function() {
        document.querySelectorAll('[id^="seo-tab-item-"]').forEach((item) => {
            const localeCode = item.id.replace('seo-tab-item-', '');
            const isVisible = document.getElementById('lang-tab-' + localeCode) !== null;
            window.setSeoLocaleVisibility(localeCode, isVisible, false);
        });
    };

    window.parseKeywordList = function(value) {
        return String(value ?? '')
            .split(/[\n,]+/)
            .map((part) => part.trim())
            .filter((part) => part !== '');
    };

    window.renderKeywordWidget = function(widget) {
        if (!(widget instanceof HTMLElement)) {
            return;
        }

        const hiddenInput = widget.querySelector('.keywords-tag-hidden');
        const tagsList = widget.querySelector('.keywords-tag-list');
        if (!(hiddenInput instanceof HTMLInputElement) || !(tagsList instanceof HTMLElement)) {
            return;
        }

        const seen = new Set();
        const keywords = window.parseKeywordList(hiddenInput.value).filter((keyword) => {
            const normalized = keyword.toLowerCase();
            if (seen.has(normalized)) {
                return false;
            }
            seen.add(normalized);
            return true;
        });

        hiddenInput.value = keywords.join(', ');
        tagsList.innerHTML = '';

        keywords.forEach((keyword) => {
            const chip = document.createElement('span');
            chip.className = 'keywords-tag-chip';
            chip.innerHTML = `
                <span class="keywords-tag-chip-text"></span>
                <button type="button" class="keywords-tag-remove" aria-label="Remove keyword">&times;</button>
            `;
            const textNode = chip.querySelector('.keywords-tag-chip-text');
            if (textNode) {
                textNode.textContent = keyword;
            }
            tagsList.appendChild(chip);
        });
    };

    window.addKeywordsToWidget = function(widget, rawValue) {
        if (!(widget instanceof HTMLElement)) {
            return;
        }

        const hiddenInput = widget.querySelector('.keywords-tag-hidden');
        const editorInput = widget.querySelector('.keywords-tag-editor');
        if (!(hiddenInput instanceof HTMLInputElement)) {
            return;
        }

        const currentKeywords = window.parseKeywordList(hiddenInput.value);
        const seen = new Set(currentKeywords.map((keyword) => keyword.toLowerCase()));

        window.parseKeywordList(rawValue).forEach((keyword) => {
            const normalized = keyword.toLowerCase();
            if (!seen.has(normalized)) {
                currentKeywords.push(keyword);
                seen.add(normalized);
            }
        });

        hiddenInput.value = currentKeywords.join(', ');
        if (editorInput instanceof HTMLInputElement) {
            editorInput.value = '';
        }
        window.renderKeywordWidget(widget);
    };

    window.bindKeywordWidget = function(widget) {
        if (!(widget instanceof HTMLElement) || widget.dataset.keywordsBound === '1') {
            return;
        }

        widget.dataset.keywordsBound = '1';
        const editorInput = widget.querySelector('.keywords-tag-editor');
        const hiddenInput = widget.querySelector('.keywords-tag-hidden');
        if (!(editorInput instanceof HTMLInputElement) || !(hiddenInput instanceof HTMLInputElement)) {
            return;
        }

        window.renderKeywordWidget(widget);

        editorInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter' || event.key === ',') {
                event.preventDefault();
                window.addKeywordsToWidget(widget, editorInput.value);
            }
        });

        editorInput.addEventListener('blur', function() {
            if (editorInput.value.trim() !== '') {
                window.addKeywordsToWidget(widget, editorInput.value);
            }
        });

        widget.addEventListener('click', function(event) {
            const removeButton = event.target.closest('.keywords-tag-remove');
            if (!removeButton) {
                return;
            }

            event.preventDefault();

            const chip = removeButton.closest('.keywords-tag-chip');
            const chipText = chip?.querySelector('.keywords-tag-chip-text')?.textContent?.trim() ?? '';
            if (chipText === '') {
                return;
            }

            const keywords = window.parseKeywordList(hiddenInput.value)
                .filter((keyword) => keyword.toLowerCase() !== chipText.toLowerCase());
            hiddenInput.value = keywords.join(', ');
            window.renderKeywordWidget(widget);
        });
    };

    window.initKeywordWidgets = function(scope = document) {
        if (!(scope instanceof Document || scope instanceof HTMLElement)) {
            return;
        }

        scope.querySelectorAll('[data-keywords-widget]').forEach((widget) => {
            window.bindKeywordWidget(widget);
        });
    };

    window.addLocaleTab = function(localeCode, localeName, localeFlag) {
        const tabs = document.getElementById('langTabs');
        const addWrapper = document.getElementById('add-locale-wrapper');
        const menu = document.getElementById('add-locale-menu');
        if (!tabs || !addWrapper || !menu) {
            return;
        }

        if (document.getElementById('lang-tab-' + localeCode)) {
            return;
        }

        const li = document.createElement('li');
        li.className = 'nav-item position-relative lang-tab-item-' + localeCode;
        li.id = 'lang-tab-item-' + localeCode;
        li.setAttribute('role', 'presentation');
        li.innerHTML = `
            <button class="nav-link d-flex align-items-center gap-2 pe-4"
                    id="lang-tab-${localeCode}"
                    data-bs-toggle="tab"
                    data-bs-target="#lang-panel-${localeCode}"
                    type="button"
                    role="tab">
                <span class="lang-flag-mini">${localeFlag || 'GL'}</span>
                <span class="lang-name-mini">${localeName}</span>
                <span class="badge bg-light-soft text-muted border ms-1 small">${localeCode.toUpperCase()}</span>
            </button>
            <button type="button"
                    class="btn-close-tab"
                    onclick="clearLocaleData(event, '${localeCode}')"
                    title="{{ __('Clear Locale') }}">
                <i class="bi bi-x"></i>
            </button>
        `;

        const seoMainTab = document.getElementById('seo-main-tab');
        const seoMainItem = seoMainTab ? seoMainTab.closest('.nav-item') : null;
        const insertionTarget = seoMainItem && seoMainItem.parentElement === tabs ? seoMainItem : addWrapper;
        tabs.insertBefore(li, insertionTarget);

        const panel = document.getElementById('lang-panel-' + localeCode);
        if (panel) {
            panel.classList.remove('d-none');
            panel.querySelectorAll('input, textarea, select').forEach((field) => {
                field.disabled = false;
            });
        }

        menu.querySelectorAll('.add-locale-item').forEach((item) => {
            if (item.dataset.localeCode === localeCode) {
                item.parentElement?.remove();
            }
        });
        window.refreshAddLocaleMenuState();
        window.ensureSeoMainTabOrder();

        const newTab = document.getElementById('lang-tab-' + localeCode);
        if (newTab && window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(newTab).show();
        }

        document.dispatchEvent(new CustomEvent('locale-tab-added', { detail: { localeCode } }));
    };

    window.clearLocaleData = function(event, localeCode) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        if (confirm("{{ __('Are you sure you want to clear all data for this locale?') }}")) {
            const nameInput = document.querySelector(`input[name="names[${localeCode}]"]`) || document.getElementById('name-' + localeCode);
            const slugInput = document.querySelector(`input[name="slugs[${localeCode}]"]`) || document.getElementById('slug-' + localeCode);
            const tabBtn = document.getElementById('lang-tab-' + localeCode);
            const tabItem = tabBtn ? tabBtn.closest('.nav-item') : null;
            const panel = document.getElementById('lang-panel-' + localeCode);

            if (nameInput) {
                nameInput.value = '';
                nameInput.dispatchEvent(new Event('input', { bubbles: true }));
                nameInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (slugInput) {
                slugInput.value = '';
                slugInput.dispatchEvent(new Event('input', { bubbles: true }));
                slugInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (tabBtn) {
                tabBtn.classList.remove('has-content');
            }

            if (panel) {
                panel.classList.remove('show', 'active');
                panel.classList.add('d-none');
                panel.querySelectorAll('input, textarea, select').forEach((field) => {
                    field.disabled = true;
                });
            }

            if (tabItem) {
                tabItem.remove();
            }

            if (panel) {
                panel.querySelectorAll('input, textarea, select').forEach((field) => {
                    if (field.matches('input[type="text"], input[type="hidden"], textarea')) {
                        field.value = '';
                    }
                });
            }

            const menu = document.getElementById('add-locale-menu');
            if (menu && !menu.querySelector(`.add-locale-item[data-locale-code="${localeCode}"]`)) {
                const localeName = tabBtn?.querySelector('.lang-name-mini')?.textContent?.trim() || localeCode.toUpperCase();
                const localeFlag = tabBtn?.querySelector('.lang-flag-mini')?.textContent?.trim() || 'GL';
                const li = document.createElement('li');
                li.innerHTML = `
                    <button type="button"
                            class="dropdown-item add-locale-item"
                            data-locale-code="${localeCode}"
                            data-locale-name="${localeName}"
                            data-locale-flag="${localeFlag}">
                        <span class="me-2">${localeFlag}</span>
                        ${localeName} (${localeCode.toUpperCase()})
                    </button>
                `;
                menu.appendChild(li);
            }

            window.setSeoLocaleVisibility(localeCode, false, true);
            window.refreshAddLocaleMenuState();
            window.ensureSeoMainTabOrder();
            window.activateFirstLocaleTab();
        }
        return false;
    };

    document.addEventListener('click', function(event) {
        const addButton = event.target.closest('.add-locale-item');
        if (!addButton) {
            return;
        }

        const localeCode = addButton.dataset.localeCode;
        const localeName = addButton.dataset.localeName || localeCode.toUpperCase();
        const localeFlag = addButton.dataset.localeFlag || 'GL';
        window.addLocaleTab(localeCode, localeName, localeFlag);
    });

    document.addEventListener('shown.bs.tab', function(event) {
        const target = event.target;
        if (!(target instanceof HTMLElement) || !target.id) {
            return;
        }

        if (target.id.startsWith('lang-tab-')) {
            const localeCode = target.id.replace('lang-tab-', '');
            const seoTab = document.getElementById('seo-tab-' + localeCode);
            if (seoTab && !seoTab.classList.contains('active') && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(seoTab).show();
            }
        }
    });

    document.addEventListener('locale-tab-added', function(event) {
        const localeCode = event.detail?.localeCode;
        if (!localeCode) {
            return;
        }

        window.setSeoLocaleVisibility(localeCode, true, false);
        window.ensureSeoMainTabOrder();
        const seoPanel = document.getElementById('seo-panel-' + localeCode);
        if (seoPanel) {
            window.initKeywordWidgets(seoPanel);
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        window.refreshAddLocaleMenuState();
        window.ensureSeoMainTabOrder();
        window.syncAllSeoLocaleTabs();
        window.initKeywordWidgets(document);
    });
}
</script>

<style>
.nav-tabs-premium .nav-item {
    position: relative;
    padding-top: 4px;
    padding-right: 4px;
}

.btn-close-tab {
    position: absolute;
    top: 0;
    right: 0;
    width: 20px;
    height: 20px;
    background: var(--admin-surface);
    border: 1px solid var(--admin-border);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    font-size: 14px;
    color: var(--admin-muted);
    transition: all 0.2s ease;
    z-index: 100;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-close-tab:hover {
    background: #ff4757;
    color: white;
    border-color: #ff4757;
    transform: scale(1.1);
}

.nav-tabs-premium .nav-link.has-content {
    border-left: 3px solid #2ed573 !important;
}

.keywords-tag-input {
    border: 1px solid var(--admin-border);
    border-radius: 0.5rem;
    padding: 0.5rem;
    background: var(--admin-surface, #fff);
}

.keywords-tag-list {
    min-height: 1.75rem;
}

.keywords-tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    background: var(--admin-surface-soft, #f6f8fb);
    border: 1px solid var(--admin-border);
    font-size: 0.75rem;
    line-height: 1.2;
}

.keywords-tag-remove {
    border: 0;
    background: transparent;
    color: var(--admin-muted);
    font-size: 0.95rem;
    line-height: 1;
    padding: 0;
    cursor: pointer;
}

.keywords-tag-remove:hover {
    color: var(--admin-danger, #dc3545);
}

.keywords-tag-editor {
    border: 0;
    box-shadow: none !important;
    padding: 0.15rem 0;
    min-height: auto;
}
</style>
