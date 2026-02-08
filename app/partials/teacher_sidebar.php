<aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 dark:bg-slate-950 text-white transform translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:static md:inset-auto md:transform-none flex flex-col border-l md:border-r border-slate-800 rounded-tl-2xl rounded-bl-2xl md:rounded-none">
    <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
        <img src="/assets/favicon/web-app-manifest-512x512.png" class="w-7 h-7 rounded-lg hidden md:block" alt="Logo" style="width: 28px; height: 28px;">
        <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Staff</span></span>
    </div>
    
    <nav class="flex-1 px-3 py-6 space-y-1">
        <?php $page = $_GET['page'] ?? 'teacher_dashboard'; ?>
        
        <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Main
        </div>
        <a href="/?page=teacher_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= $page === 'teacher_dashboard' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3 <?= $page === 'teacher_dashboard' ? 'text-blue-200' : 'text-slate-400 group-hover:text-white' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard
        </a>
        
        <button onclick="document.getElementById('campusMapModal').showModal()" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors w-full">
            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Live Campus Map
        </button>

        <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">
            Management
        </div>

        <a href="/?page=teacher_subjects" class="flex items-center px-3 py-2.5 text-sm font-medium rounded-lg group transition-colors <?= $page === 'teacher_subjects' ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' ?>">
            <svg class="w-5 h-5 mr-3 <?= $page === 'teacher_subjects' ? 'text-blue-200' : 'text-slate-400 group-hover:text-white' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
            Subjects
        </a>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                <?= strtoupper(substr(current_user()['name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                    <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars(current_user()['name']) ?></div>
                    <div class="text-xs text-slate-400 truncate">Teacher</div>
            </div>
        </a>

        <a href="/?page=logout_post" class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </a>
    </div>
</aside>
