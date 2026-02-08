<header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 md:hidden sticky top-0 z-40">
    <div class="flex items-center gap-2">
        <img src="<?= url('assets/favicon/web-app-manifest-512x512.png') ?>" alt="Logo" class="w-8 h-8 rounded-lg">
        <span class="font-bold text-slate-800 dark:text-white">FacultyLink</span>
    </div>

    <div class="flex items-center gap-4">
            <!-- Theme Toggle Mobile -->
            <!-- Theme Toggle Mobile -->
        <?php include __DIR__ . '/theme_toggle.php'; ?>

        <!-- Hamburger Menu -->
        <button onclick="toggleSidebar()" class="text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 focus:outline-none">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>
</header>

<!-- Mobile Sidebar Overlay -->
<div id="mobileSidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>
