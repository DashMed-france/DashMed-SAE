/**
 * Initializes modal navigation interactions.
 * Listens for clicks on dashboard cards to open their respective detailed modals,
 * replacing the modal's internal HTML and initializing Chart.js visualizations.
 */
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.card');
    const modalDetails = document.getElementById('modalDetails');

    const modal = document.querySelector('.modal');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const slug = card.getAttribute('data-slug');
            const detailId = card.getAttribute('data-detail-id') || `detail-${slug}`;
            const sourceDetail = document.getElementById(detailId);

            if (sourceDetail && modalDetails) {
                // Prevent memory leaks by properly destroying existing chart instances
                const existingCanvases = modalDetails.querySelectorAll('canvas');
                existingCanvases.forEach(canvas => {
                    if (canvas.chartInstance) {
                        canvas.chartInstance.destroy();
                        canvas.chartInstance = null;
                    }
                });

                modalDetails.innerHTML = sourceDetail.innerHTML;

                const chartConfigJson = card.getAttribute('data-chart');
                if (chartConfigJson) {
                    try {
                        const config = JSON.parse(chartConfigJson);

                        const canvas = modalDetails.querySelector('canvas');
                        if (canvas) {
                            const canvasId = canvas.dataset.id || config.target;
                            canvas.id = canvasId;

                            const panelId = detailId.replace('detail-', 'panel-');
                            if (typeof updatePanelChart === 'function') {
                                updatePanelChart(panelId, canvasId, config.title);
                            } else if (typeof createChart === 'function') {
                                createChart(
                                    config.type,
                                    config.title,
                                    config.labels,
                                    config.data,
                                    canvasId,
                                    config.color,
                                    config.thresholds,
                                    config.view
                                );
                            }
                        }
                    } catch (e) {
                        console.error("Error parsing chart config", e);
                    }
                }
            }

            if (typeof toggleModal === 'function') {
                if (!modal.classList.contains('show-modal')) {
                    toggleModal();
                }
            } else {
                modal.classList.add('show-modal');
            }
        });
    });
});
