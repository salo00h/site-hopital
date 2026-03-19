document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('installModal');
  const modalText = document.getElementById('installModalText');
  const btnCancel = document.getElementById('installModalCancel');
  const btnConfirm = document.getElementById('installModalConfirm');

  let currentForm = null;

  function openModal(form, litNumero) {
    currentForm = form;
    modalText.textContent =
      'Confirmer que le patient est bien installé dans le lit ' + litNumero + ' ?';

    modal.classList.remove('hidden');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    currentForm = null;
    modal.classList.add('hidden');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.querySelectorAll('[data-install-form]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();

      const litNumero = form.getAttribute('data-lit-numero') || '';
      openModal(form, litNumero);
    });
  });

  btnCancel.addEventListener('click', function () {
    closeModal();
  });

  btnConfirm.addEventListener('click', function () {
    if (currentForm) {
      currentForm.submit();
    }
  });

  modal.addEventListener('click', function (event) {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
      closeModal();
    }
  });
});