<script>
(function () {
    const components = new Set();
    const maxVisibleOptions = 80;
    let componentSerial = 0;

    function normalize(value) {
        return String(value || '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    function optionLabel(option) {
        return String(option?.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function selectedLabel(select) {
        const option = select.selectedOptions?.[0];
        return option && option.value !== '' ? optionLabel(option) : '';
    }

    function searchableOptions(select) {
        return Array.from(select.options).filter(option => !option.disabled && !option.hidden);
    }

    function enhanceSelect(select) {
        if (! select || select.dataset.searchableSelectReady === 'true') return;
        if (select.matches('[multiple], [data-searchable-select="off"]') || Number(select.size || 0) > 1) return;
        if (select.hidden || select.style.display === 'none') return;

        select.dataset.searchableSelectReady = 'true';
        componentSerial += 1;

        const wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        if (select.classList.contains('per-page-select') || (select.style.width && select.style.width !== '100%')) {
            wrapper.classList.add('is-compact');
        }

        const control = document.createElement('div');
        control.className = 'searchable-select-control';

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'searchable-select-input';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');

        const menu = document.createElement('div');
        menu.className = 'searchable-select-menu';
        menu.id = `searchable-select-menu-${componentSerial}`;
        menu.setAttribute('role', 'listbox');
        menu.hidden = true;
        input.setAttribute('aria-controls', menu.id);

        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.className = 'searchable-select-toggle';
        toggle.setAttribute('aria-label', 'Show options');
        toggle.textContent = '▾';

        const blankOption = Array.from(select.options).find(option => option.value === '');
        const labelledBy = select.getAttribute('aria-labelledby');
        const explicitLabel = select.getAttribute('aria-label');
        const nearbyLabel = select.previousElementSibling?.matches?.('label')
            ? select.previousElementSibling.textContent.replace(/\s+/g, ' ').trim()
            : '';
        const relatedLabel = select.labels?.[0]?.textContent?.replace(/\s+/g, ' ').trim() || nearbyLabel;
        if (labelledBy) input.setAttribute('aria-labelledby', labelledBy);
        else input.setAttribute('aria-label', explicitLabel || relatedLabel || select.name || 'Search options');

        input.placeholder = select.dataset.searchPlaceholder
            || (blankOption ? optionLabel(blankOption) : 'লিখে অপশন সার্চ করুন');

        control.append(input, toggle);
        wrapper.append(control, menu);
        select.insertAdjacentElement('afterend', wrapper);
        select.classList.add('searchable-select-native');
        select.setAttribute('aria-hidden', 'true');
        select.tabIndex = -1;

        let filtered = [];
        let activeIndex = -1;

        function syncValidity() {
            const labelMatches = normalize(input.value) === normalize(selectedLabel(select));
            if (!labelMatches || (select.required && !select.value)) {
                input.setCustomValidity('তালিকা থেকে একটি অপশন নির্বাচন করুন।');
            } else {
                input.setCustomValidity('');
            }
            input.required = select.required;
        }

        function refreshFromSelect() {
            input.value = selectedLabel(select);
            input.disabled = select.disabled;
            toggle.disabled = select.disabled;
            wrapper.classList.toggle('is-disabled', select.disabled);
            syncValidity();
        }

        function setActive(index) {
            const buttons = Array.from(menu.querySelectorAll('.searchable-select-option'));
            if (!buttons.length) {
                activeIndex = -1;
                return;
            }

            activeIndex = Math.max(0, Math.min(index, buttons.length - 1));
            buttons.forEach((button, buttonIndex) => button.classList.toggle('is-active', buttonIndex === activeIndex));
            buttons[activeIndex].scrollIntoView({ block: 'nearest' });
        }

        function choose(option, keepFocus = true) {
            if (!option) return;
            const previousValue = select.value;
            option.selected = true;
            select.value = option.value;
            refreshFromSelect();
            closeMenu(false);
            if (previousValue !== select.value) {
                select.dispatchEvent(new Event('input', { bubbles: true }));
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (keepFocus) input.focus();
        }

        function exactMatch() {
            const query = normalize(input.value);
            if (!query) return blankOption || null;
            return searchableOptions(select).find(option => normalize(optionLabel(option)) === query) || null;
        }

        function renderMenu(query = '') {
            const normalizedQuery = normalize(query);
            const options = searchableOptions(select);
            filtered = options
                .filter(option => !normalizedQuery || normalize(optionLabel(option)).includes(normalizedQuery) || normalize(option.value).includes(normalizedQuery))
                .sort((left, right) => {
                    if (!normalizedQuery) return left.index - right.index;
                    const leftStarts = normalize(optionLabel(left)).startsWith(normalizedQuery) ? 0 : 1;
                    const rightStarts = normalize(optionLabel(right)).startsWith(normalizedQuery) ? 0 : 1;
                    return leftStarts - rightStarts || left.index - right.index;
                });

            menu.replaceChildren();
            if (!filtered.length) {
                const empty = document.createElement('div');
                empty.className = 'searchable-select-empty';
                empty.textContent = 'কোনো মিল পাওয়া যায়নি';
                menu.appendChild(empty);
                activeIndex = -1;
                return;
            }

            filtered.slice(0, maxVisibleOptions).forEach((option, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'searchable-select-option';
                button.setAttribute('role', 'option');
                button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
                button.classList.toggle('is-selected', option.selected);
                button.textContent = optionLabel(option);
                button.addEventListener('mousedown', event => event.preventDefault());
                button.addEventListener('click', () => choose(option));
                button.addEventListener('mousemove', () => setActive(index));
                menu.appendChild(button);
            });

            if (filtered.length > maxVisibleOptions) {
                const more = document.createElement('div');
                more.className = 'searchable-select-more';
                more.textContent = `আরও ${filtered.length - maxVisibleOptions}টি ফল আছে—আরও লিখে সার্চ ছোট করুন।`;
                menu.appendChild(more);
            }

            const selectedIndex = filtered.slice(0, maxVisibleOptions).findIndex(option => option.selected);
            activeIndex = selectedIndex >= 0 ? selectedIndex : 0;
            setActive(activeIndex);
        }

        function openMenu(showAll = false) {
            if (select.disabled) return;
            components.forEach(component => {
                if (component.select !== select) component.closeMenu();
            });
            renderMenu(showAll ? '' : input.value);
            menu.hidden = false;
            wrapper.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
        }

        function closeMenu(restore = true) {
            menu.hidden = true;
            wrapper.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
            if (restore) refreshFromSelect();
        }

        input.addEventListener('focus', () => {
            input.select();
            openMenu(true);
        });
        input.addEventListener('input', () => {
            openMenu(false);
            syncValidity();
        });
        input.addEventListener('blur', () => window.setTimeout(() => {
            if (!wrapper.contains(document.activeElement)) {
                const match = exactMatch();
                if (match) choose(match, false);
                else closeMenu(true);
            }
        }, 120));
        input.addEventListener('keydown', event => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (menu.hidden) openMenu(true);
                setActive(activeIndex + (event.key === 'ArrowDown' ? 1 : -1));
                return;
            }
            if (event.key === 'Enter' && !menu.hidden) {
                event.preventDefault();
                choose(filtered[activeIndex] || exactMatch());
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                closeMenu(true);
            }
        });
        toggle.addEventListener('click', () => {
            if (menu.hidden) {
                input.focus();
                openMenu(true);
            } else {
                closeMenu(true);
            }
        });
        select.addEventListener('change', refreshFromSelect);
        select.addEventListener('focus', () => input.focus());
        select.addEventListener('invalid', event => {
            event.preventDefault();
            refreshFromSelect();
            input.focus();
            input.reportValidity();
        });
        select.form?.addEventListener('reset', () => window.setTimeout(refreshFromSelect));

        const component = { select, wrapper, input, closeMenu, refreshFromSelect, renderMenu };
        select.searchableSelectComponent = component;
        components.add(component);
        refreshFromSelect();
    }

    function enhanceWithin(root) {
        if (root instanceof HTMLSelectElement) enhanceSelect(root);
        root.querySelectorAll?.('select').forEach(enhanceSelect);
    }

    enhanceWithin(document);

    document.addEventListener('click', event => {
        components.forEach(component => {
            if (!component.wrapper.contains(event.target)) component.closeMenu();
        });
    });

    const observer = new MutationObserver(mutations => {
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === Node.ELEMENT_NODE) enhanceWithin(node);
            });

            const select = mutation.target instanceof HTMLSelectElement
                ? mutation.target
                : mutation.target.closest?.('select');
            if (!select) return;
            if (select.searchableSelectComponent) select.searchableSelectComponent.refreshFromSelect();
            else enhanceSelect(select);
        });
    });
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled', 'required', 'hidden', 'style'],
    });
})();
</script>
