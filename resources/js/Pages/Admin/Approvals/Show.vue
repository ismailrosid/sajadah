<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import MapViewer from '@/Components/Maps/MapViewer.vue';
import Modal from '@/Components/Modal.vue';
import {
    ArrowLeft,
    CheckCircle2,
    XCircle,
    Clock,
    MapPin,
    AlertTriangle,
    Calendar,
    User,
    Shield,
    Camera,
    ZoomIn,
    X,
    Loader2,
    Bot,
    UserCheck,
    FileText
} from 'lucide-vue-next';

const props = defineProps({
    attendance: Object,
    isAdminMode: {
        type: Boolean,
        default: false,
    },
});

// Photo Lightbox Modal
const showPhotoModal = ref(false);

// Approve Modal
const showApproveModal = ref(false);
const approvalNotes = ref('');
const isApproving = ref(false);

const confirmApprove = () => {
    isApproving.value = true;
    router.post(
        route(props.isAdminMode ? 'admin.attendances.approve' : 'approvals.approve', props.attendance.id),
        { approval_notes: approvalNotes.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isApproving.value = false;
                showApproveModal.value = false;
            },
        }
    );
};

// Reject Modal
const showRejectModal = ref(false);
const rejectionReason = ref('');
const isRejecting = ref(false);
const rejectionError = ref('');

const confirmReject = () => {
    if (!rejectionReason.value || !rejectionReason.value.trim()) {
        rejectionError.value = 'Alasan penolakan wajib diisi.';
        return;
    }
    rejectionError.value = '';
    isRejecting.value = true;
    router.post(
        route(props.isAdminMode ? 'admin.attendances.reject' : 'approvals.reject', props.attendance.id),
        { rejection_reason: rejectionReason.value },
        {
            preserveScroll: true,
            onFinish: () => {
                isRejecting.value = false;
                showRejectModal.value = false;
            },
        }
    );
};
</script>

<template>
    <AppLayout title="Verifikasi Bukti Absensi" :is-admin="isAdminMode">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header & Navigation -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route(isAdminMode ? 'admin.approvals.daily' : 'approvals.daily', attendance.attendance_info.date_raw)"
                        class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm"
                        title="Kembali ke Daftar Absensi"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                            <h1 class="text-base sm:text-xl font-bold text-slate-900 tracking-tight">
                                Verifikasi Absensi Sholat {{ attendance.attendance_info.prayer_label }}
                            </h1>
                            <span
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold whitespace-nowrap shrink-0"
                                :class="[
                                    attendance.status === 'approved' ? 'bg-emerald-100 text-emerald-800' : '',
                                    attendance.status === 'pending' ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-200' : '',
                                    attendance.status === 'rejected' ? 'bg-rose-100 text-rose-800' : ''
                                ]"
                            >
                                <CheckCircle2 v-if="attendance.status === 'approved'" class="w-3 h-3 text-emerald-600 shrink-0" />
                                <Clock v-else-if="attendance.status === 'pending'" class="w-3 h-3 text-amber-600 shrink-0" />
                                <XCircle v-else class="w-3 h-3 text-rose-600 shrink-0" />
                                <span>{{ attendance.status_label }}</span>
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            {{ attendance.user.name }} - {{ attendance.attendance_info.date }} pada pukul {{ attendance.attendance_info.attendance_time }}
                        </p>
                    </div>
                </div>

                <!-- Approval Actions (Top Bar) -->
                <div v-if="attendance.can_approve && attendance.status === 'pending'" class="grid grid-cols-2 gap-2.5 w-full sm:w-auto sm:flex sm:items-center">
                    <button
                        type="button"
                        @click="showRejectModal = true"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition flex items-center justify-center gap-1.5 active:scale-95"
                    >
                        <XCircle class="w-4 h-4 text-rose-600 shrink-0" />
                        <span>Reject</span>
                    </button>
                    <button
                        type="button"
                        @click="showApproveModal = true"
                        class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20 active:scale-95"
                    >
                        <CheckCircle2 class="w-4 h-4 shrink-0" />
                        <span>Approve</span>
                    </button>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column (Visual Verification Evidence): Photo & GPS Map (7 cols) -->
                <div class="lg:col-span-7 space-y-6">
                    <!-- Bukti Selfie Card -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                                <Camera class="w-4 h-4 text-emerald-600" />
                                <span>Bukti Foto Selfie Langsung</span>
                            </h2>
                        </div>

                        <div v-if="attendance.photo.has_photo" class="relative group rounded-xl overflow-hidden border border-slate-200 bg-slate-900/5 max-h-[380px] flex items-center justify-center">
                            <img
                                :src="attendance.photo.url"
                                alt="Foto Selfie Absensi"
                                class="w-full max-h-[380px] object-contain rounded-xl cursor-pointer hover:opacity-95 transition"
                                @click="showPhotoModal = true"
                            />
                            <button
                                type="button"
                                @click="showPhotoModal = true"
                                class="absolute bottom-3 right-3 px-3 py-1.5 rounded-lg bg-black/70 hover:bg-black/80 text-white text-xs font-semibold flex items-center gap-1.5 backdrop-blur-sm transition"
                            >
                                <ZoomIn class="w-3.5 h-3.5" />
                                <span>Perbesar Foto</span>
                            </button>
                        </div>
                        <div v-else class="p-8 text-center text-slate-400 text-xs border border-dashed border-slate-200 rounded-xl">
                            Foto selfie tidak tersedia atau gagal dimuat.
                        </div>
                    </div>

                    <!-- Informasi Lokasi GPS & Peta -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-600 flex items-center gap-2">
                                <MapPin class="w-4 h-4 text-emerald-600" />
                                <span>Lokasi GPS Aktual Absensi</span>
                            </h2>
                        </div>

                        <!-- Address Info (Gojek-style street name) -->
                        <div v-if="attendance.gps.location_name || attendance.gps.address" class="p-3 rounded-xl bg-slate-50 border border-slate-200/80">
                            <div class="text-xs font-bold text-slate-900">
                                {{ attendance.gps.location_name || 'Alamat Terdeteksi' }}
                            </div>
                            <div v-if="attendance.gps.address" class="text-[11px] text-slate-500 mt-0.5 leading-relaxed">
                                {{ attendance.gps.address }}
                            </div>
                        </div>

                        <!-- Map -->
                        <div class="rounded-xl overflow-hidden border border-slate-200">
                            <MapViewer
                                :user-lat="attendance.gps.latitude"
                                :user-lng="attendance.gps.longitude"
                                :gps-accuracy="attendance.gps.accuracy"
                                :height="260"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox Photo Zoom Modal -->
            <Modal :show="showPhotoModal" @close="showPhotoModal = false">
                <div class="p-4 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h3 class="text-sm font-bold text-slate-900">Foto Selfie Bukti Absensi Asli</h3>
                        <button type="button" @click="showPhotoModal = false" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="flex justify-center bg-black/5 rounded-xl p-2">
                        <img
                            :src="attendance.photo.url"
                            alt="Bukti Selfie Zoom"
                            class="max-h-[75vh] w-auto object-contain rounded-lg shadow-lg"
                        />
                    </div>
                </div>
            </Modal>

            <!-- Approve Confirmation Modal -->
            <Modal :show="showApproveModal" @close="showApproveModal = false">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <CheckCircle2 class="w-5 h-5 text-emerald-600" />
                            <span>Konfirmasi Persetujuan Absensi</span>
                        </h3>
                        <button type="button" @click="showApproveModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="text-xs text-slate-600 leading-relaxed">
                        Anda akan menyetujui absensi Sholat <strong>{{ attendance.attendance_info.prayer_label }}</strong> milik <strong>{{ attendance.user.name }}</strong> pada tanggal <strong>{{ attendance.attendance_info.date }}</strong>.
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Catatan Persetujuan</label>
                        <input
                            type="text"
                            v-model="approvalNotes"
                            placeholder="Contoh: Bukti foto dan lokasi telah diverifikasi."
                            class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showApproveModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmApprove"
                            :disabled="isApproving"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm active:scale-95 disabled:opacity-50"
                        >
                            <Loader2 v-if="isApproving" class="w-4 h-4 animate-spin" />
                            <span>{{ isApproving ? 'Memproses...' : 'Setujui Sekarang' }}</span>
                        </button>
                    </div>
                </div>
            </Modal>

            <!-- Reject Reason Modal -->
            <Modal :show="showRejectModal" @close="showRejectModal = false">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <XCircle class="w-5 h-5 text-rose-600" />
                            <span>Tolak Permohonan Absensi</span>
                        </h3>
                        <button type="button" @click="showRejectModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="text-xs text-slate-600 leading-relaxed">
                        Menolak absensi Sholat <strong>{{ attendance.attendance_info.prayer_label }}</strong> milik <strong>{{ attendance.user.name }}</strong>.
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">
                            Alasan Penolakan <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="rejectionReason"
                            rows="3"
                            placeholder="Tuliskan alasan penolakan secara jelas agar diketahui oleh user..."
                            class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-rose-500 focus:ring-rose-500"
                        ></textarea>
                        <p v-if="rejectionError" class="text-xs text-rose-600 font-medium">{{ rejectionError }}</p>
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showRejectModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmReject"
                            :disabled="isRejecting"
                            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm active:scale-95 disabled:opacity-50"
                        >
                            <Loader2 v-if="isRejecting" class="w-4 h-4 animate-spin" />
                            <span>{{ isRejecting ? 'Memproses...' : 'Konfirmasi Tolak' }}</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
