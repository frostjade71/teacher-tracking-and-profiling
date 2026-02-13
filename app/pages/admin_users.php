<?php
// app/pages/admin_users.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// Fetch all users with teacher profile data
$stmt = $pdo->prepare("
    SELECT u.*, tp.employee_no, tp.department, tp.subjects_json, tp.office_text, tp.current_subject
    FROM users u
    LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | FacultyLink</title>
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
                    <span class="text-slate-900 dark:text-white">User Management</span>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 md:mb-8">
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white">User Management</h1>
                        <p class="text-sm md:text-base text-slate-500 dark:text-slate-400 mt-1 md:mt-2">Overview of all system users.</p>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                         <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" id="userSearchInput" onkeyup="searchUsers()" placeholder="Search users..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm shadow-sm">
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                    <a href="<?= url('?page=admin_students') ?>" class="flex items-center justify-center gap-2 p-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:shadow-md hover:border-blue-500 dark:hover:border-blue-500 transition-all group">
                         <div class="text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                         </div>
                         <div class="font-semibold text-slate-700 dark:text-slate-200">Manage Students</div>
                    </a>
                    
                    <a href="<?= url('?page=admin_teachers') ?>" class="flex items-center justify-center gap-2 p-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:shadow-md hover:border-indigo-500 dark:hover:border-indigo-500 transition-all group">
                         <div class="text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                         </div>
                         <div class="font-semibold text-slate-700 dark:text-slate-200">Manage Teachers</div>
                    </a>
                    
                    <a href="<?= url('?page=admin_admins') ?>" class="flex items-center justify-center gap-2 p-4 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-xl hover:shadow-md hover:border-amber-500 dark:hover:border-amber-500 transition-all group">
                         <div class="text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                         </div>
                         <div class="font-semibold text-slate-700 dark:text-slate-200">Manage Admins</div>
                    </a>
                </div>

                <!-- Users Table -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-500 dark:text-slate-400">
                            <thead class="bg-gray-50 dark:bg-slate-700/50 text-xs uppercase text-gray-700 dark:text-slate-300">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Name</th>
                                    <th class="px-6 py-3 font-semibold">Email</th>
                                    <th class="px-6 py-3 font-semibold">Role</th>
                                    <th class="px-6 py-3 font-semibold">Joined At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-slate-400">
                                            No users found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr onclick='viewUser(<?= json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer group">
                                        <td class="px-6 py-4 font-medium text-slate-900 dark:text-white flex items-center gap-3">
                                            <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xs font-bold flex-shrink-0 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                                                <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <?= htmlspecialchars($user['name']) ?>
                                                <?php if($user['role'] === 'teacher' && !empty($user['department'])): ?>
                                                    <span class="block text-[10px] text-gray-400 font-normal"><?= htmlspecialchars($user['department']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4"><?= htmlspecialchars($user['email']) ?></td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $roleClass = '';
                                            switch($user['role']) {
                                                case 'admin': $roleClass = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'; break;
                                                case 'teacher': $roleClass = 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'; break;
                                                case 'student': $roleClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400'; break;
                                                default: $roleClass = 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
                                            }
                                            ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $roleClass ?>">
                                                <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4"><?= date('M j, Y', strtotime($user['created_at'])) ?></td>
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

    <!-- User Profile Modal -->
    <dialog id="userProfileModal" onclick="if(event.target === this) this.close()" class="p-0 rounded-2xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 dark:text-white w-[90%] max-w-2xl overflow-hidden">
        <div class="relative">
            <!-- Header Banner -->
            <div class="h-24 bg-slate-900 dark:bg-slate-950 w-full relative">
                <button onclick="document.getElementById('userProfileModal').close()" class="absolute top-4 right-4 text-white/70 hover:text-white bg-black/20 hover:bg-black/40 rounded-full p-1 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <div class="absolute -bottom-10 left-8">
                        <div class="h-20 w-20 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-md flex-shrink-0">
                            <div id="modal_avatar" class="w-full h-full bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center text-3xl font-bold text-slate-400 dark:text-slate-300">
                            <!-- Initials by JS -->
                            </div>
                        </div>
                </div>
            </div>

            <div class="pt-12 px-8 pb-8">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 id="modal_name" class="text-2xl font-bold text-slate-900 dark:text-white"><!-- Name --></h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span id="modal_role" class="px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider"><!-- Role --></span>
                            <span id="modal_department" class="text-sm text-slate-500 dark:text-slate-400 border-l border-slate-300 dark:border-slate-600 pl-2 ml-1 hidden"><!-- Dept --></span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Info -->
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Email Address</label>
                            <div id="modal_email" class="text-slate-800 dark:text-slate-200 font-medium break-all mt-1"><!-- Email --></div>
                        </div>
                        
                        <div id="modal_joined_container">
                            <label class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Joined At</label>
                            <div id="modal_joined" class="text-slate-800 dark:text-slate-200 font-medium mt-1"><!-- Joined --></div>
                        </div>
                    </div>

                    <!-- Teacher Specific Info -->
                    <div id="teacher_info_section" class="space-y-4 hidden">
                         <div>
                            <label class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Office / Room</label>
                            <div id="modal_office" class="text-slate-800 dark:text-slate-200 font-medium mt-1"><!-- Office --></div>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider">Employee ID</label>
                            <div id="modal_employee_no" class="text-slate-800 dark:text-slate-200 font-medium mt-1"><!-- ID --></div>
                        </div>
                    </div>
                </div>

                <!-- Subjects Section (Teacher Only) -->
                <div id="modal_subjects_container" class="mt-6 pt-6 border-t border-gray-100 dark:border-slate-700 hidden">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        Teaching Subjects
                    </h3>
                    <div id="modal_subjects_list" class="flex flex-wrap gap-2">
                        <!-- Subjects badges -->
                    </div>
                </div>
                
                <div class="mt-8 flex justify-end">
                    <button onclick="document.getElementById('userProfileModal').close()" class="px-5 py-2.5 bg-gray-100 dark:bg-slate-700 hover:bg-gray-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-medium rounded-xl transition-colors">
                        Close
                    </button>
                    <!-- Link to specific edit page based on role -->
                    <a id="modal_edit_link" href="#" class="ml-3 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors shadow-lg shadow-blue-500/20">
                        Manage User
                    </a>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        function viewUser(user) {
            // Populate basic info
            document.getElementById('modal_name').textContent = user.name;
            document.getElementById('modal_email').textContent = user.email;
            document.getElementById('modal_avatar').textContent = user.name.charAt(0).toUpperCase();
            
            // Format joined date
            const date = new Date(user.created_at);
            document.getElementById('modal_joined').textContent = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

            // Role styling
            const roleEl = document.getElementById('modal_role');
            roleEl.textContent = user.role;
            
            // Reset classes
            roleEl.className = 'px-2.5 py-0.5 rounded-full text-xs font-medium uppercase tracking-wider';
            
            if (user.role === 'admin') {
                roleEl.classList.add('bg-amber-100', 'text-amber-800', 'dark:bg-amber-900/30', 'dark:text-amber-400');
                document.getElementById('modal_edit_link').href = '<?= url("?page=admin_admins") ?>';
            } else if (user.role === 'teacher') {
                roleEl.classList.add('bg-indigo-100', 'text-indigo-800', 'dark:bg-indigo-900/30', 'dark:text-indigo-400');
                document.getElementById('modal_edit_link').href = '<?= url("?page=admin_teachers") ?>';
            } else {
                roleEl.classList.add('bg-emerald-100', 'text-emerald-800', 'dark:bg-emerald-900/30', 'dark:text-emerald-400');
                document.getElementById('modal_edit_link').href = '<?= url("?page=admin_students") ?>';
            }

            // Teacher specific sections
            const teacherInfo = document.getElementById('teacher_info_section');
            const subjectsContainer = document.getElementById('modal_subjects_container');
            const deptEl = document.getElementById('modal_department');

            if (user.role === 'teacher') {
                teacherInfo.classList.remove('hidden');
                subjectsContainer.classList.remove('hidden');
                deptEl.classList.remove('hidden');
                
                deptEl.textContent = user.department || 'No Department';
                document.getElementById('modal_office').textContent = user.office_text || 'N/A';
                document.getElementById('modal_employee_no').textContent = user.employee_no || 'N/A';

                // Subjects
                const subjectsList = document.getElementById('modal_subjects_list');
                subjectsList.innerHTML = '';
                
                let subjects = [];
                try {
                    if (user.subjects_json) {
                        subjects = JSON.parse(user.subjects_json);
                    }
                } catch(e) { console.error('Error parsing subjects', e); }

                if (subjects && subjects.length > 0) {
                    subjects.forEach(sub => {
                        const span = document.createElement('span');
                        span.className = 'inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800';
                        span.textContent = sub;
                        subjectsList.appendChild(span);
                    });
                } else {
                    subjectsList.innerHTML = '<span class="text-slate-400 italic text-sm">No subjects assigned.</span>';
                }

            } else {
                teacherInfo.classList.add('hidden');
                subjectsContainer.classList.add('hidden');
                deptEl.classList.add('hidden');
            }

            document.getElementById('userProfileModal').showModal();
        }

        function searchUsers() {
            var input, filter, table, tr, tdName, tdEmail, tdRole, i, txtValueName, txtValueEmail, txtValueRole;
            input = document.getElementById("userSearchInput");
            filter = input.value.toUpperCase();
            table = document.querySelector("table");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                tdName = tr[i].getElementsByTagName("td")[0];
                tdEmail = tr[i].getElementsByTagName("td")[1];
                tdRole = tr[i].getElementsByTagName("td")[2];
                
                if (tdName && tdEmail) {
                    txtValueName = tdName.textContent || tdName.innerText;
                    txtValueEmail = tdEmail.textContent || tdEmail.innerText;
                    txtValueRole = tdRole.textContent || tdRole.innerText;
                    
                    if (txtValueName.toUpperCase().indexOf(filter) > -1 || 
                        txtValueEmail.toUpperCase().indexOf(filter) > -1 ||
                        txtValueRole.toUpperCase().indexOf(filter) > -1) {
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
