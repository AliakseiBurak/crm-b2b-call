## 1. Template

- [ ] 1.1 Refactor `templates/base.html.twig` — separate menu into `navItems` (navigation links) and pass `app.user` to header for user dropdown
- [ ] 1.2 Replace `header__create` block in `templates/components/header.html.twig` with dropdown structure: button with caret + menu container
- [ ] 1.3 Add role-based conditionals: `is_granted('ROLE_MANAGER')` for "Группу", `is_granted('ROLE_ADMIN')` for "Пользователя"
- [ ] 1.4 Add user dropdown: button with `app.user.name` + caret + dropdown menu with "Выйти" link
- [ ] 1.5 Add hamburger button (hidden on desktop, visible ≤768px)
- [ ] 1.6 Add mobile sidebar markup: nav items + create dropdown + user block
- [ ] 1.7 User dropdown: label «Профиль» + first menu item with name/surname (if present) and email, then «Выйти»

## 2. CSS

- [ ] 2.1 Remove `.header__create` styles from `assets/scss/components/header.scss`
- [ ] 2.2 Add create dropdown styles: `.header-create`, `.header-create__caret`, `.header-create__menu`, `.header-create__item`
- [ ] 2.3 Add user dropdown styles: `.header-user`, `.header-user__toggle`, `.header-user__menu`, `.header-user__item`
- [ ] 2.4 Add admin dropdown styles: `.header-admin`, `.header-admin__toggle`, `.header-admin__menu`, `.header-admin__item`
- [ ] 2.5 Add hamburger button styles: `.header__hamburger` (hidden on desktop, visible ≤768px)
- [ ] 2.6 Add sidebar styles: `.header__sidebar`, `.header__sidebar-overlay`, slide-in transition
- [ ] 2.7 Update responsive rules: hide nav/create/user on mobile, show hamburger
- [ ] 2.8 Remove unused footer styles from `assets/scss/components/footer.scss` (`.footer__inner`, `.footer__col`, `.footer__title`, `.footer__text`, `.footer__menu`, `.footer a`)

## 3. JavaScript

- [ ] 3.1 Create `assets/js/header-create-dropdown.js` with click toggle + close-on-outside-click for create dropdown
- [ ] 3.2 Add user dropdown toggle logic to same file (or separate) with same pattern
- [ ] 3.3 Add admin dropdown toggle logic with same pattern
- [ ] 3.4 Add hamburger toggle logic: open/close sidebar, toggle overlay
- [ ] 3.5 Add overlay click to close sidebar
- [ ] 3.6 Ensure opening one dropdown closes the others
- [ ] 3.7 Import new JS file in `assets/app.js`

## 4. Build & Verify

- [ ] 4.1 Run `npm run build` to compile assets
- [ ] 4.2 Verify create dropdown opens/closes on click
- [ ] 4.3 Verify admin dropdown opens/closes on click (admin only)
- [ ] 4.4 Verify user dropdown opens/closes on click
- [ ] 4.5 Verify outside click closes all dropdowns
- [ ] 4.6 Verify role-based items: admin sees all 6 create + admin dropdown, manager sees 5, regular user sees 4
- [ ] 4.7 Verify mobile: hamburger visible, sidebar slides in, overlay works
- [ ] 4.8 Verify sidebar contains all nav items, create dropdown, and user block
- [ ] 4.9 Verify footer shows only copyright line centered on green gradient
