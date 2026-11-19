<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import CustomSelect from '@/Components/UI/CustomSelect.vue';
import {
    SlidersHorizontal,
    ArrowLeft,
    Plus,
    Users,
    CheckCircle2,
    XCircle,
    Trash2,
    Loader2,
    X
} from 'lucide-vue-next';

const props = defineProps({
    processes: Array,
    users: Array,
});

// Process Modal
const showProcessModal = ref(false);
const processForm = useForm({
    user_id: '',
    approver_id: '',
    approval_level: 1,
});

// Dropdown options for User
const userOptions = computed(() => {
    return (props.users || []).map((u) => ({
        value: u.id,
        label: u.name,
    }));
});

// Filter approvers to exclude selected user
const eligibleApprovers = computed(() => {
    if (!processForm.user_id) return props.users || [];
    return (props.users || []).filter(u => u.id !== Number(processForm.user_id));
});

const approverOptions = computed(() => {
    return eligibleApprovers.value.map((u) => ({
        value: u.id,
        label: u.name,
    }));
});

const openProcessModal = () => {
    processForm.reset();
    processForm.clearErrors();
    processForm.approval_level = 1;
    showProcessModal.value = true;
};

const submitProcess = () => {
    processForm.post(route('admin.approval-config.processes.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showProcessModal.value = false;
            processForm.reset();
        },
    });
};

const toggleProcessActive = (proc) => {
    const form = useForm({
        approver_id: proc.approver_id,
        approval_level: proc.approval_level,
        is_active: !proc.is_active,
    });
    form.put(route('admin.approval-config.processes.update', proc.id), {
        preserveScroll: true,
    });
};

const deleteProcess = (id) => {
    if (confirm('Hapus alur approval ini?')) {
        const form = useForm({});
        form.delete(route('admin.approval-config.processes.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout title="Konfigurasi Alur Approver" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('admin.approvals.index')"
                        class="p-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm"
                        title="Kembali ke Persetujuan Absensi"
                    >
                        <ArrowLeft class="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                            <SlidersHorizontal class="w-5 h-5 text-emerald-600" />
                            <span>Konfigurasi Alur Persetujuan (Approval Management)</span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Atur Approver yang bertanggung jawab untuk masing-masing pengguna dengan dukungan tingkatan (level).
                        </p>
                    </div>
                </div>

                <!-- Add Button -->
                <div>
                    <button
                        type="button"
                        @click="openProcessModal"
                        class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center gap-1.5 shadow-sm transition active:scale-95"
                    >
                        <Plus class="w-4 h-4" />
                        <span>Tambah Alur Approver</span>
                    </button>
                </div>
            </div>

            <!-- Alur Persetujuan User Table -->
            <div class="space-y-4">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div v-if="processes.length === 0" class="p-12 text-center text-slate-400 text-xs">
                        <Users class="w-10 h-10 mx-auto mb-2 text-slate-300" />
                        <p class="font-bold text-slate-700">Belum Ada Konfigurasi Approver</p>
                        <p class="text-slate-400 mt-1">Klik tombol "Tambah Alur Approver" untuk menetapkan approver bagi user.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="p-4 font-bold">Pengguna (User)</th>
                                    <th class="p-4 font-bold">Approver Yang Ditugaskan</th>
                                    <th class="p-4 font-bold text-center">Level</th>
                                    <th class="p-4 font-bold text-center">Status</th>
                                    <th class="p-4 font-bold text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="proc in processes" :key="proc.id" class="hover:bg-slate-50/80 transition">
                                    <td class="p-4">
                                        <div class="font-bold text-slate-900">{{ proc.user_name }}</div>
                                    </td>
                                    <td class="p-4">
                                        <div class="font-bold text-emerald-800">{{ proc.approver_name }}</div>
                                    </td>
                                    <td class="p-4 text-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Tingkat {{ proc.approval_level }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <button
                                             type="button"
                                            @click="toggleProcessActive(proc)"
                                            class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full transition cursor-pointer"
                                            :class="proc.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500'"
                                            title="Klik untuk ubah status aktif"
                                        >
                                            <CheckCircle2 v-if="proc.is_active" class="w-3.5 h-3.5 text-emerald-600" />
                                            <XCircle v-else class="w-3.5 h-3.5 text-slate-400" />
                                            <span>{{ proc.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button
                                            type="button"
                                            @click="deleteProcess(proc.id)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer"
                                            title="Hapus Konfigurasi"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Add Process (Tanpa Approval Role) -->
            <Modal :show="showProcessModal" @close="showProcessModal = false">
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">Konfigurasi Alur Approver Baru</h3>
                        <button type="button" @click="showProcessModal = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitProcess" class="space-y-4 text-xs">
                        <!-- User -->
                        <div>
                            <InputLabel for="user_id" value="Pilih Pengguna (User)" class="text-xs font-bold text-slate-700 mb-1" />
                            <CustomSelect
                                id="user_id"
                                v-model="processForm.user_id"
                                :options="userOptions"
                                placeholder="-- Pilih Pengguna --"
                                search-placeholder="Cari nama pengguna..."
                            />
                            <InputError class="mt-1" :message="processForm.errors.user_id" />
                        </div>

                        <!-- Approver -->
                        <div>
                            <InputLabel for="approver_id" value="Pilih Approver Yang Ditugaskan" class="text-xs font-bold text-slate-700 mb-1" />
                            <CustomSelect
                                id="approver_id"
                                v-model="processForm.approver_id"
                                :options="approverOptions"
                                placeholder="-- Pilih Approver --"
                                search-placeholder="Cari nama approver..."
                            />
                            <p class="text-[11px] text-slate-400 mt-1">User tidak dapat menjadi approver untuk dirinya sendiri.</p>
                            <InputError class="mt-1" :message="processForm.errors.approver_id" />
                        </div>

                        <!-- Level -->
                        <div>
                            <InputLabel for="approval_level" value="Tingkat Persetujuan (Level)" class="text-xs font-bold text-slate-700 mb-1" />
                            <input
                                id="approval_level"
                                type="number"
                                min="1"
                                max="10"
                                v-model="processForm.approval_level"
                                required
                                class="block w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500 font-mono font-bold"
                            />
                            <p class="text-[11px] text-slate-400 mt-1">Level 1 = approver pertama, Level 2 = approver bertahap berikutnya.</p>
                            <InputError class="mt-1" :message="processForm.errors.approval_level" />
                        </div>

                        <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showProcessModal = false"
                                class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="processForm.processing"
                                class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-sm active:scale-95 disabled:opacity-50 cursor-pointer"
                            >
                                <Loader2 v-if="processForm.processing" class="w-4 h-4 animate-spin" />
                                <span>Simpan Konfigurasi</span>
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </AppLayout>
</template>
