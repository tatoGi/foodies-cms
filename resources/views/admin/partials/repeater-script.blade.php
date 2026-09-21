    function syncRepeaterColorField(container) {
        if (!container) {
            return;
        }

        const textInput = container.querySelector('.repeater-color-text');
        const colorInput = container.querySelector('.repeater-color-picker');

        if (!textInput || !colorInput) {
            return;
        }

        const normalizeHex = (value) => {
            const trimmed = String(value || '').trim();

            if (/^#[0-9a-fA-F]{6}$/.test(trimmed)) {
                return trimmed;
            }

            return null;
        };

        const initialHex = normalizeHex(textInput.value);
        if (initialHex) {
            colorInput.value = initialHex;
        }

        if (container.dataset.colorBound === '1') {
            return;
        }

        colorInput.addEventListener('input', () => {
            textInput.value = colorInput.value;
        });

        textInput.addEventListener('input', () => {
            const nextHex = normalizeHex(textInput.value);
            if (nextHex) {
                colorInput.value = nextHex;
            }
        });

        container.dataset.colorBound = '1';
    }

    const updateRepeaterItemLabel = (item) => {
        if (!item) {
            return;
        }

        const leadingField = item.querySelector('.repeater-leading-field');
        const valueDisplay = item.querySelector('[data-repeater-value-display]');

        if (!leadingField || !valueDisplay) {
            return;
        }

        const getValue = () => {
            if (leadingField.tagName === 'SELECT') {
                return leadingField.options[leadingField.selectedIndex]?.textContent?.trim() || '';
            }
            return (leadingField.value || '').trim();
        };

        const updateDisplay = () => {
            const value = getValue();
            valueDisplay.textContent = value !== '' ? value : '';
            valueDisplay.style.display = value !== '' ? 'inline' : 'none';
        };

        if (leadingField.dataset.labelUpdateBound === '1') {
            return;
        }

        leadingField.addEventListener('input', updateDisplay);
        leadingField.addEventListener('change', updateDisplay);
        updateDisplay();

        leadingField.dataset.labelUpdateBound = '1';
    };

    const refreshRepeaterField = (field) => {
        if (!field) {
            return;
        }

        const items = Array.from(field.querySelectorAll('[data-repeater-item]'));
        items.forEach((item, index) => {
            item.querySelectorAll('[data-name-template]').forEach((input) => {
                const template = input.dataset.nameTemplate || '';
                if (template !== '') {
                    input.name = template.replace(/__INDEX__/g, String(index));
                }
            });

            const indexLabel = item.querySelector('[data-repeater-index]');
            if (indexLabel) {
                indexLabel.textContent = String(index + 1);
            }

            updateRepeaterItemLabel(item);
        });

        field.querySelectorAll('.repeater-color-field').forEach((container) => {
            syncRepeaterColorField(container);
        });
    };

    document.querySelectorAll('[data-repeater-field]').forEach((field) => {
        refreshRepeaterField(field);
    });

    document.querySelectorAll('.repeater-color-field').forEach((container) => {
        syncRepeaterColorField(container);
    });
