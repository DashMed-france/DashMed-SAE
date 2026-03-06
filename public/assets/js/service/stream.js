(function () {
    const patientIdEl = document.getElementById('context-patient-id');
    const patientId = patientIdEl ? patientIdEl.value : '';
    const streamUrl = patientId ? `/api_stream?id_patient=${patientId}` : '/api_stream';
    let source = new EventSource(streamUrl);

    source.onmessage = function (event) {
        try {
            const metrics = JSON.parse(event.data);

            // Dispatch a custom event on the window object with the metrics data
            const customEvent = new CustomEvent('DashMedMetricsUpdate', {
                detail: metrics
            });
            window.dispatchEvent(customEvent);

        } catch (e) {
            console.error('DashMed SSE Global fetch error:', e);
        }
    };

    source.onerror = function () {
        console.warn("DashMed Global SSE connection lost. Reconnecting...");
        // EventSource automatically attempts to reconnect, so we mostly just log it.
    };
})();
