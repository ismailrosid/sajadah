<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrayerCountdown from "@/Components/Dashboard/PrayerCountdown.vue";
import {
    Flame,
    CheckCircle2,
    Clock,
    Camera,
    ChevronRight,
    MapPin,
    Sparkles,
    AlertCircle,
    ArrowRight,
    XCircle,
} from "lucide-vue-next";

const props = defineProps({
    user: Object,
    todayAttendancesCount: Number,
    prayers: Array,
    nextPrayer: Object,
    activeOpenWindow: Object,
    durationMinutes: Number,
    weeklyStats: Object,
    streak: Number,
    serverTime: String,
});

// Real-time window closing countdown
const windowSecondsRemaining = ref(
    Math.floor(Number(props.activeOpenWindow?.remaining_window_seconds) || 0),
);
let windowTimer = null;

const windowCountdownFormatted = computed(() => {
    const s = Math.floor(Math.max(0, Number(windowSecondsRemaining.value) || 0));
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = Math.floor(s % 60);
    if (h > 0) {
        return `${String(h).padStart(2, "0")}:${String(m).padStart(2, "0")}:${String(sec).padStart(2, "0")}`;
    }
    return `${String(m).padStart(2, "0")}:${String(sec).padStart(2, "0")}`;
});

onMounted(() => {
    if (props.activeOpenWindow && windowSecondsRemaining.value > 0) {
        windowTimer = setInterval(() => {
            if (windowSecondsRemaining.value > 0) {
                windowSecondsRemaining.value--;
            } else {
                clearInterval(windowTimer);
            }
        }, 1000);
    }
});

onUnmounted(() => {
    if (windowTimer) clearInterval(windowTimer);
});
</script>

<template>
    <AppLayout title="Personal Prayer Tracker">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 space-y-6">
            <!-- Header Greeting & Personal Location -->
            <div>
                <h1 class="text-xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                    Assalamu'alaikum, {{ $page.props.auth.user.name }}
                    <Sparkles class="w-4 h-4 text-amber-500 fill-amber-500 flex-shrink-0" />
                </h1>
                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1.5">
                    <MapPin class="w-3.5 h-3.5 text-emerald-600 flex-shrink-0" />
                    <span>{{ user?.city || 'Jakarta' }} ({{ user?.timezone || 'Asia/Jakarta' }})</span>
                    <span class="text-slate-300">&bull;</span>
                    <Link :href="route('profile.edit')" class="text-emerald-600 hover:underline">
                        Ubah Lokasi
                    </Link>
                </p>
            </div>

            <!-- Active Open Window Closing Banner (When prayer window is currently open) -->
            <div
                v-if="activeOpenWindow && windowSecondsRemaining > 0"
                class="relative rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-5 shadow-lg shadow-emerald-700/15 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div class="flex items-start sm:items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-white/15 backdrop-blur-sm flex items-center justify-center flex-shrink-0 border border-white/20">
                        <Clock class="w-6 h-6 text-white animate-pulse" />
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/30 text-emerald-100 text-[11px] font-bold border border-emerald-300/30 mb-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span>
                            Periode Absensi Terbuka
                        </div>
                        <h2 class="text-base font-bold text-white">
                            Sholat {{ activeOpenWindow.label }} ({{ activeOpenWindow.time }})
                        </h2>
                        <p class="text-xs text-emerald-100">
                            Buka: {{ activeOpenWindow.window_start }} s/d {{ activeOpenWindow.window_end }} &bull; Durasi {{ durationMinutes || 60 }} menit
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between sm:justify-end gap-3 pt-2 sm:pt-0 border-t sm:border-t-0 border-white/10">
                    <div class="text-left sm:text-right">
                        <div class="text-[10px] uppercase font-semibold text-emerald-200">
                            Ditutup Dalam
                        </div>
                        <div class="font-mono text-xl sm:text-2xl font-black tracking-tight text-white">
                            {{ windowCountdownFormatted }}
                        </div>
                    </div>
                    <Link
                        :href="route('member.attendance.create', { prayer: activeOpenWindow.type })"
                        class="group relative p-2.5 sm:px-4 sm:py-2 rounded-xl bg-white hover:bg-emerald-50 text-emerald-800 text-xs font-bold shadow-md hover:shadow-lg hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-1.5 flex-shrink-0"
                    >
                        <Camera class="w-4 h-4 transition-transform duration-200 group-hover:scale-110" />
                        <span class="hidden sm:inline">Absen Sekarang</span>
                        <ArrowRight class="w-3.5 h-3.5 hidden sm:inline" />

                        <!-- Tooltip Badge on Mobile Hover -->
                        <span
                            class="sm:hidden pointer-events-none absolute bottom-full mb-2 right-0 opacity-0 group-hover:opacity-100 transition-all duration-200 bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-xl whitespace-nowrap z-50 border border-slate-700"
                        >
                            Absen Sekarang
                        </span>
                    </Link>
                </div>
            </div>

            <!-- Next Prayer Countdown Banner -->
            <PrayerCountdown :next-prayer="nextPrayer" />

            <!-- Highlight Metric Cards: Streak & Attendance Summary -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <!-- Streak Card -->
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-200 flex-shrink-0">
                        <Flame class="w-6 h-6 fill-amber-500 text-amber-500 animate-pulse" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 leading-none">
                            {{ streak }}
                            <span class="text-xs font-medium text-slate-500">Hari</span>
                        </div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mt-1">
                            Prayer Streak
                        </div>
                    </div>
                </div>

                <!-- Today Total Card -->
                <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-200 flex-shrink-0">
                        <CheckCircle2 class="w-6 h-6 text-emerald-600" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900 leading-none">
                            {{ todayAttendancesCount }}<span class="text-xs font-normal text-slate-400">/5</span>
                        </div>
                        <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider mt-1">
                            Sholat Hari Ini
                        </div>
                    </div>
                </div>

                <!-- Weekly Consistency Card -->
                <div class="col-span-2 sm:col-span-1 p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-medium">Konsistensi 7 Hari</span>
                        <span class="font-bold text-emerald-600">{{ weeklyStats?.percentage }}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 my-2 overflow-hidden">
                        <div
                            class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                            :style="{ width: `${weeklyStats?.percentage}%` }"
                        ></div>
                    </div>
                    <div class="text-[11px] text-slate-400 truncate">
                        {{ weeklyStats?.attended }} dari {{ weeklyStats?.target }} sholat terlaksana
                    </div>
                </div>
            </div>

            <!-- 5 Daily Prayers Status Timeline -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">
                            Jadwal & Status Sholat Hari Ini
                        </h2>
                        <p class="text-xs text-slate-500">
                            Waktu sholat otomatis dihitung dinamis berdasarkan lokasi Anda
                        </p>
                    </div>
                    <span class="text-xs font-mono font-medium text-slate-400">
                        {{
                            new Date().toLocaleDateString("id-ID", {
                                weekday: "long",
                                day: "numeric",
                                month: "short",
                            })
                        }}
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                    <div
                        v-for="prayer in prayers"
                        :key="prayer.type"
                        class="p-3 sm:p-3.5 rounded-xl border transition-all grid grid-cols-12 sm:flex sm:flex-col items-center sm:items-start justify-between gap-2"
                        :class="[
                            prayer.attended && prayer.status !== 'absent'
                                ? 'bg-emerald-50/60 border-emerald-200 text-emerald-950 shadow-sm'
                                : prayer.is_missed
                                ? 'bg-slate-50/80 border-slate-200 text-slate-500'
                                : prayer.is_open
                                ? 'bg-emerald-50/40 border-emerald-300 text-slate-900 shadow-sm'
                                : prayer.is_closed
                                ? 'bg-slate-50/80 border-slate-200 text-slate-500'
                                : 'bg-white border-slate-200 text-slate-700 hover:border-emerald-300',
                        ]"
                    >
                        <!-- Kolom 1: Nama Sholat -->
                        <div class="col-span-4 sm:w-full flex items-center">
                            <span class="text-xs font-bold uppercase tracking-wider">{{ prayer.label }}</span>
                        </div>

                        <!-- Kolom 2: Jam Sholat -->
                        <div class="col-span-3 sm:w-full flex items-center justify-center sm:justify-start">
                            <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-black/5">
                                {{ prayer.time }}
                            </span>
                        </div>

                        <!-- Window schedule detail -->
                        <div class="w-full text-[10px] text-slate-400 hidden sm:block">
                            Buka: {{ prayer.window_start || prayer.time }} - {{ prayer.window_end || "-" }}
                        </div>

                        <!-- Kolom 3: Status / Tombol Aksi -->
                        <div class="col-span-5 sm:w-full flex items-center justify-end sm:justify-start sm:mt-2">
                            <template v-if="prayer.attended && prayer.status !== 'absent'">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold transition-all duration-200 hover:scale-105 cursor-default whitespace-nowrap"
                                    :class="[
                                        prayer.status === 'present' || prayer.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '',
                                        prayer.status === 'pending' || prayer.status === 'pending_approval' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : '',
                                        prayer.status === 'late' ? 'bg-orange-100 text-orange-800' : '',
                                        prayer.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                    ]"
                                    :title="prayer.status_label"
                                >
                                    <Clock v-if="prayer.status === 'pending' || prayer.status === 'pending_approval'" class="w-3 h-3 text-amber-600 animate-pulse shrink-0" />
                                    <XCircle v-else-if="prayer.status === 'rejected'" class="w-3 h-3 text-rose-600 shrink-0" />
                                    <CheckCircle2 v-else class="w-3 h-3 text-emerald-600 shrink-0" />
                                    <span>{{ (prayer.status === 'pending' || prayer.status === 'pending_approval') ? 'Menunggu' : prayer.status_label }}</span>
                                </span>
                            </template>

                            <template v-else-if="prayer.is_open">
                                <Link
                                    :href="route('member.attendance.create', { prayer: prayer.type })"
                                    class="group relative inline-flex sm:w-full items-center justify-center gap-1.5 p-2 sm:py-1.5 sm:px-3 rounded-lg text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white shadow-xs hover:shadow-md hover:shadow-emerald-600/30 hover:scale-105 active:scale-95 transition-all duration-200 whitespace-nowrap"
                                >
                                    <Camera class="w-3.5 h-3.5 transition-transform duration-200 group-hover:scale-110" />
                                    <span class="hidden sm:inline">Absen Sekarang</span>

                                    <!-- Floating Tooltip Badge on Mobile Hover -->
                                    <span
                                        class="sm:hidden pointer-events-none absolute -top-8 right-0 opacity-0 group-hover:opacity-100 transition-all duration-200 bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-lg whitespace-nowrap z-30"
                                    >
                                        Absen Sekarang
                                    </span>
                                </Link>
                            </template>

                            <template v-else-if="prayer.can_late">
                                <Link
                                    :href="route('member.attendance.create', { prayer: prayer.type })"
                                    class="group relative inline-flex sm:w-full items-center justify-center gap-1.5 p-2 sm:py-1.5 sm:px-3 rounded-lg text-xs font-semibold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 hover:border-amber-400 hover:shadow-xs hover:scale-105 active:scale-95 transition-all duration-200 whitespace-nowrap shadow-2xs"
                                >
                                    <Clock class="w-3.5 h-3.5 text-amber-600 transition-transform duration-200 group-hover:scale-110" />
                                    <span class="hidden sm:inline">Susulan</span>

                                    <!-- Floating Tooltip Badge on Mobile Hover -->
                                    <span
                                        class="sm:hidden pointer-events-none absolute -top-8 right-0 opacity-0 group-hover:opacity-100 transition-all duration-200 bg-slate-900 text-white text-[10px] font-bold px-2 py-0.5 rounded-md shadow-lg whitespace-nowrap z-30"
                                    >
                                        Absen Susulan
                                    </span>
                                </Link>
                            </template>

                            <template v-else-if="prayer.is_before_registration">
                                <span class="text-xs font-semibold text-slate-300 select-none">
                                    -
                                </span>
                            </template>

                            <template v-else-if="prayer.is_missed">
                                <span class="text-[11px] font-semibold text-rose-500 select-none">
                                    Tidak Sholat
                                </span>
                            </template>

                            <template v-else>
                                <span class="text-[11px] font-medium text-slate-400 italic">
                                    Belum Masuk
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mini Weekly History Strip -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Tren Kehadiran Pekan Ini</h3>
                        <p class="text-xs text-slate-400">Total sholat wajib yang terlaksana per hari</p>
                    </div>
                    <Link
                        :href="route('member.history.index')"
                        class="group relative text-xs font-semibold text-emerald-600 hover:text-emerald-700 flex items-center gap-1 p-1.5 sm:p-0 rounded-lg hover:bg-emerald-50 hover:scale-105 active:scale-95 transition-all duration-200"
                    >
                        <span class="hidden sm:inline">Lihat Semua</span>
                        <ChevronRight class="w-4 h-4 text-emerald-600 transition-transform duration-200 group-hover:translate-x-0.5" />

                        <!-- Styled Floating Badge Tooltip on Mobile Hover -->
                        <span
                            class="sm:hidden pointer-events-none absolute bottom-full mb-2 right-0 opacity-0 group-hover:opacity-100 transition-all duration-200 bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-xl whitespace-nowrap z-50 border border-slate-700"
                        >
                            Lihat Semua
                        </span>
                    </Link>
                </div>

                <div class="grid grid-cols-7 gap-2 pt-2">
                    <div
                        v-for="day in weeklyStats?.trend"
                        :key="day.date"
                        class="flex flex-col items-center p-2 rounded-xl bg-slate-50 border border-slate-100 text-center"
                    >
                        <span class="text-[11px] font-medium text-slate-400">{{ day.short }}</span>
                        <span class="text-base font-bold my-1" :class="day.count > 0 ? 'text-emerald-600' : 'text-slate-300'">
                            {{ day.count }}
                        </span>
                        <div class="flex gap-0.5">
                            <span
                                v-for="dot in 5"
                                :key="dot"
                                class="w-1 h-1 rounded-full"
                                :class="dot <= day.count ? 'bg-emerald-500' : 'bg-slate-200'"
                            ></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
