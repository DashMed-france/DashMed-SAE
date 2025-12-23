document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.card');
    const modalDetails = document.getElementById('modalDetails');
    // We assume 'modal' variable is available from modal.js or we select it again
    const modal = document.querySelector('.modal');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            const slug = card.getAttribute('data-slug');
            const detailId = `detail-${slug}`;
            const sourceDetail = document.getElementById(detailId);

            if (sourceDetail && modalDetails) {
                // 1. Copy content
                modalDetails.innerHTML = sourceDetail.innerHTML;

                // 2. Initialize Chart if present
                // The card has the config in 'data-chart'
                const chartConfigJson = card.getAttribute('data-chart');
                if (chartConfigJson) {
                    try {
                        const config = JSON.parse(chartConfigJson);
                        // The canvas ID in the modal might need to match what createChart expects
                        // config.target is 'modal-chart-slug'
                        // We need to ensure the canvas in the modal has this ID.
                        // The sourceDetail had <canvas data-id="modal-chart-slug">
                        // We should probably explicitly set the ID on the new canvas instance in the modal
                        const canvas = modalDetails.querySelector('canvas');
                        if (canvas) {
                            canvas.id = config.target;

                            // Call createChart from chart.js
                            if (typeof createChart === 'function') {
                                createChart(
                                    config.type,
                                    config.title,
                                    config.labels, // createChart reverses them
                                    config.data,   // createChart reverses them
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

                // 3. Open Modal
                if (typeof toggleModal === 'function') {
                    // Check if not already open
                    if (!modal.classList.contains('show-modal')) {
                        toggleModal();
                    }
                } else {
                    modal.classList.add('show-modal');
                }
            }
        });
    });
});
