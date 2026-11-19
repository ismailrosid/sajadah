<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Users, Clock, CheckCircle2, AlertTriangle, Eye, ArrowRight, ClipboardCheck, Check, X, Shield, UserCheck } from 'lucide-vue-next';

defineProps({
    metrics: Object,
    pendingRequests: Array,
    prayerBreakdown: Array,
    recentAttendances: Array,
});

const approve = (id) => {
    router.post(route('admin.attendances.approve', id), {}, { preserveScroll: true });
};

const reject = (id) => {
    const reason = prompt('Masukkan alasan penolakan:');
    if (reason !== null && reason.trim() !== '') {
        router.post(route('admin.attendances.reject', id), { reason }, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Dashboard Admin" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Dashboard Utama Administrator</h1>
                    <p class="text-xs text-slate-500">Monitoring kehadiran sholat personal, verifikasi approver, dan status pengguna</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="metrics.today_pending > 0"
                        :href="route('admin.approvals.index')"
                        class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                    >
                        <ClipboardCheck class="w-4 h-4" />
                        <span>{{ metrics.today_pending }} Perlu Persetujuan</span>
                    </Link>

                    <Link
                        :href="route('admin.attendances.index')"
                        class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-700 transition"
                    >
                        Monitoring Absensi
                    </Link>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5">
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider">Total Pengguna</span>
                        <Users class="w-4 h-4 text-slate-400" />
                    </div>
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ metrics.total_users }}</div>
                    <div class="text-[11px] text-slate-400 mt-2">Terdaftar di aplikasi</div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider">Total Approver</span>
                        <UserCheck class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="text-2xl font-black text-emerald-700 leading-none">{{ metrics.total_approvers }}</div>
                    <div class="text-[11px] text-emerald-600 mt-2">Memiliki tugas aktif</div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider">Absen Hari Ini</span>
                        <CheckCircle2 class="w-4 h-4 text-teal-500" />
                    </div>
                    <div class="text-2xl font-black text-slate-900 leading-none">{{ metrics.today_total }}</div>
                    <div class="text-[11px] text-teal-600 font-semibold mt-2">
                        {{ metrics.ontime_percentage }}% tepat waktu
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider">Tepat Waktu</span>
                        <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                    </div>
                    <div class="text-2xl font-black text-emerald-600 leading-none">{{ metrics.today_present }}</div>
                    <div class="text-[11px] text-slate-400 mt-2">Dalam jendela normal</div>
                </div>

                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs">
                    <div class="flex items-center justify-between text-slate-500 mb-2">
                        <span class="text-[11px] font-semibold uppercase tracking-wider">Menunggu Approval</span>
                        <ClipboardCheck class="w-4 h-4 text-amber-500" />
                    </div>
                    <div class="text-2xl font-black text-amber-600 leading-none">{{ metrics.today_pending }}</div>
                    <div class="text-[11px] text-amber-700 mt-2">Memerlukan verifikasi</div>
                </div>
            </div>

            <!-- Pending Late Attendance Requests Banner if any -->
            <div v-if="pendingRequests.length > 0" class="p-5 rounded-2xl bg-amber-50/70 border border-amber-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <ClipboardCheck class="w-5 h-5 text-amber-600" />
                        <h2 class="text-sm font-bold text-amber-950">
                            Pengajuan Absensi Menunggu Persetujuan ({{ pendingRequests.length }})
                        </h2>
                    </div>
                    <Link
                        :href="route('admin.approvals.index')"
                        class="text-xs font-semibold text-amber-800 hover:text-amber-950 flex items-center gap-1"
                    >
                        <span>Kelola Semua</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div class="divide-y divide-amber-200/60 bg-white/80 rounded-xl border border-amber-200/70 overflow-hidden">
                    <div
                        v-for="req in pendingRequests"
                        :key="req.id"
                        class="p-3 flex items-center justify-between gap-3 text-xs"
                    >
                        <div>
                            <div class="font-bold text-slate-900">
                                {{ req.user_name }} &bull; Sholat {{ req.prayer_label }} ({{ req.attendance_date }})
                            </div>
                            <div class="text-[11px] text-amber-900 italic mt-0.5">
                                Catatan: "{{ req.request_notes || 'Absensi sholat masuk' }}"
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a
                                v-if="req.has_photo"
                                :href="req.photo_url"
                                target="_blank"
                                class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600"
                                title="Lihat Foto Selfie"
                            >
                                <Eye class="w-3.5 h-3.5" />
                            </a>
                            <button
                                type="button"
                                @click="approve(req.id)"
                                class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] flex items-center gap-1 shadow-xs transition"
                            >
                                <Check class="w-3 h-3" />
                                <span>Setujui</span>
                            </button>
                            <button
                                type="button"
                                @click="reject(req.id)"
                                class="px-3 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-semibold text-[11px] flex items-center gap-1 transition"
                            >
                                <X class="w-3 h-3" />
                                <span>Tolak</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance per Prayer Breakdown Progress Bars -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Distribusi Kehadiran Sholat Hari Ini</h2>
                        <p class="text-xs text-slate-500">Jumlah pengguna yang mencatat kehadiran di setiap waktu sholat</p>
                    </div>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full">
                        {{ metrics.total_users }} Pengguna Terdaftar
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-5 gap-3.5">
                    <div v-for="prayer in prayerBreakdown" :key="prayer.type" class="space-y-1.5 p-3.5 rounded-xl bg-slate-50 border border-slate-100">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-bold text-slate-800 uppercase">{{ prayer.label }}</span>
                            <span class="font-mono font-bold text-emerald-600">{{ prayer.count }} user</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div
                                class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                                :style="{ width: `${prayer.percentage}%` }"
                            ></div>
                        </div>
                        <div class="text-[10px] text-slate-400 text-right">
                            {{ prayer.percentage }}% dari total pengguna
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Live Attendance Stream Feed -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Aktivitas Absensi Terbaru</h2>
                        <p class="text-xs text-slate-500">Data live kehadiran pengguna yang masuk ke server</p>
                    </div>
                    <Link
                        :href="route('admin.attendances.index')"
                        class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                    >
                        <span>Lihat Semua Absensi</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>

                <div v-if="recentAttendances.length === 0" class="p-8 text-center text-slate-400 text-xs">
                    Belum ada data absensi yang masuk hari ini.
                </div>

                <div v-else class="divide-y divide-slate-100">
                    <div
                        v-for="a in recentAttendances"
                        :key="a.id"
                        class="py-3 flex items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs"
                                :class="a.status === 'present' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                            >
                                {{ a.user_name.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900">{{ a.user_name }}</div>
                                <div class="text-[11px] text-slate-500">
                                    Sholat {{ a.prayer_label }} &bull; {{ a.date }}
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                :class="[
                                    a.status === 'present' ? 'bg-emerald-100 text-emerald-800' : '',
                                    a.status === 'late' ? 'bg-amber-100 text-amber-800' : '',
                                    a.status === 'pending_approval' ? 'bg-amber-100 text-amber-800' : '',
                                    a.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                ]"
                            >
                                {{ a.status_label }}
                            </span>
                            <span class="font-mono text-slate-400 text-[11px]">{{ a.time }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
