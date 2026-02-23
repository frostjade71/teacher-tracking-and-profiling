<?php
// app/pages/admin_subjects.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM subjects ORDER BY name ASC");
$stmt->execute();
$subjects = $stmt->fetchAll();

// Fetch teachers and their subjects to map them
$stmt = $pdo->prepare("
    SELECT u.name, tp.subjects_json 
    FROM users u 
    JOIN teacher_profiles tp ON u.id = tp.teacher_user_id 
    WHERE u.role = 'teacher' AND tp.subjects_json IS NOT NULL
");
$stmt->execute();
$teachers_subjects = $stmt->fetchAll();

// Map subject names to an array of teacher names
$subject_teachers = [];
foreach ($teachers_subjects as $ts) {
    $teacher_name = $ts['name'];
    $json = $ts['subjects_json'];
    if ($json) {
        $assigned_subjects = json_decode($json, true);
        if (is_array($assigned_subjects)) {
            foreach ($assigned_subjects as $subj_name) {
                // Determine if this subject matches one of our subjects
                // We use trim to be safe
                $clean_subj = trim($subj_name);
                if (!isset($subject_teachers[$clean_subj])) {
                    $subject_teachers[$clean_subj] = [];
                }
                if (!in_array($teacher_name, $subject_teachers[$clean_subj])) {
                    $subject_teachers[$clean_subj][] = $teacher_name;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects | FacultyLink</title>
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
                    <span class="text-slate-900 dark:text-white">Subjects</span>
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
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 md:mb-8">
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white">Manage Subjects</h1>
                        <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 mt-1 md:mt-2">Add, edit, or remove subjects.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="subjectSearchInput" onkeyup="searchSubjects()" placeholder="Search subjects..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm shadow-sm">
                        </div>
                        <button onclick="document.getElementById('addSubjectModal').showModal()" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Add Subject
                        </button>
                    </div>
                </div>

                <!-- Subjects Table -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-slate-400">
                            <thead class="bg-gray-50 dark:bg-slate-700/50 text-xs uppercase text-gray-700 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Subject Code</th>
                                    <th class="px-6 py-3 font-semibold">Subject Name</th>
                                    <th class="px-6 py-3 font-semibold">Handled By</th>
                                    <th class="px-6 py-3 font-semibold">Created At</th>
                                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <?php if (empty($subjects)): ?>
                                     <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-slate-400">
                                            No subjects found. Click "Add Subject" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($subjects as $subject): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-slate-500 dark:text-slate-400">
                                            <?= htmlspecialchars($subject['code'] ?? '--') ?>
                                        </td>
                                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                                            <?= htmlspecialchars($subject['name']) ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            // Check if we have teachers for this subject
                                            // The subjects_json stores names, so we match by name
                                            $s_name = trim($subject['name']);
                                            $handlers = $subject_teachers[$s_name] ?? [];
                                            
                                            if (empty($handlers)): ?>
                                                <span class="text-xs text-slate-400 italic">No teachers assigned</span>
                                            <?php else: ?>
                                                <div class="flex flex-wrap gap-1">
                                                    <?php foreach ($handlers as $teacher_name): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                            <?= htmlspecialchars($teacher_name) ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4"><?= date('M j, Y h:i A', strtotime($subject['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right">
                                            <button onclick="editSubject(<?= htmlspecialchars(json_encode($subject)) ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button onclick="confirmDelete(<?= $subject['id'] ?>, '<?= htmlspecialchars($subject['name'], ENT_QUOTES) ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
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

    <!-- Add Subject Modal -->
    <dialog id="addSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="<?= url('?page=admin_subject_create') ?>" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Add New Subject</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Subject Code</label>
                    <input type="text" name="code" placeholder="e.g. GEN ED 005" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Subject Name *</label>
                    <input type="text" name="name" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addSubjectModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Create Subject</button>
            </div>
        </form>
    </dialog>

    <!-- Edit Subject Modal -->
    <dialog id="editSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="<?= url('?page=admin_subject_update') ?>" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Edit Subject</h3>
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Subject Code</label>
                    <input type="text" name="code" id="edit_code" placeholder="e.g. GEN ED 005" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Subject Name *</label>
                    <input type="text" name="name" id="edit_name" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editSubjectModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Update Subject</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Subject</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                Are you sure you want to delete <span id="delete_subject_name" class="font-semibold text-slate-900 dark:text-white"></span>?
            </p>
            <form id="deleteSubjectForm" action="<?= url('?page=admin_subject_delete') ?>" method="POST">
                <input type="hidden" name="id" id="delete_subject_id">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('deleteSubjectModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">Delete Subject</button>
                </div>
            </form>
        </div>
    </dialog>

    <script>
        function editSubject(subject) {
            document.getElementById('edit_id').value = subject.id;
            document.getElementById('edit_code').value = subject.code || '';
            document.getElementById('edit_name').value = subject.name;
            document.getElementById('editSubjectModal').showModal();
        }

        function confirmDelete(id, name) {
            document.getElementById('delete_subject_id').value = id;
            document.getElementById('delete_subject_name').textContent = name;
            document.getElementById('deleteSubjectModal').showModal();
        }

        function searchSubjects() {
            var input, filter, table, tr, tdCode, tdName, i, txtValueCode, txtValueName;
            input = document.getElementById("subjectSearchInput");
            filter = input.value.toUpperCase();
            table = document.querySelector("table");
            tr = table.getElementsByTagName("tr");

            // Loop through all table rows, starting from 1 to skip header
            for (i = 1; i < tr.length; i++) {
                // Code is index 0, Name is index 1, Handled By is index 2
                tdCode = tr[i].getElementsByTagName("td")[0];
                tdName = tr[i].getElementsByTagName("td")[1];
                tdHandledBy = tr[i].getElementsByTagName("td")[2];
                
                if (tdCode && tdName && tdHandledBy) {
                    txtValueCode = tdCode.textContent || tdCode.innerText;
                    txtValueName = tdName.textContent || tdName.innerText;
                    txtValueHandledBy = tdHandledBy.textContent || tdHandledBy.innerText;
                    
                    if (txtValueCode.toUpperCase().indexOf(filter) > -1 || 
                        txtValueName.toUpperCase().indexOf(filter) > -1 || 
                        txtValueHandledBy.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }
    </script>
        </main>
        </div>
    </div>
    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
