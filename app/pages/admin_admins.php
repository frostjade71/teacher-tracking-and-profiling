<?php
// app/pages/admin_admins.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// Fetch all admins
$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$stmt->execute();
$admins = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admins | FacultyLink</title>
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


        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header for Mobile -->
            <?php include __DIR__ . '/../partials/admin_mobile_header.php'; ?>


            <!-- Top Bar Desktop -->
            <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span>Admin</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <a href="<?= url('?page=admin_users') ?>" class="text-slate-700 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">User Management</a>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Admins</span>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </header>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">Manage Admins</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Add, edit, or remove administrators.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="adminSearchInput" onkeyup="searchAdmins()" placeholder="Search admins..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm shadow-sm">
                        </div>
                        <button onclick="document.getElementById('addAdminModal').showModal()" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Add Admin
                        </button>
                    </div>
                </div>

                <!-- Admins Table -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-slate-400">
                            <thead class="bg-gray-50 dark:bg-slate-700/50 text-xs uppercase text-gray-700 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Name</th>
                                    <th class="px-6 py-3 font-semibold">Email</th>
                                    <th class="px-6 py-3 font-semibold">Joined At</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <?php if (empty($admins)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-slate-400">
                                            No admins found. This should not happen if you are seeing this page!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($admins as $admin): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                                <?= strtoupper(substr($admin['name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($admin['name']) ?>
                                            <?php if ($admin['id'] === $u['id']): ?>
                                                <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-800 rounded dark:bg-green-900 dark:text-green-300">You</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($admin['email']) ?></td>
                                        <td class="px-6 py-4"><?= date('M j, Y', strtotime($admin['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                            <button onclick="editAdmin(<?= htmlspecialchars(json_encode($admin)) ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <?php if ($admin['id'] !== $u['id']): ?>
                                            <button onclick="confirmDelete(<?= $admin['id'] ?>, '<?= htmlspecialchars($admin['name'], ENT_QUOTES) ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            <?php else: ?>
                                            <button disabled class="p-2 text-slate-200 dark:text-slate-700 cursor-not-allowed">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            <?php endif; ?>
                                        </td>
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

    <!-- Add Admin Modal -->
    <dialog id="addAdminModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="<?= url('?page=admin_admin_create') ?>" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Add New Admin</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Full Name *</label>
                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address *</label>
                    <input type="email" name="email" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password *</label>
                    <div class="relative">
                        <input type="password" name="password" id="addPassword" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 pr-10 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="togglePassword('addPassword', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-500 transition-colors">
                            <svg class="h-5 w-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addAdminModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Create Admin</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteAdminModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Admin</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                Are you sure you want to delete <span id="delete_admin_name" class="font-semibold text-slate-900 dark:text-white"></span>? They will no longer have access to the dashboard.
            </p>
            <form id="deleteAdminForm" action="<?= url('?page=admin_admin_delete') ?>" method="POST">
                <input type="hidden" name="id" id="delete_admin_id">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('deleteAdminModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">Delete Admin</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Edit Admin Modal -->
    <dialog id="editAdminModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="<?= url('?page=admin_admin_update') ?>" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Edit Admin</h3>
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Full Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email Address *</label>
                    <input type="email" name="email" id="edit_email" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">New Password (optional)</label>
                    <div class="relative">
                        <input type="password" name="password" id="editPassword" placeholder="Leave blank to keep current" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 pr-10 focus:ring-blue-500 focus:border-blue-500">
                        <button type="button" onclick="togglePassword('editPassword', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-500 transition-colors">
                            <svg class="h-5 w-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editAdminModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Update Admin</button>
            </div>
        </form>
    </dialog>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('.eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
            }
        }

        function editAdmin(admin) {
            document.getElementById('edit_id').value = admin.id;
            document.getElementById('edit_name').value = admin.name;
            document.getElementById('edit_email').value = admin.email;
            document.getElementById('editAdminModal').showModal();
        }
        
        function confirmDelete(adminId, adminName) {
            document.getElementById('delete_admin_id').value = adminId;
            document.getElementById('delete_admin_name').textContent = adminName;
            document.getElementById('deleteAdminModal').showModal();
        }

        function searchAdmins() {
            var input, filter, table, tr, tdName, tdEmail, i, txtValueName, txtValueEmail;
            input = document.getElementById("adminSearchInput");
            filter = input.value.toUpperCase();
            table = document.querySelector("table");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tdName = tr[i].getElementsByTagName("td")[0];
                tdEmail = tr[i].getElementsByTagName("td")[1];
                
                if (tdName && tdEmail) {
                    txtValueName = tdName.textContent || tdName.innerText;
                    txtValueEmail = tdEmail.textContent || tdEmail.innerText;
                    
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueEmail.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>
    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
