<!-- Shared Admin Mobile Header -->
<!-- app/partials/admin_mobile_header.php -->
<header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-4 md:hidden sticky top-0 z-40">
    <div class="flex items-center gap-2">
        <img src="/assets/favicon/web-app-manifest-512x512.png" alt="Logo" class="w-8 h-8 rounded-lg">
        <span class="font-bold text-slate-800 dark:text-white truncate">FacultyLink</span>
    </div>
    
    <div class="flex items-center gap-3">
         <!-- Theme Toggle Mobile -->
        <!-- Theme Toggle Mobile -->
        <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>

        <button onclick="toggleSidebar()" class="p-2 -mr-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>
</header>

<!-- Mobile Sidebar Overlay -->
<div id="mobileSidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

<!-- Mobile Script -->
<script src="/assets/mobile.js"></script>
