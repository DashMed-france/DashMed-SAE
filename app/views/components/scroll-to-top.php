<?php

/**
 * Composant Bouton Retour en haut
 *
 * Affiche un bouton flottant permettant de revenir en haut de page
 * lorsqu'on défile vers le bas.
 */

?>
<button id="scrollToTopBtn" aria-label="Retour en haut" title="Retour en haut">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round">
        <path d="M12 19V5M5 12l7-7 7 7" />
    </svg>
</button>

<style>
    #scrollToTopBtn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 9999;
        /* Hidden by default */
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);

        width: 50px;
        height: 50px;
        border-radius: 50%;
        border: none;
        background-color: var(--primary-color, #007bff);
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease-in-out;
    }

    #scrollToTopBtn:hover {
        background-color: var(--primary-color-dark, #0056b3);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }

    #scrollToTopBtn.visible {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    /* Reduce motion preference support */
    @media (prefers-reduced-motion: reduce) {
        #scrollToTopBtn {
            transition: none;
        }

        html {
            scroll-behavior: auto !important;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const scrollTopBtn = document.getElementById('scrollToTopBtn');

        // Show button after scrolling down 300px
        const toggleVisibility = () => {
            if (window.scrollY > 300) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
                // Remove hover effect reset when hiding
                scrollTopBtn.style.transform = '';
            }
        };

        // Scroll to top smooth
        const scrollToTop = () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        };

        window.addEventListener('scroll', toggleVisibility);
        scrollTopBtn.addEventListener('click', scrollToTop);
    });
</script>
