<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import CustomDatePicker from '@/Components/UI/CustomDatePicker.vue';
import { FileSpreadsheet, FileText, Filter, Eye, CheckCircle2, Clock, XCircle, Search } from 'lucide-vue-next';

const props = defineProps({
    attendances: Object,
    filters: Object,
    stats: Object,
    members: Array,
});

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const prayerType = ref(props.filters.prayer_type || '');
const status = ref(props.filters.status || '');
const userId = ref(props.filters.user_id || '');

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
    { value: 'present', label: 'Tepat Waktu' },
    { value: 'late', label: 'Terlambat' },
    { value: 'rejected', label: 'Ditolak' },
];

const userOptions = computed(() => [
    { value: '', label: 'Semua Pengguna' },
    ...(props.members || []).map(m => ({
        value: m.id,
        label: m.name,
    })),
]);

const applyFilters = () => {
    router.get(
        route('admin.reports.index'),
        {
            start_date: startDate.value,
            end_date: endDate.value,
            prayer_type: prayerType.value || undefined,
            status: status.value || undefined,
            user_id: userId.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const buildExportUrl = (format) => {
    const params = new URLSearchParams({
        start_date: startDate.value,
        end_date: endDate.value,
    });
    if (prayerType.value) params.append('prayer_type', prayerType.value);
    if (status.value) params.append('status', status.value);
    if (userId.value) params.append('user_id', userId.value);

    return route(`admin.reports.export.${format}`) + '?' + params.toString();
};
</script>

<template>
    <AppLayout title="Laporan Kehadiran Sholat" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header & Export Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Laporan & Rekapitulasi Absensi</h1>
                    <p class="text-xs text-slate-500">Laporan terperinci kehadiran sholat pengguna dengan ekspor dokumen</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="buildExportUrl('excel')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition active:scale-95"
                    >
                        <FileSpreadsheet class="w-4 h-4" />
                        <span>Export Excel (.xlsx)</span>
                    </a>
                    <a
                        :href="buildExportUrl('pdf')"
                        target="_blank"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition active:scale-95"
                    >
                        <FileText class="w-4 h-4" />
                        <span>Export PDF (.pdf)</span>
                    </a>
                </div>
            </div>

            <!-- Aggregate Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Total Absensi</div>
                    <div class="text-xl font-black text-slate-900 mt-1">{{ stats.total }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                    <div class="text-[10px] uppercase font-bold text-emerald-600">Tepat Waktu</div>
                    <div class="text-xl font-black text-emerald-600 mt-1">{{ stats.present }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                    <div class="text-[10px] uppercase font-bold text-amber-600">Terlambat</div>
                    <div class="text-xl font-black text-amber-600 mt-1">{{ stats.late }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm">
                    <div class="text-[10px] uppercase font-bold text-rose-600">Ditolak</div>
                    <div class="text-xl font-black text-rose-600 mt-1">{{ stats.rejected }}</div>
                </div>
                <div class="p-3.5 rounded-xl bg-white border border-slate-200 shadow-sm col-span-2 sm:col-span-1">
                    <div class="text-[10px] uppercase font-bold text-slate-400">Rata-rata Jarak</div>
                    <div class="text-xl font-black text-slate-900 mt-1">{{ stats.avg_distance }}m</div>
                </div>
            </div>

            <!-- Filters Bar -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-2 text-xs">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Dari Tanggal</label>
                        <CustomDatePicker
                            v-model="startDate"
                            placeholder="Dari tanggal..."
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Sampai Tanggal</label>
                        <CustomDatePicker
                            v-model="endDate"
                            placeholder="Sampai tanggal..."
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Waktu Sholat</label>
                        <CustomSelect
                            v-model="prayerType"
                            :options="prayerOptions"
                            placeholder="Semua Sholat"
                            @change="applyFilters"
                        />
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Status Kehadiran</label>
                        <CustomSelect
                            v-model="status"
                            :options="statusOptions"
                            placeholder="Semua Status"
                            @change="applyFilters"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase mb-1">Pengguna</label>
                        <CustomSelect
                            v-model="userId"
                            :options="userOptions"
                            placeholder="Semua Pengguna"
                            search-placeholder="Cari pengguna..."
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Attendance Data Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div v-if="attendances.data.length === 0" class="p-12 text-center text-slate-400 text-xs">
                    Tidak ada data absensi yang sesuai kriteria pencarian.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="p-4">Tanggal & Jam</th>
                                <th class="p-4">Pengguna</th>
                                <th class="p-4">Sholat</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-center">Selfie</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="a in attendances.data" :key="a.id" class="hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ a.attendance_date }}</div>
                                    <div class="text-[11px] font-mono text-slate-400">{{ a.time }} WIB</div>
                                </td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-900">{{ a.user_name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ a.user_email }}</div>
                                </td>
                                <td class="p-4 font-semibold text-slate-800">
                                    {{ a.prayer_label }}
                                </td>
                                <td class="p-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="[
                                            a.status === 'present' ? 'bg-emerald-100 text-emerald-800' : '',
                                            a.status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                                            a.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                        ]"
                                    >
                                        {{ a.status_label }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                        :class="[
                                            a.status === 'present' ? 'bg-emerald-100 text-emerald-800' : '',
                                            a.status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                                            a.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                        ]"
                                    >
                                        {{ a.status_label }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <a
                                        v-if="a.has_photo"
                                        :href="a.photo_url"
                                        target="_blank"
                                        class="inline-flex p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 transition"
                                        title="Buka Foto Selfie"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </a>
                                    <span v-else class="text-slate-300">-</span>
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
        </div>
    </AppLayout>
</template>
