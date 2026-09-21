const firstLocaleCode = @json($firstSelectedLocaleCode ?? null);
const selectedBlockTypesContainer = document.getElementById('selected-block-types-inputs');

    const escapeSelector = (value) => {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }

        return String(value).replace(/([ #;?%&,.+*~\\':"!^$[\]()=>|/@])/g, '\\$1');
    };

const generateBlockInstanceKey = (blockKey) => {
    return `${blockKey}__${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
};

const bindBlockCardActions = (card) => {
    const removeButton = card.querySelector('.remove-block-instance');
    if (removeButton && removeButton.dataset.bound !== 'true') {
        removeButton.dataset.bound = 'true';
        removeButton.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const instanceKey = card.dataset.instanceKey || '';
            if (instanceKey !== '') {
                removeBlockInstance(instanceKey);
            }
        });
    }
};

const findBlockCardByInstance = (container, instanceKey) => {
    if (!container) {
        return null;
    }

    return Array.from(container.querySelectorAll('.page-block-card')).find((card) => {
        return card.dataset.instanceKey === instanceKey;
    }) || null;
};

    const rewriteBlockCardNames = (card, localeCode, blockKey, instanceKey) => {
        card.dataset.localeCode = localeCode;
        card.dataset.blockKey = blockKey;
        card.dataset.instanceKey = instanceKey;

        const search = `[${localeCode}][${blockKey}]`;
        const replacement = `[${localeCode}][${instanceKey}]`;

        card.querySelectorAll('input, textarea, select').forEach((field) => {
            const name = field.getAttribute('name');
            if (name && name.includes(search)) {
                field.setAttribute('name', name.replaceAll(search, replacement));
            }
        });

        card.querySelectorAll('[data-name-template]').forEach((field) => {
            const template = field.getAttribute('data-name-template');
            if (template && template.includes(search)) {
                field.setAttribute('data-name-template', template.replaceAll(search, replacement));
            }
        });

        let typeInput = card.querySelector('.block-type-input');
        if (!typeInput) {
            typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.className = 'block-type-input';
            card.querySelector('.block-body-content')?.prepend(typeInput);
        }

        typeInput.name = `blocks[${localeCode}][${instanceKey}][type]`;
        typeInput.value = blockKey;
    };

    const syncSelectedBlockTypeInputs = () => {
        if (!selectedBlockTypesContainer || !firstLocaleCode) {
            return;
        }

        const firstLocaleContainer = document.getElementById(`page-blocks-${firstLocaleCode}`);
        if (!firstLocaleContainer) {
            return;
        }

        selectedBlockTypesContainer.innerHTML = '';
        Array.from(firstLocaleContainer.querySelectorAll('.page-block-card')).forEach((card) => {
            const blockKey = card.dataset.blockKey || '';
            if (blockKey === '') {
                return;
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'block_types[]';
            input.value = blockKey;
            selectedBlockTypesContainer.appendChild(input);
        });
    };

    const refreshBlockSortOrders = (localeCode) => {
        const container = document.getElementById(`page-blocks-${localeCode}`);
        if (!container) {
            return;
        }

        const cards = Array.from(container.querySelectorAll('.page-block-card'));
        cards.forEach((card, index) => {
            const sortInput = card.querySelector('.block-sort-order-input');
            if (sortInput) {
                sortInput.value = String(index);
            }
        });

        const emptyState = document.getElementById(`page-blocks-empty-${localeCode}`);
        if (emptyState) {
            emptyState.classList.toggle('d-none', cards.length > 0);
        }
    };

    const refreshAllBlockSortOrders = () => {
        localeCodes.forEach((localeCode) => refreshBlockSortOrders(localeCode));
        syncSelectedBlockTypeInputs();
    };

    const addBlockToLocale = (localeCode, blockKey, instanceKey) => {
        const container = document.getElementById(`page-blocks-${localeCode}`);
        const template = document.getElementById(`page-block-template-${localeCode}-${blockKey}`);
        if (!container || !template) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INSTANCE_KEY__', instanceKey).trim();
        const card = wrapper.firstElementChild;
        if (!card) {
            return;
        }

        rewriteBlockCardNames(card, localeCode, blockKey, instanceKey);
        bindBlockCardActions(card);
        container.appendChild(card);
        window.AdminQuill?.initialize(card);

        if (!document.getElementById(`lang-tab-${localeCode}`)) {
            card.querySelectorAll('input, textarea, select').forEach((field) => {
                field.disabled = true;
            });
        }
    };

    const addBlockInstance = (blockKey) => {
        const instanceKey = generateBlockInstanceKey(blockKey);
        localeCodes.forEach((localeCode) => addBlockToLocale(localeCode, blockKey, instanceKey));
        refreshAllBlockSortOrders();
    };

const removeBlockInstance = (instanceKey) => {
    localeCodes.forEach((localeCode) => {
        const container = document.getElementById(`page-blocks-${localeCode}`);
        findBlockCardByInstance(container, instanceKey)?.remove();
    });

    refreshAllBlockSortOrders();
};

const moveBlockInstance = (instanceKey, direction) => {
    localeCodes.forEach((localeCode) => {
        const container = document.getElementById(`page-blocks-${localeCode}`);
        const card = findBlockCardByInstance(container, instanceKey);
        if (!card) {
            return;
        }

            if (direction === 'up' && card.previousElementSibling) {
                container.insertBefore(card, card.previousElementSibling);
            }

            if (direction === 'down' && card.nextElementSibling) {
                container.insertBefore(card.nextElementSibling, card);
            }
        });

        refreshAllBlockSortOrders();
    };

    const initializeExistingBlockCards = () => {
        localeCodes.forEach((localeCode) => {
            const container = document.getElementById(`page-blocks-${localeCode}`);
            if (!container) {
                return;
            }

            Array.from(container.querySelectorAll('.page-block-card')).forEach((card, index) => {
                const blockKey = card.dataset.blockKey || '';
                const instanceKey = card.dataset.instanceKey || `${blockKey}__${index}`;
                if (blockKey === '') {
                    return;
                }

                rewriteBlockCardNames(card, localeCode, blockKey, instanceKey);
                bindBlockCardActions(card);
            });
        });

        refreshAllBlockSortOrders();
    };

initializeExistingBlockCards();

window.AdminBlocks = {
    addBlockInstance,
    removeBlockInstance,
    moveBlockInstance,
    refreshAllBlockSortOrders,
    initializeExistingBlockCards,
};
