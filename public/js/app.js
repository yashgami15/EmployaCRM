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

    const getFilteredUrl = (actionUrl) => {
        const params = new URLSearchParams(window.location.search);
        const urlObj = new URL(actionUrl, window.location.origin + (window.location.pathname.startsWith('/') ? '' : '/') + window.location.pathname);
        const action = urlObj.searchParams.get('action');
        if (action) params.set('action', action);
        return 'index.php?' + params.toString();
    };

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

        // Handle all export selected buttons (candidate, client, etc.)
        const exportBtns = [
            document.getElementById('exportSelectedBtn'),
            document.getElementById('exportClientSelectedBtn'),
            document.getElementById('exportInterviewSelectedBtn')
        ];

        exportBtns.forEach(btn => {
            if (btn) {
                const baseUrl = btn.getAttribute('data-base-url') || '#';
                if (selectedCount > 0) {
                    btn.classList.remove('disabled');
                    btn.href = `${baseUrl}&selected_ids=${encodeURIComponent(idsCsv)}`;
                } else {
                    btn.classList.add('disabled');
                    btn.href = '#';
                }
            }
        });

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

    const importClientCsvBtn = document.getElementById('importClientCsvBtn');
    const clientCsvFileInput = document.getElementById('clientCsvFileInput');
    const importClientCsvForm = document.getElementById('importClientCsvForm');

    if (importClientCsvBtn && clientCsvFileInput && importClientCsvForm) {
        importClientCsvBtn.addEventListener('click', () => clientCsvFileInput.click());

        clientCsvFileInput.addEventListener('change', () => {
            if (clientCsvFileInput.files && clientCsvFileInput.files.length > 0) {
                importClientCsvForm.submit();
            }
        });
    }

    const experienceTypeSelect = document.getElementById('experienceTypeSelect');
    function toggleExperience() {
        if (!experienceTypeSelect) return;
        const isExperienced = experienceTypeSelect.value === 'Experienced';
        document.querySelectorAll('.experienced-only').forEach(el => {
            el.style.display = isExperienced ? '' : 'none';
            if (!isExperienced) {
                const input = el.querySelector('input, select, textarea');
                if (input) input.value = '';
            }
        });
        
        // Also handle section titles if they have the class
        document.querySelectorAll('h6.section-title.experienced-only').forEach(el => {
            el.style.display = isExperienced ? '' : 'none';
        });
    }

    const candidateModal = document.getElementById('addCandidateModal');
    const candidateForm = document.getElementById('candidateProfileForm');
    const candidateIdInput = document.getElementById('candidateIdInput');
    const candidateModalTitle = document.getElementById('candidateModalTitle');
    const candidateSubmitBtn = document.getElementById('candidateSubmitBtn');
    const timelineSection = document.getElementById('timelineSection');
    const candidateTimeline = document.getElementById('candidateTimeline');

    async function fetchAndRenderTimeline(module, id, container, section) {
        if (!id || !container || !section) {
            if (section) section.style.display = 'none';
            return;
        }

        section.style.display = 'block';
        container.innerHTML = '<p class="text-secondary small mb-0 text-center py-3">Loading history...</p>';

        try {
            // First, log the "View" action so the timeline isn't empty
            await fetch(`index.php?action=log_view&module=${module}&id=${id}`);

            const response = await fetch(`index.php?action=get_timeline&module=${module}&id=${id}`);
            const data = await response.json();

            if (data.length === 0) {
                container.innerHTML = '<p class="text-secondary small mb-0 text-center py-3">No activity recorded yet.</p>';
                return;
            }

            container.innerHTML = data.map(item => `
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="d-flex justify-content-between align-items-start">
                        <strong>${item.action_title}</strong>
                        <span class="text-secondary small ms-2">${item.created_at}</span>
                    </div>
                    <p class="mb-0 text-dark-emphasis">${item.action_details || ''}</p>
                    <div class="text-secondary mt-1" style="font-size: 0.7rem;">By: ${item.created_by || 'System'}</div>
                </div>
            `).join('');
        } catch (error) {
            container.innerHTML = '<p class="text-danger small mb-0 text-center py-3">Failed to load history.</p>';
        }
    }

    const setCandidateFormMode = (trigger) => {
        if (!candidateForm || !trigger) return;
        const mode = trigger.getAttribute('data-candidate-mode') || 'add';
        candidateForm.reset();

        candidateForm.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = false;
        });

        // Clean up previous header actions
        let oldActions = candidateForm.closest('.modal-content').querySelector('.header-actions-group');
        if (oldActions) oldActions.remove();

        if (mode === 'edit' || mode === 'view') {
            const isView = mode === 'view';
            candidateForm.action = getFilteredUrl('index.php?action=update_candidate');

            if (candidateIdInput) candidateIdInput.value = trigger.getAttribute('data-candidate-id') || '';
            if (candidateModalTitle) candidateModalTitle.textContent = isView ? 'View Candidate Profile' : 'Edit Candidate Profile';

            if (candidateSubmitBtn) {
                candidateSubmitBtn.textContent = 'Update Candidate';
                candidateSubmitBtn.style.display = isView ? 'none' : '';
            }

            if (isView) {
                let headerActions = document.createElement('div');
                headerActions.className = 'header-actions-group d-flex gap-2 ms-3';
                
                let editBtnHeader = document.createElement('button');
                editBtnHeader.type = 'button';
                editBtnHeader.className = 'btn btn-primary btn-sm header-edit-btn';
                editBtnHeader.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
                editBtnHeader.onclick = () => {
                    trigger.setAttribute('data-candidate-mode', 'edit');
                    setCandidateFormMode(trigger);
                };
                
                let deleteBtnHeader = document.createElement('button');
                deleteBtnHeader.type = 'button';
                deleteBtnHeader.className = 'btn btn-danger btn-sm header-delete-btn';
                deleteBtnHeader.innerHTML = '<i class="bi bi-trash"></i> Delete';
                deleteBtnHeader.onclick = () => {
                    if (confirm('Are you sure you want to delete this record?')) {
                        const id = trigger.getAttribute('data-candidate-id');
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'post';
                        deleteForm.action = getFilteredUrl('index.php?action=delete_candidate');
                        deleteForm.innerHTML = `<input type="hidden" name="_csrf" value="${document.querySelector('input[name="_csrf"]').value}">
                                                <input type="hidden" name="selected_ids[]" value="${id}">`;
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();
                    }
                };
                
                headerActions.appendChild(editBtnHeader);
                headerActions.appendChild(deleteBtnHeader);
                candidateModalTitle.classList.add('me-auto');
                candidateModalTitle.parentNode.insertBefore(headerActions, candidateModalTitle.nextSibling);
            }

            Array.from(candidateForm.elements).forEach((field) => {
                if (!field.name || field.name === '_csrf' || field.name === 'candidate_id' || field.type === 'file') return;
                const dataKey = field.name.replace(/\[\]$/, '').replace(/_/g, '-');
                const value = trigger.getAttribute(`data-${dataKey}`);
                if (value === null) return;

                if (field.type === 'checkbox') {
                    const values = value.split(',').map((item) => item.trim()).filter(Boolean);
                    field.checked = values.includes(field.value);
                    field.disabled = isView;
                    return;
                }
                if (field.tagName === 'SELECT') field.disabled = isView;
                else {
                    field.readOnly = isView;
                    if (isView) field.classList.add('bg-light');
                    else field.classList.remove('bg-light');
                }
                field.value = value;
            });

            const resumePath = trigger.getAttribute('data-resume-path');
            const currentResumeDisplay = document.getElementById('currentResumeDisplay');
            const resumeFileInput = document.querySelector('input[name="resume_file"]');

            if (currentResumeDisplay && resumeFileInput) {
                if (resumePath) {
                    currentResumeDisplay.innerHTML = `<div class="d-flex align-items-center gap-2 mt-2">
                        <a href="${resumePath}" target="_blank" class="btn btn-sm btn-outline-info"><i class="bi bi-file-earmark-text"></i> View Current Document</a>
                        ${!isView ? `<button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('input[name=\\'resume_file\\']').style.display='block'; this.style.display='none';"><i class="bi bi-arrow-repeat"></i> Replace Resume</button>` : ''}
                    </div>`;
                    resumeFileInput.style.display = 'none';
                } else {
                    currentResumeDisplay.innerHTML = `<span class="text-secondary mt-1 d-block">No resume uploaded</span>`;
                    resumeFileInput.style.display = isView ? 'none' : 'block';
                }
            }

            if (typeof toggleExperience === 'function') toggleExperience();
            
            // Timeline for candidate
            fetchAndRenderTimeline('candidate', candidateIdInput.value, candidateTimeline, timelineSection);
            return;
        }

        candidateForm.action = getFilteredUrl('index.php?action=add_candidate');
        if (candidateIdInput) candidateIdInput.value = '';
        if (candidateModalTitle) candidateModalTitle.textContent = 'Add Candidate Full Profile';
        if (candidateSubmitBtn) {
            candidateSubmitBtn.textContent = 'Save Candidate';
            candidateSubmitBtn.style.display = '';
        }

        Array.from(candidateForm.elements).forEach((field) => {
            field.readOnly = false;
            field.disabled = false;
            field.classList.remove('bg-light');
        });

        const currentResumeDisplay = document.getElementById('currentResumeDisplay');
        if (currentResumeDisplay) currentResumeDisplay.innerHTML = '';
        
        const resumeFileInput = document.querySelector('input[name="resume_file"]');
        if (resumeFileInput) {
            resumeFileInput.style.display = 'block';
        }

        const addedOn = candidateForm.elements.added_on;
        if (addedOn && !addedOn.value) addedOn.value = new Date().toISOString().slice(0, 10);
        const experienceType = candidateForm.elements.experience_type;
        if (experienceType) experienceType.value = 'Fresher';
        const status = candidateForm.elements.status;
        if (status) status.value = 'Applied';
        const source = candidateForm.elements.source;
        if (source) source.value = 'Direct';

        if (typeof toggleExperience === 'function') toggleExperience();
        if (timelineSection) timelineSection.style.display = 'none';
    };

    if (candidateModal) {
        candidateModal.addEventListener('show.bs.modal', (event) => setCandidateFormMode(event.relatedTarget));
    }

    const setFormMode = (trigger, form, idInput, titleEl, submitBtn, entityName, updateAction, addAction) => {
        if (!form || !trigger) return;
        const mode = trigger.getAttribute(`data-${entityName}-mode`) || 'add';
        form.reset();
        
        let oldActions = form.closest('.modal-content').querySelector('.header-actions-group');
        if (oldActions) oldActions.remove();

        if (mode === 'edit' || mode === 'view') {
            const isView = mode === 'view';
            form.action = getFilteredUrl(updateAction);
            
            if (idInput) idInput.value = trigger.getAttribute(`data-${entityName}-id`) || '';
            if (titleEl) titleEl.textContent = isView ? `View ${entityName.charAt(0).toUpperCase() + entityName.slice(1)}` : `Edit ${entityName.charAt(0).toUpperCase() + entityName.slice(1)}`;
            
            if (submitBtn) {
                submitBtn.textContent = 'Save Changes';
                submitBtn.style.display = isView ? 'none' : '';
            }

            if (isView) {
                let headerActions = document.createElement('div');
                headerActions.className = 'header-actions-group d-flex gap-2 ms-3';
                
                let editBtnHeader = document.createElement('button');
                editBtnHeader.type = 'button';
                editBtnHeader.className = 'btn btn-primary btn-sm header-edit-btn';
                editBtnHeader.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
                editBtnHeader.onclick = () => {
                    trigger.setAttribute(`data-${entityName}-mode`, 'edit');
                    setFormMode(trigger, form, idInput, titleEl, submitBtn, entityName, updateAction, addAction);
                };
                
                let deleteBtnHeader = document.createElement('button');
                deleteBtnHeader.type = 'button';
                deleteBtnHeader.className = 'btn btn-danger btn-sm header-delete-btn';
                deleteBtnHeader.innerHTML = '<i class="bi bi-trash"></i> Delete';
                deleteBtnHeader.onclick = () => {
                    if (confirm('Are you sure you want to delete this record?')) {
                        const id = trigger.getAttribute(`data-${entityName}-id`);
                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'post';
                        deleteForm.action = getFilteredUrl(`index.php?action=delete_${entityName}`);
                        deleteForm.innerHTML = `<input type="hidden" name="_csrf" value="${document.querySelector('input[name="_csrf"]').value}">
                                                <input type="hidden" name="selected_ids[]" value="${id}">`;
                        document.body.appendChild(deleteForm);
                        deleteForm.submit();
                    }
                };
                
                headerActions.appendChild(editBtnHeader);
                headerActions.appendChild(deleteBtnHeader);
                titleEl.classList.add('me-auto');
                titleEl.parentNode.insertBefore(headerActions, titleEl.nextSibling);
            }

            Array.from(trigger.attributes).forEach(attr => {
                if (attr.name.startsWith(`data-${entityName}-`) && attr.name !== `data-${entityName}-id` && attr.name !== `data-${entityName}-mode`) {
                    const fieldName = attr.name.replace(`data-${entityName}-`, '').replace(/-/g, '_');
                    const value = attr.value;
                    let field = form.querySelector(`[name="${fieldName}"]`) || form.querySelector(`[name="${fieldName}[]"]`);
                    
                    if (field) {
                        if (field.tagName === 'SELECT') {
                            field.disabled = isView;
                        } else {
                            field.readOnly = isView;
                            if (isView) field.classList.add('bg-light');
                            else field.classList.remove('bg-light');
                        }
                        field.value = value;
                    }
                }
            });

            // Timeline for client or other entity
            const timelineContainer = form.querySelector(`#${entityName}Timeline`);
            const timelineSect = form.querySelector(`#${entityName}TimelineSection`);
            if (timelineContainer && timelineSect) {
                fetchAndRenderTimeline(entityName, idInput.value, timelineContainer, timelineSect);
            }
        } else {
            form.action = getFilteredUrl(addAction);
            if (idInput) idInput.value = '';
            if (titleEl) titleEl.textContent = `Add ${entityName.charAt(0).toUpperCase() + entityName.slice(1)}`;
            if (submitBtn) {
                submitBtn.textContent = 'Save';
                submitBtn.style.display = '';
            }

            Array.from(form.elements).forEach((field) => {
                field.readOnly = false;
                field.disabled = false;
                field.classList.remove('bg-light');
            });

            const timelineSect = form.querySelector(`#${entityName}TimelineSection`);
            if (timelineSect) timelineSect.style.display = 'none';
        }
    };

    const clientModal = document.getElementById('addClientModal');
    const clientForm = clientModal ? clientModal.querySelector('form') : null;
    if (clientModal && clientForm) {
        clientModal.addEventListener('show.bs.modal', (event) => {
            const titleEl = clientModal.querySelector('.modal-title');
            const submitBtn = clientForm.querySelector('button[type="submit"]');
            const idInput = document.getElementById('clientIdInput');
            const trigger = event.relatedTarget;
            
            const vacanciesContainer = document.getElementById('vacanciesContainer');
            if (vacanciesContainer) {
                const items = vacanciesContainer.querySelectorAll('.vacancy-item');
                for (let i = 1; i < items.length; i++) {
                    items[i].remove();
                }
            }
            
            const addVacancyBtn = document.getElementById('addVacancyBtn');
            if (addVacancyBtn && trigger) {
                const mode = trigger.getAttribute('data-client-mode') || 'add';
                addVacancyBtn.style.display = (mode === 'edit' || mode === 'view') ? 'none' : 'block';
            }

            setFormMode(trigger, clientForm, idInput, titleEl, submitBtn, 'client', 'index.php?action=update_client', 'index.php?action=add_client');

            // Auto-generate job code if in add mode
            const mode = trigger ? trigger.getAttribute('data-client-mode') : 'add';
            if ((!mode || mode === 'add') && clientForm.elements.job_code) {
                const randCode = 'JOB-' + Math.floor(1000 + Math.random() * 9000);
                clientForm.elements.job_code.value = randCode;
            }
        });

        const addVacancyBtn = document.getElementById('addVacancyBtn');
        const vacanciesContainer = document.getElementById('vacanciesContainer');
        if (addVacancyBtn && vacanciesContainer) {
            addVacancyBtn.addEventListener('click', () => {
                const items = vacanciesContainer.querySelectorAll('.vacancy-item');
                if (items.length > 0) {
                    const clone = items[0].cloneNode(true);
                    clone.querySelectorAll('input').forEach(input => {
                        if(input.type === 'number') input.value = '0';
                        else input.value = '';
                    });
                    clone.querySelectorAll('select').forEach(select => {
                        select.selectedIndex = 0;
                    });
                    const removeBtn = clone.querySelector('.remove-vacancy-btn');
                    if (removeBtn) {
                        removeBtn.style.display = 'block';
                        removeBtn.onclick = function() {
                            clone.remove();
                        };
                    }
                    vacanciesContainer.appendChild(clone);
                }
            });
        }
    }

    const interviewModal = document.getElementById('addInterviewModal');
    const interviewForm = interviewModal ? interviewModal.querySelector('form') : null;
    if (interviewModal && interviewForm) {
        interviewModal.addEventListener('show.bs.modal', (event) => {
            const titleEl = interviewModal.querySelector('.modal-title');
            const submitBtn = interviewForm.querySelector('button[type="submit"]');
            const idInput = document.getElementById('interviewIdInput');
            setFormMode(event.relatedTarget, interviewForm, idInput, titleEl, submitBtn, 'interview', 'index.php?action=update_interview', 'index.php?action=add_interview');
        });
    }

    if (experienceTypeSelect) {
        experienceTypeSelect.addEventListener('change', toggleExperience);
    }

    // Row clicks open view modal instead of immediately clicking the button
    document.querySelectorAll('.table-candidates tbody tr').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('button, a, input, select, form')) return;
            let editBtn = row.querySelector('.candidate-edit-btn') || row.querySelector('.client-edit-btn') || row.querySelector('.interview-edit-btn');
            if (editBtn) {
                const clone = editBtn.cloneNode(true);
                const entity = clone.classList.contains('candidate-edit-btn') ? 'candidate' : (clone.classList.contains('client-edit-btn') ? 'client' : 'interview');
                clone.setAttribute(`data-${entity}-mode`, 'view');
                document.body.appendChild(clone);
                clone.click();
                clone.remove();
            }
        });
        row.style.cursor = 'pointer';
    });

    // Auto-submit filter forms
    const filterForms = document.querySelectorAll('form[method="get"][action="index.php"]');
    filterForms.forEach(form => {
        const inputs = form.querySelectorAll('input[name="search"], select[name="status"], select[name="source"], select[name="stage"], input[name="date"]');
        inputs.forEach(input => {
            if (input.tagName === 'SELECT' || (input.tagName === 'INPUT' && input.type === 'date')) {
                input.addEventListener('change', () => form.submit());
            } else if (input.tagName === 'INPUT' && input.type === 'text') {
                let debounceTimer;
                input.addEventListener('input', () => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => form.submit(), 600);
                });
            }
        });
    });

    updateSelectionUI();

    window.syncReminders = function(btn) {
        const icon = btn.querySelector('i');
        icon.classList.add('bi-spin');
        btn.disabled = true;
        
        fetch('cron-reminders.php')
            .then(r => r.text())
            .then(data => {
                alert('Reminders processed: ' + data);
                location.reload();
            })
            .catch(e => alert('Error syncing reminders'))
            .finally(() => {
                icon.classList.remove('bi-spin');
                btn.disabled = false;
            });
    };

    let lastNotifCount = 0;
    function checkNotifications() {
        fetch('index.php?action=read_notification&check=1')
            .then(r => r.json())
            .then(data => {
                const badge = document.querySelector('.notif-dot');
                if (badge) {
                    const count = parseInt(data.unread_count || 0);
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-flex' : 'none';
                    
                    if (count > lastNotifCount && lastNotifCount !== 0) {
                        playNotificationSound();
                    }
                    lastNotifCount = count;
                }
            })
            .catch(e => console.log('Notif check failed'));
    }

    function playNotificationSound() {
        try {
            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
            audio.play();
        } catch (e) { }
    }

    setInterval(checkNotifications, 30000); // 30s
    checkNotifications();
})();

function logContactActivity(event, module, id, type, url) {
    event.preventDefault();
    const formData = new FormData();
    formData.append('module', module);
    formData.append('id', id);
    formData.append('type', type);

    // Get CSRF token if exists, though not strictly required for this simple action
    const csrfInput = document.querySelector('input[name="_csrf"]');
    if (csrfInput) {
        formData.append('_csrf', csrfInput.value);
    }

    fetch('index.php?action=log_activity_ajax', {
        method: 'POST',
        body: formData
    }).finally(() => {
        window.location.href = url;
    });
}
