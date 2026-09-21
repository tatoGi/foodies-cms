<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const endpoint = @json(route('admin.ai.generate-block'));

    // page / post / product — derived from the current admin route.
    const scope = (() => {
        const path = window.location.pathname;
        if (path.includes('/products')) return 'product';
        if (path.includes('/posts')) return 'post';
        return 'page';
    })();

    const cssEscape = (value) => (window.CSS && CSS.escape ? CSS.escape(value) : String(value).replace(/(["\\\]\[])/g, '\\$1'));

    const setFieldValue = (field, value) => {
        if (!field) return;
        const normalized = value ?? '';
        if (field.tagName === 'TEXTAREA' && window.AdminQuill) {
            window.AdminQuill.setValue(field, normalized);
        } else {
            field.value = normalized;
        }
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
    };

    // Decorate every block card header with an "AI generate" button (idempotent).
    const decorateCard = (card) => {
        const group = card.querySelector('.card-header .btn-group');
        if (!group || group.querySelector('.generate-block-ai')) return;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-light generate-block-ai text-primary';
        btn.title = @json(__('Generate content with AI'));
        btn.innerHTML = '<i class="bi bi-stars"></i>';
        group.prepend(btn);
    };

    const decorateAll = () => document.querySelectorAll('.page-block-card').forEach(decorateCard);
    decorateAll();

    // Catch dynamically added block cards.
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((m) => m.addedNodes.forEach((node) => {
            if (!(node instanceof HTMLElement)) return;
            if (node.classList?.contains('page-block-card')) decorateCard(node);
            node.querySelectorAll?.('.page-block-card').forEach(decorateCard);
        }));
    });
    observer.observe(document.body, { childList: true, subtree: true });

    const fillRepeater = (card, locale, instanceKey, key, rows) => {
        const prefix = `blocks[${locale}][${instanceKey}][data][${key}]`;
        // Locate the repeater field that owns this data key.
        const owner = Array.from(card.querySelectorAll('[data-repeater-field]')).find((field) =>
            Array.from(field.querySelectorAll('[data-name-template], [name]')).some((input) => {
                const n = input.getAttribute('data-name-template') || input.getAttribute('name') || '';
                return n.includes(`[data][${key}][`);
            })
        );
        if (!owner) return;

        const addButton = owner.querySelector('.add-repeater-item');
        const countRows = () => owner.querySelectorAll(':scope > .repeater-items > [data-repeater-item]').length;

        // Ensure enough rows exist (clicking re-runs the app's own row indexer).
        let guard = 0;
        while (countRows() < rows.length && addButton && guard < 50) {
            addButton.click();
            guard++;
        }

        rows.forEach((row, i) => {
            Object.entries(row).forEach(([subKey, value]) => {
                const selector = `[name="${cssEscape(`${prefix}[${i}][${subKey}]`)}"]`;
                setFieldValue(owner.querySelector(selector), value);
            });
        });
    };

    const applyData = (card, locale, instanceKey, data) => {
        Object.entries(data).forEach(([key, value]) => {
            if (Array.isArray(value)) {
                fillRepeater(card, locale, instanceKey, key, value);
                return;
            }
            const selector = `[name="${cssEscape(`blocks[${locale}][${instanceKey}][data][${key}]`)}"]`;
            setFieldValue(card.querySelector(selector), value);
        });
    };

    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('.generate-block-ai');
        if (!btn) return;

        const card = btn.closest('.page-block-card');
        if (!card) return;

        const locale = card.dataset.localeCode || '';
        const blockType = card.dataset.blockKey || '';
        const instanceKey = card.dataset.instanceKey || '';
        if (locale === '' || blockType === '') return;

        const title = document.querySelector(`input[name="names[${locale}]"]`)?.value || '';
        const descField = document.querySelector(`textarea[name="descriptions[${locale}]"]`);
        const description = descField ? (window.AdminQuill?.getValue?.(descField) ?? descField.value) : '';

        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    scope,
                    block_type: blockType,
                    locale,
                    context: { title, description },
                }),
            });

            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload.message || 'AI generation failed.');
            }

            applyData(card, locale, instanceKey, payload.data || {});
        } catch (error) {
            alert(error instanceof Error ? error.message : 'AI generation failed.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = original;
        }
    });
});
</script>
