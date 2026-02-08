<?php
// app/partials/theme_toggle.php
?>
<div title="Toggle Theme" class="flex items-center">
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
