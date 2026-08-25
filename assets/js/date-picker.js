// Самодостаточный виджет выбора даты/времени без внешних зависимостей
// (change calls-crud). Формат значения поля — d.m.Y H:i (datetime) или
// d.m.Y (date), совпадающий с таблицей панели. Подключается к
// <input data-date-picker="datetime|date">. Значение можно также ввести
// вручную в том же формате.

import { notifyOverlayOpen, onOtherOverlayOpen } from './popover-coordinator.js';

const WEEKDAYS = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
const MONTHS = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
];
const pad = (n) => String(n).padStart(2, '0');

function formatValue(date, withTime) {
    const base = `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()}`;
    return withTime
        ? `${base} ${pad(date.getHours())}:${pad(date.getMinutes())}`
        : base;
}

function parseValue(value, withTime) {
    if (!value) {
        return null;
    }
    const str = String(value).trim();
    let m;
    if (withTime) {
        m = str.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})\s+(\d{1,2}):(\d{2})$/);
        if (!m) {
            return null;
        }
        return new Date(+m[3], +m[2] - 1, +m[1], +m[4], +m[5], 0, 0);
    }
    m = str.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/);
    if (!m) {
        return null;
    }
    return new Date(+m[3], +m[2] - 1, +m[1], 0, 0, 0, 0);
}

function buildTimeOptions(max) {
    const frag = document.createDocumentFragment();
    for (let i = 0; i <= max; i += 1) {
        const opt = document.createElement('option');
        opt.value = pad(i);
        opt.textContent = pad(i);
        frag.appendChild(opt);
    }
    return frag;
}

function createPicker(input) {
    const withTime = input.dataset.datePicker === 'datetime';
    const field = input.closest('.field') || input.parentNode;
    field.style.position = field.style.position || 'relative';

    const popover = document.createElement('div');
    popover.className = 'date-picker';
    popover.hidden = true;
    popover.innerHTML = `
        <div class="date-picker__header">
            <button type="button" class="date-picker__nav" data-dp-prev aria-label="Предыдущий месяц">‹</button>
            <span class="date-picker__title"></span>
            <button type="button" class="date-picker__nav" data-dp-next aria-label="Следующий месяц">›</button>
        </div>
        <div class="date-picker__weekdays">${WEEKDAYS.map((w) => `<span>${w}</span>`).join('')}</div>
        <div class="date-picker__grid"></div>
        ${withTime ? `
        <div class="date-picker__time">
            <select data-dp-hour aria-label="Часы"></select>
            <span>:</span>
            <select data-dp-minute aria-label="Минуты"></select>
        </div>` : ''}
    `;
    field.appendChild(popover);

    const titleEl = popover.querySelector('.date-picker__title');
    const gridEl = popover.querySelector('.date-picker__grid');
    const hourSel = popover.querySelector('[data-dp-hour]');
    const minuteSel = popover.querySelector('[data-dp-minute]');
    if (withTime) {
        hourSel.appendChild(buildTimeOptions(23));
        minuteSel.appendChild(buildTimeOptions(59));
    }

    // Состояние просмотра (отображаемый месяц) и выбранная дата.
    let view = new Date();
    let selected = parseValue(input.value, withTime);

    const render = () => {
        const y = view.getFullYear();
        const m = view.getMonth();
        titleEl.textContent = `${MONTHS[m]} ${y}`;

        gridEl.innerHTML = '';
        const first = new Date(y, m, 1);
        // Понедельник = 1 … Воскресенье = 0 -> смещение для сетки.
        const offset = (first.getDay() + 6) % 7;
        const daysInMonth = new Date(y, m + 1, 0).getDate();
        const today = new Date();

        for (let i = 0; i < offset; i += 1) {
            const empty = document.createElement('span');
            empty.className = 'date-picker__day date-picker__day--empty';
            gridEl.appendChild(empty);
        }
        for (let d = 1; d <= daysInMonth; d += 1) {
            const dayBtn = document.createElement('button');
            dayBtn.type = 'button';
            dayBtn.className = 'date-picker__day';
            dayBtn.textContent = String(d);
            const cellDate = new Date(y, m, d);
            if (selected
                && selected.getDate() === d
                && selected.getMonth() === m
                && selected.getFullYear() === y) {
                dayBtn.classList.add('date-picker__day--selected');
            }
            if (cellDate.toDateString() === today.toDateString()) {
                dayBtn.classList.add('date-picker__day--today');
            }
            dayBtn.addEventListener('click', () => selectDay(d));
            gridEl.appendChild(dayBtn);
        }
    };

    const compose = () => {
        if (!selected) {
            return;
        }
        input.value = formatValue(selected, withTime);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const selectDay = (day) => {
        const h = withTime ? (parseInt(hourSel.value, 10) || 0) : 0;
        const min = withTime ? (parseInt(minuteSel.value, 10) || 0) : 0;
        selected = new Date(view.getFullYear(), view.getMonth(), day, h, min, 0, 0);
        render();
        compose();
        if (!withTime) {
            close();
        }
    };

    const onTimeChange = () => {
        const h = parseInt(hourSel.value, 10) || 0;
        const min = parseInt(minuteSel.value, 10) || 0;
        if (!selected) {
            selected = new Date(view.getFullYear(), view.getMonth(), 1, h, min, 0, 0);
        } else {
            selected = new Date(
                selected.getFullYear(), selected.getMonth(), selected.getDate(), h, min, 0, 0,
            );
        }
        compose();
    };

    if (withTime) {
        hourSel.addEventListener('change', onTimeChange);
        minuteSel.addEventListener('change', onTimeChange);
    }

    popover.querySelector('[data-dp-prev]').addEventListener('click', () => {
        view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
        render();
    });
    popover.querySelector('[data-dp-next]').addEventListener('click', () => {
        view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
        render();
    });

    // Клики внутри поповера не должны закрывать его.
    popover.addEventListener('click', (e) => e.stopPropagation());

    const open = () => {
        // Закрываем прочие оверлеи (другие date-picker, выпадающий список).
        notifyOverlayOpen(popover);
        // Перечитываем значение: оно могло быть установлено извне
        // (например, при открытии модального окна быстрого редактирования).
        selected = parseValue(input.value, withTime);
        // Для пустого поля с временем предвыбираем текущую дату и время
        // (Фактическая дата звонка, spec «Менеджер фиксирует факт звонка»).
        if (withTime && !selected) {
            selected = new Date();
        }
        view = selected ? new Date(selected) : new Date();
        if (withTime && selected) {
            hourSel.value = pad(selected.getHours());
            minuteSel.value = pad(selected.getMinutes());
        }
        render();
        popover.hidden = false;
    };

    const close = () => {
        popover.hidden = true;
    };

    const toggle = () => (popover.hidden ? open() : close());

    input.addEventListener('click', (e) => {
        e.stopPropagation();
        toggle();
    });

    // Закрытие по клику вне поля и поповера.
    document.addEventListener('click', (e) => {
        if (!popover.hidden && !input.contains(e.target)) {
            close();
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !popover.hidden) {
            close();
        }
    });

    // Закрываем этот оверлей, когда открывается любой другой.
    onOtherOverlayOpen(popover, close);
}

function init() {
    document.querySelectorAll('[data-date-picker]').forEach((input) => {
        if (!input.dataset.datePickerReady) {
            input.dataset.datePickerReady = '1';
            createPicker(input);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

export { init as initDatePickers };
