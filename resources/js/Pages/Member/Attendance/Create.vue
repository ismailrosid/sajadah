<script setup>
import { computed, onMounted, ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CameraCapture from '@/Components/Attendance/CameraCapture.vue';
import MapViewer from '@/Components/Maps/MapViewer.vue';
import { MapPin, CheckCircle, AlertTriangle, ArrowLeft, Loader2, Navigation, Clock, FileText, Send, Info, Sparkles, XCircle } from 'lucide-vue-next';

const props = defineProps({
    prayers: Object,
    setting: Object,
    activeOpenWindow: Object,
    nextUpcomingPrayer: Object,
    serverTime: String,
    isTestMode: Boolean,
});

const selectedPrayerType = ref('subuh');

// Preselect prayer from URL query if present, otherwise prefer active open window or first un-attended prayer
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const prayerParam = urlParams.get('prayer');
    if (prayerParam && props.prayers && props.prayers[prayerParam]) {
        selectedPrayerType.value = prayerParam;
    } else if (props.activeOpenWindow && props.prayers && props.prayers[props.activeOpenWindow.type] && !props.prayers[props.activeOpenWindow.type].attended) {
        selectedPrayerType.value = props.activeOpenWindow.type;
    } else if (props.prayers) {
        for (const [key, data] of Object.entries(props.prayers)) {
            if (!data.attended) {
                selectedPrayerType.value = key;
                break;
            }
        }
    }

    startGeolocation();
});

const selectedPrayer = computed(() => {
    return props.prayers ? props.prayers[selectedPrayerType.value] : null;
});

const isAlreadyAttended = computed(() => {
    return !!selectedPrayer.value?.attended;
});

const isWindowOpen = computed(() => {
    return selectedPrayer.value?.window_status === 'open' || selectedPrayer.value?.is_open;
});

const canLate = computed(() => {
    return Boolean(selectedPrayer.value?.can_late || (selectedPrayer.value?.is_closed && !selectedPrayer.value?.is_missed));
});

const isMissed = computed(() => {
    return Boolean(selectedPrayer.value?.is_missed || selectedPrayer.value?.window_status === 'missed');
});

const isWindowClosed = computed(() => {
    return canLate.value;
});

const isUpcoming = computed(() => {
    return selectedPrayer.value?.window_status === 'upcoming' || (!selectedPrayer.value?.is_started && !selectedPrayer.value?.is_open && !selectedPrayer.value?.is_closed && !selectedPrayer.value?.is_missed);
});

// Geolocation state (recorded purely for attendance location tracking)
const userLat = ref(null);
const userLng = ref(null);
const userAccuracy = ref(null);
const geoLoading = ref(true);
const geoError = ref(null);

// Address state for Gojek-style readable location
const addressData = ref({
    road: '',
    fullAddress: '',
    loading: false,
});

const fetchReverseGeocode = async (lat, lng) => {
    if (!lat || !lng) return;
    addressData.value.loading = true;

    // 1. Try OpenStreetMap Nominatim for exact street & area details
    try {
        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
            headers: { 'Accept': 'application/json' }
        });
        if (res.ok) {
            const data = await res.json();
            if (data && data.address) {
                const addr = data.address;
                const road = addr.road || addr.pedestrian || addr.suburb || addr.neighbourhood || addr.hamlet || addr.village || data.name;
                const village = addr.village || addr.suburb || addr.quarter;
                const district = addr.city_district || addr.county || addr.municipality;
                const city = addr.city || addr.town || addr.regency;
                const state = addr.state;

                const areaParts = [village, district, city, state].filter(Boolean);
                addressData.value.road = road || addr.city || 'Lokasi Terdeteksi';
                addressData.value.fullAddress = areaParts.length ? `${road ? road + ', ' : ''}${areaParts.join(', ')}` : data.display_name;
                addressData.value.loading = false;
                return;
            }
        }
    } catch (e) {
        console.warn('Nominatim reverse geocode error:', e);
    }

    // 2. Fallback to BigDataCloud client API
    try {
        const res = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`);
        if (res.ok) {
            const data = await res.json();
            const primary = data.locality || data.city || 'Lokasi Terdeteksi';
            const parts = [data.locality, data.city, data.principalSubdivision].filter(Boolean).join(', ');
            addressData.value.road = primary;
            addressData.value.fullAddress = parts || primary;
            addressData.value.loading = false;
            return;
        }
    } catch (e) {
        console.warn('BigDataCloud reverse geocode error:', e);
    }

    // 3. Fallback to clean coordinate string if offline
    addressData.value.road = `Titik Lokasi (${lat.toFixed(4)}, ${lng.toFixed(4)})`;
    addressData.value.fullAddress = `Koordinat: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    addressData.value.loading = false;
};

const startGeolocation = () => {
    geoLoading.value = true;
    geoError.value = null;

    if (!navigator.geolocation) {
        geoError.value = 'Browser tidak mendukung GPS.';
        geoLoading.value = false;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            userLat.value = position.coords.latitude;
            userLng.value = position.coords.longitude;
            userAccuracy.value = Math.round(position.coords.accuracy);
            geoLoading.value = false;
            fetchReverseGeocode(userLat.value, userLng.value);
        },
        (error) => {
            console.error('Geolocation error:', error);
            geoLoading.value = false;
            if (props.isTestMode) {
                userLat.value = -6.2088;
                userLng.value = 106.8456;
                userAccuracy.value = 10;
                geoError.value = null;
                fetchReverseGeocode(userLat.value, userLng.value);
            } else if (error.code === error.PERMISSION_DENIED) {
                geoError.value = 'Izin lokasi ditolak.';
            } else {
                geoError.value = 'Gagal mendeteksi lokasi GPS.';
            }
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

// Form state
const capturedPhotoFile = ref(null);

const form = useForm({
    prayer_type: selectedPrayerType.value,
    latitude: null,
    longitude: null,
    gps_accuracy: null,
    location_name: '',
    address: '',
    photo: null,
    is_late_request: false,
    request_notes: '',
});

const onPhotoCaptured = ({ file }) => {
    capturedPhotoFile.value = file;
    form.photo = file;
};

const onPhotoReset = () => {
    capturedPhotoFile.value = null;
    form.photo = null;
};

const submitAttendance = () => {
    form.prayer_type = selectedPrayerType.value;
    form.latitude = userLat.value || -6.2088;
    form.longitude = userLng.value || 106.8456;
    form.gps_accuracy = userAccuracy.value || 10;
    form.location_name = addressData.value.road || '';
    form.address = addressData.value.fullAddress || '';
    form.is_late_request = props.isTestMode ? false : isWindowClosed.value;

    form.post(route('member.attendance.store'), {
        preserveScroll: true,
        onError: (errors) => {
            console.error('Submission errors:', errors);
        },
    });
};
</script>

<template>
    <AppLayout title="Absensi Sholat">
        <div class="max-w-2xl mx-auto px-4 py-6 space-y-6">
            <!-- Back button & Title -->
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Form Absensi Sholat</h1>
                    <p class="text-xs text-slate-500">Catat kehadiran sholat berjamaah dengan foto selfie dan pencatatan lokasi GPS</p>
                </div>
            </div>

            <!-- Testing Mode Active Alert Banner -->
            <div v-if="isTestMode" class="p-4 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-950 flex items-start gap-3 shadow-sm">
                <div class="p-2 rounded-lg bg-indigo-100 text-indigo-700 flex-shrink-0 mt-0.5">
                    <Sparkles class="w-5 h-5" />
                </div>
                <div class="text-xs">
                    <div class="font-bold flex items-center gap-2">
                        <span>Mode Pengetesan Aktif (Validasi Dinonaktifkan)</span>
                        <span class="text-[10px] bg-indigo-200 text-indigo-800 px-2 py-0.5 rounded-full font-bold">Uji Coba Bebas Kapan Saja</span>
                    </div>
                    <p class="mt-1 text-indigo-800 leading-relaxed">
                        Validasi jadwal sholat, batasan jendela waktu, dan batasan 1x absensi per hari dinonaktifkan sementara. Anda dapat memilih waktu sholat apa saja dan melakukan pengujian absensi berulang kali tanpa batas.
                    </p>
                </div>
            </div>

            <!-- Step 1: Prayer Type Selector Pills -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        1. Pilih Waktu Sholat
                    </label>
                    <span v-if="setting?.attendance_duration_minutes" class="text-[11px] text-slate-400">
                        Durasi Periode: {{ setting.attendance_duration_minutes }} Menit
                    </span>
                </div>

                <div class="grid grid-cols-5 gap-1.5 sm:gap-2">
                    <button
                        v-for="(prayer, key) in prayers"
                        :key="key"
                        type="button"
                        @click="selectedPrayerType = key"
                        class="p-2.5 rounded-xl border text-center transition-all flex flex-col items-center justify-between gap-1"
                        :class="[
                            selectedPrayerType === key
                                ? 'bg-emerald-600 text-white border-emerald-600 shadow-md shadow-emerald-600/20 font-bold'
                                : prayer.attended
                                ? 'bg-slate-100 border-slate-200 text-slate-500'
                                : prayer.window_status === 'open'
                                ? 'bg-emerald-50/70 border-emerald-300 text-emerald-900 font-semibold'
                                : 'bg-white border-slate-200 text-slate-700 hover:border-emerald-300'
                        ]"
                    >
                        <span class="text-xs uppercase font-bold">{{ prayer.label }}</span>
                        <span class="text-[10px] font-mono" :class="selectedPrayerType === key ? 'text-emerald-100' : 'text-slate-400'">
                            {{ prayer.time }}
                        </span>
                        
                        <!-- Badges on button -->
                        <span
                            v-if="prayer.attended"
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full"
                            :class="selectedPrayerType === key ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700'"
                        >
                            {{ prayer.status_label || 'Sudah' }}
                        </span>
                        <span
                            v-else-if="prayer.window_status === 'open' || prayer.is_open"
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded-full animate-pulse"
                            :class="selectedPrayerType === key ? 'bg-white/20 text-white' : 'bg-emerald-200 text-emerald-800'"
                        >
                            Buka
                        </span>
                        <span
                            v-else-if="prayer.can_late"
                            class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full"
                            :class="selectedPrayerType === key ? 'bg-white/20 text-white' : 'bg-amber-100 text-amber-800'"
                        >
                            Susulan
                        </span>
                        <span
                            v-else-if="prayer.is_missed || prayer.window_status === 'missed'"
                            class="text-[9px] font-semibold px-1.5 py-0.5 rounded-full"
                            :class="selectedPrayerType === key ? 'bg-white/20 text-white' : 'bg-rose-100 text-rose-800'"
                        >
                            Tutup
                        </span>
                        <span
                            v-else
                            class="text-[9px] font-medium px-1.5 py-0.5 rounded-full"
                            :class="selectedPrayerType === key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-400'"
                        >
                            Belum
                        </span>
                    </button>
                </div>
            </div>

            <!-- Prayer Window Details & Status Card -->
            <div v-if="selectedPrayer" class="space-y-3">
                <!-- Info Bar: Timing & Window Status -->
                <div class="p-4 rounded-xl border bg-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                            :class="[
                                isAlreadyAttended
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : isWindowOpen
                                    ? 'bg-emerald-100 text-emerald-700 animate-pulse'
                                    : isWindowClosed
                                    ? 'bg-amber-100 text-amber-700'
                                    : isMissed
                                    ? 'bg-rose-100 text-rose-700'
                                    : 'bg-slate-100 text-slate-500'
                            ]"
                        >
                            <XCircle v-if="isMissed && !isAlreadyAttended" class="w-5 h-5" />
                            <Clock v-else class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="text-xs font-bold text-slate-900 flex items-center gap-2">
                                Sholat {{ selectedPrayer.label }} &bull; Masuk Pukul {{ selectedPrayer.time }}
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                Periode Absensi: <span class="font-semibold text-slate-700">{{ selectedPrayer.window_start || selectedPrayer.time }}</span> s/d <span class="font-semibold text-slate-700">{{ selectedPrayer.window_end || '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Status Pill -->
                    <!-- <div>
                        <span v-if="isAlreadyAttended" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <CheckCircle class="w-3.5 h-3.5" />
                            {{ selectedPrayer.status_label || 'Tercatat' }}
                        </span>
                        <span v-else-if="isWindowOpen" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                            Periode Absensi Terbuka
                        </span>
                        <span v-else-if="isWindowClosed" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                            <AlertTriangle class="w-3.5 h-3.5" />
                            Periode Berakhir (Susulan)
                        </span>
                        <span v-else-if="isMissed" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                            <XCircle class="w-3.5 h-3.5" />
                            Absensi Ditutup (Terlewat)
                        </span>
                        <span v-else-if="isUpcoming" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                            <Clock class="w-3.5 h-3.5" />
                            Waktu Sholat Belum Masuk
                        </span>
                    </div> -->
                </div>

                <!-- Upcoming Notice -->
                <!-- Upcoming Sholat Box (When prayer not arrived yet) -->
                <div v-if="!isAlreadyAttended && isUpcoming" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-slate-700 flex items-start gap-3">
                    <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 flex-shrink-0">
                        <Info class="w-4 h-4" />
                    </div>
                    <div class="text-xs space-y-0.5">
                        <div class="font-bold text-slate-800 text-sm">Waktu Sholat Belum Masuk</div>
                        <div class="text-slate-600">
                            <span v-if="isTestMode" class="text-indigo-700 font-semibold block">⚡ Mode Pengetesan: Anda dapat langsung melakukan absensi sekarang tanpa harus menunggu waktu sholat.</span>
                            <span v-else>Periode absensi dibuka saat waktu sholat tiba pukul <strong class="text-slate-800">{{ selectedPrayer.time }}</strong>.</span>
                        </div>
                    </div>
                </div>

                <!-- Missed Prayer Notice (When next prayer already arrived) -->
                <div v-else-if="!isAlreadyAttended && isMissed && !isTestMode" class="p-4 sm:p-5 rounded-2xl bg-rose-50/80 border border-rose-200 text-slate-800 space-y-2 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600 flex-shrink-0">
                            <XCircle class="w-4 h-4" />
                        </div>
                        <div class="text-xs space-y-1">
                            <div class="font-bold text-rose-950 text-sm">Absensi Telah Ditutup</div>
                           <div class="text-slate-600 leading-relaxed">
                                Waktu sholat berikutnya telah tiba. Absensi untuk Sholat <strong class="text-slate-900">{{ selectedPrayer.label }}</strong> telah ditutup otomatis oleh sistem.
                           </div>
                        </div>
                    </div>
                </div>

                <!-- Late Request Box (When window closed and not attended and not missed) -->
                <div v-else-if="!isAlreadyAttended && isWindowClosed" class="p-4 sm:p-5 rounded-2xl bg-amber-50/70 border border-amber-200/80 text-slate-800 space-y-3.5 shadow-xs">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 flex-shrink-0">
                            <AlertTriangle class="w-4 h-4" />
                        </div>
                        <div class="text-xs space-y-0.5">
                            <div class="font-bold text-slate-800 text-sm">Waktu Absensi Berakhir</div>
                            <div class="text-slate-600 leading-relaxed">
    Absensi normal ditutup pukul <strong class="text-slate-800">{{ selectedPrayer.window_end }}</strong>. Anda masih dapat mengisi absensi susulan.
</div>
                        </div>
                    </div>

                    <div class="space-y-2 pt-2.5 border-t border-amber-200/60">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                <FileText class="w-3.5 h-3.5 text-amber-600" />
                                Alasan Keterlambatan <span class="text-rose-500">*</span>
                            </label>
                        </div>
                        <textarea
                            v-model="form.request_notes"
                            rows="2"
                            required
                            placeholder="Tulis alasan singkat keterlambatan Anda..."
                            class="w-full text-xs rounded-xl border border-amber-200/80 bg-white p-3 text-slate-800 placeholder:text-slate-400 focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 shadow-xs transition-all resize-none"
                        ></textarea>
                        <div v-if="form.errors.request_notes" class="text-xs text-rose-600 font-medium">
                            {{ form.errors.request_notes }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Geolocation Capture & Map -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                        2. Titik Lokasi Kehadiran (GPS)
                    </label>
                    <button
                        type="button"
                        @click="startGeolocation"
                        class="text-[11px] font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                    >
                        <Navigation class="w-3 h-3" />
                        Perbarui Lokasi
                    </button>
                </div>

                <!-- Gojek-style Location & Address Card -->
                <div
                    class="p-4 rounded-xl border flex items-start justify-between gap-3.5 transition-all"
                    :class="[
                        geoLoading
                            ? 'bg-slate-50 border-slate-200 text-slate-600'
                            : geoError
                            ? 'bg-rose-50 border-rose-200 text-rose-800'
                            : 'bg-white border-slate-200 shadow-sm'
                    ]"
                >
                    <div class="flex items-start gap-3.5 flex-1 min-w-0">
                        <div v-if="geoLoading" class="p-2.5 rounded-xl bg-slate-200 text-slate-600 animate-spin mt-0.5">
                            <Loader2 class="w-5 h-5" />
                        </div>
                        <div v-else-if="geoError" class="p-2.5 rounded-xl bg-rose-100 text-rose-600 mt-0.5">
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                        <div v-else class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-md shadow-emerald-600/20 mt-0.5">
                            <MapPin class="w-5 h-5" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <!-- Header Tag -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200/60 inline-flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                                    Lokasi Anda Saat Ini
                                </span>
                                <span v-if="userAccuracy" class="text-[10px] text-slate-400 font-mono">
                                    Akurasi ±{{ userAccuracy }}m
                                </span>
                            </div>

                            <!-- Nama Jalan / Area (Gojek Style Bold Title) -->
                            <div class="mt-1.5 text-sm font-bold text-slate-900 leading-snug">
                                <span v-if="geoLoading">Mendeteksi lokasi GPS...</span>
                                <span v-else-if="geoError">{{ geoError }}</span>
                                <span v-else-if="addressData.loading && !addressData.road" class="text-slate-400 font-normal text-xs animate-pulse">
                                    Mencari nama jalan & area...
                                </span>
                                <span v-else class="text-slate-900">
                                    {{ addressData.road || 'Lokasi Absensi Terdeteksi' }}
                                </span>
                            </div>

                            <!-- Alamat Lengkap Detail -->
                            <p v-if="!geoLoading && !geoError" class="text-xs text-slate-500 mt-0.5 leading-relaxed">
                                <span v-if="addressData.loading && !addressData.fullAddress" class="text-slate-400 text-[11px] animate-pulse">
                                    Mengambil rincian alamat lengkap...
                                </span>
                                <span v-else>
                                    {{ addressData.fullAddress || `Koordinat: ${userLat?.toFixed(6)}, ${userLng?.toFixed(6)}` }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Leaflet Map Viewer (Static, titik tidak bisa diubah) -->
                <MapViewer
                    :user-lat="userLat"
                    :user-lng="userLng"
                    :user-accuracy="userAccuracy"
                />
            </div>

            <!-- Step 3: Camera Live Selfie Capture -->
            <div class="space-y-2">
                <label class="text-xs font-bold uppercase tracking-wider text-slate-500">
                    3. Foto Selfie Bukti Kehadiran
                </label>
                <p class="text-xs text-slate-400">
                    Ambil foto selfie langsung saat sholat. Foto tidak dapat diunggah dari galeri file.
                </p>

                <CameraCapture
                    @captured="onPhotoCaptured"
                    @reset="onPhotoReset"
                />
            </div>

            <!-- Submit Attendance Button -->
            <div class="pt-4">
                <button
                    type="button"
                    @click="submitAttendance"
                    :disabled="form.processing || (!isTestMode && (!capturedPhotoFile || geoLoading || !!geoError || isAlreadyAttended || isUpcoming || isMissed || (isWindowClosed && !form.request_notes?.trim())))"
                    class="w-full py-3.5 px-4 rounded-xl text-sm font-bold text-white shadow-lg transition-all flex items-center justify-center gap-2"
                    :class="[
                        form.processing || (!isTestMode && (!capturedPhotoFile || geoLoading || !!geoError || isAlreadyAttended || isUpcoming || isMissed || (isWindowClosed && !form.request_notes?.trim())))
                            ? 'bg-slate-300 text-slate-500 cursor-not-allowed shadow-none'
                            : isTestMode
                            ? 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/25 active:scale-[0.98]'
                            : isWindowClosed
                            ? 'bg-amber-600 hover:bg-amber-700 shadow-amber-600/25 active:scale-[0.98]'
                            : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25 active:scale-[0.98]'
                    ]"
                >
                    <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                    <template v-else>
                        <Sparkles v-if="isTestMode" class="w-5 h-5 text-indigo-200" />
                        <CheckCircle v-else-if="!isWindowClosed && !isAlreadyAttended && !isUpcoming && !isMissed" class="w-5 h-5" />
                        <Send v-else-if="isWindowClosed && !isAlreadyAttended && !isMissed" class="w-5 h-5" />
                        <XCircle v-else-if="isMissed && !isAlreadyAttended" class="w-5 h-5" />
                        <Clock v-else class="w-5 h-5" />
                    </template>
                    <span>
                        <template v-if="form.processing">Memproses Absensi...</template>
                        <template v-else-if="isTestMode">
                            <span v-if="isAlreadyAttended">Kirim Ulang Absensi Sholat {{ selectedPrayer?.label }} (Mode Uji Coba)</span>
                            <span v-else>Kirim Absensi Sholat {{ selectedPrayer?.label }} (Bebas Kapan Saja)</span>
                        </template>
                        <template v-else-if="isAlreadyAttended">Sudah Melakukan Absensi</template>
                        <template v-else-if="isMissed">Waktu Sholat Telah Ditutup (Terlewat)</template>
                        <template v-else-if="isUpcoming">Waktu Sholat Belum Masuk</template>
                        <template v-else-if="isWindowClosed">Ajukan Absensi Susulan</template>
                        <template v-else>Kirim & Simpan Absensi</template>
                    </span>
                </button>

                <div v-if="form.errors.prayer_type" class="text-xs text-rose-600 font-medium text-center mt-2">
                    {{ form.errors.prayer_type }}
                </div>
                <div v-if="form.errors.photo" class="text-xs text-rose-600 font-medium text-center mt-2">
                    {{ form.errors.photo }}
                </div>
                <div v-if="form.errors.latitude" class="text-xs text-rose-600 font-medium text-center mt-2">
                    {{ form.errors.latitude }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
