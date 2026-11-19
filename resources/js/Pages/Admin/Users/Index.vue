<script setup>
import { ref, watch } from "vue";
import { router, Link } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import CustomSelect from "@/Components/UI/CustomSelect.vue";
import {
    Search,
    Shield,
    User as UserIcon,
    UserCheck,
    Trash2,
    SlidersHorizontal,
    Check,
    X,
    ShieldAlert,
} from "lucide-vue-next";

const props = defineProps({
    users: Object,
    filters: Object,
    counts: Object,
});

const search = ref(props.filters.search || "");
const role = ref(props.filters.role || "");
const approver = ref(props.filters.approver || "");

const roleOptions = [
    { value: "", label: "Semua Peran" },
    { value: "admin", label: "Administrator" },
    { value: "user", label: "User" },
];

const approverOptions = [
    { value: "", label: "Semua Status Approver" },
    { value: "yes", label: "Memiliki Tugas Approver" },
    { value: "no", label: "Bukan Approver" },
];

const applyFilters = () => {
    router.get(
        route("admin.users.index"),
        {
            search: search.value || undefined,
            role: role.value || undefined,
            approver: approver.value || undefined,
        },
        { preserveState: true, replace: true },
    );
};

const toggleRole = (user) => {
    const targetRole = user.role === "admin" ? "user" : "admin";
    const confirmMsg =
        targetRole === "admin"
            ? `Apakah Anda yakin ingin memberikan hak Administrator kepada ${user.name}?`
            : `Apakah Anda yakin ingin menurunkan peran ${user.name} menjadi User biasa?`;

    if (confirm(confirmMsg)) {
        router.patch(
            route("admin.users.role", user.id),
            { role: targetRole },
            { preserveScroll: true },
        );
    }
};

const deleteUser = (user) => {
    if (
        confirm(
            `Hapus akun ${user.name} secara permanen? Data absensi terkait akan terdampak.`,
        )
    ) {
        router.delete(route("admin.users.destroy", user.id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna" :is-admin="true">
        <div class="p-6 max-w-7xl mx-auto space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
            >
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">
                        Manajemen Pengguna
                    </h1>
                    <p class="text-xs text-slate-500">
                        Kelola daftar pengguna aplikasi, penugasan peran admin,
                        dan status approver
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.approval-config.index')"
                        class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                    >
                        <SlidersHorizontal class="w-3.5 h-3.5" />
                        <span>Konfigurasi Approver</span>
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                <div
                    class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs"
                >
                    <div
                        class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        Total Pengguna
                    </div>
                    <div class="text-2xl font-black text-slate-900 mt-1">
                        {{ counts.total }}
                    </div>
                </div>
                <div
                    class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs"
                >
                    <div
                        class="text-[11px] font-semibold text-purple-600 uppercase tracking-wider"
                    >
                        Administrator
                    </div>
                    <div class="text-2xl font-black text-purple-700 mt-1">
                        {{ counts.admins }}
                    </div>
                </div>
                <div
                    class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs"
                >
                    <div
                        class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"
                    >
                        User Standar
                    </div>
                    <div class="text-2xl font-black text-slate-700 mt-1">
                        {{ counts.users }}
                    </div>
                </div>
                <div
                    class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs"
                >
                    <div
                        class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider"
                    >
                        Approver Aktif
                    </div>
                    <div class="text-2xl font-black text-emerald-700 mt-1">
                        {{ counts.approvers }}
                    </div>
                </div>
            </div>

            <!-- Filter Controls -->
            <div
                class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-wrap items-center gap-3"
            >
                <div class="relative flex-1 min-w-[220px]">
                    <Search
                        class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"
                    />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari nama, email, kota domisili..."
                        class="w-full text-xs rounded-xl border-slate-200 pl-9 pr-3 py-2.5 focus:border-emerald-500 focus:ring-emerald-500"
                    />
                </div>

                <div class="w-full sm:w-44">
                    <CustomSelect
                        v-model="role"
                        :options="roleOptions"
                        placeholder="Semua Peran"
                        @change="applyFilters"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <CustomSelect
                        v-model="approver"
                        :options="approverOptions"
                        placeholder="Semua Status Approver"
                        @change="applyFilters"
                    />
                </div>

                <button
                    type="button"
                    @click="applyFilters"
                    class="px-4 py-2.5 rounded-xl bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800 transition"
                >
                    Terapkan
                </button>
            </div>

            <!-- Users Table -->
            <div
                class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr
                                class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-600"
                            >
                                <th class="py-3 px-4">Pengguna</th>
                                <th class="py-3 px-4">Domisili</th>
                                <th class="py-3 px-4">Peran Utama</th>
                                <th class="py-3 px-4">Tugas Approver</th>
                                <th class="py-3 px-4">Approver Pengguna</th>
                                <th class="py-3 px-4">Terdaftar</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="7"
                                    class="py-8 text-center text-slate-400"
                                >
                                    Tidak ada data pengguna yang sesuai dengan
                                    filter.
                                </td>
                            </tr>
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                class="hover:bg-slate-50/60 transition"
                            >
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">
                                        {{ u.name }}
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        {{ u.email }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-slate-700">
                                    {{ u.city }}
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="
                                            u.role === 'admin'
                                                ? 'bg-purple-100 text-purple-800 border border-purple-200'
                                                : 'bg-slate-100 text-slate-700'
                                        "
                                    >
                                        <Shield
                                            v-if="u.role === 'admin'"
                                            class="w-3 h-3"
                                        />
                                        <UserIcon v-else class="w-3 h-3" />
                                        {{ u.role_label }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <span
                                        v-if="u.approver_for_count > 0"
                                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200"
                                    >
                                        <UserCheck class="w-3 h-3" />
                                        Approver ({{
                                            u.approver_for_count
                                        }}
                                        User)
                                    </span>
                                    <span
                                        v-else
                                        class="text-slate-400 text-[11px]"
                                        >-</span
                                    >
                                </td>
                                <td class="py-3 px-4">
                                    <div
                                        v-if="u.assigned_approvers.length > 0"
                                        class="space-y-0.5"
                                    >
                                        <div
                                            v-for="appr in u.assigned_approvers"
                                            :key="appr.id"
                                            class="text-[11px] text-slate-700 flex items-center gap-1"
                                        >
                                            <span
                                                class="w-1.5 h-1.5 rounded-full bg-emerald-500"
                                            ></span>
                                            <span
                                                >L{{ appr.level }}:
                                                <strong>{{
                                                    appr.approver_name
                                                }}</strong></span
                                            >
                                        </div>
                                    </div>
                                    <span
                                        v-else
                                        class="text-slate-400 text-[11px]"
                                        >Default Admin</span
                                    >
                                </td>
                                <td
                                    class="py-3 px-4 text-slate-500 text-[11px]"
                                >
                                    {{ u.created_at }}
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <button
                                            type="button"
                                            @click="toggleRole(u)"
                                            class="px-2 py-1 rounded-lg border text-[10px] font-semibold transition"
                                            :class="
                                                u.role === 'admin'
                                                    ? 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100'
                                                    : 'border-purple-200 bg-purple-50 text-purple-800 hover:bg-purple-100'
                                            "
                                            :title="
                                                u.role === 'admin'
                                                    ? 'Ubah jadi User'
                                                    : 'Jadikan Admin'
                                            "
                                        >
                                            {{
                                                u.role === "admin"
                                                    ? "Jadikan User"
                                                    : "Jadikan Admin"
                                            }}
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteUser(u)"
                                            class="p-1 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                            title="Hapus Pengguna"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="users.links && users.links.length > 3"
                    class="p-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500"
                >
                    <div>
                        Menampilkan {{ users.from || 0 }} -
                        {{ users.to || 0 }} dari {{ users.total }} pengguna
                    </div>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in users.links"
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-2.5 py-1 rounded-lg text-xs"
                            :class="[
                                link.active
                                    ? 'bg-emerald-600 text-white font-bold'
                                    : 'text-slate-600 hover:bg-slate-100',
                                !link.url
                                    ? 'opacity-40 cursor-not-allowed'
                                    : '',
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
