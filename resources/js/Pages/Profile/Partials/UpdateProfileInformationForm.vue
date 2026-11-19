<script setup>
import { ref } from 'vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { MapPin, Navigation, Clock, Settings, Compass, Check } from 'lucide-vue-next';

const props = defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    userSettings: {
        type: Object,
        default: () => ({}),
    },
    calculationMethods: {
        type: Array,
        default: () => [],
    },
    timezones: {
        type: Array,
        default: () => [],
    },
});

const user = usePage().props.auth.user;
const isDetectingLocation = ref(false);
const locationDetectedSuccess = ref(false);
const locationError = ref('');

const form = useForm({
    name: user.name,
    email: user.email,
    city: props.userSettings.city || user.city || 'Jakarta',
    latitude: props.userSettings.latitude ?? user.latitude ?? -6.2088,
    longitude: props.userSettings.longitude ?? user.longitude ?? 106.8456,
    timezone: props.userSettings.timezone || user.timezone || 'Asia/Jakarta',
    prayer_calc_method: props.userSettings.prayer_calc_method || user.prayer_calc_method || 'KEMENAG',
});

const cleanCityName = (raw) => {
    if (!raw) return '';
    let name = String(raw).trim();
    if (/^kab\s+/i.test(name)) {
        name = name.replace(/^kab\s+/i, 'Kabupaten ');
    }
    return name;
};

const detectCurrentGps = () => {
    if (!navigator.geolocation) {
        locationError.value = 'Browser Anda tidak mendukung Geolocation.';
        return;
    }

    isDetectingLocation.value = true;
    locationError.value = '';
    locationDetectedSuccess.value = false;

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lat = Number(pos.coords.latitude.toFixed(7));
            const lng = Number(pos.coords.longitude.toFixed(7));
            form.latitude = lat;
            form.longitude = lng;

            // Reverse geocode to auto-fill the city name
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=14&addressdetails=1`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    const addr = data?.address || {};
                    const rawCity = addr.city || addr.regency || addr.county || addr.town || addr.municipality || addr.city_district;
                    if (rawCity) {
                        form.city = cleanCityName(rawCity);
                    }
                }
            } catch (e) {
                try {
                    const res2 = await fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lng}&localityLanguage=id`);
                    if (res2.ok) {
                        const data2 = await res2.json();
                        const rawCity2 = data2.city || data2.locality || data2.principalSubdivision;
                        if (rawCity2) {
                            form.city = cleanCityName(rawCity2);
                        }
                    }
                } catch (e2) {
                    console.warn('Reverse geocode fallback error:', e2);
                }
            }

            isDetectingLocation.value = false;
            locationDetectedSuccess.value = true;
            setTimeout(() => {
                locationDetectedSuccess.value = false;
            }, 6000);
        },
        (err) => {
            isDetectingLocation.value = false;
            locationError.value = 'Gagal mendeteksi lokasi GPS: ' + err.message;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <Settings class="w-5 h-5 text-emerald-600" />
                <span>Informasi Profil & Preferensi Sholat</span>
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Kelola informasi akun Anda dan sesuaikan lokasi untuk perhitungan waktu sholat otomatis serta batas absensi harian.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <!-- Akun Dasar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="name" value="Nama Lengkap" />
                    <TextInput
                        id="name"
                        type="text"
                        class="block w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />
                    <InputError class="mt-1 text-xs" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Alamat Email" />
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />
                    <InputError class="mt-1 text-xs" :message="form.errors.email" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-xs text-slate-700">
                    Alamat email Anda belum diverifikasi.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-xs text-emerald-600 underline hover:text-emerald-700 focus:outline-none"
                    >
                        Klik di sini untuk mengirim ulang email verifikasi.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-medium text-emerald-600"
                >
                    Tautan verifikasi baru telah dikirim ke alamat email Anda.
                </div>
            </div>

            <!-- Konfigurasi Waktu & Lokasi Sholat -->
            <div class="border-t border-slate-200/80 pt-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-1">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-1.5">
                            <MapPin class="w-4 h-4 text-emerald-600 flex-shrink-0" />
                            <span>Lokasi & Perhitungan Jadwal Sholat</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                            Waktu sholat dan jendela absensi dihitung otomatis berdasarkan koordinat atau kota domisili Anda.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="detectCurrentGps"
                        :disabled="isDetectingLocation"
                        class="self-start sm:self-auto px-3.5 py-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 text-xs font-semibold flex items-center gap-2 transition disabled:opacity-50 shadow-xs flex-shrink-0 cursor-pointer active:scale-95"
                    >
                        <Navigation class="w-3.5 h-3.5 text-emerald-600" :class="{ 'animate-spin': isDetectingLocation }" />
                        <span>{{ isDetectingLocation ? 'Mendeteksi...' : 'Ambil GPS Saya' }}</span>
                    </button>
                </div>

                <div v-if="locationDetectedSuccess" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                    <Check class="w-4 h-4 text-emerald-600 flex-shrink-0" />
                    <span>Koordinat GPS dan kota domisili (<strong>{{ form.city }}</strong>) berhasil dideteksi dari lokasi Anda! Jangan lupa klik simpan di bawah.</span>
                </div>

                <div v-if="locationError" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                    {{ locationError }}
                </div>

                <div class="max-w-md">
                    <InputLabel for="city" value="Nama Kota Domisili" />
                    <TextInput
                        id="city"
                        type="text"
                        class="block w-full mt-1"
                        v-model="form.city"
                        placeholder="Contoh: Bogor, Jakarta, Bandung"
                        required
                    />
                    <InputError class="mt-1 text-xs" :message="form.errors.city" />
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-slate-200/80">
                <PrimaryButton :disabled="form.processing" class="!bg-emerald-600 hover:!bg-emerald-700 !px-5 !py-2.5 !rounded-xl !text-xs !shadow-md !shadow-emerald-600/20">
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600 flex items-center gap-1"
                    >
                        <Check class="w-3.5 h-3.5" />
                        Pengaturan berhasil disimpan.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
