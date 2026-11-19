<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import { Calendar, CheckCircle2, Clock, XCircle, Eye, MapPin, Filter, ArrowLeft, X, Users, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    attendances: Object,
    filters: Object,
    stats: Object,
    isViewingSubordinate: Boolean,
    targetUser: Object,
    subordinates: Array,
});

const monthFilter = ref(props.filters.month);
const prayerFilter = ref(props.filters.prayer_type || '');
const statusFilter = ref(props.filters.status || '');
const selectedUserFilter = ref(props.filters.user_id || '');

const memberOptions = computed(() => [
    { value: '', label: 'Saya (Pribadi)' },
    ...(props.subordinates || []).map((m) => ({
        value: String(m.id),
        label: `${m.name}`,
    })),
]);

const prayerOptions = [
    { value: '', label: 'Semua Sholat' },
    { value: 'subuh', label: 'Subuh' },
    { value: 'dzuhur', label: 'Dzuhur' },
    { value: 'ashar', label: 'Ashar' },
    { value: 'maghrib', label: 'Maghrib' },
    { value: 'isya', label: 'Isya' },
];

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'present', label: 'Tepat Waktu / Disetujui' },
    { value: 'late', label: 'Terlambat' },
    { value: 'absent', label: 'Tidak Sholat' },
    { value: 'rejected', label: 'Ditolak' },
];

const applyFilters = () => {
    router.get(
        route('member.history.index'),
        {
            month: monthFilter.value,
            prayer_type: prayerFilter.value || undefined,
            status: statusFilter.value || undefined,
            user_id: selectedUserFilter.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

// Detail Modal
const selectedAttendance = ref(null);
const showDetailModal = ref(false);

const openDetail = (attendance) => {
    selectedAttendance.value = attendance;
    showDetailModal.value = true;
};

const closeDetail = () => {
    showDetailModal.value = false;
    selectedAttendance.value = null;
};
</script>

<template>
    <AppLayout title="Riwayat Absensi Sholat">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-slate-900">
                        {{ isViewingSubordinate ? `Riwayat Sholat: ${targetUser?.name}` : 'Riwayat Sholat Pribadi' }}
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{ isViewingSubordinate ? 'Catatan absensi dan konsistensi ibadah sholat anggota binaan Anda' : 'Catatan lengkap absensi dan konsistensi ibadah sholat lima waktu Anda' }}
                    </p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
            </div>

            <!-- Subordinate Selector untuk Approver / Admin -->
            <div
                v-if="subordinates && subordinates.length > 0"
                class="p-4 rounded-2xl bg-gradient-to-r from-emerald-50 via-teal-50 to-slate-50 border border-emerald-200/80 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3"
            >
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-xs flex-shrink-0">
                        <Users class="w-4 h-4" />
                    </div>
                    <div>
                        <div class="text-xs font-bold text-slate-900">
                            {{ isViewingSubordinate ? `Sedang Memantau: ${targetUser?.name}` : 'Pilih Riwayat Anggota Binaan' }}
                        </div>
                        <div class="text-[11px] text-slate-500">
                            {{ isViewingSubordinate ? targetUser?.email : 'Sebagai approver, Anda dapat memantau kedisiplinan sholat anakan yang Anda bina.' }}
                        </div>
                    </div>
                </div>

                <div class="w-full sm:w-64">
                    <CustomSelect
                        v-model="selectedUserFilter"
                        :options="memberOptions"
                        placeholder="Pilih Anggota"
                        @change="applyFilters"
                    />
                </div>
            </div>

            <!-- Monthly Summary Stat Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                <div class="p-2.5 sm:p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center sm:items-center text-center sm:text-left gap-1.5 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                        <CheckCircle2 class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm sm:text-lg font-black text-slate-900 leading-none">{{ stats?.present ?? 0 }}</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-tight sm:tracking-wider text-slate-400 mt-1 truncate">Tepat Waktu</div>
                    </div>
                </div>

                <div class="p-2.5 sm:p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center sm:items-center text-center sm:text-left gap-1.5 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                        <Clock class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm sm:text-lg font-black text-slate-900 leading-none">{{ stats?.late ?? 0 }}</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-tight sm:tracking-wider text-slate-400 mt-1 truncate">Terlambat</div>
                    </div>
                </div>

                <div class="p-2.5 sm:p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center sm:items-center text-center sm:text-left gap-1.5 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                        <XCircle class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm sm:text-lg font-black text-rose-600 leading-none">{{ stats?.absent ?? 0 }}</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-tight sm:tracking-wider text-slate-400 mt-1 truncate">Tidak Sholat</div>
                    </div>
                </div>

                <div class="p-2.5 sm:p-3.5 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-center sm:items-center text-center sm:text-left gap-1.5 sm:gap-3">
                    <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-slate-50 text-slate-600 flex items-center justify-center flex-shrink-0">
                        <Calendar class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm sm:text-lg font-black text-slate-900 leading-none">{{ stats?.total ?? 0 }}</div>
                        <div class="text-[9px] sm:text-[10px] font-semibold uppercase tracking-tight sm:tracking-wider text-slate-400 mt-1 truncate">Total Terlaksana</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <input
                        type="month"
                        v-model="monthFilter"
                        @change="applyFilters"
                        class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 text-slate-700 focus:border-emerald-500 focus:ring-emerald-500 shadow-xs"
                    />
                </div>

                <div>
                    <CustomSelect
                        v-model="prayerFilter"
                        :options="prayerOptions"
                        placeholder="Semua Sholat"
                        @change="applyFilters"
                    />
                </div>

                <div>
                    <CustomSelect
                        v-model="statusFilter"
                        :options="statusOptions"
                        placeholder="Semua Status"
                        @change="applyFilters"
                    />
                </div>
            </div>

            <!-- Attendance List / Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div v-if="attendances.data.length === 0" class="p-12 text-center text-slate-400">
                    <Calendar class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                    <p class="text-sm font-medium">Belum ada catatan absensi pada filter ini.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3 px-4 sm:px-6">Sholat & Waktu</th>
                                <th class="py-3 px-2 sm:px-4 text-center">Status</th>
                                <th class="py-3 px-4 sm:px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr
                                v-for="att in attendances.data"
                                :key="att.id"
                                class="hover:bg-slate-50/70 transition"
                            >
                                <!-- Kolom 1: Sholat & Waktu -->
                                <td class="py-3.5 px-4 sm:px-6">
                                    <div class="text-sm font-bold text-slate-900">
                                        {{ att.prayer_label }}
                                    </div>
                                    <div class="text-[11px] text-slate-500 mt-0.5">
                                        <span>{{ att.attendance_date }} &bull; {{ att.time }}</span>
                                    </div>
                                </td>

                                <!-- Kolom 2: Status (Seragam 4 Status Utama) -->
                                <td class="py-3.5 px-2 sm:px-4 text-center">
                                    <!-- Menunggu Approval -->
                                    <template v-if="att.status === 'pending' || att.status === 'pending_approval'">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                                            <Clock class="w-3.5 h-3.5 text-amber-600 animate-pulse" />
                                            <span>Menunggu</span>
                                        </span>
                                    </template>

                                    <!-- Ditolak -> Tidak Sholat dengan ikon [X] -->
                                    <template v-else-if="att.is_rejected || att.status === 'rejected'">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                                            <XCircle class="w-3.5 h-3.5 text-rose-600" />
                                            <span>Tidak Sholat</span>
                                        </span>
                                    </template>

                                    <!-- Terlambat -->
                                    <template v-else-if="att.status === 'late'">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-amber-800 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">
                                            <CheckCircle2 v-if="att.is_approver_verified" class="w-3.5 h-3.5 text-amber-600" />
                                            <span>Terlambat</span>
                                        </span>
                                    </template>

                                    <!-- Tepat Waktu -->
                                    <template v-else-if="att.status === 'present' || att.status === 'approved'">
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-800 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200">
                                            <CheckCircle2 v-if="att.is_approver_verified" class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>Tepat Waktu</span>
                                        </span>
                                    </template>

                                    <!-- Tidak Sholat -->
                                    <template v-else>
                                        <span class="inline-flex items-center gap-1 text-xs font-semibold text-rose-700 bg-rose-50 px-2.5 py-0.5 rounded-full border border-rose-200">
                                            <span>Tidak Sholat</span>
                                        </span>
                                    </template>
                                </td>

                                <!-- Kolom 3: Aksi / Detail -->
                                <td class="py-3.5 px-4 sm:px-6 text-right">
                                    <span v-if="att.status === 'absent'" class="text-slate-300 font-bold px-2 py-1 select-none inline-block">
                                        -
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="openDetail(att)"
                                        class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition shadow-xs active:scale-95"
                                        title="Lihat Detail Bukti"
                                    >
                                        <Eye class="w-3.5 h-3.5 text-slate-500" />
                                        <span>Detail</span>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="attendances.links && attendances.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Menampilkan {{ attendances.from }}-{{ attendances.to }} dari {{ attendances.total }} data
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in attendances.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-md border"
                            :class="[
                                link.active ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Detail Modal -->
            <Modal :show="showDetailModal" @close="closeDetail">
                <div v-if="selectedAttendance" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">
                            Detail Bukti Sholat {{ selectedAttendance.prayer_label }}
                        </h3>
                        <button @click="closeDetail" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Selfie Preview if available -->
                    <div v-if="selectedAttendance.has_photo" class="text-center">
                        <img
                            :src="selectedAttendance.photo_url"
                            alt="Bukti Selfie"
                            class="w-48 h-60 object-cover mx-auto rounded-xl border border-slate-200 shadow-sm"
                        />
                        <p class="text-[11px] text-slate-400 mt-1.5">Foto selfie verifikasi kehadiran sholat</p>
                    </div>

                    <!-- Informasi Waktu & Lokasi Absen (Table) -->
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="w-full text-xs text-left border-collapse">
                            <tbody class="divide-y divide-slate-100">
                                <tr class="bg-slate-50/50">
                                    <td class="py-2.5 px-4 font-semibold text-slate-500 w-32 sm:w-36">
                                        Waktu Absen
                                    </td>
                                    <td class="py-2.5 px-4 font-bold text-slate-900">
                                        {{ selectedAttendance.attendance_date }} {{ selectedAttendance.time }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2.5 px-4 font-semibold text-slate-500 align-top">
                                        Lokasi
                                    </td>
                                    <td class="py-2.5 px-4 text-slate-800">
                                        <div v-if="selectedAttendance.location_name" class="font-bold text-slate-900">
                                            {{ selectedAttendance.location_name }}
                                        </div>
                                        <div v-if="selectedAttendance.address" class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                            {{ selectedAttendance.address }}
                                        </div>
                                        <div v-else-if="!selectedAttendance.location_name && !selectedAttendance.address" class="text-slate-400 italic">
                                            Lokasi tidak tersedia
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="selectedAttendance.request_notes" class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs">
                        <span class="font-bold text-amber-900">Catatan Susulan:</span>
                        <p class="text-amber-800 italic mt-0.5 capitalize">"{{ selectedAttendance.request_notes }}"</p>
                    </div>

                    <div v-if="selectedAttendance.rejection_reason" class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs">
                        <span class="font-bold text-rose-900">Alasan Ditolak:</span>
                        <p class="text-rose-800 mt-0.5 capitalize">{{ selectedAttendance.rejection_reason }}</p>
                    </div>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
