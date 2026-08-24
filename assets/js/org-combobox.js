// Поисковый выпадающий список организаций (fix contacts-crud):
// нативный <select data-org-combobox> остаётся источником значения
// (отправка формы, required, предвыбор), визуально заменяется кнопкой
// и меню с фильтром. Фильтрация — по подстроке (без учёта регистра).

document.querySelectorAll('select[data-org-combobox]').forEach((select) => {
    const field = select.closest('.field') || select.parentElement;

    select.classList.add('org-combobox__native');

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'org-combobox__toggle field__input';
    toggle.setAttribute('aria-haspopup', 'listbox');
    toggle.setAttribute('aria-expanded', 'false');
    const valueSpan = document.createElement('span');
    valueSpan.className = 'org-combobox__value';
    toggle.appendChild(valueSpan);

    const menu = document.createElement('div');
    menu.className = 'org-combobox__menu';
    menu.hidden = true;
    const search = document.createElement('input');
    search.type = 'text';
    search.className = 'org-combobox__search';
    search.placeholder = 'Поиск организации…';
    search.setAttribute('aria-label', 'Поиск организации');
    const list = document.createElement('ul');
    list.className = 'org-combobox__list';
    list.setAttribute('role', 'listbox');
    menu.appendChild(search);
    menu.appendChild(list);

    select.insertAdjacentElement('afterend', menu);
    select.insertAdjacentElement('afterend', toggle);

    const options = Array.from(select.options)
        .filter((o) => o.value !== '')
        .map((o) => ({
            value: o.value,
            label: o.textContent,
        }));
    let activeIndex = -1;

    const syncLabel = () => {
        const opt = select.options[select.selectedIndex];
        valueSpan.textContent = opt ? opt.textContent : '';
    };

    const highlight = (items) => {
        items.forEach((it, i) => it.classList.toggle('is-active', i === activeIndex));
        if (items[activeIndex]) {
            items[activeIndex].scrollIntoView({ block: 'nearest' });
        }
    };

    const render = (filter = '') => {
        const q = filter.trim().toLowerCase();
        list.innerHTML = '';
        options.forEach((o) => {
            if (q && !o.label.toLowerCase().includes(q)) {
                return;
            }
            const li = document.createElement('li');
            li.className = 'org-combobox__option';
            li.setAttribute('role', 'option');
            li.dataset.value = o.value;
            li.textContent = o.label;
            if (o.value === select.value) {
                li.classList.add('is-selected');
            }
            li.addEventListener('click', () => choose(o.value));
            list.appendChild(li);
        });
        if (list.children.length === 0) {
            const empty = document.createElement('li');
            empty.className = 'org-combobox__empty';
            empty.textContent = 'Ничего не найдено';
            list.appendChild(empty);
        }
        activeIndex = -1;
    };

    const choose = (value) => {
        select.value = value;
        select.dispatchEvent(new Event('change'));
        close();
        toggle.focus();
    };

    const open = () => {
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        search.value = '';
        render('');
        search.focus();
    };

    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    toggle.addEventListener('click', () => {
        if (menu.hidden) {
            open();
        } else {
            close();
        }
    });

    search.addEventListener('input', () => render(search.value));

    search.addEventListener('keydown', (event) => {
        const items = Array.from(list.querySelectorAll('.org-combobox__option'));
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (items.length) {
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                highlight(items);
            }
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (items.length) {
                activeIndex = Math.max(activeIndex - 1, 0);
                highlight(items);
            }
        } else if (event.key === 'Enter') {
            event.preventDefault();
            if (items[activeIndex]) {
                choose(items[activeIndex].dataset.value);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            close();
            toggle.focus();
        }
    });

    document.addEventListener('click', (event) => {
        if (!menu.hidden && !field.contains(event.target)) {
            close();
        }
    });

    select.addEventListener('change', syncLabel);
    syncLabel();
});
