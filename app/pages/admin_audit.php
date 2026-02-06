<?php
// app/pages/admin_audit.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// Fetch audit logs with user info
$stmt = $pdo->prepare("
    SELECT a.*, u.name as user_name, u.role as user_role 
    FROM audit_logs a 
    LEFT JOIN users u ON a.actor_user_id = u.id 
    ORDER BY a.timestamp DESC 
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs | FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/theme.js"></script>
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
    <script src="/assets/loader.js"></script>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex-shrink-0 hidden md:flex flex-col border-r border-slate-800">
            <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
                <img src="/assets/favicon/web-app-manifest-512x512.png" class="w-7 h-7 rounded-lg" alt="Logo" style="width: 28px; height: 28px;">
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Admin</span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="/?page=admin_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                
                <a href="/?page=admin_monitor" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </a>

                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">
                    Management
                </div>

                <a href="/?page=admin_teachers" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Teachers
                </a>

                <a href="/?page=admin_subjects" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Subjects
                </a>
                
                <a href="/?page=admin_audit" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Audit Logs
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                     <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                    </div>
                    <div class="overflow-hidden">
                         <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($u['name']) ?></div>
                         <div class="text-xs text-slate-400 truncate">Administrator</div>
                    </div>
                </a>

                <a href="/?page=logout_post" class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header for Mobile -->
            <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 md:hidden">
                <span class="font-bold text-slate-800 dark:text-white">FacultyLink</span>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Mobile -->
                    <button onclick="window.toggleTheme()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition-colors">
                         <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                         <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <button class="text-gray-500 hover:text-gray-700 dark:text-slate-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </header>

             <!-- Top Bar Desktop -->
             <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span>Admin</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Audit Logs</span>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>

                    </button>
                </div>
            </header>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">Audit Logs</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2">View system activities and security events.</p>
                </div>

                <!-- Logs Table -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-slate-400">
                            <thead class="bg-gray-50 dark:bg-slate-700/50 text-xs uppercase text-gray-700 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Timestamp</th>
                                    <th class="px-6 py-3 font-semibold">User</th>
                                    <th class="px-6 py-3 font-semibold">Action</th>
                                    <th class="px-6 py-3 font-semibold">Details</th>
                                    <th class="px-6 py-3 font-semibold">IP Address</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-slate-400">
                                            No logs found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-slate-500">
                                            <?= date('M j, Y H:i:s', strtotime($log['timestamp'])) ?>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                            <?php if ($log['user_name']): ?>
                                                <?= htmlspecialchars($log['user_name']) ?> <span class="text-xs text-slate-500 font-normal">(<?= $log['user_role'] ?>)</span>
                                            <?php else: ?>
                                                <span class="text-gray-400">System / Unknown</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-600">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 max-w-xs truncate" title="<?= htmlspecialchars($log['metadata_json'] ?? '') ?>">
                                            <?= htmlspecialchars($log['entity_type'] ? $log['entity_type'] . ' #' . $log['entity_id'] : '-') ?>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-mono"><?= htmlspecialchars($log['ip']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
