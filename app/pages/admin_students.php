<?php
// app/pages/admin_students.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// Fetch students
$stmt = $pdo->prepare("
    SELECT *
    FROM users 
    WHERE role = 'student' 
    ORDER BY created_at DESC
");
$stmt->execute();
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students | FacultyLink</title>
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
                    <span class="text-slate-900 dark:text-white">Students</span>
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
                        <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white">Manage Students</h1>
                        <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 mt-1 md:mt-2">Add, edit, or remove student accounts.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="studentSearchInput" onkeyup="searchStudents()" placeholder="Search students..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm shadow-sm">
                        </div>
                        <button onclick="document.getElementById('addStudentModal').showModal()" class="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors whitespace-nowrap">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                            Add Student
                        </button>
                    </div>
                </div>

                <!-- Students Table -->
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
                                <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-slate-400">
                                            No students found. Click "Add Student" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">
                                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold">
                                                <?= strtoupper(substr($student['name'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($student['name']) ?>
                                        </td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($student['email']) ?></td>
                                        <td class="px-6 py-4"><?= date('M j, Y', strtotime($student['created_at'])) ?></td>
                                        <td class="px-6 py-4 text-right flex items-center justify-end gap-2">
                                            <button onclick="editStudent(<?= htmlspecialchars(json_encode($student)) ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <button onclick="confirmDelete(<?= $student['id'] ?>, '<?= htmlspecialchars($student['name'], ENT_QUOTES) ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
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

    <!-- Add Student Modal -->
    <dialog id="addStudentModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="/?page=admin_student_create" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Add New Student</h3>
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
                    <input type="password" name="password" required class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('addStudentModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Create Student</button>
            </div>
        </form>
    </dialog>

    <!-- Delete Confirmation Modal -->
    <dialog id="deleteStudentModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Delete Student</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">This action cannot be undone</p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-300 mb-6">
                Are you sure you want to delete <span id="delete_student_name" class="font-semibold text-slate-900 dark:text-white"></span>? All associated data will be permanently removed.
            </p>
            <form id="deleteStudentForm" action="/?page=admin_student_delete" method="POST">
                <input type="hidden" name="id" id="delete_student_id">
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('deleteStudentModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium transition-colors">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">Delete Student</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Edit Student Modal -->
    <dialog id="editStudentModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-md">
        <form action="/?page=admin_student_update" method="POST" class="p-6">
            <h3 class="text-lg font-bold mb-4">Edit Student</h3>
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
                    <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2.5 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editStudentModal').close()" class="px-4 py-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium">Update Student</button>
            </div>
        </form>
    </dialog>

    <script>
        function editStudent(student) {
            document.getElementById('edit_id').value = student.id;
            document.getElementById('edit_name').value = student.name;
            document.getElementById('edit_email').value = student.email;
            
            document.getElementById('editStudentModal').showModal();
        }
        
        function confirmDelete(studentId, studentName) {
            document.getElementById('delete_student_id').value = studentId;
            document.getElementById('delete_student_name').textContent = studentName;
            document.getElementById('deleteStudentModal').showModal();
        }

        function searchStudents() {
            var input, filter, table, tr, tdName, tdEmail, i, txtValueName, txtValueEmail;
            input = document.getElementById("studentSearchInput");
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
        </main>
        </div>
    </div>
</html>
