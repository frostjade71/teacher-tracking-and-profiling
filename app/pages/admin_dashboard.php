<?php
// app/pages/admin_dashboard.php

require_login();
require_role('admin');

$u = current_user();

// Fetch Total Faculty Count
$pdo = db();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = 1");
$stmt->execute();
$totalFaculty = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= url('assets/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('assets/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= url('assets/favicon/site.webmanifest') ?>" />
    <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
    <script src="<?= url('assets/theme.js') ?>"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen font-sans text-slate-800 dark:text-slate-200 transition-colors duration-200">

    <!-- Loader -->
    <div class="loader-container">
        <div class="loader">
            <div class="loader-square"></div>
            <div class="loader-square"></div>
            <div class="loader-square"></div>
        </div>
    </div>
    <script src="<?= url('assets/loader.js') ?>"></script>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <!-- Sidebar (Shared) -->
        <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>


        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header for Mobile -->
            <?php include __DIR__ . '/../partials/admin_mobile_header.php'; ?>


            <!-- Top Bar Desktop -->
            <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span>Overview</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Dashboard</span>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-7xl mx-auto">
                <div class="relative text-left mb-6 md:mb-10 pt-6">
                    <!-- Decorative background glow -->
                    <div class="absolute top-1/2 left-0 -translate-y-1/2 w-[150px] md:w-[400px] h-[150px] md:h-[200px] bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-[40px] md:blur-[60px] -z-10 pointer-events-none"></div>
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider mb-2 md:mb-4 border border-blue-100 dark:border-blue-800 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Admin Dashboard
                    </div>
                    
                    <h1 class="text-2xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-2 md:mb-3 tracking-tight">
                        Welcome back, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400"><?= htmlspecialchars(explode(' ', $u['name'])[0]) ?></span>
                    </h1>
                </div>

                <!-- Bento Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Main Feature: Live Monitor -->
                    <a href="<?= url('?page=admin_monitor') ?>" class="group col-span-1 md:col-span-2 row-span-2 bg-white dark:bg-[#0f1729] rounded-2xl border border-gray-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1 transition-all duration-300 overflow-hidden relative p-1">
                        <!-- Background Image -->
                        <div class="absolute inset-0 z-0">
                            <img src="<?= url('images/mapmap.webp') ?>" alt="Map Background" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <!-- Theme-aware Gradient Overlay (Fade at the left) -->
                            <div class="absolute inset-0 bg-gradient-to-r from-white via-white/60 to-transparent dark:from-[#0f1729] dark:via-[#0f1729]/60 dark:to-transparent"></div>
                        </div>

                        <div class="h-full rounded-xl p-6 md:p-8 flex flex-col z-10 relative">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-2 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-3 py-1 rounded-full text-xs font-bold tracking-wide shadow-sm uppercase backdrop-blur-sm border border-emerald-400/20">
                                    <span class="relative flex h-2 w-2">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Live Monitor
                                </div>
                            </div>
                            
                            <h2 class="text-2xl md:text-3xl font-extrabold text-slate-900 dark:text-white mb-3 transition-colors tracking-tight">
                                Campus Monitor
                            </h2>
                            <p class="text-slate-700 dark:text-slate-300 mb-8 max-w-md text-base md:text-lg leading-relaxed">
                                Real-time spatial tracking of all faculty members. Visualize availability status and office locations instantly across the campus map.
                            </p>
                            
                            <div class="mt-auto flex items-center text-blue-600 dark:text-blue-400 font-bold group/btn">
                                <span class="border-b-2 border-transparent group-hover/btn:border-blue-600 dark:group-hover/btn:border-blue-400 transition-all">Launch Interface</span>
                                <svg class="w-5 h-5 ml-2 group-hover:translate-x-1.5 transition-transform duration-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                            </div>
                        </div>
                    </a>

                    <!-- Teachers Stats Card -->
                    <div class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 relative overflow-hidden transition-all duration-300 hover:shadow-lg hover:border-indigo-300 dark:hover:border-indigo-700 hover:-translate-y-1">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-24 h-24 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        
                        <h3 class="font-bold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-2">Total Faculty</h3>
                        <div class="text-5xl font-black text-slate-900 dark:text-white mb-3 tracking-tighter"><?= number_format($totalFaculty) ?></div>
                        
                        <div class="flex items-center gap-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 w-fit px-2 py-1 rounded-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                            <span>Active profiles</span>
                        </div>
                    </div>

                    <!-- Actions List -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 flex flex-col overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <div class="p-5 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50 backdrop-blur-sm">
                            <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Quick Actions
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-slate-700">
                            <button onclick="window.location.href='<?= url('?page=admin_teachers') ?>'" class="w-full text-left p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center justify-between group transition-colors duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Add Teacher</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                            
                            <a href="<?= url('?page=admin_audit') ?>" class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 flex items-center justify-between group transition-colors duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">System Logs</span>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 group-hover:text-amber-500 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="col-span-1 md:col-span-3 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
                         <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 dark:text-white">System Status</h3>
                                <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Operational check passed</p>
                            </div>
                         </div>
                         
                         <div class="flex gap-6">
                             <div class="flex items-center gap-2.5">
                                 <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                                 <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Database</span>
                             </div>
                             <div class="flex items-center gap-2.5">
                                 <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                                 <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Geolocation Services</span>
                             </div>
                         </div>
                    </div>

                </div>
            </div>
        </main>
        </div>
    </div>
</body>
</html>
