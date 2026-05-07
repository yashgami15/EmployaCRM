(() => {
    const body = document.body;

    document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const inputId = toggle.getAttribute('data-password-toggle');
            const input = document.getElementById(inputId);

            if (!input) {
                return;
            }

            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            toggle.innerHTML = `<i class="bi ${isPassword ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        });
    });

    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');

    const closeMobileSidebar = () => {
        body.classList.remove('sidebar-open');
    };

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 991.98px)').matches) {
                body.classList.toggle('sidebar-open');
                return;
            }

            body.classList.toggle('sidebar-collapsed');
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', closeMobileSidebar);
    }

    window.addEventListener('resize', () => {
        if (!window.matchMedia('(max-width: 991.98px)').matches) {
            closeMobileSidebar();
        }
    });

    const rowCheckboxes = Array.from(document.querySelectorAll('.row-checkbox'));
    const selectVisibleCheckbox = document.getElementById('selectVisibleCheckbox');
    const selectedCountElement = document.getElementById('selectedCount');
    const bulkStatusIds = document.getElementById('bulkStatusIds');
    const deleteSelectedIds = document.getElementById('deleteSelectedIds');
    const exportSelectedBtn = document.getElementById('exportSelectedBtn');
    const deleteSelectedForm = document.getElementById('deleteSelectedForm');
    const bulkStatusForm = document.getElementById('bulkStatusForm');

    const getSelectedIds = () => rowCheckboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value);

    const updateSelectionUI = () => {
        const ids = getSelectedIds();
        const selectedCount = ids.length;
        const idsCsv = ids.join(',');

        if (selectedCountElement) {
            selectedCountElement.textContent = String(selectedCount);
        }

        if (bulkStatusIds) {
            bulkStatusIds.value = idsCsv;
        }

        if (deleteSelectedIds) {
            deleteSelectedIds.value = idsCsv;
        }

        if (exportSelectedBtn) {
            const baseUrl = exportSelectedBtn.getAttribute('data-base-url') || '#';

            if (selectedCount > 0) {
                exportSelectedBtn.classList.remove('disabled');
                exportSelectedBtn.href = `${baseUrl}&selected_ids=${encodeURIComponent(idsCsv)}`;
            } else {
                exportSelectedBtn.classList.add('disabled');
                exportSelectedBtn.href = '#';
            }
        }

        if (selectVisibleCheckbox) {
            const allChecked = rowCheckboxes.length > 0 && rowCheckboxes.every((checkbox) => checkbox.checked);
            selectVisibleCheckbox.checked = allChecked;
        }
    };

    if (rowCheckboxes.length > 0) {
        rowCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', updateSelectionUI));
    }

    if (selectVisibleCheckbox) {
        selectVisibleCheckbox.addEventListener('change', () => {
            rowCheckboxes.forEach((checkbox) => {
                checkbox.checked = selectVisibleCheckbox.checked;
            });

            updateSelectionUI();
        });
    }

    if (bulkStatusForm) {
        bulkStatusForm.addEventListener('submit', (event) => {
            const ids = getSelectedIds();
            const select = document.getElementById('bulkStatusSelect');

            if (ids.length === 0) {
                event.preventDefault();
                alert('Please select at least one candidate first.');
                return;
            }

            if (select && !select.value) {
                event.preventDefault();
                alert('Please choose a status to apply.');
            }
        });
    }

    if (deleteSelectedForm) {
        deleteSelectedForm.addEventListener('submit', (event) => {
            const ids = getSelectedIds();

            if (ids.length === 0) {
                event.preventDefault();
                alert('Please select candidates to delete.');
                return;
            }

            if (!window.confirm(`Delete ${ids.length} selected candidate(s)?`)) {
                event.preventDefault();
            }
        });
    }

    const importCsvBtn = document.getElementById('importCsvBtn');
    const csvFileInput = document.getElementById('csvFileInput');
    const importCsvForm = document.getElementById('importCsvForm');

    if (importCsvBtn && csvFileInput && importCsvForm) {
        importCsvBtn.addEventListener('click', () => csvFileInput.click());

        csvFileInput.addEventListener('change', () => {
            if (csvFileInput.files && csvFileInput.files.length > 0) {
                importCsvForm.submit();
            }
        });
    }

    const candidateModal = document.getElementById('addCandidateModal');
    const candidateForm = document.getElementById('candidateProfileForm');
    const candidateIdInput = document.getElementById('candidateIdInput');
    const candidateModalTitle = document.getElementById('candidateModalTitle');
    const candidateSubmitBtn = document.getElementById('candidateSubmitBtn');

    const setCandidateFormMode = (trigger) => {
        if (!candidateForm || !trigger) {
            return;
        }

        const mode = trigger.getAttribute('data-candidate-mode') || 'add';
        candidateForm.reset();

        candidateForm.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = false;
        });

        if (mode === 'edit') {
            candidateForm.action = 'index.php?action=update_candidate';

            if (candidateIdInput) {
                candidateIdInput.value = trigger.getAttribute('data-candidate-id') || '';
            }

            if (candidateModalTitle) {
                candidateModalTitle.textContent = 'Edit Candidate Profile';
            }

            if (candidateSubmitBtn) {
                candidateSubmitBtn.textContent = 'Update Candidate';
            }

            Array.from(candidateForm.elements).forEach((field) => {
                if (!field.name || field.name === '_csrf' || field.name === 'candidate_id' || field.type === 'file') {
                    return;
                }

                const dataKey = field.name.replace(/\[\]$/, '').replace(/_/g, '-');
                const value = trigger.getAttribute(`data-${dataKey}`) || '';

                if (field.type === 'checkbox') {
                    const values = value.split(',').map((item) => item.trim()).filter(Boolean);
                    field.checked = values.includes(field.value);
                    return;
                }

                field.value = value;
            });

            return;
        }

        candidateForm.action = 'index.php?action=add_candidate';

        if (candidateIdInput) {
            candidateIdInput.value = '';
        }

        if (candidateModalTitle) {
            candidateModalTitle.textContent = 'Add Candidate Full Profile';
        }

        if (candidateSubmitBtn) {
            candidateSubmitBtn.textContent = 'Save Candidate';
        }

        const addedOn = candidateForm.elements.added_on;
        if (addedOn && !addedOn.value) {
            addedOn.value = new Date().toISOString().slice(0, 10);
        }

        const experienceType = candidateForm.elements.experience_type;
        if (experienceType) {
            experienceType.value = 'Fresher';
        }

        const status = candidateForm.elements.status;
        if (status) {
            status.value = 'Applied';
        }

        const source = candidateForm.elements.source;
        if (source) {
            source.value = 'Direct';
        }
    };

    if (candidateModal) {
        candidateModal.addEventListener('show.bs.modal', (event) => setCandidateFormMode(event.relatedTarget));
    }

    updateSelectionUI();
})();
