<!-- app/partials/info_modal.php -->
<dialog id="infoModal" class="p-0 rounded-2xl shadow-2xl backdrop:bg-black/70 dark:bg-slate-800 w-[92%] sm:max-w-md overflow-hidden border border-gray-200 dark:border-slate-700">
    <div class="relative bg-white dark:bg-slate-800 flex flex-col h-full max-h-[80vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50 backdrop-blur-sm sticky top-0 z-10">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                System Information
            </h3>
            <button onclick="document.getElementById('infoModal').close()" class="p-2 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-colors group">
                <svg class="w-5 h-5 text-gray-500 dark:text-slate-400 group-hover:text-slate-700 dark:group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Tabs Navigation -->
        <div class="flex p-1 bg-gray-100 dark:bg-slate-900/50 rounded-lg m-4 gap-1">
            <button onclick="switchInfoTab('changelogs')" id="tabBtn-changelogs" class="info-tab-btn flex-1 py-2 px-4 rounded-md text-sm font-bold transition-all bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 shadow-sm border border-gray-200 dark:border-slate-700">
                Changelogs
            </button>
            <button onclick="switchInfoTab('credits')" id="tabBtn-credits" class="info-tab-btn flex-1 py-2 px-4 rounded-md text-sm font-bold transition-all text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300">
                Credits
            </button>
        </div>

        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto px-6 pb-6 custom-scrollbar">
            <!-- Changelogs Tab -->
            <div id="infoTab-changelogs" class="info-tab-content space-y-4">
                <div class="relative border-l-2 border-blue-500/30 pl-6 space-y-6 py-2">
                    <!-- Commit 1 (Today) -->
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-800 shadow-sm"></span>
                        <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-tighter mb-1">Feb 23, 2026</div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">Update v0.6.0alpha: Modal Integration & Bug Fixes</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Added System Information Modal and new Teacher Directory for Admin.</p>
                    </div>
                    <!-- Commit 2 -->
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-800 shadow-sm"></span>
                        <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-tighter mb-1">Feb 14, 2026</div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">Update: Documentation and Licensing</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Finalizing project documentation and licensing details for production readiness.</p>
                    </div>
                    <!-- Commit 3 -->
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-800 shadow-sm"></span>
                        <div class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-tighter mb-1">Feb 14, 2026</div>
                        <h4 class="text-sm font-bold text-slate-800 dark:text-white">Update: v0.5.0beta: added "Linky" Assistant</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Integrated LinkyBot, the AI-powered faculty assistant for real-time queries and tracking support.</p>
                    </div>
                    <!-- Commit 4 -->
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500 border-4 border-white dark:border-slate-800 shadow-sm"></span>
                        <div class="text-xs font-bold text-blue-400 dark:text-slate-500 uppercase tracking-tighter mb-1">Feb 13, 2026</div>
                        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Update v0.4beta: Enhance user management and fix loader issues</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">Implemented persistent loader fix for navigation and added User Profile Modal in Admin Users page for quick detail access.</p>
                    </div>
                    <!-- Commit 5 -->
                    <div class="relative">
                        <span class="absolute -left-[31px] top-1 w-4 h-4 rounded-full bg-blue-500/50 border-4 border-white dark:border-slate-800 shadow-sm"></span>
                        <div class="text-xs font-bold text-blue-400 dark:text-slate-500 uppercase tracking-tighter mb-1">Feb 11, 2026</div>
                        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-300">Update v0.35beta: Added teacher timetable and fixed auto-offline</h4>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Foundational teacher timetable management and resolved automatic offline status issues for better accuracy.</p>
                    </div>
                </div>
                <div class="pt-2 text-center">
                    <a href="https://github.com/frostjade71/teacher-tracking-and-profiling/commits/main/" target="_blank" class="text-[10px] font-bold text-blue-500 hover:underline uppercase tracking-wide">View Full History on GitHub &rarr;</a>
                </div>
            </div>

            <!-- Credits Tab -->
            <div id="infoTab-credits" class="info-tab-content hidden animate-in fade-in slide-in-from-bottom-2 duration-300">
                <div class="space-y-6 pt-2">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800/50">
                        <h4 class="text-sm font-black text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-3">Thesis Title</h4>
                        <p class="text-base font-extrabold text-slate-900 dark:text-white leading-tight italic">
                            "FacultyLink: Teacher Tracking and Profiling System"
                        </p>
                        <div class="h-1 w-12 bg-blue-500 rounded-full mt-4"></div>
                        <div class="mt-4 text-xs font-bold text-slate-600 dark:text-slate-400">
                            By Group 3 College Seniors<br>
                            Holy Cross College of Carigara Incorporated<br>
                            Batch 2025-2026
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h4 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest pl-1">Project Abstract</h4>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-medium bg-gray-50 dark:bg-slate-900/50 p-4 rounded-xl border border-gray-100 dark:border-slate-700/50">
                            A comprehensive Teacher Tracking and Profiling System designed to streamline campus monitoring and academic visibility. This AI-powered platform provides role-based dashboards for administrators, teachers, and students, facilitating real-time status updates and location monitoring while strictly adhering to privacy standards.
                        </p>
                    </div>
                    
                    <div class="space-y-4 pt-2">
                        <div class="flex flex-col items-center">
                            <h4 class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-4">Developed By</h4>
                            <div class="flex flex-col items-center gap-3">
                                <div class="text-lg font-black text-slate-900 dark:text-white tracking-tight">Jaderby Garcia Peñaranda</div>
                                
                                <!-- Social Icons -->
                                <div class="flex items-center gap-4 mt-1">
                                    <a href="https://github.com/frostjade71" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-900 hover:text-white dark:hover:bg-blue-600 transition-all shadow-sm" title="GitHub">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                                    </a>
                                    <a href="https://web.facebook.com/jaderby.penaranda7/" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white transition-all shadow-sm" title="Facebook">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953h-1.514c-1.491 0-1.95.925-1.95 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                    </a>
                                    <a href="https://www.linkedin.com/in/jaderby-pe%C3%B1aranda-830670359/" target="_blank" class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-[#0077b5] hover:text-white transition-all shadow-sm" title="LinkedIn">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M22.23 0H1.77C.8 0 0 .77 0 1.72v20.56C0 23.23.8 24 1.77 24h20.46c.98 0 1.77-.77 1.77-1.72V1.72C24 .77 23.23 0 22.23 0zM7.27 20.1H3.65V9.24h3.62V20.1zM5.47 7.76c-1.16 0-2.09-.93-2.09-2.09 0-1.16.93-2.09 2.09-2.09 1.16 0 2.09.93 2.09 2.09 0 1.16-.93 2.09-2.09 2.09zM20.1 20.1h-3.62v-5.6c0-1.33-.03-3.05-1.85-3.05-1.85 0-2.14 1.45-2.14 2.95v5.7h-3.62V9.24h3.48v1.48h.05c.48-.92 1.67-1.89 3.44-1.89 3.68 0 4.36 2.42 4.36 5.58v5.69z"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-center pt-2">
                            <div class="p-2 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 shadow-inner">
                                <img src="<?= url('assets/favicon/favicon-96x96.png') ?>" alt="FacultyLink" class="w-10 h-10">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</dialog>

<script>
function switchInfoTab(tab) {
    // Buttons
    const buttons = document.querySelectorAll('.info-tab-btn');
    buttons.forEach(btn => {
        btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-blue-600', 'dark:text-blue-400', 'shadow-sm', 'border', 'border-gray-200', 'dark:border-slate-700');
        btn.classList.add('text-slate-500', 'dark:text-slate-400', 'hover:text-slate-700', 'dark:hover:text-slate-300');
    });

    const activeBtn = document.getElementById('tabBtn-' + tab);
    activeBtn.classList.remove('text-slate-500', 'dark:text-slate-400', 'hover:text-slate-700', 'dark:hover:text-slate-300');
    activeBtn.classList.add('bg-white', 'dark:bg-slate-800', 'text-blue-600', 'dark:text-blue-400', 'shadow-sm', 'border', 'border-gray-200', 'dark:border-slate-700');

    // Content
    const contents = document.querySelectorAll('.info-tab-content');
    contents.forEach(c => c.classList.add('hidden'));
    
    document.getElementById('infoTab-' + tab).classList.remove('hidden');
}

// Close modal when clicking outside
document.getElementById('infoModal').addEventListener('click', function(e) {
    if (e.target === this) this.close();
});
</script>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
html.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
</style>
