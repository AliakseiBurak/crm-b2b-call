// Модальное окно быстрого редактирования звонка (change calls-crud):
// открытие по клику на кнопку «Изменить» строки звонка, заполнение формы
// данными data-атрибутов строки, динамическая загрузка контактов организации,
// закрытие по крестику/оверлею/Esc/«Отмене». Сохранение — AJAX (fetch),
// ошибки валидации выводятся под полями, строка заменяется отрисованным
// с сервера HTML без перезагрузки страницы.

const modal = document.querySelector('[data-call-edit-modal] .modal');

if (modal) {
    const form = modal.querySelector('[data-call-edit-form]');
    const fields = {
        scheduled_at: modal.querySelector('[data-call-field="scheduled_at"]'),
        contact: modal.querySelector('[data-call-field="contact"]'),
        made_at: modal.querySelector('[data-call-field="made_at"]'),
        made_by: modal.querySelector('[data-call-field="made_by"]'),
        is_deal: modal.querySelector('[data-call-field="is_deal"]'),
        notes: modal.querySelector('[data-call-field="notes"]'),
    };
    const deleteLink = modal.querySelector('[data-call-delete-link]');
    let activeRow = null;

    const clearErrors = () => {
        modal.querySelectorAll('[data-call-error]').forEach((span) => {
            span.hidden = true;
            span.textContent = '';
        });
    };

    // Контакты организации звонка: подгрузка в выпадающий список.
    const loadContacts = async (orgId, selectedId) => {
        const select = fields.contact;
        if (!select || !orgId) {
            return;
        }
        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '— контакт не выбран —';
        select.appendChild(empty);

        try {
            const response = await fetch(`/organizations/${orgId}/contacts.json`);
            const payload = await response.json();
            (payload.contacts ?? []).forEach((contact) => {
                const option = document.createElement('option');
                option.value = contact.id;
                option.textContent = contact.name;
                if (String(contact.id) === String(selectedId)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        } catch {
            // Список остаётся с единственным пустым вариантом.
        }
    };

    const open = async (row) => {
        activeRow = row;
        form.action = `/calls/${row.dataset.callId}/edit`;
        if (deleteLink) {
            deleteLink.href = `/calls/${row.dataset.callId}/delete`;
        }
        clearErrors();

        fields.scheduled_at.value = row.dataset.callScheduledAt ?? '';
        fields.made_at.value = row.dataset.callMadeAt ?? '';
        if (fields.made_by) {
            fields.made_by.value = row.dataset.callMadeBy ?? '';
        }
        fields.is_deal.checked = row.dataset.callIsDeal === '1';
        fields.notes.value = row.dataset.callNotes ?? '';

        await loadContacts(row.dataset.callOrgId, row.dataset.callContactId);

        modal.hidden = false;
        (fields.scheduled_at ?? form).focus();
    };

    const close = () => {
        modal.hidden = true;
        activeRow = null;
    };

    // Открытие: делегирование, кнопка внутри строки звонка.
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-call-edit]');
        if (trigger) {
            event.preventDefault();
            open(trigger.closest('[data-call-row]'));
            return;
        }
        // Закрытие по «Отмене»/крестику/оверлею только для этого окна.
        if (!modal.hidden && event.target.closest('[data-modal-close]')?.closest('[data-call-edit-modal]')) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            close();
        }
    });

    // AJAX-отправка формы модального окна.
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!activeRow) {
            return;
        }

        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        let payload = { ok: false, errors: {} };
        try {
            payload = await response.json();
        } catch {
            // Ответ не JSON — оставляем значения по умолчанию.
        }

        if (!payload.ok) {
            clearErrors();
            Object.entries(payload.errors ?? {}).forEach(([field, message]) => {
                const span = modal.querySelector(`[data-call-error="${field}"]`);
                if (span) {
                    span.textContent = message;
                    span.hidden = false;
                }
            });
            return;
        }

        // Обновление строки на дашборде без перезагрузки страницы:
        // сервер возвращает отрисованную строку целиком.
        if (payload.row && activeRow.parentNode) {
            activeRow.outerHTML = payload.row;
        }

        close();
    });
}

// Страница формы звонка (/calls/new, /calls/{id}/edit): динамическая
// загрузка контактов при выборе организации (change calls-crud).
const callForm = document.querySelector('[data-call-form]');

if (callForm) {
    const organizationSelect = callForm.querySelector('[data-call-organization]');
    const contactSelect = callForm.querySelector('[data-call-contact-select]');

    const fillContacts = async (orgId, selectedId) => {
        if (!contactSelect) {
            return;
        }
        contactSelect.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '— контакт не выбран —';
        contactSelect.appendChild(empty);

        if (!orgId) {
            return;
        }

        try {
            const response = await fetch(`/organizations/${orgId}/contacts.json`);
            const payload = await response.json();
            (payload.contacts ?? []).forEach((contact) => {
                const option = document.createElement('option');
                option.value = contact.id;
                option.textContent = contact.name;
                if (String(contact.id) === String(selectedId)) {
                    option.selected = true;
                }
                contactSelect.appendChild(option);
            });
        } catch {
            // Список остаётся с единственным пустым вариантом.
        }
    };

    if (organizationSelect && contactSelect) {
        organizationSelect.addEventListener('change', () => fillContacts(organizationSelect.value));

        // Первичная загрузка для предвыбранной организации (ссылка
        // «Добавить звонок») или фиксированной организации редактирования.
        const initialOrganization =
            organizationSelect.value || callForm.dataset.initialOrganization || '';
        if (initialOrganization) {
            const selected = callForm.querySelector('select[name="contact"] option[selected]');
            fillContacts(initialOrganization, selected ? selected.value : '');
        }
    }

}
