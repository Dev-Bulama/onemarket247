<x-filament-panels::page>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css">

    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
            Shows customers and vendors who have opted in to live location sharing. Agents and delivery partners have
            no app of their own yet, so they can't appear here — see the account/store settings toggle each customer
            and vendor already has.
        </p>

        <div class="mb-4 flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">User type</label>
                <select id="location-filter-type" class="fi-select-input block rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
                    <option value="">All</option>
                    <option value="customer">Customers</option>
                    <option value="vendor_owner">Vendor owners</option>
                    <option value="vendor_staff">Vendor staff</option>
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Search</label>
                <input id="location-filter-search" type="text" placeholder="Name or email"
                       class="fi-input block rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800">
            </div>

            <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                <input id="location-filter-online" type="checkbox" class="rounded border-gray-300">
                Online only (active in last 5 minutes)
            </label>

            <span id="location-count" class="text-sm text-gray-500 dark:text-gray-400"></span>
        </div>

        <div id="location-map" style="height: 600px;" class="rounded-lg"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const map = L.map('location-map').setView([9.0820, 8.6753], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            const clusterGroup = L.markerClusterGroup();
            map.addLayer(clusterGroup);

            let trailLayer = null;

            const dataUrl = @js(route('admin.location-tracking.data'));
            const historyUrlBase = @js(url('admin/location-tracking'));

            function markerColor(marker) {
                return marker.is_online ? '#16a34a' : '#9ca3af';
            }

            function loadMarkers() {
                const params = new URLSearchParams();
                const type = document.getElementById('location-filter-type').value;
                const search = document.getElementById('location-filter-search').value;
                const onlineOnly = document.getElementById('location-filter-online').checked;

                if (type) params.set('type', type);
                if (search) params.set('search', search);
                if (onlineOnly) params.set('online_only', '1');

                fetch(dataUrl + '?' + params.toString(), { headers: { Accept: 'application/json' } })
                    .then((response) => response.json())
                    .then((payload) => {
                        clusterGroup.clearLayers();

                        payload.data.forEach((item) => {
                            const marker = L.circleMarker([item.latitude, item.longitude], {
                                radius: 8,
                                color: markerColor(item),
                                fillColor: markerColor(item),
                                fillOpacity: 0.8,
                            });

                            const popup = document.createElement('div');
                            popup.innerHTML = `
                                <strong>${item.name}</strong><br>
                                ${item.type}<br>
                                ${item.is_online ? 'Online' : 'Offline'} &middot; updated ${item.recorded_at}<br>
                                <button type="button" class="location-history-btn text-blue-600 underline" data-user-id="${item.id}">View history</button>
                            `;

                            marker.bindPopup(popup);
                            marker.on('popupopen', () => {
                                popup.querySelector('.location-history-btn').addEventListener('click', () => loadHistory(item.id));
                            });

                            clusterGroup.addLayer(marker);
                        });

                        document.getElementById('location-count').textContent = payload.data.length + ' sharing location';
                    });
            }

            function loadHistory(userId) {
                fetch(historyUrlBase + '/' + userId + '/history?days=1', { headers: { Accept: 'application/json' } })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (trailLayer) {
                            map.removeLayer(trailLayer);
                        }

                        const points = payload.data.map((p) => [p.latitude, p.longitude]);

                        if (points.length > 1) {
                            trailLayer = L.polyline(points, { color: '#f97316' }).addTo(map);
                            map.fitBounds(trailLayer.getBounds());
                        }
                    });
            }

            document.getElementById('location-filter-type').addEventListener('change', loadMarkers);
            document.getElementById('location-filter-online').addEventListener('change', loadMarkers);
            document.getElementById('location-filter-search').addEventListener('input', function () {
                clearTimeout(window.__locationSearchDebounce);
                window.__locationSearchDebounce = setTimeout(loadMarkers, 400);
            });

            loadMarkers();
            setInterval(loadMarkers, 15000);
        });
    </script>
</x-filament-panels::page>
