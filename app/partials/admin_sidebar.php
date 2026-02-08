<!-- Shared Admin Sidebar Partial -->
<!-- app/partials/admin_sidebar.php -->
<?php
$current_page = $_GET['page'] ?? 'admin_dashboard';
$u = current_user();

function isActive($page_name) {
    global $current_page;
    return $current_page === $page_name ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white';
}

function iconColor($page_name) {
    global $current_page;
    return $current_page === $page_name ? 'text-blue-200' : 'text-slate-400 group-hover:text-white';
}
?>

<aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex-shrink-0 fixed md:static inset-y-0 right-0 z-50 transform translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out border-l md:border-r border-slate-800 shadow-xl md:shadow-none rounded-tl-2xl rounded-bl-2xl md:rounded-none">
    <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
        <img src="<?= url('assets/favicon/web-app-manifest-512x512.png') ?>" class="w-7 h-7 rounded-lg hidden md:block" alt="Logo" style="width: 28px; height: 28px;">
        <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Admin</span></span>
    </div>
    
    <nav class="flex-1 px-3 py-6 space-y-1">
        <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Main
        </div>
        <a href="<?= url('?page=admin_dashboard') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_dashboard') ?>">
             <svg class="w-5 h-5 mr-3 <?= iconColor('admin_dashboard') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        
        <a href="<?= url('?page=admin_analytics') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_analytics') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_analytics') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            Analytics
        </a>
        
        <a href="<?= url('?page=admin_monitor') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_monitor') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_monitor') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Live Campus Map
        </a>

        <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">
            Management
        </div>
        
        <a href="<?= url('?page=admin_campus_radar') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_campus_radar') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_campus_radar') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Campus Radar
        </a>

        <a href="<?= url('?page=admin_teachers') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_teachers') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_teachers') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Teachers
        </a>
        
        <a href="<?= url('?page=admin_students') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_students') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_students') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            Students
        </a>

        <a href="<?= url('?page=admin_admins') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_admins') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_admins') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Admins
        </a>
        
        <a href="<?= url('?page=admin_subjects') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_subjects') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_subjects') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            Subjects
        </a>

        <a href="<?= url('?page=admin_audit') ?>" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= isActive('admin_audit') ?>">
            <svg class="w-5 h-5 mr-3 <?= iconColor('admin_audit') ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            Audit Logs
        </a>
    </nav>

     <div class="p-4 border-t border-slate-800">
        <a href="<?= url('?page=profile') ?>" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
             <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                <?= strtoupper(substr($u['name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                 <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($u['name']) ?></div>
                 <div class="text-xs text-slate-400 truncate">Administrator</div>
            </div>
        </a>

        <a href="<?= url('?page=logout_post') ?>" class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </a>
    </div>
</aside>
