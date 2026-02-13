<?php
// app/pages/admin_timetable.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// Fetch current default rows
$stmt = $pdo->query("SELECT * FROM system_default_timetable_rows ORDER BY start_time ASC");
$defaultRows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable Configuration | Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= url('assets/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('assets/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= url('assets/favicon/site.webmanifest') ?>" />
    <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
    <script src="<?= url('assets/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('assets/toast.css') ?>">
    <script src="<?= url('assets/toast.js') ?>"></script>
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
        <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>

        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header for Mobile -->
            <?php include __DIR__ . '/../partials/admin_mobile_header.php'; ?>

            <!-- Top Bar -->
            <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <a href="<?= url('?page=admin_dashboard') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Timetable Configuration</span>
                </div>
                <div class="flex items-center gap-4">
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">
                <div class="p-4 md:p-8 max-w-4xl mx-auto">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 md:mb-8 gap-4">
                        <div>
                            <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white mb-1 md:mb-2">Default Timetable Rows</h1>
                            <p class="text-sm md:text-base text-slate-500 dark:text-slate-400">Configure the default time slots for new teacher timetables (usually 7AM to 6PM).</p>
                        </div>
                        <button onclick="openAddRowModal()" class="flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-blue-500/25 transition-all transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Add New Row
                        </button>
                    </div>

                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-slate-800/50">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700">Time Range</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-gray-100 dark:border-slate-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="rowsList">
                                <?php if (empty($defaultRows)): ?>
                                    <tr>
                                        <td colspan="2" class="px-6 py-10 text-center text-slate-500 dark:text-slate-400 italic">
                                            No default rows configured. New teachers will have no initial timetable.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($defaultRows as $row): ?>
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors border-b border-gray-50 dark:border-slate-700/50" data-row-id="<?= $row['id'] ?>">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-4">
                                                    <div class="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    <div>
                                                        <div class="text-sm font-bold text-slate-900 dark:text-white">
                                                            <?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?>
                                                        </div>
                                                        <div class="text-xs text-slate-500 dark:text-slate-400">
                                                            <?= htmlspecialchars($row['start_time']) ?> to <?= htmlspecialchars($row['end_time']) ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <button onclick="editRow(<?= $row['id'] ?>, '<?= $row['start_time'] ?>', '<?= $row['end_time'] ?>')" class="p-2 text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                                    </button>
                                                    <button onclick="deleteRow(<?= $row['id'] ?>)" class="p-2 text-slate-400 hover:text-red-600 dark:hover:text-red-400 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <dialog id="rowModal" class="p-0 rounded-2xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-[95%] max-w-sm border-none">
        <div class="bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-6 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50">
                <h3 class="font-bold text-slate-800 dark:text-white" id="modalTitle">Add New Row</h3>
                <button onclick="document.getElementById('rowModal').close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="rowForm" onsubmit="saveRow(event)" class="p-6 space-y-5">
                <input type="hidden" id="rowId" name="id">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">Start Time</label>
                    <input type="time" id="startTime" name="start_time" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-blue-500 transition-all" required>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-2">End Time</label>
                    <input type="time" id="endTime" name="end_time" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 outline-none focus:ring-2 focus:ring-blue-500 transition-all" required>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="document.getElementById('rowModal').close()" class="flex-1 px-4 py-3 border border-gray-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 font-bold transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-lg shadow-blue-500/25 transition-all">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function openAddRowModal() {
            document.getElementById('modalTitle').textContent = 'Add New Row';
            document.getElementById('rowId').value = '';
            document.getElementById('startTime').value = '07:00';
            document.getElementById('endTime').value = '08:00';
            document.getElementById('rowModal').showModal();
        }

        function editRow(id, start, end) {
            document.getElementById('modalTitle').textContent = 'Edit Row';
            document.getElementById('rowId').value = id;
            document.getElementById('startTime').value = start.substring(0, 5);
            document.getElementById('endTime').value = end.substring(0, 5);
            document.getElementById('rowModal').showModal();
        }

        async function saveRow(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = formData.get('id');
            formData.append('action', id ? 'update' : 'create');

            showToast('Saving row...', 'info');

            try {
                const res = await fetch('<?= url("?page=admin_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Saved successfully!', 'success');
                    window.location.reload(); // Simple reload for simplicity, or we could update DOM
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function deleteRow(id) {
            if (!confirm('Are you sure you want to delete this default row? This will not affect existing teachers, only future ones.')) return;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            showToast('Deleting row...', 'info');

            try {
                const res = await fetch('<?= url("?page=admin_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Deleted successfully!', 'success');
                    document.querySelector(`[data-row-id="${id}"]`).remove();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }
    </script>
</body>
</html>
