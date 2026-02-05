// public/assets/loader.js

document.addEventListener("DOMContentLoaded", () => {
    const loader = document.querySelector('.loader-container');
    
    // Hide loader when page is fully loaded
    window.addEventListener('load', () => {
        if (loader) {
            setTimeout(() => {
                loader.classList.add('loader-hidden');
            }, 500); // Small delay for smooth transition
        }
    });

    // Show loader when navigating to other pages
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            // Only show for internal navigation (not # links or external)
            if (href && !href.startsWith('#') && !href.startsWith('javascript') && loader) {
                loader.classList.remove('loader-hidden');
            }
        });
    });
});
