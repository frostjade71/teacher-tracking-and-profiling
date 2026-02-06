<?php
// app/pages/student_dashboard.php

require_login();
require_role('student');

$search = $_GET['search'] ?? '';
$pdo = db();

// Query teachers and their *latest* status
$sql = "
SELECT 
    u.id, u.name, u.email, 
    tp.employee_no, tp.department, tp.office_text, tp.current_room,
    (
        SELECT status 
        FROM teacher_status_events tse 
        WHERE tse.teacher_user_id = u.id 
        ORDER BY tse.set_at DESC 
        LIMIT 1
    ) as latest_status,
    (
        SELECT set_at 
        FROM teacher_status_events tse 
        WHERE tse.teacher_user_id = u.id 
        ORDER BY tse.set_at DESC 
        LIMIT 1
    ) as status_time
FROM users u
LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
WHERE u.role = 'teacher' AND u.is_active = 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$teachers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Faculty | Student Dashboard</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="/assets/map_arrows.js"></script>
    <script src="/assets/theme.js"></script>
    <style>
        #campusMap { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }

        /* Text Rotation Animation */
        .rotating-text {
            display: inline-block;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .rotate-out {
            transform: translateY(-20px);
            opacity: 0;
        }
        .rotate-in {
            transform: translateY(20px);
            opacity: 0;
        }


        /* Minimalist Tooltip */
        .room-tooltip {
            visibility: hidden;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%) translateY(-8px);
            background-color: #1e293b;
            color: #fff;
            padding: 6px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            transition: opacity 0.2s, transform 0.2s;
            pointer-events: none;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }
        .room-tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -4px;
            border-width: 4px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }
        .group\/room:hover .room-tooltip {
            visibility: visible;
            opacity: 1;
            transform: translateX(-50%) translateY(-4px);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen text-slate-800 dark:text-slate-200 transition-colors duration-200 font-sans">
    
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
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Student</span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="/?page=student_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    Find Faculty
                </a>
                
                <button onclick="document.getElementById('campusMapModal').showModal()" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors w-full">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </button>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                     <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <?= strtoupper(substr(current_user()['name'], 0, 1)) ?>
                    </div>
                    <div class="overflow-hidden">
                         <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars(current_user()['name']) ?></div>
                         <div class="text-xs text-slate-400 truncate">Student</div>
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
                    <a href="/?page=logout_post" class="text-sm font-medium text-gray-500 dark:text-slate-400">Sign Out</a>
                </div>
            </header>

            <!-- Desktop Header / Top Bar -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    Student Portal
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </div>

            <div class="max-w-6xl mx-auto px-6 py-10 font-sans">
                
                <div class="relative text-center mb-10 pt-6">
                    <!-- Decorative background glow -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[200px] md:w-[400px] h-[200px] bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-[60px] -z-10 pointer-events-none"></div>
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider mb-4 border border-blue-100 dark:border-blue-800 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Real-time Faculty Tracking
                    </div>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-3 tracking-tight">
                        Find Your <span id="rotatingText" class="rotating-text text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">Professors</span>
                    </h1>
                    
                    <p class="text-slate-600 dark:text-slate-400 text-base max-w-xl mx-auto leading-relaxed">
                        Locate faculty members instantly. Check live availability status and office locations across campus.
                    </p>
                </div>

                <!-- Search Bar -->
                <div class="max-w-3xl mx-auto mb-16 relative z-20">
                    <div class="absolute -inset-1 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl blur opacity-25 dark:opacity-40 animate-pulse transition duration-1000"></div>
                    <div class="relative flex items-center bg-white dark:bg-slate-800 rounded-2xl shadow-xl dark:shadow-slate-900/50 border border-gray-100 dark:border-slate-700 p-2 transition-transform focus-within:scale-[1.02] duration-300">
                        <div class="pl-4 text-gray-400 dark:text-slate-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="teacherSearch" value="<?= htmlspecialchars($search) ?>" 
                            class="w-full px-4 py-4 bg-transparent text-lg font-medium text-slate-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none"
                            placeholder="Search by professor name or department...">
                        <button class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold text-sm transition-all shadow-lg shadow-blue-500/30 hover:shadow-blue-600/40">
                            Search
                        </button>
                    </div>
                </div>

                <!-- Grid -->
                <div id="teacherGrid" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 relative z-10">
                    <?php foreach ($teachers as $t): ?>
                        <?php 
                            $status = $t['latest_status'] ?? 'UNKNOWN'; 
                            // $statusConfig logic is handled inside the badge match expression below or we can simplify.
                            // keeping original match logic for badges as it was quite detailed.
                        ?>
                        <a href="/?page=student_teacher&id=<?= $t['id'] ?>" 
                           data-name="<?= htmlspecialchars(strtolower($t['name'])) ?>" 
                           data-dept="<?= htmlspecialchars(strtolower($t['department'] ?? '')) ?>"
                           class="teacher-card group relative flex flex-col bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700 transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/10 hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 hover:z-30">
                            
                            <!-- Hover Gradient Background -->
                            <div class="absolute inset-0 rounded-xl bg-gradient-to-br from-blue-50/50 via-transparent to-transparent dark:from-blue-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                            
                            <div class="relative z-10 flex items-center gap-2 mb-4">
                                <div class="h-10 w-10 flex-shrink-0 rounded-xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-300 font-bold text-lg shadow-inner transition-all duration-300">
                                    <?= strtoupper(substr($t['name'], 0, 1)) ?>
                                </div>
                                <div class="flex-1 min-w-0">
                                     <h2 class="text-sm font-bold text-slate-900 dark:text-white transition-colors tracking-tight truncate leading-tight">
                                        <?= htmlspecialchars($t['name']) ?>
                                     </h2>
                                     <p class="text-[10px] font-semibold text-slate-500 dark:text-slate-400 tracking-wider truncate">
                                        <?= htmlspecialchars($t['department'] ?? 'Faculty Department') ?>
                                     </p>
                                </div>
                            </div>
                            
                            <div class="mt-auto pt-5 border-t border-slate-100 dark:border-slate-700/50 relative z-10 flex items-center justify-between">
                                <?php
                                $badgeHTML = match($status) {
                                    'AVAILABLE' => '<div class="flex items-center gap-1.5 bg-emerald-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-emerald-500/20">
                                        <span class="relative flex h-2 w-2">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                        </span>
                                        <span>Available</span>
                                    </div>',
                                    'IN_CLASS' => '<div class="flex items-center gap-1.5 bg-amber-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-amber-500/20">
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                        <span>In Class</span>
                                    </div>',
                                    'BUSY' => '<div class="flex items-center gap-1.5 bg-rose-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-rose-500/20">
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                        <span>Busy</span>
                                    </div>',
                                    'OFF_CAMPUS' => '<div class="flex items-center gap-1.5 bg-purple-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-purple-500/20">
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                        <span>Off Campus</span>
                                    </div>',
                                    'OFFLINE' => '<div class="flex items-center gap-1.5 bg-slate-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-slate-500/20">
                                        <div class="w-2 h-2 rounded-full bg-slate-300"></div>
                                        <span>Offline</span>
                                    </div>',
                                    default => '<div class="flex items-center gap-1.5 bg-gray-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-gray-500/20">
                                        <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                                        <span>Unknown</span>
                                    </div>'
                                };
                                echo $badgeHTML;
                                ?>

                                <?php if (!empty($t['current_room'])): ?>
                                    <div class="group/room relative flex items-center gap-2 bg-slate-50 dark:bg-slate-700/50 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 shadow-sm cursor-help">
                                        <div class="room-tooltip">
                                            Class in Room <?= htmlspecialchars($t['current_room']) ?>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        <span class="text-xs font-extrabold text-slate-700 dark:text-slate-200">
                                            <?= htmlspecialchars($t['current_room']) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div id="noResults" class="<?= count($teachers) === 0 ? '' : 'hidden' ?> max-w-lg mx-auto text-center py-20">
                    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-8">
                        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-400">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">No professors found</h3>
                        <p class="text-slate-500 dark:text-slate-400 text-sm mb-6">We couldn't find any faculty members matching your search terms.</p>
                        <button onclick="document.getElementById('teacherSearch').value = ''; document.getElementById('teacherSearch').dispatchEvent(new Event('input'));" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-xl transition-colors shadow-lg shadow-blue-500/20">
                            Clear Filters
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Live Campus Map Modal -->
    <dialog id="campusMapModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/70 dark:bg-slate-800 w-full max-w-6xl h-[80vh]">
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">Live Campus Map</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400">Updates every 10s</p>
                </div>
                <button onclick="document.getElementById('campusMapModal').close()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-500 dark:text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div id="campusMap" class="flex-1 bg-slate-100 dark:bg-slate-900 relative">
                <!-- Loading Overlay -->
                <div id="mapLoader" class="absolute inset-0 bg-white dark:bg-slate-800 flex flex-col items-center justify-center z-50">
                    <div class="loader">
                        <div class="loader-square"></div>
                        <div class="loader-square"></div>
                        <div class="loader-square"></div>
                    </div>
                    <p class="mt-4 text-slate-600 dark:text-slate-400 font-medium">Loading map...</p>
                </div>
            </div>
        </div>
    </dialog>

    <script>
    // Campus Map Modal Initialization
    let campusMapInstance = null;
    let campusMarkers = {};
    
    document.getElementById('campusMapModal').addEventListener('click', function(e) {
        if (e.target === this) this.close();
    });
    
    document.getElementById('campusMapModal').addEventListener('close', function() {
        if (campusMapInstance) {
            campusMapInstance.remove();
            campusMapInstance = null;
            campusMarkers = {};
        }
        // Reset loader for next open
        document.getElementById('mapLoader').style.display = 'flex';
    });
    
    const originalShowModal = HTMLDialogElement.prototype.showModal;
    HTMLDialogElement.prototype.showModal = function() {
        originalShowModal.call(this);
        if (this.id === 'campusMapModal' && !campusMapInstance) {
            setTimeout(() => {
                campusMapInstance = L.map('campusMap').setView([11.3003, 124.6856], 19);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(campusMapInstance);
                
                // Initialize Arrow System
                campusArrowSystem = new MapArrowSystem(campusMapInstance);
                
                // Hide loader when map is ready
                campusMapInstance.whenReady(() => {
                    setTimeout(() => {
                        document.getElementById('mapLoader').style.display = 'none';
                    }, 500);
                });
                
                // Force map to recalculate size
                setTimeout(() => {
                    campusMapInstance.invalidateSize();
                    updateCampusLocations();
                }, 100);
                
                // Poll every 10 seconds
                setInterval(updateCampusLocations, 10000);
            }, 200);
        }
    };
    
    async function updateCampusLocations() {
        if (!campusMapInstance) return;
        
        try {
            const response = await fetch('/?page=public_locations_json');
            const teachers = await response.json();
            
            teachers.forEach(t => {
                const lat = parseFloat(t.lat);
                const lng = parseFloat(t.lng);
                
                if (!lat || !lng) return;
                
                let popupContent = `
                    <div class="min-w-[150px]">
                        <h3 class="font-bold text-slate-800 text-sm mb-1">${t.name}</h3>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full font-bold shadow-sm">${t.status}</span>
                        <div class="text-xs text-gray-500 mt-2">
                            Last: ${t.captured_at}<br>
                            Acc: ${t.accuracy_m}m
                        </div>
                    </div>
                `;
                
                if (campusMarkers[t.id]) {
                    campusMarkers[t.id].setLatLng([lat, lng]).setPopupContent(popupContent);
                } else {
                    // Create Custom Icon
                    const teacherIcon = L.divIcon({
                        className: 'custom-map-icon',
                        html: `<div class="w-10 h-10 bg-blue-600 rounded-full border-2 border-white shadow-lg flex items-center justify-center relative">
                                 <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                 <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[6px] border-l-transparent border-r-[6px] border-r-transparent border-t-[8px] border-t-blue-600"></div>
                               </div>`,
                        iconSize: [40, 48],
                        iconAnchor: [20, 48],
                        popupAnchor: [0, -48]
                    });

                    campusMarkers[t.id] = L.marker([lat, lng], {icon: teacherIcon})
                        .addTo(campusMapInstance)
                        .bindPopup(popupContent);
                        
                    // Add to arrow system
                    if (campusArrowSystem) {
                        campusArrowSystem.addMarker(t.id, campusMarkers[t.id]);
                    }
                }
            });
        } catch (err) {
            console.error("Failed to fetch locations", err);
        }
    }
    
    // Auto-open modal if openMap parameter is present
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('openMap') === '1') {
        // Remove the parameter from URL without reload
        const newUrl = window.location.pathname + '?page=student_dashboard';
        window.history.replaceState({}, '', newUrl);
        
        // Open modal after a short delay
        setTimeout(() => {
            document.getElementById('campusMapModal').showModal();
        }, 500);
    }

    // Real-time Search Implementation
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('teacherSearch');
        const teacherCards = document.querySelectorAll('.teacher-card');
        const noResults = document.getElementById('noResults');
        const teacherGrid = document.getElementById('teacherGrid');

        function filterTeachers() {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            teacherCards.forEach(card => {
                const name = card.getAttribute('data-name');
                const dept = card.getAttribute('data-dept');

                if (name.includes(query) || dept.includes(query)) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
                teacherGrid.classList.add('hidden');
            } else {
                noResults.classList.add('hidden');
                teacherGrid.classList.remove('hidden');
            }
        }

        searchInput.addEventListener('input', filterTeachers);

        // Initial filter if search value exists (from URL or user input)
        if (searchInput.value) {
            filterTeachers();
        }

        // Text Rotation Logic
        const rotateWords = ["Professors", "Teachers", "Instructors"];
        let rotateIndex = 0;
        const rotateElement = document.getElementById('rotatingText');

        if (rotateElement) {
            setInterval(() => {
                // Determine next word
                rotateIndex = (rotateIndex + 1) % rotateWords.length;
                const nextWord = rotateWords[rotateIndex];

                // Animate Out
                rotateElement.classList.add('rotate-out');

                setTimeout(() => {
                    // Switch text and prepare for Animate In
                    rotateElement.textContent = nextWord;
                    rotateElement.classList.remove('rotate-out');
                    rotateElement.classList.add('rotate-in');

                    // Trigger reflow to ensure the rotate-in class is applied before removing it
                    void rotateElement.offsetWidth; 

                    // Animate In
                    rotateElement.classList.remove('rotate-in');
                }, 300); // Matches CSS transition duration
            }, 6000); // 6 seconds interval
        }
    });
    </script>
</body>
</html>
