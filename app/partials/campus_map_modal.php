<!-- Shared Campus Map Modal Partial -->
<!-- app/partials/campus_map_modal.php -->

<style>
    /* Dark Mode Popup Overrides for Shared Modal */
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

<dialog id="campusMapModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/70 dark:bg-slate-800 w-[90%] max-w-6xl h-[80vh]">
    <div class="flex flex-col h-full">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-slate-700">
            <div class="flex items-center gap-3">
                <div class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">Live Campus Map</h3>
                <p class="text-xs text-gray-500 dark:text-slate-400">Updates every 5s</p>
            </div>
            <button type="button" onclick="document.getElementById('campusMapModal').close()" class="p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
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
let campusArrowSystem = null;
// Radar Settings (Global to this script)
let campusLat = 11.3003;
let campusLng = 124.6856;
let radiusMeters = 500;

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
    const loader = document.getElementById('mapLoader');
    if(loader) loader.style.display = 'flex';
});

// Override showModal to init map
// Note: We need to be careful not to double-patch if this script is loaded multiple times or similar.
// But PHP include usually happens once per page load.
if (!window.hasPatchedShowModal) {
    window.hasPatchedShowModal = true;
    const originalShowModal = HTMLDialogElement.prototype.showModal;
    HTMLDialogElement.prototype.showModal = function() {
        originalShowModal.call(this);
        if (this.id === 'campusMapModal' && !campusMapInstance) {
            // Log the view for analytics
            fetch('<?= url("?page=log_map_view_post") ?>', { method: 'POST' }).catch(console.error);

            setTimeout(async () => {
                // Fetch Radar Settings first
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

                campusMapInstance = L.map('campusMap').setView([campusLat, campusLng], 19);
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(campusMapInstance);

                // Add Radar Circle
                L.circle([campusLat, campusLng], {
                    color: '#8b5cf6', // purple-500
                    fillColor: '#8b5cf6',
                    fillOpacity: 0.1,
                    radius: radiusMeters,
                    weight: 1
                }).addTo(campusMapInstance);
                
                // Initialize Arrow System
                if (typeof MapArrowSystem !== 'undefined') {
                    campusArrowSystem = new MapArrowSystem(campusMapInstance);
                }
                
                // Hide loader when map is ready
                campusMapInstance.whenReady(() => {
                    setTimeout(() => {
                        const loader = document.getElementById('mapLoader');
                        if(loader) loader.style.display = 'none';
                    }, 500);
                });
                
                // Force map to recalculate size
                setTimeout(() => {
                    campusMapInstance.invalidateSize();
                    updateCampusLocations();
                }, 100);
                
                // Poll every 10 seconds
                // We use a global interval ID so we can clear it if needed, or just let it run while modal is open?
                // Better to clear it on close, but for simplicity we can just check if modal is open in update function
                if (window.campusMapInterval) clearInterval(window.campusMapInterval);
                window.campusMapInterval = setInterval(updateCampusLocations, 5000);
            }, 200);
        }
    };
}

async function updateCampusLocations() {
    const modal = document.getElementById('campusMapModal');
    if (!modal || !modal.open || !campusMapInstance) return;
    
    try {
        const response = await fetch('<?= url("?page=public_locations_json") ?>');
        const teachers = await response.json();
        
        const seenIds = new Set();

        teachers.forEach(t => {
            const lat = parseFloat(t.lat);
            const lng = parseFloat(t.lng);
            
            if (!lat || !lng) return;

            // Check if teacher is inside campus radar
            const R = 6371e3; // metres
            const φ1 = lat * Math.PI/180;
            const φ2 = campusLat * Math.PI/180;
            const Δφ = (campusLat-lat) * Math.PI/180;
            const Δλ = (campusLng-lng) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            const distance = R * c;

            // Only show if inside radius
            // NOTE: Admin Monitor uses a different logic (shows all). This partial is for Public/Student/Teacher views.
            if (distance > radiusMeters) {
                // If marker exists, remove it (in case they moved out)
                if (campusMarkers[t.id]) {
                    campusMapInstance.removeLayer(campusMarkers[t.id]);
                    if (campusArrowSystem) campusArrowSystem.removeMarker(t.id);
                    delete campusMarkers[t.id];
                }
                return; 
            }

            seenIds.add(t.id);
            

            // Status Badge Logic
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

        // Remove stale markers (e.g. expired or moved outside via API logic)
        for (let id in campusMarkers) {
            if (!seenIds.has(parseInt(id)) && !seenIds.has(id)) {
                campusMapInstance.removeLayer(campusMarkers[id]);
                if (campusArrowSystem) campusArrowSystem.removeMarker(id);
                delete campusMarkers[id];
            }
        }
    } catch (err) {
        console.error("Failed to fetch locations", err);
    }
}

// Auto-open modal if openMap parameter is present
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('openMap') === '1') {
    // Remove the parameter from URL without reload
    const newUrl = window.location.pathname + window.location.search.replace(/[?&]openMap=1/, '');
    window.history.replaceState({}, '', newUrl);
    
    // Open modal after a short delay
    setTimeout(() => {
        document.getElementById('campusMapModal').showModal();
    }, 500);
}
</script>
