<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Clock, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    nextPrayer: {
        type: Object,
        default: null, // { type: 'ashar', label: 'Ashar', time: '15:05', datetime: '...' }
    },
});

const remainingTime = ref({
    hours: '00',
    minutes: '00',
    seconds: '00',
    totalSeconds: 0,
});

let timer = null;

const updateCountdown = () => {
    if (!props.nextPrayer || !props.nextPrayer.datetime) {
        remainingTime.value = { hours: '00', minutes: '00', seconds: '00', totalSeconds: 0 };
        return;
    }

    const target = new Date(props.nextPrayer.datetime).getTime();
    const now = new Date().getTime();
    const diff = Math.max(0, Math.floor((target - now) / 1000));

    const hours = Math.floor(diff / 3600);
    const minutes = Math.floor((diff % 3600) / 60);
    const seconds = diff % 60;

    remainingTime.value = {
        hours: String(hours).padStart(2, '0'),
        minutes: String(minutes).padStart(2, '0'),
        seconds: String(seconds).padStart(2, '0'),
        totalSeconds: diff,
    };
};

onMounted(() => {
    updateCountdown();
    timer = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 text-white p-5 shadow-lg shadow-emerald-900/10">
        <!-- Background subtle islamic pattern decoration -->
        <div class="absolute -right-6 -bottom-8 w-44 h-44 rounded-full bg-white/5 blur-2xl pointer-events-none"></div>

        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-white/10 backdrop-blur-sm">
                    <Clock class="w-4 h-4 text-emerald-300" />
                </span>
                <span class="text-xs font-medium text-emerald-200 uppercase tracking-wider">
                    Menuju Waktu Sholat
                </span>
            </div>
            <span v-if="nextPrayer" class="px-2.5 py-1 rounded-full bg-emerald-500/30 text-emerald-200 text-xs font-semibold border border-emerald-400/30">
                {{ nextPrayer.label }} pukul {{ nextPrayer.time }}
            </span>
        </div>

        <div v-if="nextPrayer" class="flex items-baseline gap-2 my-2">
            <div class="flex items-center gap-1.5">
                <div class="flex flex-col items-center">
                    <span class="text-3xl font-black tracking-tight font-mono">{{ remainingTime.hours }}</span>
                    <span class="text-[10px] text-emerald-200 uppercase">Jam</span>
                </div>
                <span class="text-2xl font-bold opacity-60 pb-3">:</span>
                <div class="flex flex-col items-center">
                    <span class="text-3xl font-black tracking-tight font-mono">{{ remainingTime.minutes }}</span>
                    <span class="text-[10px] text-emerald-200 uppercase">Menit</span>
                </div>
                <span class="text-2xl font-bold opacity-60 pb-3">:</span>
                <div class="flex flex-col items-center">
                    <span class="text-3xl font-black tracking-tight font-mono text-emerald-300">{{ remainingTime.seconds }}</span>
                    <span class="text-[10px] text-emerald-300 uppercase">Detik</span>
                </div>
            </div>
        </div>

        <div v-else class="text-sm text-emerald-100 py-2">
            Belum ada jadwal sholat berikutnya yang aktif.
        </div>
    </div>
</template>
