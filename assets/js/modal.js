function initModals() {
  const openers = document.querySelectorAll('[data-modal-open]');

  openers.forEach((opener) => {
    opener.addEventListener('click', () => {
      const target = document.getElementById(opener.dataset.modalOpen);
      if (target) {
        target.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      }
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach((closeBtn) => {
    closeBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      closeModal(closeBtn.closest('.modal'));
    });
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeModal(document.querySelector('.modal.is-open'));
    }
  });
}

function closeModal(modal) {
  if (!modal) return;
  modal.classList.remove('is-open');
  document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', initModals);