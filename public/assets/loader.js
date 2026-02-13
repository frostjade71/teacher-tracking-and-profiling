// public/assets/loader.js

document.addEventListener("DOMContentLoaded", () => {
    const loader = document.querySelector('.loader-container');

    const hideLoader = () => {
        if (loader) {
            setTimeout(() => {
                loader.classList.add('loader-hidden');
            }, 500); // Small delay for smooth transition
        }
    };

    // Hide loader when page is fully loaded
    window.addEventListener('load', hideLoader);

    // Also hide loader when page is restored from bfcache
    window.addEventListener('pageshow', (event) => {
        // event.persisted is true if the page was restored from the bfcache
        if (event.persisted || loader) {
            hideLoader();
        }
    });

    // Show loader when navigating to other pages
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');

            // Check if it's a modifier click (new tab/window)
            if (e.ctrlKey || e.shiftKey || e.metaKey || e.button === 1) {
                return;
            }

            // Only show for internal navigation (not # links or external)
            if (href && !href.startsWith('#') && !href.startsWith('javascript') && loader) {
                // Check if target is _blank
                if (link.target === '_blank') {
                    return;
                }
                loader.classList.remove('loader-hidden');
            }
        });
    });
});
