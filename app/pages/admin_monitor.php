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
    <link rel="icon" type="image/png" href="<?= url('assets/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= url('assets/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('assets/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= url('assets/favicon/site.webmanifest') ?>" />
    <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
     crossorigin=""/>
     
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
     crossorigin=""></script>
    <script src="<?= url('assets/map_arrows.js') ?>"></script>
    <script src="<?= url('assets/theme.js') ?>"></script>
    <style>
        #map { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        /* Dark mode map filter (optional, experimental) */
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }
        
        /* Dark Mode Popup Overrides */
        html.dark .leaflet-popup-content-wrapper,
        html.dark .leaflet-popup-tip {
            background-color: #0f172a; /* slate-900 */
            color: #f8fafc; /* slate-50 */
            border: 1px solid #334155; /* slate-700 */
        }
        html.dark .leaflet-popup-content b { color: #f8fafc; }
        
        /* Force remove default Leaflet spacing */
        .leaflet-popup-content p { margin: 0; }
        .leaflet-popup-content h3 { margin: 0; }
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
    <script src="<?= url('assets/loader.js') ?>"></script>

    <div class="flex h-screen">
         <!-- Sidebar (Consistent with Dashboard) -->
         <!-- Sidebar (Shared) -->
         <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>


        <!-- Main Content (Map) -->
        <main class="flex-1 flex flex-col relative h-full w-full">
            
            <!-- Floating Overlay for Mobile / Title -->
            <div class="absolute top-4 left-4 z-[40] bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-4 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 max-w-sm transition-colors">
                <div class="flex items-center gap-3">
                    <div>
                        <h1 class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="relative flex h-3 w-3">
                              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                              <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                            </span>
                            Live Monitor
                        </h1>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">Real-time location updates. <br class="md:hidden"> Markers update every 5s.</p>
                    </div>
                </div>
            </div>
                
            <div class="md:hidden mt-2">
                <a href="<?= url('?page=admin_dashboard') ?>" class="text-xs text-blue-600 dark:text-blue-400 font-medium">&larr; Back to Dashboard</a>
            </div>

            <!-- Right Side Controls -->
            <div class="absolute top-4 right-4 z-[40] bg-white/90 dark:bg-slate-800/90 backdrop-blur-sm p-3 rounded-xl shadow-lg border border-gray-100 dark:border-slate-700 transition-colors flex flex-col items-center gap-3">
                 <!-- Hamburger (Mobile Only) -->
                 <button onclick="toggleSidebar()" class="md:hidden w-10 h-10 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                 </button>

                 <!-- Theme Toggle -->
                 <?php $hideInfoIcon = true; include __DIR__ . '/../partials/theme_toggle.php'; ?>
               
                <!-- Settings Button -->
                <button onclick="openSettingsModal()" class="w-10 h-10 flex items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors" title="Settings">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                 <!-- Reset Location Button -->
                 <button onclick="openResetModal()" class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors" title="Reset All Locations">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
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

    <!-- Confirmation Modal -->
    <div id="resetModal" class="fixed inset-0 z-[500] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200 dark:border-slate-700">
                    <div class="bg-white dark:bg-slate-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Reset All Locations?</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-slate-300">
                                        Are you sure you want to remove all teacher locations? This will clear all markers from the live map for everyone. This action cannot be undone.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" onclick="confirmReset()" class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Reset Locations</button>
                        <button type="button" onclick="closeResetModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="fixed inset-0 z-[500] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-800 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-200 dark:border-slate-700">
                    <div class="bg-white dark:bg-slate-800 px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                             <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">Monitor Settings</h3>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium leading-6 text-gray-900 dark:text-white mb-2">Detailed Expiration Timer</label>
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <label for="inputHours" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Hours</label>
                                            <input type="number" id="inputHours" min="0" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-slate-700 dark:ring-slate-600 dark:text-white px-3" placeholder="0">
                                        </div>
                                        <div class="flex-1">
                                            <label for="inputMinutes" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Minutes</label>
                                            <input type="number" id="inputMinutes" min="0" max="59" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-slate-700 dark:ring-slate-600 dark:text-white px-3" placeholder="0">
                                        </div>
                                        <div class="flex-1">
                                            <label for="inputSeconds" class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Seconds</label>
                                            <input type="number" id="inputSeconds" min="0" max="59" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-slate-700 dark:ring-slate-600 dark:text-white px-3" placeholder="0">
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Teacher will automatically go <strong>OFFLINE</strong> if no activity is detected for this duration.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-slate-700/50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" onclick="saveSettings()" class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Save</button>
                        <button type="button" onclick="closeSettingsModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white dark:bg-slate-800 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 hover:bg-gray-50 dark:hover:bg-slate-700 sm:mt-0 sm:w-auto">Cancel</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="mobileSidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity opacity-0"></div>

    <!-- Mobile Script -->
    <script src="<?= url('assets/mobile.js') ?>"></script>

    <script>
        // Initialize map variables globally
        var map; 
        var arrowSystem;
        var markers = {};

        async function initMap() {
             // Fetch Radar Settings first
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

            map = L.map('map').setView([campusLat, campusLng], 19);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

             // Add Radar Circle
            L.circle([campusLat, campusLng], {
                color: '#8b5cf6', // purple-500
                fillColor: '#8b5cf6',
                fillOpacity: 0.1,
                radius: radiusMeters,
                weight: 1
            }).addTo(map);
            
            // Initialize Arrow System
            if (typeof MapArrowSystem !== 'undefined') {
                arrowSystem = new MapArrowSystem(map);
            } else {
                console.warn("MapArrowSystem not defined");
            }
            
            // Hide loader when map is ready
            map.whenReady(() => {
                setTimeout(() => {
                    document.getElementById('mapLoader').style.display = 'none';
                }, 500);
            });

            // Start polling
            updateLocations();
            setInterval(updateLocations, 5000);
        }

        initMap();

        async function updateLocations() {
            if (!map) return; // Wait for map to be initialized

            try {
                const response = await fetch('<?= url("?page=admin_locations_json") ?>');
                const teachers = await response.json();

                const seenIds = new Set();

                teachers.forEach(t => {
                    const lat = parseFloat(t.lat);
                    const lng = parseFloat(t.lng);
                    
                    if (!lat || !lng) return;

                    seenIds.add(t.id);


                    // Status Badge Logic (mirroring PHP match)
                    let statusBadge = '';
                    switch (t.status) {
                        case 'AVAILABLE':
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-emerald-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">Available</span>';
                            break;
                        case 'IN_CLASS':
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-amber-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">In Class</span>';
                            break;
                            case 'BUSY':
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-rose-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">Busy</span>';
                            break;
                        case 'OFF_CAMPUS':
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-purple-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">Off Campus</span>';
                            break;
                        case 'OFFLINE':
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-slate-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">Offline</span>';
                            break;
                        default:
                            statusBadge = '<span class="inline-flex items-center gap-1.5 bg-gray-500 text-white px-2 py-0.5 rounded-full text-[10px] font-bold shadow-sm">Unknown</span>';
                    }


                    // Room badge
                    let roomBadge = '';
                    if (t.current_room) {
                        roomBadge = `
                            <div class="flex items-center gap-1.5 bg-slate-50 dark:bg-slate-700/50 px-2 py-0.5 rounded-md border border-slate-200 dark:border-slate-600 shadow-sm">
                                <svg class="w-3 h-3 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span class="text-[10px] font-extrabold text-slate-700 dark:text-slate-200">${t.current_room}</span>
                            </div>
                        `;
                    }

                    let popupContent = `
                        <div class="min-w-[150px] font-sans">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="h-8 w-8 flex-shrink-0 rounded-md bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-500 dark:text-slate-300 font-bold text-xs shadow-inner">
                                    ${t.name.charAt(0).toUpperCase()}
                                </div>
                                <div class="flex-1 min-w-0 flex flex-col justify-center">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white leading-none m-0 p-0 truncate">${t.name}</h3>
                                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 leading-none m-0 p-0 mt-0.5 truncate opacity-90">${t.department || ''}</p>
                                </div>
                            </div>
                            
                            <div class="pt-2 border-t border-slate-100 dark:border-slate-700/50 flex items-center gap-2">
                                ${statusBadge}
                                ${roomBadge}
                            </div>
                        </div>
                    `;

                    if (markers[t.id]) {
                        // Update existing marker
                        markers[t.id].setLatLng([lat, lng])
                                     .setPopupContent(popupContent);
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
                            
                        // Add to arrow system if available
                        if (arrowSystem) {
                            arrowSystem.addMarker(t.id, markers[t.id]);
                        }
                    }
                });

                // Remove stale markers
                for (let id in markers) {
                    if (!seenIds.has(parseInt(id)) && !seenIds.has(id)) {
                        map.removeLayer(markers[id]);
                        if (arrowSystem) arrowSystem.removeMarker(id);
                        delete markers[id];
                    }
                }

            } catch (err) {
                console.error("Failed to fetch locations", err);
            }
        }

        // Reset Modal Logic
        function openResetModal() {
            document.getElementById('resetModal').classList.remove('hidden');
        }

        function closeResetModal() {
            document.getElementById('resetModal').classList.add('hidden');
        }

        async function confirmReset() {
            try {
                const res = await fetch('<?= url("?page=admin_reset_locations") ?>', {
                    method: 'POST'
                });
                const data = await res.json();
                
                if (data.success) {
                    // Clear local markers immediately
                    for (let id in markers) {
                        map.removeLayer(markers[id]);
                    }
                    markers = {};
                    
                    // Force update
                    // updateLocations();
                    
                    // closeResetModal();
                    
                    // Reload page for fresh map as requested
                    window.location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (e) {
                console.error(e);
                alert("An error occurred while resetting locations.");
            }
        }

        // Settings Modal Logic
        function openSettingsModal() {
            <?php 
            require_once __DIR__ . '/../settings.php';
            // Default 3 hours = 10800 seconds
            $totalSeconds = (int)get_setting('location_expiration_seconds', 10800);
            
            // Convert to H:M:S
            $h = floor($totalSeconds / 3600);
            $m = floor(($totalSeconds % 3600) / 60);
            $s = $totalSeconds % 60;
            ?>
            
            document.getElementById('inputHours').value = "<?php echo $h; ?>";
            document.getElementById('inputMinutes').value = "<?php echo $m; ?>";
            document.getElementById('inputSeconds').value = "<?php echo $s; ?>";
            
            document.getElementById('settingsModal').classList.remove('hidden');
        }

        function closeSettingsModal() {
            document.getElementById('settingsModal').classList.add('hidden');
        }

        async function saveSettings() {
            const h = parseInt(document.getElementById('inputHours').value) || 0;
            const m = parseInt(document.getElementById('inputMinutes').value) || 0;
            const s = parseInt(document.getElementById('inputSeconds').value) || 0;
            
            const total = (h * 3600) + (m * 60) + s;
            
            if (total < 1) {
                alert("Please set a valid duration (at least 1 second).");
                return;
            }

            try {
                const formData = new FormData();
                formData.append('hours', h);
                formData.append('minutes', m);
                formData.append('seconds', s);

                const res = await fetch('<?= url("?page=admin_settings_save") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert("Error: " + data.message);
                }
            } catch (e) {
                console.error(e);
                alert("An error occurred while saving settings.");
            }
        }
    </script>
</body>
</html>
