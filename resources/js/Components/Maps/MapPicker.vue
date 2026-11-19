<script setup>
import { onMounted, ref, watch } from 'vue';
import L from 'leaflet';

const props = defineProps({
    latitude: {
        type: Number,
        default: -6.2088,
    },
    longitude: {
        type: Number,
        default: 106.8456,
    },
    radius: {
        type: Number,
        default: 100,
    },
});

const emit = defineEmits(['update:coordinates']);

const mapContainer = ref(null);
let map = null;
let marker = null;
let circle = null;

const customIcon = L.divIcon({
    className: 'custom-leaflet-marker',
    html: `
        <div style="background-color: #059669; width: 28px; height: 28px; border-radius: 50%; border: 3px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); display: flex; align-items: center; justify-content: center; color: white;">
            <svg style="width: 14px; height: 14px; fill: currentColor;" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
        </div>
    `,
    iconSize: [28, 28],
    iconAnchor: [14, 14],
});

onMounted(() => {
    if (!mapContainer.value) return;

    map = L.map(mapContainer.value).setView([props.latitude, props.longitude], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    marker = L.marker([props.latitude, props.longitude], {
        draggable: true,
        icon: customIcon,
    }).addTo(map);

    circle = L.circle([props.latitude, props.longitude], {
        radius: props.radius,
        color: '#059669',
        fillColor: '#10b981',
        fillOpacity: 0.18,
        weight: 2,
    }).addTo(map);

    marker.on('dragend', (e) => {
        const { lat, lng } = e.target.getLatLng();
        circle.setLatLng([lat, lng]);
        emit('update:coordinates', { latitude: lat, longitude: lng });
    });

    map.on('click', (e) => {
        const { lat, lng } = e.latlng;
        marker.setLatLng([lat, lng]);
        circle.setLatLng([lat, lng]);
        emit('update:coordinates', { latitude: lat, longitude: lng });
    });

    // Fix leaflet sizing on modal/tabs
    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});

watch(
    () => props.radius,
    (newRadius) => {
        if (circle) {
            circle.setRadius(newRadius);
        }
    }
);

watch(
    () => [props.latitude, props.longitude],
    ([newLat, newLng]) => {
        if (marker && circle && map) {
            const current = marker.getLatLng();
            if (current.lat !== newLat || current.lng !== newLng) {
                marker.setLatLng([newLat, newLng]);
                circle.setLatLng([newLat, newLng]);
                map.panTo([newLat, newLng]);
            }
        }
    }
);
</script>

<template>
    <div class="relative w-full rounded-xl overflow-hidden border border-slate-200 shadow-sm">
        <div ref="mapContainer" class="w-full h-72 z-0"></div>
        <div class="absolute bottom-2 left-2 z-[400] bg-white/90 backdrop-blur-sm px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 shadow border border-slate-200">
            Klik peta atau geser pin untuk menentukan titik masjid
        </div>
    </div>
</template>
