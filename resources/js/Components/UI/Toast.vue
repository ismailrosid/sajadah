<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next';

const page = usePage();
const visible = ref(false);
const message = ref('');
const type = ref('success');
let timeoutId = null;

const showToast = (msg, toastType) => {
    message.value = msg;
    type.value = toastType;
    visible.value = true;

    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        visible.value = false;
    }, 4500);
};

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;
        if (flash.success) {
            showToast(flash.success, 'success');
        } else if (flash.error) {
            showToast(flash.error, 'error');
        } else if (flash.warning) {
            showToast(flash.warning, 'warning');
        } else if (flash.info) {
            showToast(flash.info, 'info');
        }
    },
    { deep: true, immediate: true }
);

const close = () => {
    visible.value = false;
    if (timeoutId) clearTimeout(timeoutId);
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="transform -translate-y-4 opacity-0 scale-95"
        enter-to-class="transform translate-y-0 opacity-100 scale-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="transform translate-y-0 opacity-100 scale-100"
        leave-to-class="transform -translate-y-2 opacity-0 scale-95"
    >
        <div
            v-if="visible"
            class="fixed top-20 inset-x-4 sm:inset-x-auto sm:top-6 sm:right-6 sm:w-full sm:max-w-md z-50 pointer-events-none"
        >
            <div
                :class="[
                    'pointer-events-auto flex items-center justify-between p-3.5 sm:p-4 rounded-2xl shadow-xl border backdrop-blur-md transition-all',
                    type === 'success' ? 'bg-emerald-50/95 border-emerald-200 text-emerald-900 shadow-emerald-900/10' : '',
                    type === 'error' ? 'bg-rose-50/95 border-rose-200 text-rose-900 shadow-rose-900/10' : '',
                    type === 'warning' ? 'bg-amber-50/95 border-amber-200 text-amber-900 shadow-amber-900/10' : '',
                    type === 'info' ? 'bg-blue-50/95 border-blue-200 text-blue-900 shadow-blue-900/10' : '',
                ]"
            >
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="flex-shrink-0">
                        <CheckCircle2 v-if="type === 'success'" class="w-5 h-5 text-emerald-600" />
                        <AlertCircle v-else-if="type === 'error'" class="w-5 h-5 text-rose-600" />
                        <AlertTriangle v-else-if="type === 'warning'" class="w-5 h-5 text-amber-600" />
                        <Info v-else class="w-5 h-5 text-blue-600" />
                    </div>
                    <div class="text-xs sm:text-sm font-semibold leading-relaxed break-words">
                        {{ message }}
                    </div>
                </div>
                <button
                    @click="close"
                    class="ml-3 flex-shrink-0 p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-black/5 transition"
                    aria-label="Tutup notifikasi"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </Transition>
</template>
