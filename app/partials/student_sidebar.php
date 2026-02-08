<aside id="sidebar" class="fixed inset-y-0 right-0 z-50 w-64 bg-slate-900 dark:bg-slate-950 text-white transform translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out md:static md:inset-auto md:transform-none flex flex-col border-l md:border-r border-slate-800 rounded-tl-2xl rounded-bl-2xl md:rounded-none">
    <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
        <img src="/assets/favicon/web-app-manifest-512x512.png" class="w-7 h-7 rounded-lg hidden md:block" alt="Logo" style="width: 28px; height: 28px;">
        <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Student</span></span>
    </div>
    
    <nav class="flex-1 px-3 py-6 space-y-1">
        <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Main
        </div>
        <a href="/?page=student_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
            <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            Find Faculty
        </a>
        
        <button onclick="document.getElementById('campusMapModal').showModal()" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors w-full">
            <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
            Live Campus Map
        </button>
    </nav>

    <div class="p-4 border-t border-slate-800">
        <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                <?= strtoupper(substr(current_user()['name'], 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                    <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars(current_user()['name']) ?></div>
                    <div class="text-xs text-slate-400 truncate">Student</div>
            </div>
        </a>

        <a href="/?page=logout_post" class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            Sign Out
        </a>
    </div>
</aside>
