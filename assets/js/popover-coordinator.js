// Координатор оверлеев (change calls-crud): при открытии одного
// (date-picker, выпадающий список организаций) все остальные должны
// закрыться. Общий механизм для независимых компонентов на странице.

const EVENT = 'overlay:open';

export function notifyOverlayOpen(owner) {
    document.dispatchEvent(new CustomEvent(EVENT, { detail: { owner } }));
}

export function onOtherOverlayOpen(owner, handler) {
    document.addEventListener(EVENT, (event) => {
        if (event.detail && event.detail.owner !== owner) {
            handler();
        }
    });
}
