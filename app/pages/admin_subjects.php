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
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" class="w-6 h-6" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
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
                
                <a href="/?page=admin_subjects" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Subjects
                </a>

                <a href="/?page=admin_audit" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
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
                    <span class="text-slate-900 dark:text-white">Subjects</span>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </header>

            <div class="p-6 md:p-8 max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">Manage Subjects</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Create and edit subjects.</p>
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
    <dialog id="addSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-full max-w-md">
        <form action="/?page=admin_subject_create" method="POST" class="p-6">
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
    <dialog id="editSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-full max-w-md">
        <form action="/?page=admin_subject_update" method="POST" class="p-6">
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
    <dialog id="deleteSubjectModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-full max-w-md">
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
            <form id="deleteSubjectForm" action="/?page=admin_subject_delete" method="POST">
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
</body>
</html>
