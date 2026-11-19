<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import CustomDatePicker from '@/Components/UI/CustomDatePicker.vue';
import { Search, Eye, Filter, Calendar, MapPin, Camera, CheckCircle2, Clock, XCircle, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    attendances: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const prayerType = ref(props.filters.prayer_type || '');
const status = ref(props.filters.status || '');
const date = ref(props.filters.date || '');

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
    { value: 'approved', label: 'Tepat Waktu / Disetujui' },
    { value: 'pending', label: 'Menunggu Approval' },
    { value: 'rejected', label: 'Ditolak' },
];

const applyFilters = () => {
    router.get(
        route('admin.attendances.index'),
        {
            search: search.value || undefined,
            prayer_type: prayerType.value || undefined,
            status: status.value || undefined,
            date: date.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};
</script>

<template>
    <AppLayout title="Monitoring Absensi" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Monitoring Absensi Seluruh Pengguna</h1>
                    <p class="text-xs text-slate-500">Pantau seluruh catatan kehadiran sholat personal, verifikasi lokasi GPS, dan bukti foto</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.approvals.index')"
                        class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                    >
                        <span>Monitoring Persetujuan</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Total Absensi</div>
                    <div class="text-2xl font-black text-slate-900 mt-1">{{ stats.total }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Tepat Waktu</div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">{{ stats.present }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="text-[11px] font-semibold text-amber-600 uppercase tracking-wider">Terlambat</div>
                    <div class="text-2xl font-black text-amber-700 mt-1">{{ stats.late }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider">Menunggu Approval</div>
                    <div class="text-2xl font-black text-indigo-700 mt-1">{{ stats.pending }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Ditolak</div>
                    <div class="text-2xl font-black text-rose-700 mt-1">{{ stats.rejected }}</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-wrap items-center gap-3">
                <div class="w-full sm:w-48">
                    <CustomDatePicker
                        v-model="date"
                        placeholder="Pilih Tanggal..."
                        @change="applyFilters"
                    />
                </div>

                <div class="relative flex-1 min-w-[200px]">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama atau email pengguna..."
                        class="w-full text-xs font-normal rounded-xl border border-slate-200 pl-8 pr-3 py-2 text-slate-700 placeholder:text-slate-400 focus:border-emerald-500 focus:ring-emerald-500 shadow-xs"
                    />
                </div>

                <div class="w-full sm:w-40">
                    <CustomSelect
                        v-model="prayerType"
                        :options="prayerOptions"
                        placeholder="Semua Sholat"
                        @change="applyFilters"
                    />
                </div>

                <div class="w-full sm:w-48">
                    <CustomSelect
                        v-model="status"
                        :options="statusOptions"
                        placeholder="Semua Status"
                        @change="applyFilters"
                    />
                </div>

                <button
                    type="button"
                    @click="applyFilters"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition"
                >
                    Terapkan
                </button>
            </div>

            <!-- Attendance Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-600">
                                <th class="py-3 px-4">Pengguna</th>
                                <th class="py-3 px-4">Waktu Sholat</th>
                                <th class="py-3 px-4">Jam Server</th>
                                <th class="py-3 px-4">Lokasi GPS</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Approver</th>
                                <th class="py-3 px-4">Catatan</th>
                                <th class="py-3 px-4 text-right">Bukti Foto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="attendances.data.length === 0">
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    Tidak ada catatan absensi yang sesuai.
                                </td>
                            </tr>
                            <tr
                                v-for="a in attendances.data"
                                :key="a.id"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ a.user_name }}</div>
                                    <div class="text-[11px] text-slate-500">{{ a.user_email }}</div>
                                </td>
                                <td class="py-3 px-4 font-semibold text-slate-800">
                                    {{ a.prayer_label }}
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-600 text-[11px]">
                                    {{ a.time }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px]">
                                    <div class="flex items-center gap-1 font-mono">
                                        <MapPin class="w-3 h-3 text-emerald-600 flex-shrink-0" />
                                        <span>{{ Number(a.latitude).toFixed(4) }}, {{ Number(a.longitude).toFixed(4) }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="[
                                            a.status === 'present' || a.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '',
                                            a.status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                                            a.status === 'pending' || a.status === 'pending_approval' ? 'bg-indigo-100 text-indigo-800' : '',
                                            a.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                        ]"
                                    >
                                        <CheckCircle2 v-if="a.is_approver_verified" class="w-3 h-3 text-emerald-600" />
                                        <XCircle v-else-if="a.is_rejected" class="w-3 h-3 text-rose-600" />
                                        <span>{{ a.status_label }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px]">
                                    <span v-if="a.approved_by_name" class="font-medium text-slate-800">
                                        {{ a.approved_by_name }}
                                    </span>
                                    <span v-else-if="a.approval_source === 'system'" class="text-indigo-600 italic">
                                        Sistem Otomatis
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-[11px] max-w-xs truncate">
                                    <span v-if="a.approval_notes" class="text-emerald-700 italic" :title="`Persetujuan: ${a.approval_notes}`">
                                        "{{ a.approval_notes }}"
                                    </span>
                                    <span v-else-if="a.rejection_reason" class="text-rose-700 italic" :title="`Alasan tolak: ${a.rejection_reason}`">
                                        Tolak: "{{ a.rejection_reason }}"
                                    </span>
                                    <span v-else-if="a.request_notes" class="text-amber-800 italic" :title="`Susulan: ${a.request_notes}`">
                                        Susulan: "{{ a.request_notes }}"
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a
                                        v-if="a.has_photo"
                                        :href="a.photo_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-[11px] font-medium transition"
                                    >
                                        <Camera class="w-3 h-3 text-emerald-600" />
                                        <span>Selfie</span>
                                    </a>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="attendances.links && attendances.links.length > 3"
                    class="p-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"
                >
                    <div>Menampilkan {{ attendances.from || 0 }} - {{ attendances.to || 0 }} dari {{ attendances.total }} data</div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in attendances.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs"
                            :class="[
                                link.active ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
