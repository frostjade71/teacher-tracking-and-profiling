class MapArrowSystem {
    constructor(map) {
        this.map = map;
        this.arrows = {}; // Store arrow elements by marker ID
        this.markers = {}; // Store references to markers 
        
        // Update arrows on map movement
        this.map.on('move', () => this.updateArrows());
        this.map.on('zoom', () => this.updateArrows());
        this.map.on('resize', () => this.updateArrows());
    }

    addMarker(id, marker) {
        this.markers[id] = marker;
        this.updateArrows();
    }

    removeMarker(id) {
        if (this.arrows[id]) {
            this.arrows[id].remove();
            delete this.arrows[id];
        }
        delete this.markers[id];
    }

    clear() {
        Object.keys(this.arrows).forEach(id => {
            this.arrows[id].remove();
        });
        this.arrows = {};
        this.markers = {};
    }

    updateArrows() {
        const bounds = this.map.getBounds();
        const center = this.map.getCenter();

        Object.keys(this.markers).forEach(id => {
            const marker = this.markers[id];
            const latLng = marker.getLatLng();

            // Check if marker is contained in current view
            if (bounds.contains(latLng)) {
                // Remove arrow if it exists (marker matches view)
                if (this.arrows[id]) {
                    this.arrows[id].remove();
                    delete this.arrows[id];
                }
                return;
            }

            // Marker is off-screen, calculate direction
            const bearing = this.getBearing(center.lat, center.lng, latLng.lat, latLng.lng);
            
            // Create or update arrow
            if (!this.arrows[id]) {
                const arrow = this.createArrow(id, marker);
                this.arrows[id] = arrow;
                arrow.addTo(this.map);
            }
            
            this.updateArrowPosition(this.arrows[id], bearing);
        });
    }

    createArrow(id, marker) {
        // Create custom div icon for the arrow
        const icon = L.divIcon({
            className: 'map-arrow-icon',
            html: `
                <div class="w-10 h-10 bg-white dark:bg-slate-800 rounded-full shadow-lg border-2 border-blue-500 flex items-center justify-center cursor-pointer transform hover:scale-110 transition-transform duration-200" title="Click to find teacher">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                    </svg>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        const arrowMarker = L.marker(this.map.getCenter(), {
            icon: icon,
            zIndexOffset: 1000 // Ensure arrows are on top
        });

        // Click handler to zoom to teacher
        arrowMarker.on('click', () => {
            this.map.flyTo(marker.getLatLng(), 18, {
                animate: true,
                duration: 1.5
            });
        });

        return arrowMarker;
    }

    updateArrowPosition(arrow, bearing) {
        // Calculate position on the edge of the map container
        const mapSize = this.map.getSize();
        const centerPoint = L.point(mapSize.x / 2, mapSize.y / 2);
        
        // Define a "safe area" inset from the edges so arrows don't get cut off
        const inset = 30; 
        const width = mapSize.x - (inset * 2);
        const height = mapSize.y - (inset * 2);
        
        // Convert bearing to radians (standard math uses 0 = East, Leaflet bearing 0 = North)
        // Adjust bearing to be standard trig angle (0 = East, counter-clockwise)
        const angleRad = (bearing - 90) * (Math.PI / 180);
        
        // Calculate intersection with the bounding box
        // Line equation: y = tan(angle) * x
        
        // Dimensions of the rectangle relative to center
        const xMax = width / 2;
        const yMax = height / 2;
        
        // Helper to find intersection
        let dx = Math.cos(angleRad); // Standard trig: 0 is East
        let dy = Math.sin(angleRad); // Standard trig: positive y is Down in CSS logic? No, canvas y is down.
        
        // Actually, let's use a simpler approach. 
        // We project out from center and clamp to bounds.
        
        // Angle in radians for standard trig (0 at 3 o'clock, clockwise for screen coords)
        // Leaflet bearing: 0 N (12 o'clock), 90 E (3 o'clock)
        const rad = (bearing - 90) * (Math.PI / 180);
        
        // Vector
        const vx = Math.cos(rad);
        const vy = Math.sin(rad);

        // Find scale factor to hit the edge
        const tX = vx > 0 ? xMax / vx : -xMax / vx;
        const tY = vy > 0 ? yMax / vy : -yMax / vy;
        
        // Smallest positive t dictates which edge we hit first
        const t = Math.min(Math.abs(tX), Math.abs(tY));
        
        const x = centerPoint.x + (vx * t);
        const y = centerPoint.y + (vy * t);

        const point = L.point(x, y);
        const latLng = this.map.containerPointToLatLng(point);
        
        arrow.setLatLng(latLng);
        
        // Rotate the arrow icon itself to point out
        const iconDiv = arrow.getElement().querySelector('div');
        if (iconDiv) {
            iconDiv.style.transform = `rotate(${bearing}deg)`;
        }
    }

    // Calculate bearing between two points
    getBearing(startLat, startLng, destLat, destLng) {
        startLat = this.toRadians(startLat);
        startLng = this.toRadians(startLng);
        destLat = this.toRadians(destLat);
        destLng = this.toRadians(destLng);

        const y = Math.sin(destLng - startLng) * Math.cos(destLat);
        const x = Math.cos(startLat) * Math.sin(destLat) -
                  Math.sin(startLat) * Math.cos(destLat) * Math.cos(destLng - startLng);
        const brng = Math.atan2(y, x);
        const brngDeg = this.toDegrees(brng);
        return (brngDeg + 360) % 360;
    }

    toRadians(degrees) {
        return degrees * Math.PI / 180;
    }

    toDegrees(radians) {
        return radians * 180 / Math.PI;
    }
}
