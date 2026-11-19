<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import { Sliders, Save, CheckCircle2, Clock, Camera, Shield, Loader2, Compass, MapPin, ClipboardCheck, ArrowUpRight } from 'lucide-vue-next';

const props = defineProps({
    setting: Object,
    calculationMethods: Array,
    timezones: Array,
});

const form = useForm({
    attendance_duration_minutes: props.setting.attendance_duration_minutes || 60,
    prayer_calc_method: props.setting.prayer_calc_method || 'KEMENAG',
    is_attendance_enabled: !!props.setting.is_attendance_enabled,
    require_selfie: !!props.setting.require_selfie,
    require_gps: !!props.setting.require_gps,
    auto_approve_ontime: props.setting.auto_approve_ontime !== undefined ? !!props.setting.auto_approve_ontime : true,
    auto_approve_after_hours: props.setting.auto_approve_after_hours || 24,
});

const submit = () => {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Pengaturan Aplikasi" :is-admin="true">
        <div class="p-6 max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Pengaturan Aplikasi SAJADA</h1>
                    <p class="text-xs text-slate-500">
                        Konfigurasi aturan absensi, durasi periode kehadiran, dan metode perhitungan hisab waktu sholat
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Card 1: Saklar Fitur Utama -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <Shield class="w-4 h-4 text-emerald-600" />
                        Kebijakan & Validasi Absensi
                    </h2>

                    <div class="space-y-3 divide-y divide-slate-100">
                        <!-- Saklar Aktifkan Absensi -->
                        <div class="pt-3 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-800">Status Sistem Absensi Global</div>
                                <div class="text-[11px] text-slate-500">Jika dinonaktifkan, pengguna tidak dapat mengirim absensi baru</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_attendance_enabled" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Wajib Selfie -->
                        <div class="pt-3 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-800">Wajib Foto Selfie Aktual</div>
                                <div class="text-[11px] text-slate-500">Mewajibkan bukti visual kamera depan saat menekan tombol absen</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.require_selfie" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Wajib GPS -->
                        <div class="pt-3 flex items-center justify-between">
                            <div>
                                <div class="text-xs font-bold text-slate-800">Wajib Titik Koordinat GPS</div>
                                <div class="text-[11px] text-slate-500">Mencatat lokasi aktual pengguna ketika melakukan absensi</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.require_gps" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Durasi Periode Absensi Otomatis -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <Clock class="w-4 h-4 text-emerald-600" />
                        Periode Absensi Otomatis (Attendance Window)
                    </h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Periode buka absensi dihitung secara dinamis: dimulai saat waktu sholat tiba, dan ditutup setelah durasi menit di bawah ini terlewati.
                    </p>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">
                            Durasi Standar Periode Absensi (Menit)
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                v-model.number="form.attendance_duration_minutes"
                                type="number"
                                min="10"
                                max="180"
                                class="w-32 text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 font-mono font-bold"
                            />
                            <span class="text-xs text-slate-500">menit sejak adzan / waktu sholat masuk</span>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Kebijakan Approval & Persetujuan Absensi -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <ClipboardCheck class="w-4 h-4 text-emerald-600" />
                            Kebijakan Alur Approval Pengguna
                        </h2>
                        <Link
                            :href="route('admin.approval-config.index')"
                            class="text-[11px] font-bold text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1 transition"
                        >
                            <span>Atur Approver Per User</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <p class="text-xs text-slate-500 leading-relaxed">
                        Menu Approval hanya diberikan kepada User yang memiliki penugasan Approver aktif. Untuk pengguna yang tidak memiliki tugas approver khusus, absensi akan otomatis mengikuti kebijakan sistem admin di bawah ini:
                    </p>

                    <div class="space-y-4 pt-2 divide-y divide-slate-100">
                        <!-- Auto Approve Ontime Setting -->
                        <div class="pt-3 flex items-center justify-between">
                            <div class="pr-4">
                                <div class="text-xs font-bold text-slate-800">Otomatis Setujui Absensi Sesuai Aturan Admin</div>
                                <div class="text-[11px] text-slate-500">
                                    Pengguna yang tidak butuh approval khusus otomatis disetujui hadir jika foto selfie dan koordinat GPS valid sesuai jendela waktu sholat.
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="checkbox" v-model="form.auto_approve_ontime" class="sr-only peer" />
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                            </label>
                        </div>

                        <!-- Auto-Approve After Hours -->
                        <div class="pt-4">
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Batas Waktu Auto-Approval Sistem (Jam)
                            </label>
                            <div class="flex items-center gap-3">
                                <input
                                    v-model.number="form.auto_approve_after_hours"
                                    type="number"
                                    min="1"
                                    max="168"
                                    class="w-32 text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 font-mono font-bold"
                                />
                                <span class="text-xs text-slate-500">jam sebelum pengajuan absensi yang tertunda otomatis disetujui sistem</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Metode Perhitungan Jadwal Sholat -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                        <Compass class="w-4 h-4 text-emerald-600" />
                        Metode Perhitungan Hisab Jadwal Sholat
                    </h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1">
                            Otoritas / Formula Perhitungan Standar
                        </label>
                        <CustomSelect
                            id="prayer_calc_method"
                            v-model="form.prayer_calc_method"
                            :options="calculationMethods"
                            placeholder="Pilih Otoritas Perhitungan"
                            search-placeholder="Cari otoritas metode..."
                        />
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Save v-else class="w-4 h-4" />
                        <span>Simpan Pengaturan</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
