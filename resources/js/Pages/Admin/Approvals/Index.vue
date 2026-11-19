<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import CustomDatePicker from '@/Components/UI/CustomDatePicker.vue';
import {
    Calendar,
    Clock,
    CheckCircle2,
    XCircle,
    ArrowRight,
    Search,
    Filter,
    RotateCcw,
    SlidersHorizontal,
    FileCheck
} from 'lucide-vue-next';

const props = defineProps({
    dailyGroups: Object,
    filters: Object,
    isApproverOnly: Boolean,
    isAdminMode: {
        type: Boolean,
        default: false,
    },
});

const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');
const status = ref(props.filters.status || '');
const search = ref(props.filters.search || '');

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
];

const applyFilters = () => {
    router.get(
        route(props.isAdminMode ? 'admin.approvals.index' : 'approvals.index'),
        {
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
            status: status.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    startDate.value = '';
    endDate.value = '';
    status.value = '';
    search.value = '';
    applyFilters();
};
</script>

<template>
    <AppLayout title="Persetujuan Absensi Sholat" :is-admin="isAdminMode">
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                        Persetujuan Absensi Sholat
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Rekapitulasi dan verifikasi persetujuan absensi sholat yang dikelompokkan per tanggal.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        v-if="!isApproverOnly"
                        :href="route('admin.approval-config.index')"
                        class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 shadow-sm transition active:scale-95"
                    >
                        <SlidersHorizontal class="w-4 h-4 text-slate-500" />
                        <span>Konfigurasi Alur Approver</span>
                    </Link>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Dari Tanggal</label>
                        <CustomDatePicker
                            v-model="startDate"
                            placeholder="Pilih tanggal awal..."
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                        <CustomDatePicker
                            v-model="endDate"
                            placeholder="Pilih tanggal akhir..."
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Filter Status</label>
                        <CustomSelect
                            v-model="status"
                            :options="statusOptions"
                            placeholder="Semua Status"
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Cari Tanggal / User</label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="YYYY-MM-DD atau nama..."
                                class="w-full text-xs font-normal rounded-xl border border-slate-200 py-2 pl-8 pr-3 text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 shadow-xs"
                                @keyup.enter="applyFilters"
                            />
                            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                        </div>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="flex-1 py-2 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-sm active:scale-95"
                        >
                            <Filter class="w-3.5 h-3.5" />
                            <span>Filter</span>
                        </button>
                        <button
                            type="button"
                            @click="resetFilters"
                            class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-50 transition"
                            title="Reset Filter"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Grouped Daily Table: ONE ROW PER DATE -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div v-if="dailyGroups.data.length === 0" class="p-12 text-center text-slate-400 text-xs">
                    <Calendar class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                    <p class="font-bold text-slate-700">Tidak Ada Data Absensi</p>
                    <p class="text-slate-400 mt-1">Belum ada catatan absensi yang sesuai dengan filter pencarian.</p>
                </div>

                <div v-else>
                    <!-- Mobile Card View: Clean and compact without horizontal scrolling -->
                    <div class="md:hidden divide-y divide-slate-100">
                        <div
                            v-for="row in dailyGroups.data"
                            :key="row.date_raw"
                            class="p-4 space-y-3 hover:bg-slate-50/60 transition"
                        >
                            <!-- Top: Date info + total badge -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                        <Calendar class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-xs">
                                            {{ row.attendance_date }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-medium">
                                            Hari {{ row.attendance_day }}
                                        </div>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-slate-100 text-slate-700">
                                    {{ row.total_attendances }} Absensi
                                </span>
                            </div>

                            <!-- Middle: Status counter chips -->
                            <div class="grid grid-cols-3 gap-2">
                                <div
                                    class="flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-center border"
                                    :class="row.pending_count > 0 ? 'bg-amber-50/80 border-amber-200 text-amber-900' : 'bg-slate-50/50 border-slate-100 text-slate-400'"
                                >
                                    <div class="flex items-center gap-1 text-[10px] font-medium text-slate-500 mb-0.5">
                                        <Clock class="w-2.5 h-2.5 text-amber-500" />
                                        <span>Pending</span>
                                    </div>
                                    <span class="text-xs font-black" :class="row.pending_count > 0 ? 'text-amber-700' : 'text-slate-400'">
                                        {{ row.pending_count }}
                                    </span>
                                </div>

                                <div
                                    class="flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-center border"
                                    :class="row.approved_count > 0 ? 'bg-emerald-50/80 border-emerald-200 text-emerald-900' : 'bg-slate-50/50 border-slate-100 text-slate-400'"
                                >
                                    <div class="flex items-center gap-1 text-[10px] font-medium text-slate-500 mb-0.5">
                                        <CheckCircle2 class="w-2.5 h-2.5 text-emerald-500" />
                                        <span>Approved</span>
                                    </div>
                                    <span class="text-xs font-black" :class="row.approved_count > 0 ? 'text-emerald-700' : 'text-slate-400'">
                                        {{ row.approved_count }}
                                    </span>
                                </div>

                                <div
                                    class="flex flex-col items-center justify-center py-1.5 px-2 rounded-xl text-center border"
                                    :class="row.rejected_count > 0 ? 'bg-rose-50/80 border-rose-200 text-rose-900' : 'bg-slate-50/50 border-slate-100 text-slate-400'"
                                >
                                    <div class="flex items-center gap-1 text-[10px] font-medium text-slate-500 mb-0.5">
                                        <XCircle class="w-2.5 h-2.5 text-rose-500" />
                                        <span>Rejected</span>
                                    </div>
                                    <span class="text-xs font-black" :class="row.rejected_count > 0 ? 'text-rose-700' : 'text-slate-400'">
                                        {{ row.rejected_count }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bottom: Detail Link -->
                            <Link
                                :href="route(isAdminMode ? 'admin.approvals.daily' : 'approvals.daily', row.date_raw)"
                                class="w-full py-2 px-3 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 text-slate-700 font-semibold text-xs transition active:scale-98 flex items-center justify-center gap-1.5 shadow-2xs"
                            >
                                <span>Lihat Detail Absensi</span>
                                <ArrowRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>

                    <!-- Desktop Table View: Shown only on md and larger -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="p-4 font-bold">Tanggal Absensi</th>
                                    <th class="p-4 font-bold text-center">Total Absensi</th>
                                    <th class="p-4 font-bold text-center">Pending</th>
                                    <th class="p-4 font-bold text-center">Approved</th>
                                    <th class="p-4 font-bold text-center">Rejected</th>
                                    <th class="p-4 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="row in dailyGroups.data"
                                    :key="row.date_raw"
                                    class="hover:bg-slate-50/80 transition"
                                >
                                    <!-- Tanggal Absensi (Tanggal + Hari) -->
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                            <Calendar class="w-4 h-4 text-emerald-600" />
                                            <span>{{ row.attendance_date }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-400 mt-0.5 font-medium ml-6">
                                            Hari {{ row.attendance_day }}
                                        </div>
                                    </td>

                                    <!-- Total Absensi -->
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-black bg-slate-100 text-slate-800">
                                            {{ row.total_attendances }}
                                        </span>
                                    </td>

                                    <!-- Pending Count -->
                                    <td class="p-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="row.pending_count > 0 ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : 'bg-slate-50 text-slate-400'"
                                        >
                                            <Clock v-if="row.pending_count > 0" class="w-3 h-3 text-amber-600 animate-pulse" />
                                            <span>{{ row.pending_count }}</span>
                                        </span>
                                    </td>

                                    <!-- Approved Count -->
                                    <td class="p-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="row.approved_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-50 text-slate-400'"
                                        >
                                            <CheckCircle2 v-if="row.approved_count > 0" class="w-3 h-3 text-emerald-600" />
                                            <span>{{ row.approved_count }}</span>
                                        </span>
                                    </td>

                                    <!-- Rejected Count -->
                                    <td class="p-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="row.rejected_count > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-50 text-slate-400'"
                                        >
                                            <XCircle v-if="row.rejected_count > 0" class="w-3 h-3 text-rose-600" />
                                            <span>{{ row.rejected_count }}</span>
                                        </span>
                                    </td>

                                    <!-- Aksi: Tombol Detail -->
                                    <td class="p-4 text-right">
                                        <Link
                                            :href="route(isAdminMode ? 'admin.approvals.daily' : 'approvals.daily', row.date_raw)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-700 text-slate-700 font-semibold text-xs transition active:scale-95 shadow-sm"
                                        >
                                            <span>Detail</span>
                                            <ArrowRight class="w-3.5 h-3.5" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="dailyGroups.links && dailyGroups.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Menampilkan {{ dailyGroups.from }}-{{ dailyGroups.to }} dari {{ dailyGroups.total }} tanggal
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in dailyGroups.links"
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
        </div>
    </AppLayout>
</template>
