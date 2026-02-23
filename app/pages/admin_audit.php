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
                    <span>Admin</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Audit Logs</span>
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
                <div class="mb-6 md:mb-8">
                    <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white">Audit Logs</h1>
                    <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 mt-1 md:mt-2">View system activities and security events.</p>
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
    </div>
    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
