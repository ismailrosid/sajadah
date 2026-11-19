<script setup>
import { ref } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import { Building2, Plus, Edit, Trash2, Users, Landmark, CheckCircle, X, Loader2 } from 'lucide-vue-next';

defineProps({
    communities: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingCommunityId = ref(null);

const form = useForm({
    name: '',
    description: '',
    address: '',
    timezone: 'Asia/Jakarta',
    status: 'active',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingCommunityId.value = null;
    form.reset();
    form.timezone = 'Asia/Jakarta';
    form.status = 'active';
    showModal.value = true;
};

const openEditModal = (c) => {
    isEditing.value = true;
    editingCommunityId.value = c.id;
    form.name = c.name;
    form.description = c.description || '';
    form.address = c.address || '';
    form.timezone = c.timezone;
    form.status = c.status;
    showModal.value = true;
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('superadmin.communities.update', editingCommunityId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('superadmin.communities.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    }
};

const deleteCommunity = (c) => {
    if (confirm(`Yakin ingin menghapus komunitas ${c.name}? Seluruh data anggota, masjid, dan absensi di dalamnya akan dihapus.`)) {
        router.delete(route('superadmin.communities.destroy', c.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout title="Super Admin - Kelola Komunitas" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Super Admin &bull; Seluruh Komunitas</h1>
                    <p class="text-xs text-slate-500">Kelola tenant komunitas dan pantau data global sistem SAJADA</p>
                </div>
                <button
                    type="button"
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm transition active:scale-95"
                >
                    <Plus class="w-4 h-4" />
                    <span>Buat Komunitas Baru</span>
                </button>
            </div>

            <!-- Communities Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div v-if="communities.data.length === 0" class="p-12 text-center text-slate-400 text-xs">
                    Belum ada komunitas terdaftar di sistem.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-100 text-[11px] uppercase tracking-wider text-slate-400">
                            <tr>
                                <th class="p-4">Komunitas</th>
                                <th class="p-4">Zona Waktu</th>
                                <th class="p-4">Anggota</th>
                                <th class="p-4">Masjid</th>
                                <th class="p-4">Total Absensi</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="c in communities.data" :key="c.id" class="hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 font-bold flex items-center justify-center text-xs">
                                            {{ c.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ c.name }}</div>
                                            <div class="text-[11px] text-slate-400 truncate max-w-xs">{{ c.address || c.description || '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 font-mono text-slate-600">
                                    {{ c.timezone }}
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    {{ c.members_count }}
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    {{ c.mosques_count }}
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    {{ c.attendances_count }}
                                </td>
                                <td class="p-4">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-bold"
                                        :class="c.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                    >
                                        {{ c.status === 'active' ? 'Aktif' : 'Ditangguhkan' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditModal(c)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                                            title="Edit Komunitas"
                                        >
                                            <Edit class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteCommunity(c)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                            title="Hapus Komunitas"
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
                <div v-if="communities.links && communities.links.length > 3" class="p-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-xs text-slate-500">
                        Total {{ communities.total }} komunitas
                    </div>
                    <div class="flex gap-1">
                        <Link
                            v-for="(link, i) in communities.links"
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

            <!-- Create / Edit Community Modal -->
            <Modal :show="showModal" @close="showModal = false">
                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-slate-900">
                            {{ isEditing ? 'Edit Data Komunitas' : 'Buat Komunitas Baru' }}
                        </h3>
                        <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Nama Komunitas / Lembaga</label>
                        <input
                            type="text"
                            v-model="form.name"
                            required
                            placeholder="Contoh: DKM Masjid Baiturrahman"
                            class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Deskripsi Singkat</label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Keterangan singkat kegiatan..."
                            class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-bold text-slate-700">Alamat</label>
                        <textarea
                            v-model="form.address"
                            rows="2"
                            placeholder="Alamat kantor / sekretariat..."
                            class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Zona Waktu</label>
                            <select
                                v-model="form.timezone"
                                class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                                <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                                <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                                <option value="UTC">UTC</option>
                            </select>
                        </div>

                        <div v-if="isEditing" class="space-y-1">
                            <label class="text-xs font-bold text-slate-700">Status Operasional</label>
                            <select
                                v-model="form.status"
                                class="w-full text-xs rounded-xl border-slate-200 py-2 px-3 focus:border-emerald-500 focus:ring-emerald-500"
                            >
                                <option value="active">Aktif</option>
                                <option value="suspended">Ditangguhkan (Suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold shadow-sm flex items-center gap-1.5"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <span>{{ isEditing ? 'Simpan Perubahan' : 'Buat Komunitas' }}</span>
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    </AppLayout>
</template>
