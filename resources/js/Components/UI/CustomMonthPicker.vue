<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, ChevronDown, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '', // Expected format: "YYYY-MM" (e.g. "2026-11")
    },
    placeholder: {
        type: String,
        default: 'Pilih bulan...',
    },
    id: {
        type: String,
        default: null,
    },
    clearable: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const pickerRef = ref(null);

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const shortMonthNames = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
];

// Current viewed year in the picker
const now = new Date();
const viewYear = ref(now.getFullYear());

const initViewYear = () => {
    if (props.modelValue) {
        const parts = props.modelValue.split('-');
        const y = parseInt(parts[0], 10);
        if (!isNaN(y)) {
            viewYear.value = y;
            return;
        }
    }
    viewYear.value = new Date().getFullYear();
};

watch(() => props.modelValue, () => {
    if (props.modelValue && !isOpen.value) {
        initViewYear();
    }
}, { immediate: true });

// Formatted display text (e.g. "November 2026")
const formattedDisplay = computed(() => {
    if (!props.modelValue) return '';
    const parts = props.modelValue.split('-');
    if (parts.length !== 2) return props.modelValue;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    if (isNaN(year) || isNaN(month) || month < 0 || month > 11) return props.modelValue;
    return `${monthNames[month]} ${year}`;
});

const prevYear = () => {
    viewYear.value--;
};

const nextYear = () => {
    viewYear.value++;
};

const togglePicker = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        initViewYear();
    }
};

const closePicker = () => {
    isOpen.value = false;
};

const isSelectedMonth = (monthIndex) => {
    if (!props.modelValue) return false;
    const parts = props.modelValue.split('-');
    if (parts.length !== 2) return false;
    const y = parseInt(parts[0], 10);
    const m = parseInt(parts[1], 10) - 1;
    return y === viewYear.value && m === monthIndex;
};

const isCurrentActualMonth = (monthIndex) => {
    const d = new Date();
    return d.getFullYear() === viewYear.value && d.getMonth() === monthIndex;
};

const selectMonth = (monthIndex) => {
    const m = String(monthIndex + 1).padStart(2, '0');
    const val = `${viewYear.value}-${m}`;
    emit('update:modelValue', val);
    emit('change', val);
    closePicker();
};

const selectThisMonth = () => {
    const d = new Date();
    viewYear.value = d.getFullYear();
    selectMonth(d.getMonth());
};

const clearValue = (event) => {
    event?.stopPropagation();
    emit('update:modelValue', '');
    emit('change', '');
};

const handleClickOutside = (event) => {
    if (pickerRef.value && !pickerRef.value.contains(event.target)) {
        closePicker();
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closePicker();
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div ref="pickerRef" :id="id" class="relative w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="togglePicker"
            class="w-full text-left rounded-xl border bg-white py-2 px-3 text-xs font-normal text-slate-700 shadow-xs transition-all flex items-center justify-between gap-2 cursor-pointer focus:outline-none"
            :class="[
                isOpen
                    ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                    : 'border-slate-200 hover:border-emerald-400 hover:bg-slate-50/50'
            ]"
        >
            <div class="flex items-center gap-2 truncate flex-1">
                <CalendarIcon class="w-4 h-4 text-slate-400 flex-shrink-0" :class="{ 'text-emerald-600': isOpen || modelValue }" />
                <span v-if="modelValue" class="text-slate-700 font-medium truncate">
                    {{ formattedDisplay }}
                </span>
                <span v-else class="text-slate-400 font-normal truncate">
                    {{ placeholder }}
                </span>
            </div>

            <div class="flex items-center gap-1">
                <button
                    v-if="clearable && modelValue"
                    type="button"
                    @click="clearValue"
                    class="p-0.5 rounded-md text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                    title="Hapus pilihan"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
                <ChevronDown
                    class="w-4 h-4 text-slate-400 transition-transform duration-200 flex-shrink-0"
                    :class="{ 'rotate-180 !text-emerald-600': isOpen }"
                />
            </div>
        </button>

        <!-- Dropdown Popup (Custom Modern Rounded UI) -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="transform scale-95 opacity-0 -translate-y-1"
            enter-to-class="transform scale-100 opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="transform scale-100 opacity-100 translate-y-0"
            leave-to-class="transform scale-95 opacity-0 -translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute left-0 mt-2 z-50 w-72 rounded-2xl bg-white border border-slate-200 shadow-2xl p-4"
            >
                <!-- Year Header Navigation -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <button
                        type="button"
                        @click="prevYear"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition active:scale-95"
                        title="Tahun Sebelumnya"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <span class="text-sm font-bold text-slate-800">
                        {{ viewYear }}
                    </span>
                    <button
                        type="button"
                        @click="nextYear"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-600 hover:text-slate-900 transition active:scale-95"
                        title="Tahun Berikutnya"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <!-- 12 Months Grid (3 columns x 4 rows) -->
                <div class="grid grid-cols-3 gap-2 py-3">
                    <button
                        v-for="(name, idx) in shortMonthNames"
                        :key="idx"
                        type="button"
                        @click="selectMonth(idx)"
                        class="py-2 px-1 text-xs font-semibold rounded-xl transition text-center"
                        :class="[
                            isSelectedMonth(idx)
                                ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/20'
                                : isCurrentActualMonth(idx)
                                ? 'bg-emerald-50 text-emerald-800 border border-emerald-300 font-bold hover:bg-emerald-100'
                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'
                        ]"
                    >
                        {{ name }}
                    </button>
                </div>

                <!-- Footer Action -->
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="selectThisMonth"
                        class="font-semibold text-emerald-600 hover:text-emerald-700 transition"
                    >
                        Bulan Ini
                    </button>
                    <button
                        type="button"
                        @click="closePicker"
                        class="text-slate-400 hover:text-slate-600 transition"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
