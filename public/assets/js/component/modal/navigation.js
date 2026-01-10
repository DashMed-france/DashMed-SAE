document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.card');
    const modalDetails = document.getElementById('modalDetails');

    const modal = document.querySelector('.modal');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const slug = card.getAttribute('data-slug');
            const detailId = `detail-${slug}`;
            const sourceDetail = document.getElementById(detailId);

            if (sourceDetail && modalDetails) {
                modalDetails.innerHTML = sourceDetail.innerHTML;

                const chartConfigJson = card.getAttribute('data-chart');
                if (chartConfigJson) {
                    try {
                        const config = JSON.parse(chartConfigJson);

                        const canvas = modalDetails.querySelector('canvas');
                        if (canvas) {
                            canvas.id = config.target;

                            if (typeof updatePanelChart === 'function') {
                                updatePanelChart(`panel-${slug}`, config.target, config.title);
                            } else if (typeof createChart === 'function') {
                                createChart(
                                    config.type,
                                    config.title,
                                    config.labels,
                                    config.data,
                                    config.target,
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
