<?php
// app/pages/student_dashboard.php

require_login();
require_role('student');

$search = $_GET['search'] ?? '';
$pdo = db();

// Trigger auto-offline check logic (same as map)
require_once __DIR__ . '/../actions/auto_offline_helper.php';
check_and_process_expirations();

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
    <script src="<?= url('assets/loader.js') ?>"></script>
    
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (Shared) -->
        <?php include __DIR__ . '/../partials/student_sidebar.php'; ?>



        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
             <!-- Header for Mobile -->
             <?php include __DIR__ . '/../partials/student_mobile_header.php'; ?>


            <!-- Desktop Header / Top Bar -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    Student Portal
                </div>
                <div class="flex items-center gap-4">
                    <!-- Theme Toggle Desktop -->
                    <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-7xl mx-auto font-sans">
                <div class="relative text-center mb-6 md:mb-10 pt-6">
                    <!-- Decorative background glow -->
                    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[150px] md:w-[400px] h-[150px] md:h-[200px] bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-[40px] md:blur-[60px] -z-10 pointer-events-none"></div>
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider mb-2 md:mb-4 border border-blue-100 dark:border-blue-800 shadow-sm mx-auto">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Real-time Faculty Tracking
                    </div>
                    
                    <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-2 md:mb-3 tracking-tight">
                        Find Your <span id="rotatingText" class="rotating-text text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">Professors</span>
                    </h1>
                    
                    <p class="text-slate-600 dark:text-slate-400 text-sm md:text-base max-w-xl mx-auto leading-relaxed">
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
                            class="w-full px-4 py-4 bg-transparent text-sm md:text-lg font-medium text-slate-700 dark:text-white placeholder-gray-400 dark:placeholder-slate-500 focus:outline-none"
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
                        <a href="<?= url('?page=student_teacher&id=' . $t['id']) ?>" 
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
                                switch($status) {
                                    case 'AVAILABLE':
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-emerald-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-emerald-500/20">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/></svg>
                                            <span>Available</span>
                                        </div>';
                                        break;
                                    case 'IN_CLASS':
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-amber-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-amber-500/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
                                            <span>In Class</span>
                                        </div>';
                                        break;
                                    case 'BUSY':
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-rose-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-rose-500/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5 5l14 14"/></svg>
                                            <span>Busy</span>
                                        </div>';
                                        break;
                                    case 'OFF_CAMPUS':
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-purple-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-purple-500/20">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>
                                            <span>Off Campus</span>
                                        </div>';
                                        break;
                                    case 'OFFLINE':
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-slate-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-slate-500/20">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>
                                            <span>Offline</span>
                                        </div>';
                                        break;
                                    default:
                                        $badgeHTML = '<div class="flex items-center gap-1.5 bg-gray-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-sm shadow-gray-500/20">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="2"/></svg>
                                            <span>Unknown</span>
                                        </div>';
                                        break;
                                }
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
    </div>

    <!-- Live Campus Map Modal (Shared) -->
    <?php include __DIR__ . '/../partials/campus_map_modal.php'; ?>


    <script>


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
    <script src="<?= url('assets/mobile.js') ?>"></script>
</body>
</html>
