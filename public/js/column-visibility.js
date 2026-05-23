document.addEventListener('DOMContentLoaded', () => {
    const tableWraps = document.querySelectorAll('.data-table-wrap');
    
    // Get current module for storage key
    const urlParams = new URLSearchParams(window.location.search);
    const module = urlParams.get('action') || 'candidate';
    const storageKey = `hide-cols-${module}`;
    
    // Load saved preferences
    let hiddenCols = [];
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved) hiddenCols = JSON.parse(saved);
    } catch(e) {}

    tableWraps.forEach(wrap => {
        const table = wrap.querySelector('table');
        if (!table) return;

        const controlsContainer = wrap.previousElementSibling;
        if (!controlsContainer || !controlsContainer.classList.contains('d-flex')) return;

        const thead = table.querySelector('thead');
        if (!thead) return;

        const headers = thead.querySelectorAll('th');

        const dropdownHtml = `
            <div class="dropdown column-visibility-dropdown ms-3">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                    <i class="bi bi-layout-three-columns"></i> Columns
                </button>
                <div class="dropdown-menu shadow p-3" style="max-height: 400px; overflow-y: auto; width: 250px;">
                    <h6 class="dropdown-header px-0 text-dark">Toggle Columns</h6>
                    <div class="column-list"></div>
                </div>
            </div>
        `;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = dropdownHtml;
        const dropdownEl = tempDiv.firstElementChild;

        const columnList = dropdownEl.querySelector('.column-list');

        headers.forEach((th, index) => {
            const headerText = th.textContent.trim();
            if (!headerText || headerText === 'Action' || th.querySelector('input')) return;

            const isHidden = hiddenCols.includes(index);
            
            const itemHtml = `
                <div class="form-check mb-1">
                    <input class="form-check-input toggle-col-btn" type="checkbox" id="col-toggle-${index}" data-col-index="${index}" ${!isHidden ? 'checked' : ''}>
                    <label class="form-check-label" for="col-toggle-${index}" style="font-size: 0.9rem;">
                        ${headerText}
                    </label>
                </div>
            `;
            
            const itemDiv = document.createElement('div');
            itemDiv.innerHTML = itemHtml;
            columnList.appendChild(itemDiv.firstElementChild);
            
            // Apply initial hidden state
            if (isHidden) {
                th.style.display = 'none';
            }
        });

        // Apply initial hidden state to rows
        if (hiddenCols.length > 0) {
            const tbody = table.querySelector('tbody');
            if(tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const cells = row.querySelectorAll('td, th');
                    hiddenCols.forEach(colIndex => {
                        if (cells.length > colIndex) {
                            cells[colIndex].style.display = 'none';
                        }
                    });
                });
            }
        }

        const msAutoElement = controlsContainer.querySelector('.ms-auto');
        if (msAutoElement) {
            controlsContainer.insertBefore(dropdownEl, msAutoElement);
        } else {
            controlsContainer.appendChild(dropdownEl);
        }

        const toggleBtns = dropdownEl.querySelectorAll('.toggle-col-btn');
        toggleBtns.forEach(btn => {
            btn.addEventListener('change', (e) => {
                const colIndex = parseInt(e.target.dataset.colIndex, 10);
                const show = e.target.checked;
                
                headers[colIndex].style.display = show ? '' : 'none';
                
                const tbody = table.querySelector('tbody');
                if(tbody) {
                    const rows = tbody.querySelectorAll('tr');
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td, th');
                        // Ensure we don't crash if row has colspans
                        if (cells.length > colIndex && !cells[0].hasAttribute('colspan')) {
                            cells[colIndex].style.display = show ? '' : 'none';
                        }
                    });
                }
                
                // Save preference
                if (!show) {
                    if (!hiddenCols.includes(colIndex)) hiddenCols.push(colIndex);
                } else {
                    hiddenCols = hiddenCols.filter(i => i !== colIndex);
                }
                localStorage.setItem(storageKey, JSON.stringify(hiddenCols));
            });
        });
    });
});
