<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Camera, MapPin, Clock, Users, Shield, ArrowRight, CheckCircle2, Sparkles, UserCheck } from 'lucide-vue-next';

defineProps({
    canLogin: {
        type: Boolean,
        default: true,
    },
    canRegister: {
        type: Boolean,
        default: true,
    },
});
</script>

<template>
    <Head title="SAJADA - Personal Prayer Attendance & Tracker" />

    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans selection:bg-emerald-500 selection:text-white">
        <!-- Top Navbar -->
        <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
                <ApplicationLogo />

                <div class="flex items-center gap-3">
                    <template v-if="$page.props.auth.user">
                        <Link
                            :href="route('dashboard')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
                        >
                            Buka Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-100 transition"
                        >
                            Masuk
                        </Link>
                        <Link
                            :href="route('register')"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs transition"
                        >
                            Daftar Akun
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 py-12 md:py-20 space-y-16">
            <div class="text-center space-y-5 max-w-3xl mx-auto">
                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold">
                    <Sparkles class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Personal Prayer Attendance &amp; Tracker</span>
                </div>

                <h1 class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight leading-tight">
                    Catat dan Rawat Konsistensi Ibadah Sholat Pribadi Anda.
                </h1>

                <p class="text-sm sm:text-base text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Aplikasi absensi dan pelacak sholat personal dengan validasi <strong>Live Camera Selfie</strong>, pencatatan <strong>Lokasi GPS Aktual</strong>, dan <strong>Dynamic Approval System</strong>.
                </p>

                <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link
                        :href="route('login')"
                        class="w-full sm:w-auto px-6 py-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-lg shadow-emerald-600/20 flex items-center justify-center gap-2 transition active:scale-95"
                    >
                        <span>Mulai Sekarang</span>
                        <ArrowRight class="w-4 h-4" />
                    </Link>
                    <a
                        href="#demo-accounts"
                        class="w-full sm:w-auto px-5 py-3.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition"
                    >
                        Lihat Akun Demo Pengujian
                    </a>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <Camera class="w-5 h-5" />
                    </div>
                    <h2 class="font-bold text-slate-900 text-sm">Foto Selfie Kamera Langsung</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Pengambilan foto selfie live langsung melalui kamera web/mobile tanpa upload galeri untuk verifikasi kehadiran.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <MapPin class="w-5 h-5" />
                    </div>
                    <h2 class="font-bold text-slate-900 text-sm">Pencatatan GPS Aktual</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Merekam titik koordinat aktual saat absensi dilakukan dari mana saja lengkap dengan estimasi alamat jalan dan akurasi sensor.
                    </p>
                </div>

                <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <UserCheck class="w-5 h-5" />
                    </div>
                    <h2 class="font-bold text-slate-900 text-sm">Dynamic Approval Workflow</h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Sistem persetujuan berjenjang di mana User dapat ditugaskan menjadi Approver tanpa memerlukan role khusus.
                    </p>
                </div>
            </div>

            <!-- Demo Accounts Card Section -->
            <div id="demo-accounts" class="p-6 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <Shield class="w-4 h-4 text-emerald-600" />
                            Akun Demo Pengujian Sistem
                        </h2>
                        <p class="text-xs text-slate-500">Gunakan akun berikut untuk menguji masing-masing hak akses peran:</p>
                    </div>
                    <span class="text-[11px] font-mono text-slate-400">Password: password</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Admin -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-indigo-600">1. Administrator</div>
                        <div class="text-xs font-bold text-slate-900">superadmin@sholatin.test</div>
                        <p class="text-[11px] text-slate-500">Kelola pengguna, aturan approval, monitoring seluruh data absensi & aplikasi.</p>
                    </div>

                    <!-- User with Approval duties -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-emerald-600">2. User (Approver)</div>
                        <div class="text-xs font-bold text-slate-900">fathur@sholatin.test</div>
                        <p class="text-[11px] text-slate-500">Mencatat sholat personal + menu Approval dinamis untuk memproses absensi user lain.</p>
                    </div>

                    <!-- Regular User -->
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1">
                        <div class="text-[10px] uppercase font-bold text-teal-600">3. User Standar</div>
                        <div class="text-xs font-bold text-slate-900">ahmad@sholatin.test</div>
                        <p class="text-[11px] text-slate-500">Absensi sholat selfie & GPS, jadwal sholat pribadi, riwayat, streak, dan profil.</p>
                    </div>
                </div>

                <div class="pt-2 text-center">
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:text-emerald-700"
                    >
                        <span>Langsung Menuju Halaman Login</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-400">
            <p><strong>SAJADA</strong> &bull; Personal Prayer Attendance &amp; Tracker &bull; &copy; {{ new Date().getFullYear() }}</p>
        </footer>
    </div>
</template>
