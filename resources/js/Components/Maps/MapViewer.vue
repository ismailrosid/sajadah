<script setup>
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';

const props = defineProps({
    mosqueLat: {
        type: Number,
        default: null,
    },
    mosqueLng: {
        type: Number,
        default: null,
    },
    mosqueRadius: {
        type: Number,
        default: 100,
    },
    mosqueName: {
        type: String,
        default: 'Masjid',
    },
    userLat: {
        type: Number,
        default: null,
    },
    userLng: {
        type: Number,
        default: null,
    },
    userAccuracy: {
        type: Number,
        default: null,
    },
});

const mapContainer = ref(null);
let map = null;
let mosqueMarker = null;
let userMarker = null;
let radiusCircle = null;
let userAccuracyCircle = null;
let line = null;

const mosqueIcon = L.divIcon({
    className: 'custom-mosque-marker',
    html: `
        <div style="background-color: #059669; width: 32px; height: 32px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; color: white;">
            <svg style="width: 16px; height: 16px; fill: currentColor;" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
    `,
    iconSize: [32, 32],
    iconAnchor: [16, 16],
});

const userIcon = L.divIcon({
    className: 'custom-user-marker',
    html: `
        <div style="background-color: #059669; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.25); display: flex; align-items: center; justify-content: center; color: white;">
            <div style="width: 10px; height: 10px; background-color: white; border-radius: 50%;"></div>
        </div>
    `,
    iconSize: [30, 30],
    iconAnchor: [15, 15],
});

const updateMap = () => {
    if (!map) return;

    if (props.userLat && props.userLng) {
        if (!userMarker) {
            userMarker = L.marker([props.userLat, props.userLng], {
                icon: userIcon,
                draggable: false,
            }).addTo(map);
            userMarker.bindPopup('<strong>Lokasi Kehadiran Anda</strong><br>Titik GPS saat mencatat absensi');
        } else {
            userMarker.setLatLng([props.userLat, props.userLng]);
        }

        if (props.userAccuracy) {
            if (!userAccuracyCircle) {
                userAccuracyCircle = L.circle([props.userLat, props.userLng], {
                    radius: props.userAccuracy,
                    color: '#10b981',
                    fillColor: '#34d399',
                    fillOpacity: 0.15,
                    weight: 1,
                    dashArray: '3, 6',
                }).addTo(map);
            } else {
                userAccuracyCircle.setLatLng([props.userLat, props.userLng]);
                userAccuracyCircle.setRadius(props.userAccuracy);
            }
        }

        if (props.mosqueLat && props.mosqueLng && mosqueMarker) {
            if (!line) {
                line = L.polyline([
                    [props.mosqueLat, props.mosqueLng],
                    [props.userLat, props.userLng]
                ], {
                    color: '#64748b',
                    weight: 2,
                    dashArray: '5, 5'
                }).addTo(map);
            } else {
                line.setLatLngs([
                    [props.mosqueLat, props.mosqueLng],
                    [props.userLat, props.userLng]
                ]);
            }

            const elements = [userMarker];
            if (mosqueMarker) elements.push(mosqueMarker);
            const group = L.featureGroup(elements);
            map.fitBounds(group.getBounds().pad(0.2));
        } else {
            map.setView([props.userLat, props.userLng], 16);
        }
    } else if (props.mosqueLat && props.mosqueLng) {
        map.setView([props.mosqueLat, props.mosqueLng], 16);
    }
};

onMounted(() => {
    if (!mapContainer.value) return;

    const initialLat = props.userLat || props.mosqueLat || -6.2088;
    const initialLng = props.userLng || props.mosqueLng || 106.8456;

    map = L.map(mapContainer.value).setView([initialLat, initialLng], 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19,
    }).addTo(map);

    if (props.mosqueLat && props.mosqueLng) {
        mosqueMarker = L.marker([props.mosqueLat, props.mosqueLng], { icon: mosqueIcon }).addTo(map);
        mosqueMarker.bindPopup(`<strong>${props.mosqueName}</strong>`);
    }

    updateMap();

    setTimeout(() => {
        map.invalidateSize();
    }, 250);
});

watch(() => [props.userLat, props.userLng, props.userAccuracy, props.mosqueLat, props.mosqueLng], () => {
    updateMap();
});
</script>

<template>
    <div class="relative w-full rounded-xl overflow-hidden border border-slate-200 shadow-sm">
        <div ref="mapContainer" class="w-full h-56 z-0"></div>
    </div>
</template>
