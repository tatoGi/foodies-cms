<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const slugify = (text) => {
        return text.toString()
            .normalize('NFKC')
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s-]+/gu, '')
            .replace(/\s+/g, '-')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    };

    const setFieldValue = (field, value) => {
        if (!field) {
            return;
        }

        const normalized = value ?? '';
        if (field.tagName === 'TEXTAREA' && window.AdminQuill) {
            window.AdminQuill.setValue(field, normalized);
        } else {
            field.value = normalized;
        }

        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const setLoadingState = (tools, loading) => {
        tools.querySelectorAll('button').forEach((button) => {
            button.disabled = loading;
        });
        tools.classList.toggle('opacity-75', loading);
    };

    const setKeywordsValue = (localeCode, value) => {
        const panel = document.getElementById(`seo-panel-${localeCode}`);
        if (!panel) {
            return;
        }

        const hiddenInput = panel.querySelector('.keywords-tag-hidden');
        if (!(hiddenInput instanceof HTMLInputElement)) {
            return;
        }

        hiddenInput.value = value || '';
        if (window.initKeywordWidgets) {
            window.initKeywordWidgets(panel);
        }
    };

    const parsePathSegments = (name) => {
        const segments = [];
        const matcher = /([^[\]]+)|\[([^[\]]*)\]/g;
        let match;

        while ((match = matcher.exec(name)) !== null) {
            const segment = match[1] ?? match[2] ?? '';
            if (segment !== '') {
                segments.push(segment);
            }
        }

        return segments;
    };

    const assignNestedValue = (target, path, value) => {
        let current = target;

        path.forEach((segment, index) => {
            const isLast = index === path.length - 1;
            const nextSegment = path[index + 1] ?? '';
            const nextIsIndex = /^\d+$/.test(nextSegment);

            if (isLast) {
                current[segment] = value;
                return;
            }

            if (!(segment in current) || typeof current[segment] !== 'object' || current[segment] === null) {
                current[segment] = nextIsIndex ? [] : {};
            }

            current = current[segment];
        });
    };

    const buildDraftTranslations = (form) => {
        if (!(form instanceof HTMLFormElement)) {
            return {};
        }

        const payload = {};
        const formData = new FormData(form);

        formData.forEach((rawValue, rawName) => {
            if (typeof rawName !== 'string') {
                return;
            }

            const path = parsePathSegments(rawName);
            if (path.length === 0) {
                return;
            }

            assignNestedValue(payload, path, typeof rawValue === 'string' ? rawValue : '');
        });

        const names = payload.names ?? {};
        const descriptions = payload.descriptions ?? {};
        const categories = payload.categories ?? {};
        const blocks = payload.blocks ?? {};

        return Array.from(new Set([
            ...Object.keys(names),
            ...Object.keys(descriptions),
            ...Object.keys(categories),
            ...Object.keys(blocks),
        ])).reduce((translations, localeCode) => {
            const localeBlocks = Object.entries(blocks[localeCode] ?? {})
                .map(([instanceKey, block]) => ({
                    instance_key: instanceKey,
                    type: block.type ?? '',
                    sort_order: Number(block.sort_order ?? 0),
                    data: block.data ?? {},
                }))
                .sort((a, b) => a.sort_order - b.sort_order);

            translations[localeCode] = {
                name: names[localeCode] ?? '',
                body: descriptions[localeCode] ?? '',
                category: categories[localeCode] ?? '',
                blocks: localeBlocks,
            };

            return translations;
        }, {});
    };

    const callAiEndpoint = async (url, localeCode, extraPayload = {}) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                target_locale: localeCode,
                ...extraPayload,
            }),
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(payload.message || 'AI request failed.');
        }

        return payload;
    };

    document.querySelectorAll('.ai-locale-tools').forEach((tools) => {
        const localeCode = tools.dataset.targetLocale || '';
        const translateUrl = tools.dataset.translateUrl || '';
        const seoUrl = tools.dataset.seoUrl || '';
        const draftMode = tools.dataset.draftMode === 'true';
        const form = tools.closest('form');

        tools.querySelector('.ai-translate-locale-btn')?.addEventListener('click', async () => {
            if (localeCode === '' || translateUrl === '') {
                return;
            }

            setLoadingState(tools, true);
            try {
                const payload = await callAiEndpoint(
                    translateUrl,
                    localeCode,
                    draftMode ? { translations: buildDraftTranslations(form) } : {}
                );
                const nameInput = document.querySelector(`input[name="names[${localeCode}]"]`);
                const slugInput = document.querySelector(`input[name="slugs[${localeCode}]"]`);
                const bodyInput = document.querySelector(`textarea[name="descriptions[${localeCode}]"]`);
                const blocksContainer = document.getElementById(`page-blocks-${localeCode}`);

                setFieldValue(nameInput, payload.fields?.name || '');
                setFieldValue(bodyInput, payload.fields?.body || '');

                const categoryInput = document.querySelector(`input[name="categories[${localeCode}]"]`);
                if (categoryInput && payload.fields?.category !== undefined) {
                    setFieldValue(categoryInput, payload.fields.category);
                }

                if (slugInput instanceof HTMLInputElement) {
                    slugInput.value = slugify(payload.fields?.name || '');
                    slugInput.dataset.manual = 'true';
                    slugInput.dispatchEvent(new Event('input', { bubbles: true }));
                    slugInput.dispatchEvent(new Event('change', { bubbles: true }));
                }

                if (blocksContainer && typeof payload.blocks_html === 'string' && payload.blocks_html.trim() !== '') {
                    blocksContainer.innerHTML = payload.blocks_html;
                    window.AdminQuill?.initialize(blocksContainer);
                    window.AdminBlocks?.initializeExistingBlockCards();
                }
            } catch (error) {
                alert(error instanceof Error ? error.message : 'AI translation failed.');
            } finally {
                setLoadingState(tools, false);
            }
        });

    });

    document.addEventListener('click', async (event) => {
        const seoButton = event.target.closest('.ai-generate-seo-btn');
        if (!seoButton) {
            return;
        }

        const buttonWrapper = seoButton.parentElement;
        const localePanel = seoButton.closest('[data-locale-code]');
        const form = seoButton.closest('form');

        const localeCode = localePanel?.dataset.localeCode || '';
        const seoUrl = buttonWrapper?.dataset.seoUrl || '';
        const draftMode = buttonWrapper?.dataset.draftMode === 'true';

        if (localeCode === '' || seoUrl === '') {
            return;
        }

        const setLoading = (loading) => {
            seoButton.disabled = loading;
            if (buttonWrapper) {
                buttonWrapper.classList.toggle('opacity-75', loading);
            }
        };

        setLoading(true);
        try {
            const payload = await callAiEndpoint(
                seoUrl,
                localeCode,
                draftMode ? { translations: buildDraftTranslations(form) } : {}
            );
            setFieldValue(document.querySelector(`input[name="meta_titles[${localeCode}]"]`), payload.seo?.meta_title || '');
            setFieldValue(document.querySelector(`textarea[name="meta_descriptions[${localeCode}]"]`), payload.seo?.meta_description || '');
            setFieldValue(document.querySelector(`input[name="focus_keywords[${localeCode}]"]`), payload.seo?.focus_keyword || '');
            setFieldValue(document.querySelector(`input[name="canonical_urls[${localeCode}]"]`), payload.seo?.canonical_url || '');
            setKeywordsValue(localeCode, payload.seo?.keywords || '');
        } catch (error) {
            alert(error instanceof Error ? error.message : 'AI SEO generation failed.');
        } finally {
            setLoading(false);
        }
    });
});
</script>
