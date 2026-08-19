const form = document.getElementById('modal-call-form');

if (form) {
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    form.hidden = true;
    const success = document.getElementById('modal-call-success');
    if (success) success.hidden = false;
  });
}