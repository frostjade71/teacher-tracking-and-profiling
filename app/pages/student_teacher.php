<?php
// app/pages/student_teacher.php

require_login();
require_role('student');

$teacher_id = $_GET['id'] ?? null;
if (!$teacher_id) {
    redirect('student_dashboard');
}

$pdo = db();

// Trigger auto-offline check logic (same as map)
require_once __DIR__ . '/../actions/auto_offline_helper.php';
check_and_process_expirations();
$stmt = $pdo->prepare("
    SELECT 
        u.id, u.name, u.email, 
        tp.employee_no, tp.department, tp.office_text, tp.current_subject, tp.subjects_json
    FROM users u
    LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
    WHERE u.id = ? AND u.role = 'teacher' AND u.is_active = 1
");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

if (!$teacher) {
    echo "<h1>Teacher not found</h1>";
    exit;
}

// Fetch latest status
// Fetch latest status
$stmtStatus = $pdo->prepare("
    SELECT status, set_at 
    FROM teacher_status_events 
    WHERE teacher_user_id = ? 
    ORDER BY set_at DESC 
    LIMIT 1
");
$stmtStatus->execute([$teacher_id]);
$latestStatus = $stmtStatus->fetch();

$status = $latestStatus['status'] ?? 'UNKNOWN';
$set_at = $latestStatus['set_at'] ?? null;

// Fetch latest note
$stmtNote = $pdo->prepare("
    SELECT note 
    FROM teacher_notes 
    WHERE teacher_user_id = ? 
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmtNote->execute([$teacher_id]);
$latestNote = $stmtNote->fetch();
$note = $latestNote['note'] ?? '';

// Fetch Timetable Data for Embedding
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$stmt = $pdo->prepare("SELECT * FROM teacher_timetables WHERE teacher_user_id = ? ORDER BY day, start_time");
$stmt->execute([$teacher_id]);
$timetableEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$schedule = [];
foreach ($timetableEntries as $entry) {
    $schedule[$entry['day']][$entry['start_time']] = $entry;
}

$stmt = $pdo->prepare("SELECT DISTINCT start_time, end_time FROM teacher_timetables WHERE teacher_user_id = ? ORDER BY start_time ASC");
$stmt->execute([$teacher_id]);
$timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

switch($status) {
    case 'AVAILABLE':
        $statusConfig = ['bg' => 'bg-emerald-500', 'text' => 'text-white', 'border' => 'border-emerald-600', 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>'];
        break;
    case 'IN_CLASS':
        $statusConfig = ['bg' => 'bg-amber-500', 'text' => 'text-white', 'border' => 'border-amber-600', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>'];
        break;
    case 'BUSY':
        $statusConfig = ['bg' => 'bg-rose-500', 'text' => 'text-white', 'border' => 'border-rose-600', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>'];
        break;
    case 'OFFLINE':
        $statusConfig = ['bg' => 'bg-slate-500', 'text' => 'text-white', 'border' => 'border-slate-600', 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>'];
        break;
    case 'OFF_CAMPUS':
        $statusConfig = ['bg' => 'bg-purple-500', 'text' => 'text-white', 'border' => 'border-purple-600', 'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>'];
        break;
    default:
        $statusConfig = ['bg' => 'bg-gray-500', 'text' => 'text-white', 'border' => 'border-gray-600', 'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>'];
        break;
}

$subjects = json_decode($teacher['subjects_json'] ?? '[]', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($teacher['name']) ?> | Profile</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= url('assets/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('assets/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= url('assets/favicon/site.webmanifest') ?>" />
    <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="<?= url('assets/map_arrows.js') ?>"></script>
    <script src="<?= url('assets/theme.js') ?>"></script>
    <style>
        #campusMap { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }

        /* Timetable Styles */
        .timetable-grid { 
            display: grid; 
            grid-template-columns: 80px repeat(5, 1fr) 0px; 
            width: 100%; 
            border-radius: 12px; 
            overflow: hidden; 
            border: 1px solid #e2e8f0; 
            background-color: #f1f5f9;
            gap: 1px;
        }
        html.dark .timetable-grid { border-color: #334155; background-color: #1e293b; }

        .grid-header { 
            background-color: #f8fafc; 
            padding: 0.75rem; 
            text-align: center; 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            color: #64748b; 
            border-bottom: 1px solid #e2e8f0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 50px; 
        }
        html.dark .grid-header { background-color: #1e293b; color: #94a3b8; border-color: #334155; }

        .grid-time { 
            background-color: #f1f5f9; 
            padding: 0.75rem; 
            text-align: center; 
            font-size: 0.75rem; 
            font-weight: 700; 
            color: #64748b; 
            border-right: 1px solid #e2e8f0; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        html.dark .grid-time { background-color: #0f172a; color: #94a3b8; border-color: #334155; }

        .grid-cell { 
            min-height: 80px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 0.5rem; 
            background-color: #ffffff;
            border-bottom: 1px solid #f1f5f9; 
            position: relative; 
        }
        html.dark .grid-cell { background-color: #1e293b; border-color: #334155; }
    </style>
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
    <script src="<?= url('assets/loader.js') ?>"></script>
    
    <div class="flex h-screen overflow-hidden">

         <!-- Sidebar (Shared) -->
         <?php include __DIR__ . '/../partials/student_sidebar.php'; ?>

        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
             <!-- Header for Mobile -->
             <?php include __DIR__ . '/../partials/student_mobile_header.php'; ?>


            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold flex items-center gap-2">
                    <a href="<?= url('?page=student_dashboard') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Find Faculty</a>
                    <span class="text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Profile View</span>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                     <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-5xl mx-auto">
                
                <!-- Back Button -->
                <a href="<?= url('?page=student_dashboard') ?>" class="inline-flex items-center text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white mb-6 transition-colors group">
                    <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 shadow-sm flex items-center justify-center mr-2 group-hover:scale-110 transition-transform border border-gray-200 dark:border-slate-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </div>
                    <span class="font-medium text-sm">Back to Dashboard</span>
                </a>

                <?php if (!$teacher): ?>
                    <div class="bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 p-4 rounded-xl text-center border border-red-100 dark:border-red-800">
                        Teacher not found.
                    </div>
                <?php else: ?>
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors">
                    
                    <!-- Header Banner -->
                    <div class="h-32 bg-slate-900 dark:bg-slate-950 w-full relative">
                        <div class="absolute -bottom-10 left-8">
                             <div class="h-24 w-24 rounded-2xl bg-white dark:bg-slate-800 p-1 shadow-md">
                                 <div class="w-full h-full bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center text-xl md:text-3xl font-bold text-slate-400 dark:text-slate-300">
                                    <?= strtoupper(substr($teacher['name'], 0, 1)) ?>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div class="pt-14 px-8 pb-8">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
                            <div>
                                <h1 class="text-3xl font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($teacher['name']) ?></h1>
                                <p class="text-slate-500 dark:text-slate-400 font-medium"><?= htmlspecialchars($teacher['department'] ?? 'General Faculty') ?> &bull; <?= htmlspecialchars($teacher['office_text'] ?? 'Main Office') ?></p>
                                
                                <!-- Current Subject Display -->
                                <div class="flex items-center gap-2 mt-2 text-sm">
                                    <span class="text-slate-400 dark:text-slate-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                    </span>
                                    <?php if (!empty($teacher['current_subject'])): ?>
                                        <span class="font-medium text-blue-600 dark:text-blue-400"><?= htmlspecialchars($teacher['current_subject']) ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 dark:text-slate-500 italic">Currently not Teaching</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- Status Badge -->
                            <div class="mt-4 md:mt-0 flex flex-col items-end">
                                <div class="flex items-center space-x-2 px-4 py-2 rounded-full border <?= $statusConfig['bg'] ?> <?= $statusConfig['border'] ?> <?= $statusConfig['text'] ?>">
                                    <span><?= $statusConfig['icon'] ?></span>
                                    <span class="font-bold tracking-wide text-sm"><?= htmlspecialchars($status) ?></span>
                                </div>
                                <div class="text-xs text-gray-400 dark:text-slate-500 mt-2 font-medium">
                                    Updated: <?= $set_at ? date('M j, g:i a', strtotime($set_at)) : 'N/A' ?>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <!-- Main Content -->
                            <div class="md:col-span-2 space-y-8">
                                
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
                            </div>

                            <!-- Sidebar Info -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 dark:bg-slate-700/30 rounded-xl p-5 border border-gray-100 dark:border-slate-700">
                                    <h4 class="text-xs font-bold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-4">Contact Info</h4>
                                    
                                    <div class="mb-4">
                                        <div class="text-xs text-gray-500 dark:text-slate-400 mb-1">Email Address</div>
                                        <a href="mailto:<?= htmlspecialchars($teacher['email']) ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium break-all">
                                            <?= htmlspecialchars($teacher['email']) ?>
                                        </a>
                                    </div>
                                     <div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 mb-1">Employee ID</div>
                                        <div class="text-slate-800 dark:text-slate-200 font-mono text-sm"><?= htmlspecialchars($teacher['employee_no'] ?? 'N/A') ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Timetable Section -->
                <?php if (!empty($timeSlots)): ?>
                <div class="mt-8 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden transition-colors p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <?= htmlspecialchars(explode(' ', $teacher['name'])[0]) ?>'s Timetable
                        </h3>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-100 dark:border-slate-700 shadow-inner bg-slate-50 dark:bg-slate-900/50">
                        <div class="min-w-[800px] p-4 dark:bg-[#1e293b]">
                            <?php 
                                $readonly = true;
                                $profile = $teacher; // Use retrieved teacher profile data
                                include __DIR__ . '/../partials/teacher_timetable_grid.php'; 
                            ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php endif; ?>

            </div>
        </main>
        </div>
    </div>

    <!-- Live Campus Map Modal (Shared) -->
    <?php include __DIR__ . '/../partials/campus_map_modal.php'; ?>


    <script>

    </script>
    <script src="<?= url('assets/mobile.js') ?>"></script>
    
    <!-- Chatbot Widget -->
    <?php include __DIR__ . '/../partials/chatbot_widget.php'; ?>
</body>
</html>
