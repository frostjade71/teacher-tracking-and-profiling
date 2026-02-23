<?php
// app/pages/teacher_dashboard.php

require_login();
require_role('teacher');

$u = current_user();
$pdo = db();

// Trigger auto-offline check logic (same as map)
require_once __DIR__ . '/../actions/auto_offline_helper.php';
check_and_process_expirations();

// Get current status
$stmt = $pdo->prepare("
    SELECT status
    FROM teacher_status_events 
    WHERE teacher_user_id = ? 
    ORDER BY set_at DESC 
    LIMIT 1
");
$stmt->execute([$u['id']]);
$current = $stmt->fetch();
$currentStatus = $current['status'] ?? 'UNKNOWN';
$stmt = $pdo->prepare("
    SELECT note, expires_at 
    FROM teacher_notes 
    WHERE teacher_user_id = ? 
    AND (expires_at IS NULL OR expires_at > NOW())
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$u['id']]);
$currentNoteRow = $stmt->fetch();
$currentNote = $currentNoteRow['note'] ?? '';
$currentExpiresAt = $currentNoteRow['expires_at'] ?? null;
if ($currentExpiresAt) {
    // Convert to ISO 8601 for JS
    $currentExpiresAt = date('c', strtotime($currentExpiresAt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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
    <link rel="stylesheet" href="<?= url('assets/toast.css') ?>">
    <script src="<?= url('assets/toast.js') ?>"></script>
    <style>
        #campusMap { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen text-slate-800 dark:text-slate-200 font-sans transition-colors duration-200">
    
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
        <?php include __DIR__ . '/../partials/teacher_sidebar.php'; ?>



        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
             <!-- Header for Mobile -->
             <?php include __DIR__ . '/../partials/teacher_mobile_header.php'; ?>


            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span>Overview</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Dashboard</span>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                     <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-7xl mx-auto">
                <div class="relative text-left mb-6 md:mb-10 pt-6">
                    <!-- Decorative background glow -->
                    <div class="absolute top-1/2 left-0 -translate-y-1/2 w-[150px] md:w-[400px] h-[150px] md:h-[200px] bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-[40px] md:blur-[60px] -z-10 pointer-events-none"></div>
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider mb-2 md:mb-4 border border-blue-100 dark:border-blue-800 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Faculty Dashboard
                    </div>
                    
                    <h1 class="text-2xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-2 md:mb-3 tracking-tight">
                        Welcome back, <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400"><?= htmlspecialchars(explode(' ', $u['name'])[0]) ?></span>
                    </h1>
                    

                </div>

<?php
// Status Configuration for Styling
$statusConfig = [
    'AVAILABLE' => [
        'bg' => '', // using inline style
        'color' => '#10b981', // emerald-500
        'text' => 'text-white',
        'border' => 'border-emerald-700 dark:border-emerald-600',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>'
    ],
    'IN_CLASS' => [
        'bg' => '',
        'color' => '#f59e0b', // amber-500
        'text' => 'text-white',
        'border' => 'border-amber-700 dark:border-amber-600',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>'
    ],
    'BUSY' => [
        'bg' => '',
        'color' => '#ef4444', // red-500
        'text' => 'text-white',
        'border' => 'border-rose-700 dark:border-rose-600',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>'
    ],
    'OFF_CAMPUS' => [
        'bg' => '',
        'color' => '#a855f7', // purple-500
        'text' => 'text-white',
        'border' => 'border-purple-700 dark:border-purple-600',
        'icon' => '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>'
    ],
    'OFFLINE' => [
        'bg' => '',
        'color' => '#64748b', // slate-500
        'text' => 'text-white',
        'border' => 'border-slate-700 dark:border-slate-600',
        'icon' => '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>'
    ]
];

$defaultConfig = $statusConfig['OFFLINE'];
$config = $statusConfig[$currentStatus] ?? $defaultConfig;
?>

                <!-- Main Grid Layout -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Current Status Card -->
                    <div id="statusCard" style="background-color: <?= $config['color'] ?>;" class="col-span-1 md:col-span-2 relative overflow-hidden rounded-2xl shadow-xl transition-all duration-500 hover:shadow-2xl group">
                        <!-- Glass overlay -->
                        <div class="absolute inset-0 bg-white/10 backdrop-blur-[2px]"></div>
                        <div class="absolute inset-0 bg-gradient-to-br from-white/20 via-transparent to-black/10"></div>
                        
                        <!-- Abstract Decoration -->
                        <div class="absolute -right-12 -top-12 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
                        
                        <div class="relative p-8 z-10 text-white">
                            <div class="flex items-start justify-between mb-8">
                                <div>
                                    <span id="statusLabel" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md border border-white/20 text-xs font-bold uppercase tracking-wider shadow-sm mb-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                                        Current Status
                                    </span>
                                    <h2 id="currentStatusText" class="text-4xl md:text-5xl font-black tracking-tighter mt-2 shadow-black/5 drop-shadow-md">
                                        <?= htmlspecialchars($currentStatus) ?>
                                    </h2>
                                </div>
                                <div id="statusIcon" class="p-4 bg-white/20 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg group-hover:scale-110 transition-transform duration-300">
                                    <?= $config['icon'] ?>
                                </div>
                            </div>

                            <div id="currentNoteContainer" class="<?= $currentNote ? '' : 'hidden' ?> bg-black/20 backdrop-blur-md rounded-xl p-4 border border-white/10 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-5 h-5 text-white/70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                    <span id="currentNoteText" class="text-white/90 font-medium italic">"<?= htmlspecialchars($currentNote) ?>"</span>
                                </div>
                                <span id="noteTimer" class="px-2 py-1 text-xs font-mono bg-white/20 rounded-lg text-white font-bold hidden border border-white/10"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Panel -->
                    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-lg overflow-hidden flex flex-col">
                        <div class="p-5 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50 backdrop-blur-sm flex justify-between items-center">
                            <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                Status Update
                            </h3>
                            <button onclick="document.getElementById('quickUpdateModal').showModal()" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                                Quick Update &rarr;
                            </button>
                        </div>
                        
                        <div class="p-5 grid grid-cols-2 gap-3 flex-1">
                            <button type="button" onclick="updateStatus('AVAILABLE')" class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-100 dark:border-slate-700 bg-emerald-50/50 dark:bg-emerald-900/10 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 hover:shadow-md transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                                </div>
                                <span class="font-bold text-xs">Available</span>
                            </button>

                            <button type="button" onclick="updateStatus('IN_CLASS')" class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-100 dark:border-slate-700 bg-amber-50/50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/30 hover:shadow-md transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
                                </div>
                                <span class="font-bold text-xs">In Class</span>
                            </button>

                            <button type="button" onclick="updateStatus('BUSY')" class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-100 dark:border-slate-700 bg-rose-50/50 dark:bg-rose-900/10 text-rose-700 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/30 hover:shadow-md transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-rose-100 dark:bg-rose-900/50 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>
                                </div>
                                <span class="font-bold text-xs">Busy</span>
                            </button>

                            <button type="button" onclick="updateStatus('OFF_CAMPUS')" class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-100 dark:border-slate-700 bg-purple-50/50 dark:bg-purple-900/10 text-purple-700 dark:text-purple-400 hover:bg-purple-100 dark:hover:bg-purple-900/30 hover:shadow-md transition-all duration-200">
                                <div class="w-8 h-8 rounded-full bg-purple-100 dark:bg-purple-900/50 flex items-center justify-center mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>
                                </div>
                                <span class="font-bold text-xs">Off Campus</span>
                            </button>
                        </div>
                    </div>

                    <!-- Note & Location Panel -->
                    <div class="flex flex-col gap-4">
                        <!-- Note Input -->
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 shadow-lg p-5 flex flex-col h-full">
                            <label class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3 block">Quick Note</label>
                            <div class="flex gap-2 flex-1 items-start">
                                <input type="text" id="statusNote" value="<?= htmlspecialchars($currentNote) ?>" placeholder="e.g. 'Back in 15 mins'..." class="w-full p-3 bg-gray-50 dark:bg-slate-900 border-none rounded-xl focus:ring-2 focus:ring-blue-500 text-sm font-medium">
                                <button id="btnNoteAction" onclick="handleNoteAction()" class="p-3 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-blue-600 hover:text-white dark:hover:bg-blue-600 transition-colors" title="Save Note">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- GPS Button -->
                        <button id="btnLoc" class="w-full h-full group relative bg-slate-900 dark:bg-slate-800 border border-slate-800 dark:border-slate-700 text-white rounded-2xl p-5 hover:bg-slate-800 dark:hover:bg-slate-700 transition-all duration-300 shadow-lg hover:-translate-y-0.5 flex flex-col justify-center items-center overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-indigo-600/20 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="relative z-10 flex items-center gap-3">
                                <span class="relative flex h-3 w-3">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                                </span>
                                <span class="font-bold text-sm tracking-wide">Broadcast Location</span>
                            </div>
                            <div class="relative z-10 text-xs text-slate-400 mt-1">Use GPS for precise tracking</div>
                        </button>
                    </div>

                    <p id="locMsg" class="col-span-1 md:col-span-2 text-sm text-center text-gray-400 h-5 mt-2"></p>
                </div>
            </div>

        </main>
        </div>
    </div>

    <!-- Live Campus Map Modal (Shared) -->
    <?php include __DIR__ . '/../partials/campus_map_modal.php'; ?>


    <!-- Note Expiry Modal -->
    <dialog id="noteExpiryModal" class="p-6 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-full max-w-md">
        <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Remove Note until?</h3>
        <div class="grid grid-cols-1 gap-3">
            <button onclick="submitNote(5)" class="p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 5 Minutes</button>
            <button onclick="submitNote(10)" class="p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 10 Minutes</button>
            <button onclick="submitNote(15)" class="p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 15 Minutes</button>
            <button onclick="submitNote(30)" class="p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 30 Minutes</button>
            <button onclick="submitNote(60)" class="p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 1 Hour</button>
            <button onclick="submitNote('MANUAL')" class="p-3 text-left rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors font-bold">Until I change it</button>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-700 flex justify-end">
            <button onclick="document.getElementById('noteExpiryModal').close()" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition-colors">Cancel</button>
        </div>
    </dialog>

    <!-- Quick Update Modal -->
    <dialog id="quickUpdateModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/70 dark:bg-slate-800 w-[92%] sm:max-w-sm overflow-hidden">
        <div class="relative bg-white dark:bg-slate-800">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Quick Update</h3>
                <button onclick="closeQuickUpdate()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Content Container -->
            <div id="modalContent" class="relative overflow-hidden" style="min-height: 280px;">
                
                <!-- Step 1: In a Class? -->
                <div id="step1" class="modal-step absolute inset-0 p-4 sm:p-6">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4 text-center">In a Class?</h4>
                    <div class="grid grid-cols-2 gap-4">
                        <button onclick="handleInClass(true)" class="p-5 sm:p-6 rounded-lg border-2 border-green-200 dark:border-green-800 bg-white dark:bg-slate-900 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 hover:shadow-lg transition-all font-bold text-lg">
                            Yes
                        </button>
                        <button onclick="handleInClass(false)" class="p-5 sm:p-6 rounded-lg border-2 border-red-200 dark:border-red-800 bg-white dark:bg-slate-900 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 hover:shadow-lg transition-all font-bold text-lg">
                            No
                        </button>
                    </div>
                </div>

                <!-- Step 2a: Room Number (if in class) -->
                <div id="step2a" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Room #?</h4>
                    <input type="text" id="quickRoomNumber" placeholder="Enter room number" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-800 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 mb-4">
                    <button onclick="handleRoomNumber()" class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                        Next
                    </button>
                </div>

                <!-- Step 2b: Subject Selection (if in class) -->
                <div id="step2b" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Subject?</h4>
                    <input type="text" id="quickSubjectSearch" placeholder="Search your subjects..." class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-800 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 mb-3">
                    <div id="quickSubjectList" class="max-h-64 overflow-y-auto space-y-2">
                        <!-- Subject options will be populated here -->
                    </div>
                </div>

                <!-- Step 3a: Status Selection (if NOT in class) -->
                <div id="step3a" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Your Status?</h4>
                    <div class="space-y-3">
                        <button onclick="handleStatusChoice('AVAILABLE')" class="w-full p-3 sm:p-4 rounded-lg border-2 border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:shadow-lg transition-all font-medium text-left flex items-center gap-3">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                            Available
                        </button>
                        <button onclick="handleStatusChoice('BUSY')" class="w-full p-3 sm:p-4 rounded-lg border-2 border-rose-200 dark:border-rose-800 bg-white dark:bg-slate-900 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:shadow-lg transition-all font-medium text-left flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>
                            Busy
                        </button>
                        <button onclick="handleStatusChoice('OFF_CAMPUS')" class="w-full p-3 sm:p-4 rounded-lg border-2 border-purple-200 dark:border-purple-800 bg-white dark:bg-slate-900 text-purple-700 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:shadow-lg transition-all font-medium text-left flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>
                            Off Campus
                        </button>
                    </div>
                </div>

                <!-- Step 3b: Add Note (optional) -->
                <div id="step3b" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Add a Note?</h4>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-3">Optional - leave blank to skip</p>
                    <input type="text" id="quickNoteInput" placeholder="e.g., 'Back in office at 2:30 PM'" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-800 dark:text-white placeholder-gray-400 dark:placeholder-slate-600 mb-4">
                    <div class="flex gap-2">
                        <button onclick="handleNote(true)" class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                            Next
                        </button>
                        <button onclick="handleNote(false)" class="px-6 py-3 bg-gray-200 dark:bg-slate-700 hover:bg-gray-300 dark:hover:bg-slate-600 text-gray-700 dark:text-slate-300 font-medium rounded-lg transition-colors">
                            Skip
                        </button>
                    </div>
                </div>

                <!-- Step 3c: Note Duration -->
                <div id="step3c" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <h4 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Remove Note until?</h4>
                    <div class="space-y-2">
                        <button onclick="handleNoteDuration(5)" class="w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 5 Minutes</button>
                        <button onclick="handleNoteDuration(10)" class="w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 10 Minutes</button>
                        <button onclick="handleNoteDuration(15)" class="w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 15 Minutes</button>
                        <button onclick="handleNoteDuration(30)" class="w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 30 Minutes</button>
                        <button onclick="handleNoteDuration(60)" class="w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium">For 1 Hour</button>
                        <button onclick="handleNoteDuration('MANUAL')" class="w-full p-3 text-left rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors font-bold">Until I change it</button>
                    </div>
                </div>

                <!-- Step 4: Loading/Processing -->
                <div id="step4" class="modal-step absolute inset-0 p-4 sm:p-6 hidden">
                    <div class="flex flex-col items-center justify-center py-8">
                        <div class="loader mb-4">
                            <div class="loader-square"></div>
                            <div class="loader-square"></div>
                            <div class="loader-square"></div>
                        </div>
                        <p id="loadingText" class="text-slate-600 dark:text-slate-400 font-medium text-center"></p>
                    </div>
                </div>

            </div>
        </div>
    </dialog>

    <style>
        .modal-step {
            transition: none;
            background: inherit;
        }
        .modal-step.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .modal-step:not(.hidden) {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* Sliding animations */
        .modal-step.slide-out-left {
            animation: slideOutLeft 0.3s ease-out forwards;
        }
        .modal-step.slide-in-right {
            animation: slideInRight 0.3s ease-out forwards;
        }
        
        @keyframes slideOutLeft {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>

    <script>
    // AJAX Status Update Function
    const statusConfig = {
        'AVAILABLE': {
            'color': '#10b981',
            'bg': '', 
            'text': 'text-white',
            'border': 'border-emerald-700 dark:border-emerald-600',
            'icon': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>'
        },
        'IN_CLASS': {
            'color': '#f59e0b',
            'bg': '',
            'text': 'text-white',
            'border': 'border-amber-700 dark:border-amber-600',
            'icon': '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>'
        },
        'BUSY': {
            'color': '#ef4444',
            'bg': '',
            'text': 'text-white',
            'border': 'border-rose-700 dark:border-rose-600',
            'icon': '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>'
        },
        'OFF_CAMPUS': {
            'color': '#a855f7',
            'bg': '',
            'text': 'text-white',
            'border': 'border-purple-700 dark:border-purple-600',
            'icon': '<svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>'
        },
        'OFFLINE': {
            'color': '#64748b',
            'bg': '',
            'text': 'text-white',
            'border': 'border-slate-700 dark:border-slate-600',
            'icon': '<svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>'
        }
    };

    async function updateStatus(status) {
        showToast('Updating status...', 'info');
        try {
            const formData = new FormData();
            formData.append('status', status);
            const res = await fetch('<?= url("?page=teacher_status_post") ?>', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                showToast(data.message || 'Status updated successfully!', 'success');
                document.getElementById('currentStatusText').textContent = status;
                
                // Update Styling
                const config = statusConfig[status] || statusConfig['OFFLINE'];
                const card = document.getElementById('statusCard');
                const label = document.getElementById('statusLabel');
                const text = document.getElementById('currentStatusText');
                const icon = document.getElementById('statusIcon');
                const noteContainer = document.getElementById('currentNoteContainer');

                // Reset logic (remove all potential classes)
                // A simpler way: hard set class attribute if we knew all classes. 
                // But we want to preserve layout classes.
                // We will manually remove all possible bg/text/border colors from config.
                const allConfigs = Object.values(statusConfig);
                allConfigs.forEach(c => {
                    if (c.bg) c.bg.split(' ').filter(cls => cls).forEach(cls => card.classList.remove(cls));
                    if (c.border) c.border.split(' ').filter(cls => cls).forEach(cls => card.classList.remove(cls));
                    
                    const textClasses = c.text ? c.text.split(' ').filter(cls => cls) : [];
                    textClasses.forEach(cls => {
                        label.classList.remove(cls);
                        text.classList.remove(cls);
                        icon.classList.remove(cls);
                        noteContainer.classList.remove(cls);
                    });
                });

                // Add new classes
                // config.bg is empty now, but kept for logic consistency if added back
                if (config.bg) config.bg.split(' ').filter(cls => cls).forEach(cls => card.classList.add(cls));
                if (config.border) config.border.split(' ').filter(cls => cls).forEach(cls => card.classList.add(cls));
                
                if (config.text) {
                    config.text.split(' ').filter(cls => cls).forEach(cls => {
                        label.classList.add(cls);
                        text.classList.add(cls);
                        icon.classList.add(cls);
                        noteContainer.classList.add(cls);
                    });
                }
                
                // Set inline color
                card.style.backgroundColor = config.color;

                // Update Icon
                icon.innerHTML = config.icon;

            } else {
                showToast(data.message || 'Failed to update status', 'error');
            }
        } catch (err) {
             showToast('Error: ' + err.message, 'error');
        }
    }

    function checkNoteAndOpenModal() {
        const noteInput = document.getElementById('statusNote');
        const note = noteInput ? noteInput.value.trim() : '';
        if (note === '') { submitNote('MANUAL'); } 
        else { document.getElementById('noteExpiryModal').showModal(); }
    }

    // Quick Note Logic
    let savedNoteContent = "<?= htmlspecialchars($currentNote) ?>";

    document.addEventListener('DOMContentLoaded', () => {
        const noteInput = document.getElementById('statusNote');
        if (noteInput) {
            noteInput.addEventListener('input', updateNoteButtonState);
            noteInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') handleNoteAction();
            });
            updateNoteButtonState();
        }
    });

    function updateNoteButtonState() {
        const input = document.getElementById('statusNote');
        const btn = document.getElementById('btnNoteAction');
        if (!input || !btn) return;
        
        const currentVal = input.value.trim();
        
        // Logic:
        // If saved note exists and input matches it -> Show X (Remove)
        // Otherwise (no saved note OR input changed) -> Show Check (Save)
        if (savedNoteContent !== '' && currentVal === savedNoteContent) {
            // Show X (Remove)
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>';
            btn.classList.add('text-red-500', 'dark:text-red-400');
            btn.title = "Remove Note";
        } else {
            // Show Check (Save)
            btn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>';
            btn.classList.remove('text-red-500', 'dark:text-red-400');
            btn.title = "Save Note";
        }
    }

    function handleNoteAction() {
        const input = document.getElementById('statusNote');
        // If button is in "X" state
        if (savedNoteContent !== '' && input.value.trim() === savedNoteContent) {
            // Clear Note
            input.value = '';
            submitNote('MANUAL');
        } else {
            // Save Note
            checkNoteAndOpenModal();
        }
    }

    let noteTimerInterval;

    async function submitNote(expiryOption) {
        // If modal is open, close it
        const modal = document.getElementById('noteExpiryModal');
        if (modal && modal.open) modal.close();
        
        const noteInput = document.getElementById('statusNote');
        const note = noteInput.value.trim();
        
        showToast('Saving note...', 'info');

        try {
            const formData = new FormData();
            formData.append('note', note);
            formData.append('expiry_option', expiryOption);

            const res = await fetch('<?= url("?page=teacher_note_post") ?>', {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (data.success) {
                 showToast('Note saved successfully!', 'success');
                 
                 // Update UI
                 const noteContainer = document.getElementById('currentNoteContainer');
                 const noteText = document.getElementById('currentNoteText');
                 
                 if (noteContainer && noteText) {
                    if (data.note !== '') {
                        noteText.textContent = data.note;
                        noteContainer.classList.remove('hidden');
                        
                        // Handle Timer
                        if (data.expires_at) {
                            startNoteTimer(data.expires_at);
                        } else {
                            stopNoteTimer();
                        }
                    } else {
                        noteContainer.classList.add('hidden');
                        stopNoteTimer();
                    }
                 }

                 // Update Local State for Button Logic
                 savedNoteContent = data.note;
                 updateNoteButtonState();
            } else {
                showToast('Failed to save note', 'error');
            }
        } catch (err) {
            showToast('Error saving note: ' + err.message, 'error');
        }
    }

    function stopNoteTimer() {
        if (noteTimerInterval) clearInterval(noteTimerInterval);
        const timerEl = document.getElementById('noteTimer');
        if (timerEl) timerEl.classList.add('hidden');
    }

    function startNoteTimer(expiresAtStr) {
        stopNoteTimer();
        const timerEl = document.getElementById('noteTimer');
        if (!timerEl) return;
        
        const expiresAt = new Date(expiresAtStr).getTime();
        
        timerEl.classList.remove('hidden');
        
        function update() {
            const now = new Date().getTime();
            const distance = expiresAt - now;
            
            if (distance < 0) {
                stopNoteTimer();
                // Optionally refresh or hide note
                document.getElementById('currentNoteContainer').classList.add('hidden');
                document.getElementById('statusNote').value = '';
                showToast('Note expired', 'info');
                return;
            }
            
            // Calc minutes and seconds
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            timerEl.textContent = `${minutes}m ${seconds}s`;
        }
        
        update(); // Run immediately
        noteTimerInterval = setInterval(update, 1000);
    }

    async function updateStatus_OLD(status) {
        const note = document.getElementById('statusNote').value;
        const msg = document.getElementById('statusMsg');
        
        // msg.textContent = 'Updating status...';
        // msg.className = 'text-sm text-center text-blue-600 dark:text-blue-400 mt-3 font-medium animate-pulse';
        showToast('Updating status...', 'info');
        
        try {
            const formData = new FormData();
            formData.append('status', status);
            formData.append('note', note);
            
            const res = await fetch('<?= url("?page=teacher_status_post") ?>', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (data.success) {
                // msg.textContent = data.message || 'Status updated successfully!';
                // msg.className = 'text-sm text-center text-green-600 dark:text-green-400 mt-3 font-bold';
                showToast(data.message || 'Status updated successfully!', 'success');
                
                // Update UI without reload
                document.getElementById('currentStatusText').textContent = status;
                
                // Update note display
                const noteContainer = document.getElementById('currentNoteContainer');
                const noteText = document.getElementById('currentNoteText');
                
                if (note && note.trim() !== '') {
                    noteText.textContent = note;
                    noteContainer.classList.remove('hidden');
                } else {
                    noteContainer.classList.add('hidden');
                }
                
                // Clear input
                document.getElementById('statusNote').value = '';
                
                // Clear success message after delay
                // setTimeout(() => {
                //     msg.textContent = '';
                // }, 3000);
            } else {
                // msg.textContent = data.message || 'Failed to update status';
                // msg.className = 'text-sm text-center text-red-500 mt-3 font-bold';
                showToast(data.message || 'Failed to update status', 'error');
            }
        } catch (err) {
            // msg.textContent = 'Error: ' + err.message;
            // msg.className = 'text-sm text-center text-red-500 mt-3 font-bold';
             showToast('Error: ' + err.message, 'error');
        }
    }
    
    // GPS Update Logic
    document.getElementById('btnLoc').addEventListener('click', async () => {
        const msg = document.getElementById('locMsg');
        
        if (!navigator.geolocation) {
            msg.textContent = 'Geolocation is not supported by your browser';
            return;
        }

        // Check for secure context
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            msg.innerHTML = '<span class="text-rose-500">Error: Location requires HTTPS.</span>';
            return;
        }

        msg.textContent = 'Locating...';
        
        // Fetch Campus Radar Settings
        let campusLat = 11.3003;
        let campusLng = 124.6856;
        let radiusMeters = 500;

        try {
            const res = await fetch('<?= url("?page=campus_radar_json") ?>');
            const data = await res.json();
            if (data.lat && data.lng) {
                campusLat = parseFloat(data.lat);
                campusLng = parseFloat(data.lng);
                radiusMeters = parseFloat(data.radius_meters) || 500;
            }
        } catch (e) {
            console.error("Failed to fetch campus radar settings", e);
        }

        navigator.geolocation.getCurrentPosition(async (pos) => {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const acc = pos.coords.accuracy;
            
            // Calculate Distance to Campus
            const R = 6371e3; // metres
            const φ1 = lat * Math.PI/180; // φ, λ in radians
            const φ2 = campusLat * Math.PI/180;
            const Δφ = (campusLat-lat) * Math.PI/180;
            const Δλ = (campusLng-lng) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            const distance = R * c; // in metres
            
            // Auto-update status if outside
            // Note: Server-side also does this check, but good to check client side too for immediate feedback
            let newStatus = 'AVAILABLE'; 
            // We don't want to auto-set AVAILABLE if they set themselves to BUSY manually?
            // The requirement says: "whenever a updated status teacher is outside the radar ... his/her status will be 'Off Campus'"
            // It doesn't strictly say we must set it to AVAILABLE if inside.
            
            // So we only force OFF_CAMPUS if outside.
            if (distance > radiusMeters) {
                newStatus = 'OFF_CAMPUS';
                // We should probably tell the user or just update it
            } else {
                // If inside, we just keep current status or maybe default to AVAILABLE if they were OFF_CAMPUS?
                // For now let's just send the location. Use current status from UI.
                newStatus = document.getElementById('currentStatusText').textContent.trim();
                if (newStatus === 'OFF_CAMPUS') newStatus = 'AVAILABLE'; // Default back to available if they return?
                // Let's safe bet: don't change status if inside, unless they were off campus.
            }
            
            // Actually, let's let the server handle the status update logic for now, 
            // OR we can do it here to be responsive.
            // Let's stick to the plan: if distance > radius -> OFF_CAMPUS.
            
            if (distance > radiusMeters) {
                 await updateStatus('OFF_CAMPUS');
                 msg.textContent = `Location sent (Outside Campus: ${Math.round(distance)}m away)`;
            } else {
                 msg.textContent = `Location sent (Inside Campus)`;
            }

            try {
                const res = await fetch('<?= url("?page=teacher_location_post") ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        lat: lat,
                        lng: lng,
                        accuracy_m: acc
                    })
                });
                const data = await res.json();
                
                if (data.success && data.new_status) {
                    await updateStatus(data.new_status);
                    // Update text to reflect exact status since updateStatus might just toast
                    // But our updateStatus function actually updates the UI too!
                    // See lines ~560 in original file (updateStatus function)
                    // So calling updateStatus(data.new_status) is perfect.
                    
                    // Note: updateStatus sends a POST request to teacher_status_post.
                    // We might not want to DOUBLE send.
                    // Ideally we just update the UI.
                    
                    // Let's copy the UI update logic from updateStatus or extract it.
                    // For now, to be safe and simple:
                    // Only update UI if we have a function for it.
                    // Actually updateStatus sends a network request. We want to avoid that if the server already updated it.
                    
                    // Let's MANUALLY update UI here to match updateStatus logic
                    document.getElementById('currentStatusText').textContent = data.new_status;
                    
                    // We need statusConfig definitions here. They are defined globally in script? 
                    // Yes, lines 513-549.
                    
                    const config = statusConfig[data.new_status] || statusConfig['OFFLINE'];
                    const card = document.getElementById('statusCard');
                    const label = document.getElementById('statusLabel');
                    const text = document.getElementById('currentStatusText');
                    const icon = document.getElementById('statusIcon');
                    const noteContainer = document.getElementById('currentNoteContainer');
    
                    const allConfigs = Object.values(statusConfig);
                    allConfigs.forEach(c => {
                        if (c.bg) c.bg.split(' ').filter(cls => cls).forEach(cls => card.classList.remove(cls));
                        if (c.border) c.border.split(' ').filter(cls => cls).forEach(cls => card.classList.remove(cls));
                        
                        const textClasses = c.text ? c.text.split(' ').filter(cls => cls) : [];
                        textClasses.forEach(cls => {
                            label.classList.remove(cls);
                            text.classList.remove(cls);
                            icon.classList.remove(cls);
                            noteContainer.classList.remove(cls);
                        });
                    });
    
                    if (config.bg) config.bg.split(' ').filter(cls => cls).forEach(cls => card.classList.add(cls));
                    if (config.border) config.border.split(' ').filter(cls => cls).forEach(cls => card.classList.add(cls));
                    
                    if (config.text) {
                        config.text.split(' ').filter(cls => cls).forEach(cls => {
                            label.classList.add(cls);
                            text.classList.add(cls);
                            icon.classList.add(cls);
                            noteContainer.classList.add(cls);
                        });
                    }
                    
                    card.style.backgroundColor = config.color;
                    icon.innerHTML = config.icon;
                    
                    if (data.new_status === 'OFF_CAMPUS') {
                        msg.textContent = `Location sent (Outside Campus) - Status updated to OFF CAMPUS`;
                    } else if (data.new_status === 'AVAILABLE') {
                        msg.textContent = `Location sent (Inside Campus) - Status updated to AVAILABLE`;
                    }
                }
            } catch (err) {
                console.error("Loc upload failed", err);
            }

        }, (err) => {
            let errorMessage = err.message;
            if (errorMessage.includes("secure origin") || errorMessage.includes("secure context")) {
                errorMessage = "Location access requires HTTPS.";
            } else if (err.code === 1) { // PERMISSION_DENIED
                errorMessage = "Location permission denied.";
            }
            msg.textContent = 'Error: ' + errorMessage;
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    });

        
        // Removed duplicate/broken GPS logic
    
    // Removed duplicate map initialization logic
    
    // Quick Update Modal Functions
    let quickUpdateData = {
        inClass: false,
        roomNumber: '',
        subject: '',
        status: '',
        note: '',
        noteDuration: null
    };

    let teacherSubjects = [];

    // Fetch teacher's subjects when modal opens
    async function fetchTeacherSubjects() {
        try {
            const response = await fetch('<?= url("?page=teacher_subjects_api") ?>');
            if (response.ok) {
                const data = await response.json();
                teacherSubjects = data.subjects || [];
            }
        } catch (err) {
            console.error('Failed to fetch subjects:', err);
        }
    }

    // Navigate to a step with animation
    function navigateToStep(fromStepId, toStepId) {
        if (!toStepId) return;
        
        const toStep = document.getElementById(toStepId);
        
        if (fromStepId) {
            const fromStep = document.getElementById(fromStepId);
            
            // Slide out the current step
            fromStep.classList.add('slide-out-left');
            
            // Slide in the new step
            toStep.classList.remove('hidden');
            toStep.classList.add('slide-in-right');
            
            // Clean up after animation
            setTimeout(() => {
                fromStep.classList.add('hidden');
                fromStep.classList.remove('slide-out-left');
                fromStep.style.transform = '';
                
                toStep.classList.remove('slide-in-right');
                toStep.style.transform = '';
            }, 300);
        } else {
            // No previous step, just show the new one
            ['step1', 'step2a', 'step2b', 'step3a', 'step3b', 'step3c', 'step4'].forEach(id => {
                if (id !== toStepId) {
                    document.getElementById(id).classList.add('hidden');
                }
            });
            toStep.classList.remove('hidden');
        }
    }

    // Reset modal to initial state
    function resetQuickUpdateModal() {
        quickUpdateData = {
            inClass: false,
            roomNumber: '',
            subject: '',
            status: '',
            note: '',
            noteDuration: null
        };
        
        // Hide all steps except step1
        ['step2a', 'step2b', 'step3a', 'step3b', 'step3c', 'step4'].forEach(id => {
            document.getElementById(id).classList.add('hidden');
        });
        document.getElementById('step1').classList.remove('hidden');
        
        // Clear inputs
        document.getElementById('quickRoomNumber').value = '';
        document.getElementById('quickSubjectSearch').value = '';
        document.getElementById('quickNoteInput').value = '';
    }

    // Close modal
    function closeQuickUpdate() {
        document.getElementById('quickUpdateModal').close();
        setTimeout(resetQuickUpdateModal, 300);
    }

    // Step 1: Handle "In a Class?" choice
    function handleInClass(isInClass) {
        quickUpdateData.inClass = isInClass;
        
        if (isInClass) {
            navigateToStep('step1', 'step2a');
            // Focus on room number input
            setTimeout(() => {
                document.getElementById('quickRoomNumber').focus();
            }, 350);
        } else {
            navigateToStep('step1', 'step3a');
        }
    }

    // Step 2a: Handle room number
    function handleRoomNumber() {
        const roomNumber = document.getElementById('quickRoomNumber').value.trim();
        if (!roomNumber) {
            showToast('Please enter a room number', 'error');
            return;
        }
        quickUpdateData.roomNumber = roomNumber;
        
        // Load subjects and navigate to step2b
        loadQuickSubjects();
        navigateToStep('step2a', 'step2b');
        setTimeout(() => {
            document.getElementById('quickSubjectSearch').focus();
        }, 350);
    }

    // Load teacher subjects into the quick modal
    async function loadQuickSubjects() {
        if (teacherSubjects.length === 0) {
            await fetchTeacherSubjects();
        }
        
        const listContainer = document.getElementById('quickSubjectList');
        listContainer.innerHTML = '';
        
        if (teacherSubjects.length === 0) {
            listContainer.innerHTML = '<p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No subjects assigned. Please assign subjects first.</p>';
            return;
        }
        
        teacherSubjects.forEach(subject => {
            const btn = document.createElement('button');
            btn.className = 'w-full p-3 text-left rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 transition-colors font-medium subject-option';
            btn.textContent = subject;
            btn.onclick = () => handleSubjectChoice(subject);
            listContainer.appendChild(btn);
        });
        
        // Add search functionality
        const searchInput = document.getElementById('quickSubjectSearch');
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const options = listContainer.querySelectorAll('.subject-option');
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    // Step 2b: Handle subject choice
    function handleSubjectChoice(subject) {
        quickUpdateData.subject = subject;
        quickUpdateData.status = 'IN_CLASS';
        
        // Proceed to final update
        performQuickUpdate();
    }

    // Step 3a: Handle status choice (when NOT in class)
    function handleStatusChoice(status) {
        quickUpdateData.status = status;
        navigateToStep('step3a', 'step3b');
        setTimeout(() => {
            document.getElementById('quickNoteInput').focus();
        }, 350);
    }

    // Step 3b: Handle note input
    function handleNote(continueWithNote) {
        if (continueWithNote) {
            const note = document.getElementById('quickNoteInput').value.trim();
            if (!note) {
                // If they clicked Next but no note, just skip
                quickUpdateData.note = '';
                performQuickUpdate();
                return;
            }
            quickUpdateData.note = note;
            navigateToStep('step3b', 'step3c');
        } else {
            // Skip note
            quickUpdateData.note = '';
            performQuickUpdate();
        }
    }

    // Step 3c: Handle note duration
    function handleNoteDuration(duration) {
        quickUpdateData.noteDuration = duration;
        performQuickUpdate();
    }

    // Perform the actual update
    async function performQuickUpdate() {
        navigateToStep(null, 'step4');
        
        const loadingText = document.getElementById('loadingText');
        
        try {
            // Step 1: Update Status
            loadingText.textContent = 'Updating Status...';
            await updateQuickStatus();
            
            // Step 2: Handle Session Data
            if (quickUpdateData.inClass) {
                // If in class, save the new room/subject
                if (quickUpdateData.roomNumber || quickUpdateData.subject) {
                    loadingText.textContent = 'Saving Room & Subject...';
                    await updateQuickSession();
                }
            } else {
                // If NOT in class, clear any existing session data
                loadingText.textContent = 'Clearing Class Data...';
                quickUpdateData.roomNumber = '';
                quickUpdateData.subject = '';
                await updateQuickSession();
            }
            
            // Step 3: Update Note (if any)
            if (quickUpdateData.note) {
                loadingText.textContent = 'Saving Note...';
                await updateQuickNote();
            }
            
            // Step 4: Update Location
            loadingText.textContent = 'Updating Location...';
            await updateQuickLocation(quickUpdateData.inClass);
            
            // Done
            loadingText.textContent = 'Done!';
            showToast('Quick update completed successfully!', 'success');
            
            setTimeout(() => {
                closeQuickUpdate();
                // Refresh the page to show updated status
                location.reload();
            }, 1000);
            
        } catch (err) {
            loadingText.textContent = 'Error occurred';
            showToast('Failed to complete update: ' + err.message, 'error');
            setTimeout(closeQuickUpdate, 2000);
        }
    }

    // Update status via API
    async function updateQuickStatus() {
        const formData = new FormData();
        formData.append('status', quickUpdateData.status);
        
        const res = await fetch('<?= url("?page=teacher_status_post") ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to update status');
        }
    }

    // Update note via API
    async function updateQuickNote() {
        const formData = new FormData();
        formData.append('note', quickUpdateData.note);
        formData.append('expiry_option', quickUpdateData.noteDuration || 'MANUAL');
        
        const res = await fetch('<?= url("?page=teacher_note_post") ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to save note');
        }
    }

    // Update current session (room & subject)
    async function updateQuickSession() {
        const formData = new FormData();
        formData.append('room', quickUpdateData.roomNumber || '');
        formData.append('subject', quickUpdateData.subject || '');
        
        const res = await fetch('<?= url("?page=teacher_session_update") ?>', {
            method: 'POST',
            body: formData
        });
        
        const data = await res.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to update session');
        }
    }

    // Update location via GPS
    async function updateQuickLocation(skipRoomClear = false) {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                reject(new Error('Location requires HTTPS connection'));
                return;
            }
            
            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            };
            
            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    try {
                        const payload = {
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude,
                            accuracy_m: pos.coords.accuracy,
                            skip_room_clear: skipRoomClear
                        };
                        
                        const res = await fetch('<?= url("?page=teacher_location_post") ?>', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify(payload)
                        });
                        
                        if (!res.ok) {
                            throw new Error('Location update failed');
                        }
                        resolve();
                    } catch (err) {
                        reject(err);
                    }
                },
                (err) => {
                    let errorMessage = err.message;
                    if (errorMessage.includes("secure origin")) {
                         errorMessage = "Location requires HTTPS";
                    } else if (err.code === 1) {
                         errorMessage = "Location permission denied";
                    }
                    reject(new Error(errorMessage));
                },
                options
            );
        });
    }

    // Fetch subjects when page loads
    fetchTeacherSubjects();
    
    // Initialize timer if exists
    <?php if ($currentExpiresAt): ?>
    startNoteTimer('<?= $currentExpiresAt ?>');
    <?php endif; ?>
    </script>
    <script src="<?= url('assets/mobile.js') ?>"></script>
    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
