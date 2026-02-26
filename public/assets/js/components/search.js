document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('global-search-input');
    const resultsContainer = document.getElementById('search-results');
    let debounceTimer = null;

    if (!searchInput || !resultsContainer) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        clearTimeout(debounceTimer);

        if (query.length < 2) {
            hideResults();
            return;
        }

        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
            hideResults();
        }
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length >= 2 && resultsContainer.innerHTML !== '') {
            showResults();
        }
    });

    async function performSearch(query) {
        try {
            let url = `/?page=api_search&q=${encodeURIComponent(query)}`;
            const patientId = getCurrentPatientId();
            if (patientId) {
                url += `&patient_id=${patientId}`;
            }

            const response = await fetch(url);
            if (!response.ok) throw new Error('Erreur réseau');

            const data = await response.json();
            renderResults(data.results);
        } catch (error) {
            console.error('Erreur recherche:', error);
            resultsContainer.innerHTML = '<div class="search-message error">Une erreur est survenue.</div>';
            showResults();
        }
    }

    function getCurrentPatientId() {
        const contextInput = document.getElementById('context-patient-id');
        if (contextInput && contextInput.value) {
            return contextInput.value;
        }

        const params = new URLSearchParams(window.location.search);

        if (params.has('id')) {
            return params.get('id');
        }

        if (params.has('id_patient')) {
            return params.get('id_patient');
        }

        return null;
    }

    function renderResults(results) {
        if (!results || (datasetIsEmpty(results))) {
            resultsContainer.innerHTML = '<div class="search-message">Aucun résultat trouvé.</div>';
            showResults();
            return;
        }

        let html = '';

        if (results.patients && results.patients.length > 0) {
            html += '<div class="search-category">Patients</div>';
            results.patients.forEach(p => {
                const href = p.room_id
                    ? `/?page=dashboard&room=${p.room_id}`
                    : `/?page=dossierpatient&id=${p.id_patient}`;
                html += `
                    <a href="${href}" class="search-item">
                        <div class="item-icon patient">
                             <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                             <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                             <circle cx="12" cy="7" r="4"></circle>
                             </svg>
                        </div>
                        <div class="item-details">
                            <span class="item-title">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</span>
                            <span class="item-subtitle">Né(e) le ${formatDate(p.birth_date)}</span>
                        </div>
                    </a>
                `;
            });
        }

        if (results.doctors && results.doctors.length > 0) {
            html += '<div class="search-category">Médecins</div>';
            results.doctors.forEach(d => {
                const currentPid = getCurrentPatientId();
                const docLink = currentPid ? `/?page=dossierpatient&id=${currentPid}#doctor-${d.id_user}` : null;
                const cssClass = docLink ? "search-item" : "search-item non-clickable";
                const wrapperStart = docLink ? `<a href="${docLink}" class="${cssClass}">` : `<div class="${cssClass}">`;
                const wrapperEnd = docLink ? `</a>` : `</div>`;

                html += `
                    ${wrapperStart}
                        <div class="item-icon doctor">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                        </div>
                        <div class="item-details">
                            <span class="item-title">Dr. ${escapeHtml(d.last_name)} ${escapeHtml(d.first_name)}</span>
                            <span class="item-subtitle">${escapeHtml(d.profession || 'Médecin')}</span>
                        </div>
                    ${wrapperEnd}
                `;
            });
        }

        if (results.parameter && results.parameter.length > 0) {
            html += '<div class="search-category">Indicateurs</div>';
            results.parameter.forEach(i => {
                html += `
                    <a href="/?page=monitoring&id=${i.id_patient}#indicateurs-${i.id_parameter}" class="search-item">
                        <div class="item-icon indicateur">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"> <line x1="18" y1="20" x2="18" y2="10"></line> <line x1="12" y1="20" x2="12" y2="4"></line> <line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        </div>
                        <div class="item-details">
                            <span class="item-title">${escapeHtml(i.display_name)}</span>
                            <span class="item-subtitle"> Category: ${escapeHtml(i.category)} • Descriptif: ${escapeHtml(i.description)} </span>
                        </div>
                    </a>
                `;
            })
        }

        if (results.consultations && results.consultations.length > 0) {
            html += '<div class="search-category">Consultations</div>';
            results.consultations.forEach(c => {
                html += `
                    <a href="/?page=medicalprocedure&id=${c.id_patient}#consultation-${c.id_consultation}" class="search-item">
                        <div class="item-icon consultation">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        </div>
                        <div class="item-details">
                            <span class="item-title">${escapeHtml(c.title || c.type)}</span>
                            <span class="item-subtitle">Dr. ${escapeHtml(c.doc_name)} • ${formatDate(c.date)} • Patient: ${escapeHtml(c.p_last)} ${escapeHtml(c.p_first)}</span>
                        </div>
                    </a>
                `;
            });
        }

        resultsContainer.innerHTML = html;
        showResults();
    }

    function showResults() {
        resultsContainer.classList.remove('hidden');
        resultsContainer.classList.add('visible');
    }

    function hideResults() {
        resultsContainer.classList.remove('visible');
        resultsContainer.classList.add('hidden');
    }

    function datasetIsEmpty(results) {
        return (!results.patients || results.patients.length === 0) &&
            (!results.doctors || results.doctors.length === 0) &&
            (!results.parameter || results.parameter.length === 0) &&
            (!results.consultations || results.consultations.length === 0);
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/[&<>"']/g, function (m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[m];
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        return d.toLocaleDateString('fr-FR');
    }
});