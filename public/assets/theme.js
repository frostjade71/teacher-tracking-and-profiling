/**
 * public/assets/theme.js
 * Handles Light/Dark mode toggling and persistence.
 */

const themeModule = (() => {
  function getStoredTheme() {
    return localStorage.getItem("theme");
  }

  function setStoredTheme(theme) {
    localStorage.setItem("theme", theme);
  }

  function getPreferredTheme() {
    const stored = getStoredTheme();
    if (stored) {
      return stored;
    }
    return window.matchMedia("(prefers-color-scheme: dark)").matches
      ? "dark"
      : "light";
  }

  function applyTheme() {
    const theme = getPreferredTheme();
    if (theme === "dark") {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  }

  function toggleTheme() {
    const current = getPreferredTheme();
    const next = current === "dark" ? "light" : "dark";
    setStoredTheme(next);
    applyTheme();
  }

  // Initialize on load
  applyTheme();

  return {
    toggle: toggleTheme,
    init: applyTheme,
  };
})();

// Expose globally
window.toggleTheme = themeModule.toggle;
