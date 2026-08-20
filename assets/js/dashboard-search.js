// Авто-применение поиска организаций панели по мере ввода (сп. dashboard:
// «applied immediately as the user types»). GET-форма переотправляется
// с дебаунсом; пустое поле сбрасывает запрос на базовую страницу.

const DEBOUNCE_MS = 400;

document.querySelectorAll('[data-dashboard-search]').forEach((input) => {
    const form = input.closest('form');
    if (!form) {
        return;
    }

    let timer = null;

    const submit = () => {
        if (input.value.trim() === '') {
            window.location.href = form.action;
            return;
        }
        form.submit();
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(submit, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            submit();
        }
    });
});