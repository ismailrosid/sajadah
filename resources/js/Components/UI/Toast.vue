<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';

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
    <div
        v-if="visible"
        class="fixed top-5 right-5 z-50 max-w-sm w-full transition-all duration-300 ease-out transform translate-y-0"
    >
        <div
            :class="[
                'flex items-start p-4 rounded-xl shadow-lg border backdrop-blur-md',
                type === 'success' ? 'bg-emerald-50/95 border-emerald-200 text-emerald-900' : '',
                type === 'error' ? 'bg-rose-50/95 border-rose-200 text-rose-900' : '',
                type === 'info' ? 'bg-blue-50/95 border-blue-200 text-blue-900' : '',
            ]"
        >
            <div class="flex-shrink-0 mt-0.5">
                <CheckCircle2 v-if="type === 'success'" class="w-5 h-5 text-emerald-600" />
                <AlertCircle v-else-if="type === 'error'" class="w-5 h-5 text-rose-600" />
                <Info v-else class="w-5 h-5 text-blue-600" />
            </div>
            <div class="ml-3 flex-1 text-sm font-medium leading-relaxed">
                {{ message }}
            </div>
            <button
                @click="close"
                class="ml-3 flex-shrink-0 p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-black/5"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
