<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { ChevronDown, Check, Search, X } from 'lucide-vue-next';

const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: '',
    },
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Pilih...',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari data...',
    },
    id: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const selectRef = ref(null);
const searchInputRef = ref(null);
const searchQuery = ref('');

const selectedOption = computed(() => {
    return props.options.find((opt) => String(opt.value) === String(props.modelValue)) || null;
});

const isSearchable = computed(() => props.options.length > 5);

const filteredOptions = computed(() => {
    if (!isSearchable.value || !searchQuery.value.trim()) {
        return props.options;
    }
    const q = searchQuery.value.toLowerCase().trim();
    return props.options.filter((opt) => {
        const label = String(opt.label || '').toLowerCase();
        const sublabel = opt.sublabel ? String(opt.sublabel).toLowerCase() : '';
        const value = String(opt.value || '').toLowerCase();
        return label.includes(q) || sublabel.includes(q) || value.includes(q);
    });
});

const toggleDropdown = async () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        searchQuery.value = '';
        if (isSearchable.value) {
            await nextTick();
            searchInputRef.value?.focus();
        }
    }
};

const closeDropdown = () => {
    isOpen.value = false;
    searchQuery.value = '';
};

const selectOption = (opt) => {
    emit('update:modelValue', opt.value);
    emit('change', opt.value);
    closeDropdown();
};

const handleClickOutside = (event) => {
    if (selectRef.value && !selectRef.value.contains(event.target)) {
        closeDropdown();
    }
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        closeDropdown();
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
    <div ref="selectRef" :id="id" class="relative w-full">
        <!-- Trigger Button -->
        <button
            type="button"
            @click="toggleDropdown"
            class="w-full text-left rounded-xl border bg-white py-2 px-3 text-xs font-normal text-slate-700 shadow-xs transition-all flex items-center justify-between gap-2 cursor-pointer focus:outline-none"
            :class="[
                isOpen
                    ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                    : 'border-slate-200 hover:border-emerald-400 hover:bg-slate-50/50'
            ]"
        >
            <div class="truncate flex-1" v-if="selectedOption">
                <span
                    class="block truncate text-xs"
                    :class="selectedOption.value === '' ? 'text-slate-500 font-normal' : 'text-slate-700 font-medium'"
                >
                    {{ selectedOption.label }}
                </span>
                <span v-if="selectedOption.sublabel" class="text-[11px] text-slate-400 block truncate">
                    {{ selectedOption.sublabel }}
                </span>
            </div>
            <span class="text-slate-400 font-normal text-xs" v-else>
                {{ placeholder }}
            </span>

            <ChevronDown
                class="w-4 h-4 text-slate-400 transition-transform duration-200 flex-shrink-0"
                :class="{ 'rotate-180 !text-emerald-600': isOpen }"
            />
        </button>

        <!-- Dropdown Menu Popup (Custom Rounded, Shadowed, Modern with Search) -->
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
                class="absolute left-0 right-0 mt-2 z-50 rounded-2xl bg-white border border-slate-200 shadow-2xl shadow-slate-900/15 overflow-hidden flex flex-col max-h-72"
            >
                <!-- Search Box (Hanya muncul jika opsi lebih dari 5 data) -->
                <div
                    v-if="isSearchable"
                    class="group flex items-center px-3.5 py-2.5 border-b border-slate-100 bg-slate-50/60 focus-within:bg-white focus-within:border-emerald-200 transition-colors gap-2.5"
                >
                    <Search class="w-4 h-4 text-slate-400 group-focus-within:text-emerald-600 transition-colors flex-shrink-0" />
                    <input
                        ref="searchInputRef"
                        v-model="searchQuery"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full bg-transparent border-0 p-0 text-xs text-slate-800 placeholder-slate-400 focus:ring-0 focus:outline-none leading-relaxed"
                        @keydown.stop
                    />
                    <button
                        v-if="searchQuery"
                        type="button"
                        @click="searchQuery = ''"
                        class="text-slate-400 hover:text-slate-600 p-0.5 rounded-full hover:bg-slate-100 transition flex-shrink-0"
                        title="Hapus pencarian"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Options List Container -->
                <div class="p-1.5 space-y-1 overflow-y-auto overscroll-contain flex-1">
                    <button
                        v-for="opt in filteredOptions"
                        :key="opt.value"
                        type="button"
                        @click="selectOption(opt)"
                        class="w-full text-left rounded-xl px-3 py-2 text-xs transition-all flex items-center justify-between gap-3 group cursor-pointer"
                        :class="[
                            String(opt.value) === String(modelValue)
                                ? 'bg-emerald-50 text-emerald-800 font-bold border border-emerald-200/80 shadow-xs'
                                : 'text-slate-700 hover:bg-slate-100/80 hover:text-slate-900'
                        ]"
                    >
                        <div class="truncate flex-1 leading-snug">
                            <span class="block truncate">
                                {{ opt.label }}
                            </span>
                            <span
                                v-if="opt.sublabel"
                                class="block text-[11px] truncate font-normal"
                                :class="String(opt.value) === String(modelValue) ? 'text-emerald-600' : 'text-slate-400'"
                            >
                                {{ opt.sublabel }}
                            </span>
                        </div>

                        <span
                            v-if="String(opt.value) === String(modelValue)"
                            class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs"
                        >
                            <Check class="w-3 h-3 stroke-[3]" />
                        </span>
                    </button>

                    <!-- Empty state if search has no results -->
                    <div v-if="filteredOptions.length === 0" class="py-6 text-center text-xs text-slate-400">
                        <p class="font-semibold text-slate-600">Tidak ada hasil ditemukan</p>
                        <p class="text-[11px] mt-0.5 text-slate-400">Coba kata kunci pencarian yang lain</p>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>
