(() => {
    const reminderModal = document.getElementById('clientReminderModal');

    if (!reminderModal) {
        return;
    }

    reminderModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;

        if (!button) {
            return;
        }

        const clientId = button.getAttribute('data-client-id') || '';
        const clientName = button.getAttribute('data-client-name') || '';
        const clientEmail = button.getAttribute('data-client-email') || '';
        const clientPhone = button.getAttribute('data-client-phone') || '';

        const idInput = reminderModal.querySelector('#reminderClientId');
        const titleInput = reminderModal.querySelector('#reminderTitle');
        const emailInput = reminderModal.querySelector('#reminderEmail');
        const phoneInput = reminderModal.querySelector('#reminderPhone');

        if (idInput) {
            idInput.value = clientId;
        }

        if (titleInput) {
            titleInput.value = `Follow-up - ${clientName}`;
        }

        if (emailInput) {
            emailInput.value = clientEmail;
        }

        if (phoneInput) {
            phoneInput.value = clientPhone;
        }
    });
})();
