<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import MapPicker from '@/Components/Maps/MapPicker.vue';
import { Landmark, Plus, Edit, Trash2, MapPin, CheckCircle, X, Loader2 } from 'lucide-vue-next';

defineProps({
    mosques: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingMosqueId = ref(null);

const form = useForm({
    name: '',
    address: '',
    latitude: -6.2088,
    longitude: 106.8456,
    radius_meters: 100,
    is_default: false,
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingMosqueId.value = null;
    form.reset();
    form.latitude = -6.2088;
    form.longitude = 106.8456;
    form.radius_meters = 100;
    form.is_default = false;
    showModal.value = true;
};

const openEditModal = (mosque) => {
    isEditing.value = true;
    editingMosqueId.value = mosque.id;
    form.name = mosque.name;
    form.address = mosque.address || '';
    form.latitude = Number(mosque.latitude);
    form.longitude = Number(mosque.longitude);
    form.radius_meters = mosque.radius_meters;
    form.is_default = !!mosque.is_default;
    form.description = mosque.description || '';
    showModal.value = true;
};

const onMapCoordinatesUpdate = ({ latitude, longitude }) => {
    form.latitude = Number(latitude.toFixed(8));
    form.longitude = Number(longitude.toFixed(8));
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.mosques.update', editingMosqueId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.mosques.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteMosque = (mosque) => {
    if (confirm(`Hapus masjid ${mosque.name}?`)) {
        router.delete(route('admin.mosques.destroy', mosque.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Kelola Masjid Komunitas" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Masjid & Lokasi Absensi</h1>
                    <p class="text-xs text-slate-500">Kelola titik koordinat GPS dan radius kehadiran sholat berjamaah</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition active:scale-95"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Masjid Baru</span>
                </button>
            </div>

            <!-- Mosque Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="m in mosques.data"
                    :key="m.id"
                    class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between space-y-4 hover:border-emerald-200 transition"
                >
                    <div class="space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100 flex-shrink-0">
                                    <Landmark class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-900 text-sm leading-snug">{{ m.name }}</h3>
                                    <span v-if="m.is_default" class="text-[10px] font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-full inline-block mt-0.5">
                                        Masjid Utama
                                    </span>
                                </div>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="openEditModal(m)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                >
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="deleteMosque(m)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 line-clamp-2">
                            {{ m.address || 'Alamat belum diatur' }}
                        </p>
                    </div>

                    <!-- Geofence & Stat Info -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1 text-slate-600">
                            <MapPin class="w-3.5 h-3.5 text-emerald-600" />
                            <span>Radius: <strong>{{ m.radius_meters }} meter</strong></span>
                        </div>
                        <div class="text-slate-400 text-[11px]">
                            {{ m.attendances_count }} absensi
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create / Edit Mosque Modal -->
            <Modal :show="showModal" @close="showModal = false" max-width="2xl">
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ isEditing ? 'Edit Data Masjid' : 'Tambah Masjid Baru' }}
                        </h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-bold text-slate-700">Nama Masjid / Musholla</label>
                            <input
                                type="text"
                                v-model="form.name"
                                required
                                placeholder="Contoh: Masjid Jami Al-Ikhlas"
                                class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                        </div>

                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-bold text-slate-700">Alamat Lengkap</label>
                            <textarea
                                v-model="form.address"
                                rows="2"
                                placeholder="Alamat fisik masjid..."
                                class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                            ></textarea>
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Radius Absensi (Meter)</label>
                            <input
                                type="number"
                                v-model.number="form.radius_meters"
                                min="20"
                                max="1000"
                                required
                                class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                            />
                            <p class="text-[10px] text-slate-400">Jarak maksimal jamaah dari titik pusat masjid</p>
                        </div>

                        <div class="space-y-1 flex items-center pt-5">
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    v-model="form.is_default"
                                    class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                />
                                <span class="text-xs font-medium text-slate-700">Jadikan Masjid Utama Komunitas</span>
                            </label>
                        </div>
                    </div>

                    <!-- Map Picker -->
                    <div class="space-y-1.5 pt-2">
                        <label class="text-xs font-bold text-slate-700">
                            Pilih Titik Koordinat GPS pada Peta
                        </label>
                        <MapPicker
                            :latitude="form.latitude"
                            :longitude="form.longitude"
                            :radius="form.radius_meters"
                            @update:coordinates="onMapCoordinatesUpdate"
                        />
                        <div class="flex items-center gap-3 text-[11px] font-mono text-slate-500 pt-1">
                            <span>Lat: {{ form.latitude }}</span>
                            <span>Lng: {{ form.longitude }}</span>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm flex items-center gap-1.5"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Tambahkan Masjid' }}</span>
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    </AppLayout>
</template>
