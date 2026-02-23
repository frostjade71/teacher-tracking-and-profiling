<?php
// app/pages/admin_campus_radar.php

require_login();
require_role('admin');

$u = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Radar | Admin</title>
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
    <script src="<?= url('assets/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('assets/toast.css') ?>">
    <script src="<?= url('assets/toast.js') ?>"></script>
    <style>
        #radarMap { height: 600px; width: 100%; z-index: 1; border-radius: 12px; }
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }
    </style>
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
        <!-- Sidebar (Shared) -->
        <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>


        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header for Mobile -->
            <?php include __DIR__ . '/../partials/admin_mobile_header.php'; ?>

             <!-- Top Bar -->
            <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <a href="<?= url('?page=admin_dashboard') ?>" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Dashboard</a>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Campus Radar</span>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                     <!-- Theme Toggle Desktop -->
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-6xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-6 md:mb-8 gap-4">
                    <div>
                        <h1 class="text-xl md:text-3xl font-bold text-slate-900 dark:text-white mb-1 md:mb-2">Campus Radar Configuration</h1>
                        <p class="text-sm md:text-base text-slate-500 dark:text-slate-400">Set the main campus location and geofence radius. Teachers outside this zone will be marked "Off Campus".</p>
                    </div>
                    <button onclick="saveRadarSettings()" class="flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-lg hover:shadow-blue-500/25 transition-all transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        Save Configuration
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Map Card -->
                    <div class="lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-1 shadow-sm">
                        <div id="radarMap"></div>
                    </div>

                    <!-- Settings Card -->
                    <div class="space-y-6">
                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m8-2a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                                Coordinates
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Center Latitude</label>
                                    <input type="number" id="latInput" step="any" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 font-mono text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Center Longitude</label>
                                    <input type="number" id="lngInput" step="any" class="w-full p-3 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 font-mono text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                Geofence Zone
                            </h3>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Radius (Meters)</label>
                                <div class="flex items-center gap-3">
                                    <input type="range" id="radiusSlider" min="100" max="2000" step="50" class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-slate-700">
                                    <input type="number" id="radiusInput" class="w-24 p-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 font-mono text-sm text-center focus:ring-2 focus:ring-blue-500 outline-none">
                                </div>
                                <p class="text-xs text-slate-400 mt-2">
                                    Drag the marker on the map to set center. Drag the slider to adjust radius.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        let map, centerMarker, radarCircle;

        // Initialize Map
        async function initMap() {
            // Fetch current settings
            try {
                const res = await fetch('<?= url("?page=campus_radar_json") ?>');
                const data = await res.json();
                
                const lat = data.lat || 11.3003;
                const lng = data.lng || 124.6856;
                const radius = data.radius_meters || 500;
                
                // Update Inputs
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;
                document.getElementById('radiusInput').value = radius;
                document.getElementById('radiusSlider').value = radius;

                // Create Map
                map = L.map('radarMap').setView([lat, lng], 16);
                
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                // Add Draggable Marker
                centerMarker = L.marker([lat, lng], {draggable: true}).addTo(map);
                
                // Add Circle
                radarCircle = L.circle([lat, lng], {
                    color: '#8b5cf6', // purple-500
                    fillColor: '#8b5cf6',
                    fillOpacity: 0.2,
                    radius: radius
                }).addTo(map);

                // Events
                centerMarker.on('drag', function(e) {
                    const pos = e.target.getLatLng();
                    radarCircle.setLatLng(pos);
                    updateInputs(pos.lat, pos.lng);
                });

                centerMarker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    map.panTo(pos);
                });
                
                map.on('click', function(e) {
                    centerMarker.setLatLng(e.latlng);
                    radarCircle.setLatLng(e.latlng);
                    updateInputs(e.latlng.lat, e.latlng.lng);
                });

            } catch (err) {
                console.error("Failed to init map", err);
                showToast("Failed to load map settings", "error");
            }
        }

        function updateInputs(lat, lng) {
            document.getElementById('latInput').value = lat.toFixed(6);
            document.getElementById('lngInput').value = lng.toFixed(6);
        }

        function updateMapFromInputs() {
            const lat = parseFloat(document.getElementById('latInput').value);
            const lng = parseFloat(document.getElementById('lngInput').value);
            const radius = parseFloat(document.getElementById('radiusInput').value);
            
            if (lat && lng) {
                const newPos = [lat, lng];
                centerMarker.setLatLng(newPos);
                radarCircle.setLatLng(newPos);
                map.panTo(newPos);
            }
            
            if (radius) {
                radarCircle.setRadius(radius);
            }
        }

        // Input Listeners
        document.getElementById('latInput').addEventListener('change', updateMapFromInputs);
        document.getElementById('lngInput').addEventListener('change', updateMapFromInputs);
        
        // Radius Slider Sync
        const slider = document.getElementById('radiusSlider');
        const rInput = document.getElementById('radiusInput');

        slider.addEventListener('input', function() {
            rInput.value = this.value;
            if(radarCircle) radarCircle.setRadius(this.value);
        });

        rInput.addEventListener('input', function() {
            slider.value = this.value;
            if(radarCircle) radarCircle.setRadius(this.value);
        });

        async function saveRadarSettings() {
            const lat = document.getElementById('latInput').value;
            const lng = document.getElementById('lngInput').value;
            const radius = document.getElementById('radiusInput').value;
            
            showToast("Saving settings...", "info");

            try {
                const formData = new FormData();
                formData.append('lat', lat);
                formData.append('lng', lng);
                formData.append('radius', radius);

                const res = await fetch('<?= url("?page=admin_save_radar") ?>', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await res.json();
                
                if (data.success) {
                    showToast(data.message, "success");
                } else {
                    showToast(data.message, "error");
                }
            } catch (err) {
                showToast("Error saving settings: " + err.message, "error");
            }
        }

        // Init
        document.addEventListener('DOMContentLoaded', initMap);

    </script>
        </main>
        </div>
    </div>
    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
