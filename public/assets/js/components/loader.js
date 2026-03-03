const hideDashMedLoader = () => {
    const loader = document.getElementById('dashmed-loader');
    if (loader) {
        loader.classList.add('hidden');
        setTimeout(() => {
            loader.style.display = 'none';
        }, 500);
    }
};

if (document.readyState === 'complete') {
    hideDashMedLoader();
} else {
    window.addEventListener('load', hideDashMedLoader);
}
