// public/assets/mobile.js

function toggleSidebar() {
    const sidebar = document.querySelector('aside');
    const overlay = document.getElementById('mobileSidebarOverlay');
    
    if (!sidebar) return;

    // Check if sidebar is currently showing based on translate class
    const isHidden = sidebar.classList.contains('translate-x-full');

    if (isHidden) {
        // Show Sidebar
        sidebar.classList.remove('translate-x-full');
        
        // Show Overlay
        if (overlay) {
            overlay.classList.remove('hidden');
            // Small delay to allow display:block to apply before changing opacity for transition
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
            }, 10);
        }
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    } else {
        // Hide Sidebar
        sidebar.classList.add('translate-x-full');
        
        // Hide Overlay
        if (overlay) {
            overlay.classList.add('opacity-0');
            setTimeout(() => {
                overlay.classList.add('hidden');
            }, 300); // Match transition duration
        }
        
        // Restore body scroll
        document.body.style.overflow = '';
    }
}

// Close sidebar on link click (optional, improves UX)
document.addEventListener('DOMContentLoaded', () => {
    const sidebarLinks = document.querySelectorAll('aside a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', () => {
             // Only close if we are on mobile (check if sidebar utilizes the mobile class)
             const sidebar = document.querySelector('aside');
             if (sidebar && window.innerWidth < 768) {
                 toggleSidebar();
             }
        });
    });
});
