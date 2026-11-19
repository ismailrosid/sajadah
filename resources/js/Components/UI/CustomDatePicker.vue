<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Pilih tanggal...',
    },
    id: {
        type: String,
        default: null,
    },
    clearable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const pickerRef = ref(null);

// Current view year & month (0-indexed for month: 0 = Jan, 11 = Dec)
const viewDate = ref(new Date());

const monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

const shortMonthNames = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
];

const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

// Initialize viewDate based on modelValue if provided
const initViewDate = () => {
    if (props.modelValue) {
        const parts = props.modelValue.split('-');
        if (parts.length === 3) {
            const year = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            if (!isNaN(year) && !isNaN(month) && !isNaN(day)) {
                viewDate.value = new Date(year, month, day);
                return;
            }
        }
    }
    viewDate.value = new Date();
};

watch(() => props.modelValue, () => {
    if (props.modelValue && !isOpen.value) {
        initViewDate();
    }
}, { immediate: true });

// Formatted display text for trigger button
const formattedDisplay = computed(() => {
    if (!props.modelValue) return '';
    const parts = props.modelValue.split('-');
    if (parts.length !== 3) return props.modelValue;
    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);
    if (isNaN(year) || isNaN(month) || isNaN(day)) return props.modelValue;
    return `${day} ${shortMonthNames[month] || ''} ${year}`;
});

const viewYear = computed(() => viewDate.value.getFullYear());
const viewMonth = computed(() => viewDate.value.getMonth());

const prevMonth = () => {
    viewDate.value = new Date(viewYear.value, viewMonth.value - 1, 1);
};

const nextMonth = () => {
    viewDate.value = new Date(viewYear.value, viewMonth.value + 1, 1);
};

const todayString = computed(() => {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
});

// Calendar grid days (including trailing days from previous/next months)
const calendarDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value;

    const firstDayOfMonth = new Date(year, month, 1).getDay(); // 0 (Sun) to 6 (Sat)
    const daysInCurrentMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const days = [];

    // 1. Previous month trailing days
    for (let i = firstDayOfMonth - 1; i >= 0; i--) {
        const d = daysInPrevMonth - i;
        const prevMonthIndex = month === 0 ? 11 : month - 1;
        const prevYear = month === 0 ? year - 1 : year;
        const dateStr = `${prevYear}-${String(prevMonthIndex + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            dateString: dateStr,
            isCurrentMonth: false,
        });
    }

    // 2. Current month days
    for (let d = 1; d <= daysInCurrentMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            dateString: dateStr,
            isCurrentMonth: true,
        });
    }

    // 3. Next month leading days (to fill 35 or 42 cells)
    const totalSlots = days.length <= 35 ? 35 : 42;
    const remainingSlots = totalSlots - days.length;
    for (let d = 1; d <= remainingSlots; d++) {
        const nextMonthIndex = month === 11 ? 0 : month + 1;
        const nextYear = month === 11 ? year + 1 : year;
        const dateStr = `${nextYear}-${String(nextMonthIndex + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        days.push({
            day: d,
            dateString: dateStr,
            isCurrentMonth: false,
        });
    }

    return days;
});

const togglePicker = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        initViewDate();
    }
};

const closePicker = () => {
    isOpen.value = false;
};

const selectDate = (dateStr) => {
    emit('update:modelValue', dateStr);
    emit('change', dateStr);
    closePicker();
};

const clearDate = (event) => {
    event?.stopPropagation();
    emit('update:modelValue', '');
    emit('change', '');
};

const selectToday = () => {
    selectDate(todayString.value);
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
            class="w-full text-left rounded-xl border bg-white py-2 px-3 text-xs font-medium text-slate-800 shadow-xs transition-all flex items-center justify-between gap-2 cursor-pointer focus:outline-none"
            :class="[
                isOpen
                    ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                    : 'border-slate-200 hover:border-emerald-400 hover:bg-slate-50/50'
            ]"
        >
            <div class="flex items-center gap-2 truncate flex-1">
                <CalendarIcon class="w-4 h-4 text-slate-400 flex-shrink-0" :class="{ 'text-emerald-600': isOpen || modelValue }" />
                <span v-if="modelValue" class="text-slate-800 font-semibold truncate">
                    {{ formattedDisplay }}
                </span>
                <span v-else class="text-slate-400 font-normal truncate">
                    {{ placeholder }}
                </span>
            </div>

            <div class="flex items-center gap-1">
                <span
                    v-if="clearable && modelValue"
                    @click.stop="clearDate"
                    class="p-0.5 rounded-full hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition"
                    title="Hapus Tanggal"
                >
                    <X class="w-3.5 h-3.5" />
                </span>
            </div>
        </button>

        <!-- Calendar Popover Menu (Modern Rounded-2xl, Elevated Shadow) -->
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
                class="absolute left-0 mt-2 z-50 rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-slate-900/15 p-4 w-72 select-none"
            >
                <!-- Month & Year Navigation Header -->
                <div class="flex items-center justify-between mb-3">
                    <button
                        type="button"
                        @click="prevMonth"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition"
                        title="Bulan Sebelumnya"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <div class="text-xs font-bold text-slate-800">
                        {{ monthNames[viewMonth] }} {{ viewYear }}
                    </div>

                    <button
                        type="button"
                        @click="nextMonth"
                        class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition"
                        title="Bulan Berikutnya"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>

                <!-- Days of Week Header -->
                <div class="grid grid-cols-7 gap-1 text-center mb-1">
                    <span
                        v-for="dayName in dayNames"
                        :key="dayName"
                        class="text-[10px] font-bold text-slate-400 uppercase py-1"
                    >
                        {{ dayName }}
                    </span>
                </div>

                <!-- Days Matrix -->
                <div class="grid grid-cols-7 gap-1">
                    <button
                        v-for="(cell, idx) in calendarDays"
                        :key="idx"
                        type="button"
                        @click="selectDate(cell.dateString)"
                        class="h-8 w-8 mx-auto rounded-xl text-xs flex items-center justify-center transition-all relative font-medium"
                        :class="[
                            cell.dateString === modelValue
                                ? 'bg-emerald-600 text-white font-bold shadow-xs'
                                : cell.dateString === todayString
                                    ? 'bg-emerald-50 text-emerald-700 font-bold border border-emerald-200 hover:bg-emerald-100'
                                    : cell.isCurrentMonth
                                        ? 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'
                                        : 'text-slate-300 hover:bg-slate-50 hover:text-slate-400'
                        ]"
                    >
                        {{ cell.day }}
                    </button>
                </div>

                <!-- Footer Shortcuts -->
                <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-xs">
                    <button
                        type="button"
                        @click="selectToday"
                        class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline transition"
                    >
                        Hari Ini
                    </button>

                    <button
                        v-if="modelValue"
                        type="button"
                        @click="clearDate"
                        class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 transition"
                    >
                        Hapus
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
