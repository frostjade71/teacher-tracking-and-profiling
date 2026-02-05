<?php
// app/pages/teacher_dashboard.php

require_login();
require_role('teacher');

$u = current_user();
$pdo = db();

// Get current status
$stmt = $pdo->prepare("
    SELECT status, note 
    FROM teacher_status_events 
    WHERE teacher_user_id = ? 
    ORDER BY set_at DESC 
    LIMIT 1
");
$stmt->execute([$u['id']]);
$current = $stmt->fetch();
$currentStatus = $current['status'] ?? 'UNKNOWN';
$currentNote = $current['note'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard</title>
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
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen text-slate-800 dark:text-slate-200 font-sans transition-colors duration-200">
    
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
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Staff</span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="/?page=teacher_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                    Control Panel
                </a>
                
                <button onclick="document.getElementById('campusMapModal').showModal()" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors w-full">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </button>
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
                    <a href="/?page=logout_post" class="text-sm font-medium text-gray-500 dark:text-slate-400">Sign Out</a>
                </div>
            </header>

            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    Control Panel
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </div>

            <div class="max-w-3xl mx-auto p-6 md:p-12">
                
                <div class="mb-10 content-center text-center">
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Status Control</h1>
                    <p class="text-slate-500 dark:text-slate-400 mt-2">Update your availability for students and admin.</p>
                </div>

                <!-- Current Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 mb-8 transition-colors">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">Current Status</span>
                            <h2 id="currentStatusText" class="text-2xl font-semibold text-slate-900 dark:text-white mt-1"><?= htmlspecialchars($currentStatus) ?></h2>
                        </div>
                        <div id="currentStatusDot" class="h-3 w-3 rounded-full bg-green-500"></div>
                    </div>
                    <div id="currentNoteContainer" class="<?= $currentNote ? '' : 'hidden' ?> mt-4 text-sm text-gray-600 dark:text-gray-300">
                        <span id="currentNoteText"><?= htmlspecialchars($currentNote) ?></span>
                    </div>
                </div>

                <h3 class="font-bold text-slate-800 dark:text-white mb-4 px-2">Update Status</h3>
                
                <!-- Current Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 p-6 transition-colors">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
                        <!-- Status Buttons -->
                        <button type="button" onclick="updateStatus('AVAILABLE')" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-emerald-200 dark:border-emerald-800 bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:shadow-lg hover:shadow-emerald-500/20 hover:-translate-y-0.5 transition-all duration-300 group">
                            <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="8"/>
                            </svg>
                            <span class="font-medium text-sm">Available</span>
                        </button>
                        <button type="button" onclick="updateStatus('IN_CLASS')" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-amber-200 dark:border-amber-800 bg-white dark:bg-slate-900 text-amber-700 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:shadow-lg hover:shadow-amber-500/20 hover:-translate-y-0.5 transition-all duration-300 group">
                            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <path d="M7 8h10M7 12h10M7 16h6"/>
                            </svg>
                            <span class="font-medium text-sm">In Class</span>
                        </button>
                        <button type="button" onclick="updateStatus('BUSY')" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-rose-200 dark:border-rose-800 bg-white dark:bg-slate-900 text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 hover:shadow-lg hover:shadow-rose-500/20 hover:-translate-y-0.5 transition-all duration-300 group">
                            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M5 5l14 14"/>
                            </svg>
                            <span class="font-medium text-sm">Busy</span>
                        </button>
                        <button type="button" onclick="updateStatus('OFF_CAMPUS')" class="flex flex-col items-center justify-center p-4 rounded-lg border-2 border-purple-200 dark:border-purple-800 bg-white dark:bg-slate-900 text-purple-700 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:shadow-lg hover:shadow-purple-500/20 hover:-translate-y-0.5 transition-all duration-300 group">
                            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
                            </svg>
                            <span class="font-medium text-sm">Off Campus</span>
                        </button>
                        <button type="button" onclick="updateStatus('OFFLINE')" class="col-span-2 md:col-span-1 flex flex-col items-center justify-center p-4 rounded-lg border-2 border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:shadow-lg hover:shadow-slate-500/20 hover:-translate-y-0.5 transition-all duration-300 group">
                            <svg class="w-6 h-6 mb-2" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="2"/>
                            </svg>
                            <span class="font-medium text-sm">Offline</span>
                        </button>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-slate-400 mb-2">Add a note (optional)</label>
                        <input type="text" id="statusNote" placeholder="e.g. 'Back in office at 2:30 PM'" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-slate-800 dark:text-white placeholder-gray-400 dark:placeholder-slate-600">
                    </div>
                    <p id="statusMsg" class="text-sm text-center mt-3 h-5"></p>
                </div>

                <div class="mt-8">
                    <button id="btnLoc" class="w-full group relative bg-slate-900 dark:bg-emerald-600 text-white font-bold py-4 px-6 rounded-xl hover:bg-slate-800 dark:hover:bg-emerald-700 transition-all duration-300 shadow-lg hover:shadow-2xl hover:shadow-slate-900/30 dark:hover:shadow-emerald-600/30 hover:-translate-y-0.5 flex items-center justify-between">
                        <span class="flex items-center">
                            <svg class="h-5 w-5 mr-3 text-blue-400 dark:text-emerald-200 group-hover:animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Update precise location (GPS)
                        </span>
                        <svg class="h-5 w-5 text-gray-500 dark:text-emerald-200 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <p id="locMsg" class="text-sm text-center text-gray-400 mt-3 h-5"></p>
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
    // AJAX Status Update Function
    async function updateStatus(status) {
        const note = document.getElementById('statusNote').value;
        const msg = document.getElementById('statusMsg');
        
        msg.textContent = 'Updating status...';
        msg.className = 'text-sm text-center text-blue-600 dark:text-blue-400 mt-3 font-medium animate-pulse';
        
        try {
            const formData = new FormData();
            formData.append('status', status);
            formData.append('note', note);
            
            const res = await fetch('/?page=teacher_status_post', {
                method: 'POST',
                body: formData
            });
            
            const data = await res.json();
            
            if (data.success) {
                msg.textContent = data.message || 'Status updated successfully!';
                msg.className = 'text-sm text-center text-green-600 dark:text-green-400 mt-3 font-bold';
                
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
                setTimeout(() => {
                    msg.textContent = '';
                }, 3000);
            } else {
                msg.textContent = data.message || 'Failed to update status';
                msg.className = 'text-sm text-center text-red-500 mt-3 font-bold';
            }
        } catch (err) {
            msg.textContent = 'Error: ' + err.message;
            msg.className = 'text-sm text-center text-red-500 mt-3 font-bold';
        }
    }
    
    // Real GPS Location Update
    document.getElementById('btnLoc').addEventListener('click', async () => {
        const msg = document.getElementById('locMsg');
        
        if (!navigator.geolocation) {
            msg.textContent = "Geolocation is not supported by your browser.";
            msg.className = "text-sm text-center text-red-500 mt-3 font-bold";
            return;
        }

        msg.textContent = "Acquiring GPS signal...";
        msg.className = "text-sm text-center text-blue-600 dark:text-blue-400 mt-3 font-medium animate-pulse";
        
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(async (pos) => {
            msg.textContent = "Signal acquired! Updating server...";
            
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const accuracy = pos.coords.accuracy;
            
            // Geofencing Logic
            // Holy Cross College Coordinates
            const campusLat = 11.3003;
            const campusLng = 124.6856;
            const radiusMeters = 500; // 500m radius threshold
            
            // Calculate distance (Haversine Formula) (in meters)
            const R = 6371e3; // Earth radius in meters
            const φ1 = lat * Math.PI/180;
            const φ2 = campusLat * Math.PI/180;
            const Δφ = (campusLat - lat) * Math.PI/180;
            const Δλ = (campusLng - lng) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c;
            
            // Auto-update status based on distance
            let newStatus = 'AVAILABLE';
            if (distance > radiusMeters) {
                newStatus = 'OFF_CAMPUS';
            }
            
            // Call existing updateStatus function
            // Note: updateStatus is async but we don't need to wait for it here
            // We pass a flag or handle UI separately if needed, but the function handles its own UI
            console.log(`Distance to campus: ${distance.toFixed(2)}m. Auto-setting status to: ${newStatus}`);
            updateStatus(newStatus);

            const payload = {
                lat: lat,
                lng: lng,
                accuracy_m: accuracy
            };
            
            try {
                const res = await fetch('/?page=teacher_location_post', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(payload)
                });
                
                if (res.ok) {
                    msg.textContent = "Location updated successfully.";
                    msg.className = "text-sm text-center text-green-600 dark:text-green-400 mt-3 font-bold";
                    setTimeout(() => msg.textContent = '', 3000);
                } else {
                    msg.textContent = "Server Error: " + res.statusText;
                    msg.className = "text-sm text-center text-red-500 mt-3 font-bold";
                }
            } catch (err) {
                msg.textContent = "Upload Error: " + err.message;
                msg.className = "text-sm text-center text-red-500 mt-3 font-bold";
            }
        }, (err) => {
            let errorMsg = "Unable to retrieve location.";
            switch(err.code) {
                case err.PERMISSION_DENIED: errorMsg = "User denied the request for Geolocation."; break;
                case err.POSITION_UNAVAILABLE: errorMsg = "Location information is unavailable."; break;
                case err.TIMEOUT: errorMsg = "The request to get user location timed out."; break;
            }
            msg.textContent = errorMsg;
            msg.className = "text-sm text-center text-red-500 mt-3 font-bold";
        }, options);
    });
    
    // Campus Map Modal Initialization
    let campusMapInstance = null;
    let campusMarkers = {};
    
    document.getElementById('campusMapModal').addEventListener('click', function(e) {
        if (e.target === this) this.close();
    });
    
    // Initialize map when modal opens
    document.getElementById('campusMapModal').addEventListener('close', function() {
        if (campusMapInstance) {
            campusMapInstance.remove();
            campusMapInstance = null;
            campusMarkers = {};
        }
        // Reset loader for next open
        document.getElementById('mapLoader').style.display = 'flex';
    });
    
    // Override showModal to initialize map
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
        const newUrl = window.location.pathname + '?page=teacher_dashboard';
        window.history.replaceState({}, '', newUrl);
        
        // Open modal after a short delay
        setTimeout(() => {
            document.getElementById('campusMapModal').showModal();
        }, 500);
    }
    </script>
</body>
</html>
