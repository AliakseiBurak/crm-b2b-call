// Шапка: выпадающие списки («Создать», «⚙ Админ», пользователь) и
// мобильная боковая панель. Один обработчик document click отвечает
// и за переключение по кнопке, и за закрытие по клику вне списка
// (паттерн org-combobox.js).

const dropdownConfigs = [
    ['data-header-create', 'data-header-create-toggle', 'data-header-create-menu'],
    ['data-header-admin', 'data-header-admin-toggle', 'data-header-admin-menu'],
    ['data-header-user', 'data-header-user-toggle', 'data-header-user-menu'],
];

const dropdowns = [];

dropdownConfigs.forEach(([rootAttr, toggleAttr, menuAttr]) => {
    document.querySelectorAll(`[${rootAttr}]`).forEach((root) => {
        const toggle = root.querySelector(`[${toggleAttr}]`);
        const menu = root.querySelector(`[${menuAttr}]`);
        if (!toggle || !menu) {
            return;
        }
        dropdowns.push({ root, toggle, menu });
    });
});

const setOpen = (dropdown, open) => {
    dropdown.menu.hidden = !open;
    dropdown.toggle.setAttribute('aria-expanded', String(open));
};

const closeAll = (exceptMenu) => {
    dropdowns.forEach((dropdown) => {
        if (dropdown.menu !== exceptMenu) {
            setOpen(dropdown, false);
        }
    });
};

document.addEventListener('click', (event) => {
    dropdowns.forEach((dropdown) => {
        if (!dropdown.root.contains(event.target)) {
            setOpen(dropdown, false);
            return;
        }
        if (dropdown.toggle.contains(event.target)) {
            const willOpen = dropdown.menu.hidden;
            closeAll();
            setOpen(dropdown, willOpen);
        }
    });
});

// Мобильная боковая панель: гамбургер открывает/закрывает,
// клик по оверлею закрывает.
const hamburger = document.querySelector('[data-header-hamburger]');
const sidebar = document.querySelector('[data-header-sidebar]');
const overlay = document.querySelector('[data-header-sidebar-overlay]');

if (hamburger && sidebar && overlay) {
    const setSidebarOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        overlay.classList.toggle('is-open', open);
        hamburger.setAttribute('aria-expanded', String(open));
        if (open) {
            closeAll();
        }
    };

    hamburger.addEventListener('click', () => {
        setSidebarOpen(!sidebar.classList.contains('is-open'));
    });

    overlay.addEventListener('click', () => setSidebarOpen(false));
}
