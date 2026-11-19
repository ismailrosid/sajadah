<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import {
    Calendar,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    Clock,
    Eye,
    Search,
    Bot,
    UserCheck,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    date: String,
    attendances: Array,
    filters: Object,
    isAdminMode: {
        type: Boolean,
        default: false,
    },
});

const prayerType = ref(props.filters.prayer_type || '');
const status = ref(props.filters.status || '');
const search = ref(props.filters.search || '');

const formattedDate = computed(() => {
    if (!props.date) return '';
    try {
        const d = new Date(props.date + 'T00:00:00');
        return d.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        });
    } catch {
        return props.date;
    }
});

const prayerOptions = [
    { value: '', label: 'Semua Waktu Sholat' },
    { value: 'subuh', label: 'Subuh' },
    { value: 'dzuhur', label: 'Dzuhur' },
    { value: 'ashar', label: 'Ashar' },
    { value: 'maghrib', label: 'Maghrib' },
    { value: 'isya', label: 'Isya' },
];

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'absent', label: 'Tidak Sholat' },
];

const applyFilters = () => {
    router.get(
        route(props.isAdminMode ? 'admin.approvals.daily' : 'approvals.daily', props.date),
        {
            prayer_type: prayerType.value || undefined,
            status: status.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};
</script>

<template>
    <AppLayout title="Detail Absensi Harian" :is-admin="isAdminMode">
        <div class="p-4 sm:p-6 max-w-7xl mx-auto space-y-4 sm:space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route(isAdminMode ? 'admin.approvals.index' : 'approvals.index')"
                        class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm"
                        title="Kembali ke Rekap Harian"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight">
                            Daftar Absensi
                        </h1>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-xs font-semibold">
                                <Calendar class="w-3.5 h-3.5 text-emerald-600" />
                                <span>{{ formattedDate }}</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-wrap items-center gap-3">
                <div class="w-full sm:w-44">
                    <CustomSelect
                        v-model="prayerType"
                        :options="prayerOptions"
                        placeholder="Semua Waktu Sholat"
                        @change="applyFilters"
                    />
                </div>

                <div class="w-full sm:w-44">
                    <CustomSelect
                        v-model="status"
                        :options="statusOptions"
                        placeholder="Semua Status"
                        @change="applyFilters"
                    />
                </div>

                <div class="flex-1 min-w-[200px]">
                    <div class="relative">
                        <input
                            type="text"
                            v-model="search"
                            placeholder="Cari nama user atau email..."
                            class="w-full text-xs rounded-xl border-slate-200 py-2 pl-8 pr-3 text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                            @keyup.enter="applyFilters"
                        />
                        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" />
                    </div>
                </div>

                <button
                    type="button"
                    @click="applyFilters"
                    class="py-2 px-4 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold flex items-center gap-1.5 transition"
                >
                    <Filter class="w-3.5 h-3.5" />
                    <span>Filter</span>
                </button>
            </div>

            <!-- Attendance Items Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div v-if="attendances.length === 0" class="p-12 text-center text-slate-400 text-xs">
                    <Calendar class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                    <p class="font-bold text-slate-700">Belum Ada Absensi</p>
                    <p class="text-slate-400 mt-1">Tidak ada data absensi pada tanggal ini yang sesuai dengan filter.</p>
                </div>

                <div v-else>
                    <!-- Mobile Card View: Clean and compact without horizontal scrolling -->
                    <div class="md:hidden divide-y divide-slate-100">
                        <div
                            v-for="att in attendances"
                            :key="att.id"
                            class="p-4 space-y-3 hover:bg-slate-50/60 transition"
                        >
                            <!-- User Header & Prayer Badge -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs flex-shrink-0">
                                        {{ att.user_name.charAt(0) }}
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <div class="font-bold text-slate-900 text-xs truncate">{{ att.user_name }}</div>
                                        <div class="text-[10px] text-slate-400 truncate">{{ att.user_email }}</div>
                                    </div>
                                </div>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 flex-shrink-0">
                                    {{ att.prayer_label }}
                                </span>
                            </div>

                            <!-- Meta details: Time, Status, Approval Source -->
                            <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-50">
                                <div class="flex items-center gap-1.5 text-slate-600 font-mono text-[11px]">
                                    <Clock class="w-3 h-3 text-slate-400" />
                                    <span>{{ att.time }}</span>
                                </div>

                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    :class="[
                                        att.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '',
                                        att.status === 'pending' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : '',
                                        att.status === 'rejected' ? 'bg-rose-100 text-rose-800' : '',
                                        att.status === 'absent' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ''
                                    ]"
                                >
                                    <CheckCircle2 v-if="att.status === 'approved'" class="w-3 h-3 text-emerald-600" />
                                    <Clock v-else-if="att.status === 'pending'" class="w-3 h-3 text-amber-600" />
                                    <XCircle v-else class="w-3 h-3 text-rose-600" />
                                    <span>{{ att.status_label }}</span>
                                </span>
                            </div>

                            <!-- Approval source info (if present) -->
                            <div v-if="att.approval_source === 'system'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-600 bg-slate-50 px-2 py-0.5 rounded-md">
                                <Bot class="w-3 h-3 text-indigo-500" />
                                <span>Disetujui Sistem</span>
                            </div>
                            <div v-else-if="att.approval_source === 'approver'" class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">
                                <UserCheck class="w-3 h-3 text-emerald-600" />
                                <span>Disetujui Approver</span>
                            </div>

                            <!-- Detail Bukti Action Button: Strip '-' if Tidak Sholat -->
                            <div v-if="att.status === 'absent'" class="text-center py-1 text-slate-300 font-bold text-xs select-none">
                                -
                            </div>
                            <Link
                                v-else
                                :href="route(isAdminMode ? 'admin.approvals.show' : 'approvals.show', att.id)"
                                class="w-full py-2 px-3 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-xs shadow-2xs transition active:scale-98 flex items-center justify-center gap-1.5"
                            >
                                <Eye class="w-3.5 h-3.5 text-slate-500" />
                                <span>Detail Bukti</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Desktop Table View: Shown only on md and larger -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="p-4 font-bold">User</th>
                                    <th class="p-4 font-bold">Jenis Sholat</th>
                                    <th class="p-4 font-bold">Waktu Absensi</th>
                                    <th class="p-4 font-bold text-center">Status</th>
                                    <th class="p-4 font-bold">Sumber Persetujuan</th>
                                    <th class="p-4 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="att in attendances"
                                    :key="att.id"
                                    class="hover:bg-slate-50/80 transition"
                                >
                                    <!-- User -->
                                    <td class="p-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 font-bold flex items-center justify-center text-xs">
                                                {{ att.user_name.charAt(0) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">{{ att.user_name }}</div>
                                                <div class="text-[11px] text-slate-400">{{ att.user_email }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Jenis Sholat -->
                                    <td class="p-4">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                            {{ att.prayer_label }}
                                        </span>
                                    </td>

                                    <!-- Waktu Absensi -->
                                    <td class="p-4 font-mono font-medium text-slate-700">
                                        {{ att.time }}
                                    </td>

                                    <!-- Status -->
                                    <td class="p-4 text-center">
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold"
                                            :class="[
                                                att.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '',
                                                att.status === 'pending' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : '',
                                                att.status === 'rejected' ? 'bg-rose-100 text-rose-800' : '',
                                                att.status === 'absent' ? 'bg-rose-50 text-rose-700 border border-rose-200' : ''
                                            ]"
                                        >
                                            <CheckCircle2 v-if="att.status === 'approved'" class="w-3.5 h-3.5 text-emerald-600" />
                                            <Clock v-else-if="att.status === 'pending'" class="w-3.5 h-3.5 text-amber-600" />
                                            <XCircle v-else class="w-3.5 h-3.5 text-rose-600" />
                                            <span>{{ att.status_label }}</span>
                                        </span>
                                    </td>

                                    <!-- Sumber Persetujuan -->
                                    <td class="p-4">
                                        <div v-if="att.approval_source === 'system'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-md">
                                            <Bot class="w-3.5 h-3.5 text-indigo-500" />
                                            <span>Disetujui oleh Sistem</span>
                                        </div>
                                        <div v-else-if="att.approval_source === 'approver'" class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md">
                                            <UserCheck class="w-3.5 h-3.5 text-emerald-600" />
                                            <span>Disetujui Approver</span>
                                        </div>
                                        <span v-else class="text-slate-400 text-xs italic">-</span>
                                    </td>

                                    <!-- Aksi: Buka Detail Verifikasi: Strip '-' if Tidak Sholat -->
                                    <td class="p-4 text-right">
                                        <Link
                                            v-if="att.status !== 'absent'"
                                            :href="route(isAdminMode ? 'admin.approvals.show' : 'approvals.show', att.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold text-xs shadow-sm transition active:scale-95"
                                        >
                                            <Eye class="w-3.5 h-3.5" />
                                            <span>Detail Bukti</span>
                                        </Link>
                                        <span v-else class="text-slate-300 font-bold pr-4 select-none">-</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
