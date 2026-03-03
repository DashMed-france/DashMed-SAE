const SkeletonLoader = {
    reveal(skeletonId, contentId, delay) {
        const ms = delay || 0;
        const skeleton = document.getElementById(skeletonId);
        const content = document.getElementById(contentId);

        if (!skeleton || !content) return;

        content.style.display = 'none';

        const doReveal = function () {
            skeleton.style.display = 'none';
            content.style.display = '';
        };

        if (ms > 0) {
            setTimeout(doReveal, ms);
        } else {
            doReveal();
        }
    },

    revealAll(delay) {
        const ms = delay || 0;
        const skeletons = document.querySelectorAll('[data-skeleton-for]');

        skeletons.forEach(function (skeleton) {
            const contentId = skeleton.getAttribute('data-skeleton-for');
            const content = document.getElementById(contentId);
            if (!content) return;

            content.style.display = 'none';
        });

        const doReveal = function () {
            skeletons.forEach(function (skeleton) {
                const contentId = skeleton.getAttribute('data-skeleton-for');
                var content = document.getElementById(contentId);
                if (!content) return;

                skeleton.style.display = 'none';
                content.style.display = '';
            });
        };

        if (ms > 0) {
            setTimeout(doReveal, ms);
        } else {
            doReveal();
        }
    },

    init() {
        document.addEventListener('DOMContentLoaded', function () {
            var skeletons = document.querySelectorAll('[data-skeleton-for]');
            skeletons.forEach(function (skeleton) {
                var contentId = skeleton.getAttribute('data-skeleton-for');
                var content = document.getElementById(contentId);
                if (content) {
                    content.style.display = 'none';
                }
            });
        });

        window.addEventListener('load', function () {
            var autoSkeletons = document.querySelectorAll('[data-skeleton-auto]');
            autoSkeletons.forEach(function (skeleton) {
                var contentId = skeleton.getAttribute('data-skeleton-for');

                if (contentId) {
                    // We drop the forced delay to reveal exactly when loading formally finishes
                    SkeletonLoader.reveal(skeleton.id, contentId, 0);
                }
            });
        });
    }
};

SkeletonLoader.init();
