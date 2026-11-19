<script setup>
import { computed, ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import CustomMonthPicker from '@/Components/UI/CustomMonthPicker.vue';
import { Calendar, CheckCircle2, Check, Clock, XCircle, Eye, MapPin, Filter, ArrowLeft, X, Users, AlertCircle } from 'lucide-vue-next';

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

const getTableStatusLabel = (att) => {
    if (!att) return '';
    if (att.status === 'pending' || att.status === 'pending_approval') {
        return 'Menunggu';
    }
    if (att.status === 'late') {
        return 'Terlambat';
    }
    if (att.status === 'present' || att.status === 'approved') {
        return 'Tepat Waktu';
    }
    return 'Tidak Sholat';
};

const getDetailStatusLabel = (att) => {
    if (!att) return '';
    if (att.status === 'pending' || att.status === 'pending_approval') {
        return 'Menunggu Persetujuan';
    }
    if (att.is_rejected || att.status === 'rejected') {
        return 'Ditolak (Tidak Sholat)';
    }
    if (att.is_approver_verified) {
        return att.status === 'late' ? 'Disetujui Terlambat' : 'Disetujui Tepat Waktu';
    }
    if (att.status === 'late') {
        return 'Terlambat';
    }
    if (att.status === 'present' || att.status === 'approved') {
        return 'Tepat Waktu';
    }
    return 'Tidak Sholat';
};

const getStatusBadgeClasses = (att) => {
    if (!att) return '';
    if (att.status === 'present' || att.status === 'approved') {
        return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    }
    if (att.status === 'pending' || att.status === 'pending_approval') {
        return 'bg-amber-100 text-amber-800 border-amber-200 ring-1 ring-amber-200';
    }
    if (att.status === 'late') {
        return 'bg-amber-100 text-amber-800 border-amber-200';
    }
    if (att.status === 'rejected' || att.status === 'absent' || att.is_rejected) {
        return 'bg-rose-100 text-rose-800 border-rose-200';
    }
    return 'bg-slate-100 text-slate-700 border-slate-200';
};

const getRowClasses = (att) => {
    if (!att) return 'hover:bg-slate-50/70';
    if (att.status === 'present' || att.status === 'approved') {
        return 'bg-emerald-50/40 hover:bg-emerald-50/70 border-l-4 border-l-emerald-500';
    }
    if (att.status === 'pending' || att.status === 'pending_approval') {
        return 'bg-amber-50/30 hover:bg-amber-50/60 border-l-4 border-l-amber-400';
    }
    if (att.status === 'late') {
        return 'bg-amber-50/40 hover:bg-amber-50/70 border-l-4 border-l-amber-500';
    }
    if (att.status === 'rejected' || att.status === 'absent' || att.is_rejected) {
        return 'bg-rose-50/40 hover:bg-rose-50/70 border-l-4 border-l-rose-500';
    }
    return 'hover:bg-slate-50/70';
};

const hasApprovalStatus = (att) => {
    if (!att) return false;
    return !!(att.is_approver_verified || att.is_rejected || att.status === 'rejected' || att.status === 'pending' || att.status === 'pending_approval');
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
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <CustomMonthPicker
                        v-model="monthFilter"
                        placeholder="Pilih Bulan..."
                        @change="applyFilters"
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
                                <th class="py-3 px-3 sm:px-6">Sholat</th>
                                <th class="py-3 px-2 sm:px-4 text-center">Tanggal</th>
                                <th class="py-3 px-2 sm:px-4 text-center">Jam</th>
                                <th class="py-3 px-3 sm:px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <tr
                                v-for="att in attendances.data"
                                :key="att.id"
                                class="transition border-b border-slate-100/70"
                                :class="getRowClasses(att)"
                            >
                                <!-- Kolom 1: Sholat (Murni nama sholat, tanpa ikon) -->
                                <td class="py-3.5 px-3 sm:px-6 font-bold text-slate-900 text-sm">
                                    {{ att.prayer_label }}
                                </td>

                                <!-- Kolom 2: Tanggal -->
                                <td class="py-3.5 px-2 sm:px-4 text-center text-slate-600 text-[11px] sm:text-xs whitespace-nowrap">
                                    {{ att.attendance_date }}
                                </td>

                                <!-- Kolom 3: Jam -->
                                <td class="py-3.5 px-2 sm:px-4 text-center">
                                    <span
                                        v-if="att.status === 'absent'"
                                        class="text-xs font-semibold text-rose-400"
                                    >
                                        -
                                    </span>
                                    <span
                                        v-else
                                        class="text-xs sm:text-sm font-bold text-slate-800 tracking-tight"
                                    >
                                        {{ att.time }}
                                    </span>
                                </td>

                                <!-- Kolom 4: Aksi (Cukup ikon mata saja) -->
                                <td class="py-3.5 px-3 sm:px-6 text-center">
                                    <span v-if="att.status === 'absent'" class="text-slate-300 font-bold px-2 py-1 select-none inline-block">
                                        -
                                    </span>
                                    <button
                                        v-else
                                        type="button"
                                        @click="openDetail(att)"
                                        class="p-1.5 sm:p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 hover:text-emerald-600 hover:border-emerald-300 transition shadow-xs active:scale-95 inline-flex items-center justify-center"
                                        title="Lihat Detail Bukti"
                                    >
                                        <Eye class="w-4 h-4 text-slate-600 hover:text-emerald-600 transition" />
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
                                        Status
                                    </td>
                                    <td class="py-2.5 px-4 font-bold">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                            :class="getStatusBadgeClasses(selectedAttendance)"
                                        >
                                            <CheckCircle2 v-if="selectedAttendance.is_approver_verified" class="w-3.5 h-3.5" :class="selectedAttendance.status === 'late' ? 'text-amber-600' : 'text-emerald-600'" />
                                            <XCircle v-else-if="selectedAttendance.is_rejected || selectedAttendance.status === 'rejected'" class="w-3.5 h-3.5 text-rose-600" />
                                            <Clock v-else-if="selectedAttendance.status === 'pending' || selectedAttendance.status === 'pending_approval'" class="w-3.5 h-3.5 text-amber-600 animate-pulse" />
                                            <span>{{ getDetailStatusLabel(selectedAttendance) }}</span>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
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
