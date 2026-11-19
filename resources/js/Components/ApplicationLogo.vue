<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-2.5 select-none">
        <div class="relative flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-500/20">
            <!-- Elegant Crescent / Minaret abstract silhouette -->
            <svg class="w-6 h-6 fill-none stroke-white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M12 3v3m0 12v3M4.22 4.22l2.12 2.12m11.32 11.32l2.12 2.12M3 12h3m12 0h3M4.22 19.78l2.12-2.12m11.32-11.32l2.12-2.12" opacity="0.4" />
                <path d="M12 7a5 5 0 1 0 5 5 5 5 0 0 0-5-5z" fill="currentColor" fill-opacity="0.2" />
                <path d="m9 12 2 2 4-4" />
            </svg>
        </div>
        <div v-if="!compact" class="flex flex-col">
            <span class="text-xl font-bold tracking-tight text-slate-900 flex items-center">
                SAJA<span class="text-emerald-600">DA</span>
            </span>
            <span class="text-[10px] font-medium text-slate-600 -mt-1 tracking-wider uppercase">
                Prayer Attendance & Tracker
            </span>
        </div>
    </div>
</template>
