<?php
// app/pages/profile.php

require_login();
$u = current_user();
$pdo = db();

// Determine role-specific data
$role = $u['role'];
$user_id = $u['id'];

// Default variables
$department = 'General User';
$office_text = 'N/A';
$subjects = [];
$status = 'UNKNOWN';
$note = '';
$set_at = null;

if ($role === 'teacher') {
    // Fetch Teacher Profile Info
    $stmt = $pdo->prepare("SELECT * FROM teacher_profiles WHERE teacher_user_id = ?");
    $stmt->execute([$user_id]);
    $tp = $stmt->fetch();

    if ($tp) {
        $department = $tp['department'] ?? 'General Faculty';
        $office_text = $tp['office_text'] ?? 'Main Office';
        $subjects = json_decode($tp['subjects_json'] ?? '[]', true);
    } else {
        $department = 'Teacher (Profile Not Setup)';
    }

    // Fetch Teacher Status
    $stmtStatus = $pdo->prepare("
        SELECT status, note, set_at 
        FROM teacher_status_events 
        WHERE teacher_user_id = ? 
        ORDER BY set_at DESC 
        LIMIT 1
    ");
    $stmtStatus->execute([$user_id]);
    $latestStatus = $stmtStatus->fetch();

    $status = $latestStatus['status'] ?? 'UNKNOWN';
    $note = $latestStatus['note'] ?? '';
    $set_at = $latestStatus['set_at'] ?? null;
} elseif ($role === 'admin') {
    $department = 'Administrator';
    $office_text = 'Admin Office';
    $status = 'ACTIVE'; // Admins are always assumed active/available in this context
} else {
    $department = 'Student';
    $office_text = 'Campus';
    $status = 'ACTIVE';
}

// Status Config (Same as student_teacher.php)
$statusConfig = match($status) {
    'AVAILABLE', 'ACTIVE' => ['bg' => 'bg-emerald-100 dark:bg-emerald-900/30', 'text' => 'text-emerald-800 dark:text-emerald-400', 'border' => 'border-emerald-200 dark:border-emerald-800', 'icon' => '🟢'],
    'IN_CLASS'   => ['bg' => 'bg-amber-100 dark:bg-amber-900/30', 'text' => 'text-amber-800 dark:text-amber-400', 'border' => 'border-amber-200 dark:border-amber-800', 'icon' => '🟠'],
    'BUSY'       => ['bg' => 'bg-rose-100 dark:bg-rose-900/30', 'text' => 'text-rose-800 dark:text-rose-400', 'border' => 'border-rose-200 dark:border-rose-800', 'icon' => '🔴'],
    'OFFLINE'    => ['bg' => 'bg-slate-100 dark:bg-slate-700', 'text' => 'text-slate-600 dark:text-slate-300', 'border' => 'border-slate-200 dark:border-slate-600', 'icon' => '⚫'],
    'OFF_CAMPUS' => ['bg' => 'bg-purple-100 dark:bg-purple-900/30', 'text' => 'text-purple-800 dark:text-purple-400', 'border' => 'border-purple-200 dark:border-purple-800', 'icon' => '🟣'],
    default      => ['bg' => 'bg-gray-100 dark:bg-slate-700', 'text' => 'text-gray-800 dark:text-slate-300', 'border' => 'border-gray-200 dark:border-slate-600', 'icon' => '⚪']
};

// Sidebar Logic based on Role
$sidebar_context = match($role) {
    'admin' => 'Admin',
    'student' => 'Student',
    'teacher' => 'Staff',
    default => 'User'
};

$dashboard_link = match($role) {
    'admin' => '/?page=admin_dashboard',
    'student' => '/?page=student_dashboard',
    'teacher' => '/?page=teacher_dashboard',
    default => '/'
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/theme.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors duration-200 font-sans text-slate-800 dark:text-slate-200">
    
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
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500"><?= $sidebar_context ?></span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="<?= $dashboard_link ?>" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                     <?php if($role === 'admin'): ?>
                        <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard
                     <?php elseif($role === 'teacher'): ?>
                        <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                        Control Panel
                     <?php else: ?>
                        <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        Find Faculty
                     <?php endif; ?>
                </a>

                <?php if ($role === 'admin'): ?>
                <a href="/?page=admin_monitor" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </a>
                
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">Management</div>
                <a href="/?page=admin_teachers" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                     <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Teachers
                </a>
                <a href="/?page=admin_audit" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                     <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    Audit Logs
                </a>
                <?php elseif ($role === 'teacher' || $role === 'student'): 
                    $mapRedirectPage = ($role === 'teacher') ? 'teacher_dashboard' : 'student_dashboard';
                ?>
                <a href="/?page=<?= $mapRedirectPage ?>&openMap=1" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </a>
                <?php endif; ?>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                     <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                    </div>
                    <div class="overflow-hidden">
                         <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($u['name']) ?></div>
                         <div class="text-xs text-slate-400 truncate">Staff Member</div>
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
             <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 md:hidden sticky top-0 z-20">
                <span class="font-bold text-slate-800 dark:text-white">FacultyLink</span>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Mobile -->
                    <button onclick="window.toggleTheme()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition-colors">
                         <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                         <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <a href="<?= $dashboard_link ?>" class="text-sm font-medium text-gray-500">Back</a>
                </div>
            </header>

            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold flex items-center gap-2">
                    <a href="<?= $dashboard_link ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Home</a>
                    <span class="text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">My Profile</span>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </div>

            <div class="p-6 md:p-12 max-w-5xl mx-auto">
                
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors">
                    
                    <!-- Header Banner -->
                    <div class="h-32 bg-slate-900 dark:bg-slate-950 w-full relative">
                        <div class="absolute -bottom-10 left-8">
                             <div class="h-24 w-24 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-md">
                                 <div class="w-full h-full bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center text-3xl font-bold text-slate-400 dark:text-slate-300">
                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div class="pt-14 px-8 pb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                            <div>
                                <h1 class="text-3xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($u['name']) ?></h1>
                                <p class="text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($department) ?> &bull; <?= htmlspecialchars($office_text) ?></p>
                            </div>
                            
                            <?php if ($role === 'teacher'): ?>
                            <!-- Status Badge for Teachers -->
                            <div class="mt-4 md:mt-0 flex flex-col items-end">
                                <div class="flex items-center space-x-2 px-4 py-2 rounded-full border <?= $statusConfig['bg'] ?> <?= $statusConfig['border'] ?>">
                                    <span class="text-lg"><?= $statusConfig['icon'] ?></span>
                                    <span class="font-bold <?= $statusConfig['text'] ?> tracking-wide text-sm"><?= htmlspecialchars($status) ?></span>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-slate-500 mt-2 font-medium">
                                    Updated: <?= $set_at ? date('M j, g:i a', strtotime($set_at)) : 'N/A' ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <!-- Main Content -->
                            <div class="md:col-span-2 space-y-8">
                                
                                <?php if ($role === 'teacher'): ?>
                                    <!-- Note Section -->
                                    <?php if ($note): ?>
                                    <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-100 dark:border-blue-800 rounded-xl p-6 relative">
                                         <svg class="w-8 h-8 text-blue-200 dark:text-blue-800 absolute top-4 left-4" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21L14.017 18C14.017 16.8954 13.1216 16 12.017 16H9.01697L9.01697 21H14.017ZM16.017 21L16.017 8C16.017 6.89543 15.1216 6 14.017 6H7.01697C5.9124 6 5.01697 6.89543 5.01697 8V21L16.017 21ZM18.017 8H20.017C21.1216 8 22.017 8.89543 22.017 10V21L18.017 21V8ZM2.01697 21L4.01697 21L4.01697 10C4.01697 8.89543 4.9124 8 6.01697 8H6.99222C7.36979 5.16335 9.77884 3 12.6841 3C15.5894 3 17.9984 5.16335 18.376 8H19.017C20.1216 8 21.017 8.89543 21.017 10V18L24.017 18L24.017 21H2.01697Z"></path></svg>
                                        <div class="pl-10">
                                            <h3 class="text-blue-900 dark:text-blue-300 font-semibold mb-1 text-sm uppercase tracking-wider">Current Status Note</h3>
                                            <p class="text-blue-800 dark:text-blue-200 text-lg italic">"<?= htmlspecialchars($note) ?>"</p>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Subjects -->
                                    <div>
                                        <h3 class="text-slate-900 dark:text-white font-bold mb-4 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                            Teaching Subjects
                                        </h3>
                                        <?php if (!empty($subjects)): ?>
                                            <div class="flex flex-wrap gap-2">
                                                <?php foreach($subjects as $sub): ?>
                                                    <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-gray-100 dark:bg-slate-700 text-gray-800 dark:text-slate-200 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                                        <?= htmlspecialchars($sub) ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-gray-400 dark:text-slate-500 italic">No subjects listed.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Generic Profile Content -->
                                    <div class="bg-gray-50 dark:bg-slate-700/30 rounded-xl p-5 border border-gray-100 dark:border-slate-700">
                                        <p class="text-slate-600 dark:text-slate-400">Welcome to your profile page. This information is visible to other users on the platform.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Sidebar Info -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 dark:bg-slate-700/30 rounded-xl p-5 border border-gray-100 dark:border-slate-700">
                                    <h4 class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-4">Account Info</h4>
                                    
                                    <div class="mb-4">
                                        <div class="text-xs text-gray-500 dark:text-slate-400 mb-1">Email Address</div>
                                        <div class="text-slate-800 dark:text-slate-200 font-medium break-all">
                                            <?= htmlspecialchars($u['email']) ?>
                                        </div>
                                    </div>
                                     <div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 mb-1">Role</div>
                                        <div class="text-slate-800 dark:text-slate-200 font-mono text-sm capitalize"><?= htmlspecialchars($role) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </main>
    </div>
</body>
</html>
