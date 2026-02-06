<?php
// app/pages/admin_monitor.php

require_login();
require_role('admin');

// Log this view access
audit_log('VIEW_MONITOR', null, null, null);

$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Campus Monitor | Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
     crossorigin=""/>
     
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
     crossorigin=""></script>
    <script src="/assets/map_arrows.js"></script>
    <script src="/assets/theme.js"></script>
    <style>
        #map { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        /* Dark mode map filter (optional, experimental) */
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }
    </style>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen font-sans text-slate-800 dark:text-white overflow-hidden transition-colors duration-200">

    <!-- Loader -->
    <div class="loader-container">
        <div class="loader">
            <div class="loader-square"></div>
            <div class="loader-square"></div>
            <div class="loader-square"></div>
        </div>
    </div>
    <script src="/assets/loader.js"></script>

    <div class="flex h-screen">
         <!-- Sidebar (Consistent with Dashboard) -->
         <aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex-shrink-0 hidden md:flex flex-col z-20 border-r border-slate-800">
            <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
                <img src="/assets/favicon/web-app-manifest-512x512.png" class="w-7 h-7 rounded-lg" alt="Logo" style="width: 28px; height: 28px;">
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Admin</span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="/?page=admin_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                     <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                
                <a href="/?page=admin_monitor" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 text-white rounded-lg group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </a>

                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">
                    Management
                </div>

                <a href="/?page=admin_teachers" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Teachers
                </a>
                
                <a href="/?page=admin_subjects" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
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

        <!-- Main Content (Map) -->
        <main class="flex-1 flex flex-col relative h-full w-full">
            
            <!-- Floating Overlay for Mobile / Title -->
            <div class="absolute top-4 left-4 z-[400] bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 max-w-sm transition-colors">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <h1 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Live Monitor
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Real-time location updates. Markers update every 10s.</p>
                    </div>
                     <!-- Theme Toggle Mini -->
                     <button onclick="window.toggleTheme()" class="p-1.5 rounded-lg bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                        <svg class="w-4 h-4 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-4 h-4 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
                
                <a href="/?page=admin_dashboard" class="md:hidden text-xs text-blue-600 dark:text-blue-400 mt-2 block font-medium">&larr; Back to Dashboard</a>
            </div>

            <div id="map" class="w-full h-full bg-slate-100 dark:bg-slate-900 relative">
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

        </main>
    </div>

    <script>
        // Initialize map (Holy Cross College of Carigara)
        var map = L.map('map').setView([11.3003, 124.6856], 19);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        
        // Initialize Arrow System
        const arrowSystem = new MapArrowSystem(map);
        
        // Hide loader when map is ready
        map.whenReady(() => {
            setTimeout(() => {
                document.getElementById('mapLoader').style.display = 'none';
            }, 500);
        });

        var markers = {};

        async function updateLocations() {
            try {
                const response = await fetch('/?page=admin_locations_json');
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

                    if (markers[t.id]) {
                        // Update existing marker
                        markers[t.id].setLatLng([lat, lng])
                                     .setPopupContent(popupContent);
                    } else {
                        if (markers[t.id]) {
                    markers[t.id].setLatLng([lat, lng]).setPopupContent(popupContent);
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

                    markers[t.id] = L.marker([lat, lng], {icon: teacherIcon})
                        .addTo(map)
                        .bindPopup(popupContent);
                        
                    // Add to arrow system
                    arrowSystem.addMarker(t.id, markers[t.id]);
                }    }
                });

            } catch (err) {
                console.error("Failed to fetch locations", err);
            }
        }

        // Poll every 10 seconds
        updateLocations();
        setInterval(updateLocations, 10000);
    </script>
</body>
</html>
