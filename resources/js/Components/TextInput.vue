<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-xl border-slate-200 bg-white text-slate-800 placeholder-slate-400 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-xs py-2.5 px-3 transition"
        v-model="model"
        ref="input"
    />
</template>
