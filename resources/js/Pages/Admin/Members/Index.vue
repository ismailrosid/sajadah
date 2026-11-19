<script setup>
import { ref } from 'vue';
import { router, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Users, UserPlus, Search, Check, X, Trash2, Shield, User, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    members: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters.search || '');
const activeStatus = ref(props.filters.status || '');

const filterByStatus = (status) => {
    activeStatus.value = status;
    router.get(
        route('admin.members.index'),
        {
            status: status || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const handleSearch = () => {
    router.get(
        route('admin.members.index'),
        {
            status: activeStatus.value || undefined,
            search: search.value || undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const approve = (memberId) => {
    router.post(route('admin.members.approve', memberId), {}, { preserveScroll: true });
};

const reject = (memberId) => {
    if (confirm('Tolak pendaftaran anggota ini?')) {
        router.post(route('admin.members.reject', memberId), {}, { preserveScroll: true });
    }
};

const updateRole = (memberId, role) => {
    router.patch(route('admin.members.role', memberId), { role }, { preserveScroll: true });
};

const remove = (memberId, name) => {
    if (confirm(`Hapus anggota ${name}?`)) {
        router.delete(route('admin.members.destroy', memberId), { preserveScroll: true });
    }
};

// Invite Modal
const showInviteModal = ref(false);
const inviteForm = useForm({
    email: '',
    role: 'member',
});

const submitInvite = () => {
    inviteForm.post(route('admin.members.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            showInviteModal.value = false;
            inviteForm.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="Kelola Anggota Komunitas" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kelola Anggota Komunitas</h1>
                    <p class="text-xs text-slate-500">Daftar jamaah, persetujuan anggota baru, dan penugasan peran pengurus</p>
                </div>
                <button
                    type="button"
                    @click="showInviteModal = true"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition active:scale-95"
                >
                    <UserPlus class="w-4 h-4" />
                    <span>Tambah / Undang Anggota</span>
                </button>
            </div>

            <!-- Status Tabs & Search -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-sm">
                <!-- Tabs -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <button
                        type="button"
                        @click="filterByStatus('')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="!activeStatus ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Semua ({{ counts.total }})
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('active')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="activeStatus === 'active' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Aktif ({{ counts.active }})
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('pending')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition flex items-center gap-1.5"
                        :class="activeStatus === 'pending' ? 'bg-amber-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        <span>Menunggu</span>
                        <span v-if="counts.pending > 0" class="px-1.5 py-0.2 rounded-full text-[10px] bg-amber-200 text-amber-900 font-bold">
                            {{ counts.pending }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="filterByStatus('rejected')"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="activeStatus === 'rejected' ? 'bg-rose-600 text-white' : 'text-slate-600 hover:bg-slate-100'"
                    >
                        Ditolak
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                    <input
                        type="text"
                        v-model="search"
                        @keyup.enter="handleSearch"
                        placeholder="Cari nama / email..."
                        class="w-full text-xs pl-9 pr-3 py-1.5 rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>
            </div>

            <!-- Members Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div v-if="members.data.length === 0" class="p-12 text-center text-slate-400 text-xs">
                    Tidak ada data anggota dengan filter ini.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="p-4">Anggota</th>
                                <th class="p-4">Kontak</th>
                                <th class="p-4">Peran</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Bergabung</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="m in members.data" :key="m.id" class="hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs">
                                            {{ m.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ m.name }}</div>
                                            <div class="text-[11px] text-slate-400">{{ m.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-slate-600">
                                    {{ m.phone || '-' }}
                                </td>
                                <td class="p-4">
                                    <select
                                        :value="m.role"
                                        @change="updateRole(m.id, $event.target.value)"
                                        class="text-xs py-1 px-2 rounded-lg border-slate-200 font-medium text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"
                                    >
                                        <option value="member">Member</option>
                                        <option value="community_admin">Admin Komunitas</option>
                                    </select>
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                        :class="[
                                            m.status === 'active' ? 'bg-emerald-100 text-emerald-800' : '',
                                            m.status === 'pending' ? 'bg-amber-100 text-amber-800' : '',
                                            m.status === 'rejected' ? 'bg-rose-100 text-rose-800' : '',
                                            m.status === 'inactive' ? 'bg-slate-100 text-slate-600' : ''
                                        ]"
                                    >
                                        {{ m.status_label }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-400 font-mono text-[11px]">
                                    {{ m.joined_at || m.created_at }}
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Approve / Reject actions for Pending -->
                                        <template v-if="m.status === 'pending'">
                                            <button
                                                type="button"
                                                @click="approve(m.id)"
                                                title="Setujui Keanggotaan"
                                                class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition"
                                            >
                                                <Check class="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="reject(m.id)"
                                                title="Tolak Keanggotaan"
                                                class="p-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 transition"
                                            >
                                                <X class="w-4 h-4" />
                                            </button>
                                        </template>

                                        <!-- Remove member button -->
                                        <button
                                            type="button"
                                            @click="remove(m.id, m.name)"
                                            title="Hapus dari Komunitas"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="members.links && members.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Total {{ members.total }} anggota
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in members.links"
                            :key="i"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1 text-xs rounded-md border"
                            :class="[
                                link.active ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                !link.url ? 'opacity-40 cursor-not-allowed' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- Invite Member Modal -->
            <Modal :show="showInviteModal" @close="showInviteModal = false">
                <form @submit.prevent="submitInvite" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">Undang / Tambah Anggota</h3>
                        <button type="button" @click="showInviteModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Email Anggota</label>
                        <input
                            type="email"
                            v-model="inviteForm.email"
                            required
                            placeholder="nama@email.com"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Peran dalam Komunitas</label>
                        <select
                            v-model="inviteForm.role"
                            class="w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        >
                            <option value="member">Member (Jamaah)</option>
                            <option value="community_admin">Admin Komunitas (Pengurus)</option>
                        </select>
                    </div>

                    <div class="pt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showInviteModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="inviteForm.processing"
                            class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm flex items-center gap-1.5"
                        >
                            <Loader2 v-if="inviteForm.processing" class="w-4 h-4 animate-spin" />
                            <span>Kirim Undangan</span>
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    </AppLayout>
</template>
