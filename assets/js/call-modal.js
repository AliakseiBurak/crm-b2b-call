// Модальное окно быстрого редактирования звонка (change calls-crud, call-result).

const modalRoot = document.querySelector('[data-call-edit-modal]');
const modal = modalRoot?.querySelector('.modal');

if (modal && modalRoot) {
    const form = modal.querySelector('[data-call-edit-form]');
    const fields = {
        scheduled_at: modal.querySelector('[data-call-field="scheduled_at"]'),
        contact: modal.querySelector('[data-call-field="contact"]'),
        made_at: modal.querySelector('[data-call-field="made_at"]'),
        made_by: modal.querySelector('[data-call-field="made_by"]'),
        is_deal: modal.querySelector('[data-call-field="is_deal"]'),
        is_no_answer: modal.querySelector('[data-call-field="is_no_answer"]'),
        mailing_campaign: modal.querySelector('[data-call-field="mailing_campaign"]'),
        mailing_contact: modal.querySelector('[data-call-field="mailing_contact"]'),
        next_call_date: modal.querySelector('[data-call-field="next_call_date"]'),
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

    const loadContacts = async (orgId, selectedId, mailingSelectedId) => {
        const select = fields.contact;
        const mailingSelect = fields.mailing_contact;
        if (!select || !orgId) {
            return;
        }

        select.innerHTML = '';
        const empty = document.createElement('option');
        empty.value = '';
        empty.textContent = '— контакт не выбран —';
        select.appendChild(empty);

        if (mailingSelect) {
            mailingSelect.innerHTML = '';
            const mailingEmpty = document.createElement('option');
            mailingEmpty.value = '';
            mailingEmpty.textContent = '— вся организация —';
            mailingSelect.appendChild(mailingEmpty);
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
                select.appendChild(option);

                if (mailingSelect) {
                    const mailingOption = document.createElement('option');
                    mailingOption.value = contact.id;
                    mailingOption.textContent = contact.name;
                    if (String(contact.id) === String(mailingSelectedId ?? selectedId)) {
                        mailingOption.selected = true;
                    }
                    mailingSelect.appendChild(mailingOption);
                }
            });
        } catch {
            // Список остаётся с единственным пустым вариантом.
        }
    };

    const toggleNextCallField = (row) => {
        const nextCallField = fields.next_call_date?.closest('.field');
        const nextCallContext = modal.querySelector('[data-call-next-call-context]');
        const nextCallHint = modal.querySelector('[data-call-next-call-hint]');
        const hasNextCall = Boolean(row.dataset.callNextCallId);
        if (nextCallField) {
            nextCallField.hidden = hasNextCall;
        }
        if (fields.next_call_date) {
            fields.next_call_date.value = '';
            fields.next_call_date.disabled = hasNextCall;
        }
        if (nextCallContext) {
            nextCallContext.hidden = !hasNextCall;
            if (nextCallHint && hasNextCall) {
                const date = row.dataset.callNextCallDate || '—';
                nextCallHint.textContent =
                    `Запланирован на ${date}. Изменить или удалить его можно через строку этого звонка в списке организации.`;
            }
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
        if (fields.is_no_answer) {
            fields.is_no_answer.checked = row.dataset.callIsNoAnswer === '1';
        }
        if (fields.mailing_campaign) {
            fields.mailing_campaign.value = '';
        }
        fields.notes.value = row.dataset.callNotes ?? '';

        toggleNextCallField(row);

        await loadContacts(row.dataset.callOrgId, row.dataset.callContactId, row.dataset.callContactId);

        modal.hidden = false;
        (fields.scheduled_at ?? form).focus();
    };

    const close = () => {
        modal.hidden = true;
        activeRow = null;
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-call-edit]');
        if (trigger) {
            event.preventDefault();
            open(trigger.closest('[data-call-row]'));
            return;
        }
        if (!modal.hidden && event.target.closest('[data-modal-close]')?.closest('[data-call-edit-modal]')) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) {
            close();
        }
    });

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

        if (payload.row && activeRow.parentNode) {
            const list = activeRow.closest('.org-calls__list');
            activeRow.outerHTML = payload.row;

            if (payload.nextCallRow && list) {
                list.insertAdjacentHTML('afterbegin', payload.nextCallRow);
                const summary = list.closest('.org-calls__all')?.querySelector('.org-calls__all-summary');
                if (summary) {
                    const count = list.querySelectorAll('[data-call-row]').length;
                    summary.textContent = `Все звонки (${count})`;
                }
            }
        }

        close();
    });
}

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

        const initialOrganization =
            organizationSelect.value || callForm.dataset.initialOrganization || '';
        if (initialOrganization) {
            const selected = callForm.querySelector('select[name="contact"] option[selected]');
            fillContacts(initialOrganization, selected ? selected.value : '');
        }
    }
}
