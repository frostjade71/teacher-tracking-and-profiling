// public/assets/toast.js

class Toast {
  static init() {
    if (!document.getElementById("toast-container")) {
      const container = document.createElement("div");
      container.id = "toast-container";
      document.body.appendChild(container);
    }
  }

  static show(message, type = "info", duration = 3000) {
    this.init();
    const container = document.getElementById("toast-container");

    const toast = document.createElement("div");
    toast.className = `toast ${type}`;

    // Icon handling
    let iconHtml = "";
    if (type === "success") {
      iconHtml =
        '<svg class="toast-icon text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
    } else if (type === "error") {
      iconHtml =
        '<svg class="toast-icon text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
    } else {
      iconHtml =
        '<svg class="toast-icon text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
    }

    toast.innerHTML = `${iconHtml}<span>${message}</span>`;
    container.appendChild(toast);

    // Trigger reflow
    void toast.offsetWidth;

    // Show
    requestAnimationFrame(() => {
      toast.classList.add("show");
    });

    // Auto remove
    setTimeout(() => {
      toast.classList.remove("show");
      toast.addEventListener("transitionend", () => {
        toast.remove();
      });
    }, duration);
  }
}

// Global helper
window.showToast = (message, type = "info") => Toast.show(message, type);
