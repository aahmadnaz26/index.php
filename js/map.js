document.addEventListener("DOMContentLoaded", function () {
    const mapContainer = document.getElementById("map");
    if (!mapContainer) return;

    // Set default view centered on Salford University
    const defaultLocation = [53.483959, -2.244644];
    const defaultZoom = 13;

    // Create Leaflet map
    var map = L.map('map', {
        zoomControl: false,
        preferCanvas: true
    }).setView(defaultLocation, defaultZoom);

    // Add zoom control to top right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // Map elements
    var userMarker;
    var accuracyCircle;
    var facilityLayerGroup = L.layerGroup().addTo(map);

    // Marker icons
    const icons = {
        human: L.icon({
            iconUrl: 'images/man.png' || 'https://cdn-icons-png.flaticon.com/512/447/447031.png',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        }),
        facility: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        }),
        highlighted: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
            iconSize: [35, 56],
            iconAnchor: [17, 56],
            popupAnchor: [1, -56],
            shadowSize: [56, 56]
        })
    };

    // Add base layers (Street and Satellite)
    const baseLayers = {
        "Street Map": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map),
        "Satellite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri',
            maxZoom: 19
        })
    };

    // Toggle between base layers
    L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

    // Start location tracking
    initGeolocation();

    // Draw an accuracy circle around the user's location
    function updateAccuracyCircle(coords) {
        const { latitude, longitude, accuracy } = coords;
        const displayAccuracy = Math.min(accuracy, 500); // Limit radius to 500m

        if (accuracyCircle) {
            map.removeLayer(accuracyCircle);
        }

        accuracyCircle = L.circle([latitude, longitude], {
            radius: displayAccuracy,
            fillColor: '#3388ff',
            fillOpacity: 0.1,
            color: '#3388ff',
            weight: 1,
            opacity: 0.7
        }).addTo(map);
    }

    // Try to get the user's location
    function initGeolocation() {
        if (!navigator.geolocation) {
            showFallback("Geolocation not supported");
            return;
        }

        // Get current location, then start watching
        navigator.geolocation.getCurrentPosition(
            (position) => {
                updateUserPosition(position);
                navigator.geolocation.watchPosition(
                    updateUserPosition,
                    handleGeolocationError,
                    { enableHighAccuracy: true, maximumAge: 30000, timeout: 10000 }
                );
            },
            handleGeolocationError,
            { maximumAge: 0, timeout: 5000, enableHighAccuracy: true }
        );
    }

    // Update marker and circle to match user position
    function updateUserPosition(position) {
        const { latitude, longitude, accuracy } = position.coords;
        const positionText = `
            <b>Your Location</b>
            <p>Latitude: ${latitude.toFixed(6)}</p>
            <p>Longitude: ${longitude.toFixed(6)}</p>
            <p>Accuracy: ±${Math.round(accuracy)} meters</p>
        `;

        if (userMarker) {
            userMarker.setLatLng([latitude, longitude])
                .setPopupContent(positionText);
        } else {
            userMarker = L.marker([latitude, longitude], {
                icon: icons.human,
                zIndexOffset: 1000
            }).addTo(map)
                .bindPopup(positionText)
                .openPopup();
        }

        updateAccuracyCircle(position.coords);
        map.flyTo([latitude, longitude], 16, { duration: 1 });
    }

    // Handle location error (e.g., blocked, unavailable)
    function handleGeolocationError(error) {
        console.warn(`Geolocation error (${error.code}): ${error.message}`);
        showFallback(
            error.code === 1 ? "Location access denied" :
                error.code === 2 ? "Position unavailable" :
                    "Location detection timed out"
        );
    }

    // Show fallback marker if location fails
    function showFallback(message) {
        if (!userMarker) {
            userMarker = L.marker(defaultLocation, {
                icon: icons.human,
                zIndexOffset: 1000
            }).addTo(map)
                .bindPopup(`<b>Notice</b><p>${message}</p>`)
                .openPopup();
        }
        map.setView(defaultLocation, defaultZoom);
    }

    // If facilities are defined, render them on the map
    if (typeof allFacilities !== 'undefined') {
        renderFacilities(allFacilities);
    }

    // Add markers for each facility with popups and table sync
    function renderFacilities(facilities) {
        facilityLayerGroup.clearLayers(); // Remove old markers

        if (!Array.isArray(facilities) || facilities.length === 0) return;

        facilities.forEach(facility => {
            const lat = parseFloat(facility.lat);
            const lng = parseFloat(facility.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                const popupContent = `
                <div class="facility-popup">
                    <h4 class="facility-title">${facility.title}</h4>
                    <div class="facility-details">
                        <p><strong>Status:</strong> ${facility.comments || 'No status'}</p>
                        <p><strong>Category:</strong> ${facility.category}</p>
                        <p><strong>Description:</strong> ${facility.description}</p>
                        <p><strong>Location:</strong> ${facility.houseNumber}, ${facility.streetName}, ${facility.town}, ${facility.county}, ${facility.postcode}</p>
                    </div>
                </div>`;

                const marker = L.marker([lat, lng], {
                    icon: icons.facility,
                    facilityId: facility.id
                }).bindPopup(popupContent, {
                    maxWidth: 300,
                    className: 'facility-popup-container'
                });

                // Clicking a marker highlights the related row in the table
                marker.on('click', function () {
                    document.querySelectorAll('.facility-row').forEach(row => {
                        row.classList.remove('active-row');
                    });

                    const row = document.querySelector(`.facility-row[data-id="${facility.id}"]`);
                    if (row) {
                        row.classList.add('active-row');
                        row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });

                facilityLayerGroup.addLayer(marker);
            }
        });
    }

    // Add scale to bottom left of map
    L.control.scale({ position: 'bottomleft' }).addTo(map);

    // When user clicks a table row, fly to that marker
    const rows = document.querySelectorAll(".facility-row");
    let activeMarker = null;

    rows.forEach(row => {
        row.addEventListener("click", function () {
            const facilityId = this.dataset.id;
            const lat = parseFloat(this.dataset.lat);
            const lng = parseFloat(this.dataset.lng);

            if (!isNaN(lat) && !isNaN(lng)) {
                map.flyTo([lat, lng], 16, {
                    duration: 0.5,
                    easeLinearity: 0.25
                });

                // Highlight the clicked marker
                if (activeMarker) {
                    activeMarker.setIcon(icons.facility);
                    if (map.hasLayer(activeMarker)) {
                        activeMarker.closePopup();
                    }
                }

                facilityLayerGroup.eachLayer(marker => {
                    const markerPos = marker.getLatLng();
                    if (markerPos.lat === lat && markerPos.lng === lng) {
                        activeMarker = marker;
                        marker.setIcon(icons.highlighted);

                        setTimeout(() => {
                            if (map.hasLayer(marker)) {
                                marker.openPopup();
                            }
                        }, 500);
                    }
                });

                rows.forEach(r => r.classList.remove("active-row"));
                this.classList.add("active-row");
            }
        });
    });

    // Send comment update to server (AJAX)
    async function handleStatusUpdate(facilityId, comment) {
        try {
            const response = await fetch('updateStatus.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ facilityId, comment })
            });

            const result = await response.json();

            if (result.success) {
                const facilityIndex = allFacilities.findIndex(f => f.id == facilityId);
                if (facilityIndex !== -1) {
                    allFacilities[facilityIndex].currentStatus = comment;
                }
            }
        } catch (error) {
            console.error("AJAX error:", error);
        }
    }

    // Extra styling for selected row + popup
    const style = document.createElement('style');
    style.textContent = `
        .active-row {
            background-color: #f0f7ff !important;
            box-shadow: inset 3px 0 0 #0066cc;
            transition: all 0.2s ease;
        }
        .active-row td {
            font-weight: 500;
        }
        .facility-popup-container {
            font-family: Arial, sans-serif;
        }
        .facility-popup h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .recenter-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
    `;
    document.head.appendChild(style);

    // Recenter button moves map back to user location
    const recenterBtn = document.createElement("button");
    recenterBtn.className = "recenter-btn";
    recenterBtn.innerText = "📍 Recenter";
    recenterBtn.addEventListener("click", () => {
        if (userMarker) {
            map.flyTo(userMarker.getLatLng(), 16, { duration: 0.5 });
        }
    });
    mapContainer.appendChild(recenterBtn);
});
