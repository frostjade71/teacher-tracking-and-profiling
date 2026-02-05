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
    tp.employee_no, tp.department, tp.office_text,
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

$params = [];
if ($search) {
    $sql .= " AND (u.name LIKE ? OR tp.department LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
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
                
                <div class="text-center mb-12">
                    <h1 class="text-3xl md:text-4xl font-bold text-slate-900 dark:text-white mb-4">Find Your Professors</h1>
                    <p class="text-slate-500 dark:text-slate-400 text-lg max-w-2xl mx-auto">Search for faculty members to view their current availability status and office location.</p>
                </div>

                <!-- Search Bar -->
                <div class="max-w-2xl mx-auto mb-12 relative">
                    <form method="GET" class="relative">
                        <input type="hidden" name="page" value="student_dashboard">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                            class="w-full pl-5 pr-14 py-4 rounded-lg border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 focus:border-blue-400 dark:focus:border-blue-500 transition-all text-lg placeholder-gray-400 dark:placeholder-slate-500"
                            placeholder="Search by name or department...">
                        <button type="submit" class="absolute right-2 top-2 bottom-2 bg-blue-600 hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-600/50 text-white px-6 rounded-lg font-medium transition-all duration-300 flex items-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </button>
                    </form>
                </div>

                <!-- Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($teachers as $t): ?>
                        <?php 
                            $status = $t['latest_status'] ?? 'UNKNOWN'; 
                            $statusConfig = match($status) {
                                'AVAILABLE'  => ['bg' => 'bg-emerald-50 dark:bg-emerald-900/30', 'text' => 'text-emerald-700 dark:text-emerald-400', 'dot' => 'bg-emerald-500 dark:bg-emerald-400'],
                                'IN_CLASS'   => ['bg' => 'bg-amber-50 dark:bg-amber-900/30', 'text' => 'text-amber-700 dark:text-amber-400', 'dot' => 'bg-amber-500 dark:bg-amber-400'],
                                'BUSY'       => ['bg' => 'bg-rose-50 dark:bg-rose-900/30', 'text' => 'text-rose-700 dark:text-rose-400', 'dot' => 'bg-rose-500 dark:bg-rose-400'],
                                'OFFLINE'    => ['bg' => 'bg-gray-100 dark:bg-slate-700/50', 'text' => 'text-gray-600 dark:text-slate-400', 'dot' => 'bg-gray-400 dark:bg-slate-500'],
                                'OFF_CAMPUS' => ['bg' => 'bg-purple-50 dark:bg-purple-900/30', 'text' => 'text-purple-700 dark:text-purple-400', 'dot' => 'bg-purple-500 dark:bg-purple-400'],
                                default      => ['bg' => 'bg-gray-100 dark:bg-slate-700/50', 'text' => 'text-gray-600 dark:text-slate-400', 'dot' => 'bg-gray-400 dark:bg-slate-500']
                            };
                        ?>
                        <a href="/?page=student_teacher&id=<?= $t['id'] ?>" class="group bg-white dark:bg-slate-800 p-6 rounded-lg border border-gray-200 dark:border-slate-700 hover:border-blue-400 dark:hover:border-blue-600 hover:shadow-xl hover:shadow-blue-500/10 hover:-translate-y-1 transition-all duration-300">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                     <div class="h-12 w-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400 dark:text-slate-300 font-bold text-lg group-hover:bg-blue-100 dark:group-hover:bg-blue-900/50 group-hover:text-blue-600 dark:group-hover:text-blue-400 group-hover:scale-110 transition-all duration-300">
                                        <?= strtoupper(substr($t['name'], 0, 1)) ?>
                                     </div>
                                     <div>
                                         <h2 class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($t['name']) ?></h2>
                                         <p class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide"><?= htmlspecialchars($t['department'] ?? 'Faculty') ?></p>
                                     </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100 dark:border-slate-700">
                                <?php
                                // Unique badge designs for each status
                                $badgeHTML = match($status) {
                                    'AVAILABLE' => '<div class="flex items-center gap-1.5 bg-gradient-to-r from-emerald-500 to-green-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-md shadow-emerald-500/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <span>Available</span>
                                    </div>',
                                    'IN_CLASS' => '<div class="flex items-center gap-1.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-md shadow-amber-500/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z"/></svg>
                                        <span>In Class</span>
                                    </div>',
                                    'BUSY' => '<div class="flex items-center gap-1.5 bg-gradient-to-r from-rose-500 to-red-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-md shadow-rose-500/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"/></svg>
                                        <span>Busy</span>
                                    </div>',
                                    'OFF_CAMPUS' => '<div class="flex items-center gap-1.5 bg-gradient-to-r from-purple-500 to-indigo-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-md shadow-purple-500/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                        <span>Off Campus</span>
                                    </div>',
                                    'OFFLINE' => '<div class="flex items-center gap-1.5 bg-gradient-to-r from-slate-400 to-gray-500 text-white px-3 py-1.5 rounded-full text-xs font-bold shadow-md shadow-slate-500/30">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1V8a1 1 0 00-1-1H8z" clip-rule="evenodd"/></svg>
                                        <span>Offline</span>
                                    </div>',
                                    default => '<div class="flex items-center gap-1.5 bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300 px-3 py-1.5 rounded-full text-xs font-bold">
                                        <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                        <span>Unknown</span>
                                    </div>'
                                };
                                echo $badgeHTML;
                                ?>
                                <div class="text-xs text-gray-400 dark:text-slate-500">
                                     <?= $t['office_text'] ?? 'Main Office' ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($teachers) === 0): ?>
                    <div class="text-center py-20 bg-white dark:bg-slate-800 rounded-lg border border-dashed border-gray-300 dark:border-slate-700">
                        <p class="text-gray-400 dark:text-slate-500 font-medium">No faculty members found matching your search.</p>
                        <a href="/?page=student_dashboard" class="text-blue-600 dark:text-blue-400 text-sm font-semibold mt-2 inline-block hover:underline">Clear Search</a>
                    </div>
                <?php endif; ?>

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
    </script>
</body>
</html>
