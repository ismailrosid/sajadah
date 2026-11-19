<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { Camera, RefreshCw, CheckCircle, AlertTriangle } from 'lucide-vue-next';

const emit = defineEmits(['captured', 'error', 'reset']);

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const capturedImage = ref(null);
const isStreaming = ref(false);
const errorMessage = ref(null);
const facingMode = ref('user'); // front camera by default

const startCamera = async () => {
    errorMessage.value = null;
    stopCamera();

    try {
        const constraints = {
            video: {
                facingMode: facingMode.value,
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
            audio: false,
        };

        const mediaStream = await navigator.mediaDevices.getUserMedia(constraints);
        stream.value = mediaStream;

        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            videoRef.value.onloadedmetadata = () => {
                videoRef.value.play();
                isStreaming.value = true;
            };
        }
    } catch (err) {
        console.error('Camera access error:', err);
        errorMessage.value = 'Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan di browser Anda.';
        emit('error', errorMessage.value);
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach((track) => track.stop());
        stream.value = null;
    }
    isStreaming.value = false;
};

const switchCamera = () => {
    facingMode.value = facingMode.value === 'user' ? 'environment' : 'user';
    startCamera();
};

const useDummyPhoto = () => {
    const canvas = document.createElement('canvas');
    canvas.width = 640;
    canvas.height = 480;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#0f172a';
    ctx.fillRect(0, 0, 640, 480);
    ctx.fillStyle = '#10b981';
    ctx.font = 'bold 26px sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('Foto Selfie Pengujian', 320, 210);
    ctx.fillStyle = '#94a3b8';
    ctx.font = '18px sans-serif';
    ctx.fillText('Mode Pengetesan Aktif', 320, 250);
    ctx.font = '14px sans-serif';
    ctx.fillText(new Date().toLocaleTimeString('id-ID'), 320, 285);

    canvas.toBlob((blob) => {
        if (!blob) return;
        const file = new File([blob], `selfie_test_${Date.now()}.jpg`, { type: 'image/jpeg' });
        capturedImage.value = URL.createObjectURL(blob);
        stopCamera();
        errorMessage.value = null;
        emit('captured', { file, previewUrl: capturedImage.value });
    }, 'image/jpeg', 0.88);
};

const takePhoto = () => {
    if (!videoRef.value || !canvasRef.value) return;

    const video = videoRef.value;
    const canvas = canvasRef.value;

    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;

    const context = canvas.getContext('2d');
    // Mirror image if front camera
    if (facingMode.value === 'user') {
        context.translate(canvas.width, 0);
        context.scale(-1, 1);
    }
    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob((blob) => {
        if (!blob) return;

        const file = new File([blob], `selfie_${Date.now()}.jpg`, { type: 'image/jpeg' });
        capturedImage.value = URL.createObjectURL(blob);
        stopCamera();
        emit('captured', { file, previewUrl: capturedImage.value });
    }, 'image/jpeg', 0.88);
};

const retakePhoto = () => {
    capturedImage.value = null;
    emit('reset');
    startCamera();
};

onMounted(() => {
    startCamera();
});

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <div class="relative w-full max-w-sm mx-auto overflow-hidden rounded-2xl bg-slate-900 aspect-[3/4] flex items-center justify-center shadow-lg border border-slate-800">
        <!-- Error State -->
        <div v-if="errorMessage" class="p-6 text-center text-rose-300">
            <AlertTriangle class="w-10 h-10 mx-auto mb-3 text-rose-400" />
            <p class="text-sm font-medium mb-4">{{ errorMessage }}</p>
            <div class="flex flex-col gap-2">
                <button
                    type="button"
                    @click="startCamera"
                    class="px-4 py-2 text-xs font-semibold bg-rose-600/30 border border-rose-500/50 rounded-lg hover:bg-rose-600/50 text-white"
                >
                    Coba Lagi
                </button>
                <button
                    type="button"
                    @click="useDummyPhoto"
                    class="px-4 py-2 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition"
                >
                    Gunakan Foto Uji Coba (Dummy)
                </button>
            </div>
        </div>

        <!-- Live Camera Stream -->
        <template v-else-if="!capturedImage">
            <video
                ref="videoRef"
                autoplay
                playsinline
                muted
                class="w-full h-full object-cover transform"
                :class="{ '-scale-x-100': facingMode === 'user' }"
            ></video>

            <!-- Selfie Guide Oval Overlay -->
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-48 h-64 border-2 border-dashed border-white/40 rounded-[50%] shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
            </div>

            <div class="absolute top-3 left-3 bg-black/50 backdrop-blur-md px-3 py-1 rounded-full text-[11px] font-medium text-emerald-300 flex items-center gap-1.5 border border-emerald-500/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Kamera Langsung
            </div>

            <button
                type="button"
                @click="useDummyPhoto"
                title="Gunakan Foto Uji Coba Cepat"
                class="absolute top-3 right-3 bg-black/50 hover:bg-black/70 backdrop-blur-md px-2.5 py-1 rounded-full text-[10px] font-medium text-emerald-300 border border-emerald-500/30 transition flex items-center gap-1"
            >
                ⚡ Foto Uji Coba
            </button>

            <!-- Controls overlay -->
            <div class="absolute bottom-4 inset-x-0 flex items-center justify-around px-6">
                <button
                    type="button"
                    @click="switchCamera"
                    title="Ganti Kamera"
                    class="p-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-full border border-white/20 transition-transform active:scale-95"
                >
                    <RefreshCw class="w-5 h-5" />
                </button>

                <button
                    type="button"
                    @click="takePhoto"
                    title="Ambil Selfie"
                    class="p-1 bg-white rounded-full shadow-xl transition-transform active:scale-90"
                >
                    <div class="w-14 h-14 rounded-full border-4 border-slate-900 bg-emerald-500 hover:bg-emerald-600 flex items-center justify-center text-white">
                        <Camera class="w-7 h-7" />
                    </div>
                </button>

                <div class="w-11"></div> <!-- Spacer to keep capture button centered -->
            </div>
        </template>

        <!-- Captured Freeze Frame Preview -->
        <template v-else>
            <img :src="capturedImage" alt="Selfie Absensi" class="w-full h-full object-cover" />
            
            <div class="absolute top-3 left-3 bg-emerald-600/90 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold text-white flex items-center gap-1.5 shadow">
                <CheckCircle class="w-3.5 h-3.5" />
                Foto Siap Dikirim
            </div>

            <div class="absolute bottom-4 inset-x-0 flex justify-center px-4">
                <button
                    type="button"
                    @click="retakePhoto"
                    class="px-5 py-2.5 bg-slate-900/80 backdrop-blur-md text-white text-xs font-semibold rounded-xl border border-white/20 hover:bg-slate-900 flex items-center gap-2 shadow-lg"
                >
                    <RefreshCw class="w-4 h-4" />
                    Ambil Ulang Foto
                </button>
            </div>
        </template>

        <!-- Hidden canvas for capturing frame -->
        <canvas ref="canvasRef" class="hidden"></canvas>
    </div>
</template>
