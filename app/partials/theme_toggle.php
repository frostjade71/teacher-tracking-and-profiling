<?php
// app/partials/theme_toggle.php
?>
<div title="Toggle Theme" class="flex items-center gap-3">
    <!-- Info Button -->
    <?php if (!isset($hideInfoIcon) || !$hideInfoIcon): ?>
    <button onclick="const m = document.getElementById('infoModal'); if(m) m.showModal()" class="flex items-center justify-center w-8 h-8 rounded-full hover:bg-gray-100 dark:hover:bg-slate-700/50 transition-colors text-slate-500 dark:text-slate-400 hover:text-blue-500 dark:hover:text-blue-400" title="System Info">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"></path>
        </svg>
    </button>
    <div class="w-px h-4 bg-gray-200 dark:border-slate-700"></div>
    <?php endif; ?>
    <input type="checkbox" class="theme-checkbox" id="themeToggleCheckbox" onchange="window.toggleTheme()">
</div>

<script>
    // Initialize checkbox state based on current theme
    document.addEventListener('DOMContentLoaded', () => {
        const checkbox = document.getElementById('themeToggleCheckbox');
        if (checkbox) {
            // Check if dark mode is active
            if (document.documentElement.classList.contains('dark')) {
                checkbox.checked = false; // Dark mode = unchecked (moon) based on CSS
            } else {
                checkbox.checked = true; // Light mode = checked (sun)
            }

            // Listen for theme changes from other tabs/toggles
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        checkbox.checked = !isDark;
                    }
                });
            });
            observer.observe(document.documentElement, { attributes: true });
        }
    });
</script>
