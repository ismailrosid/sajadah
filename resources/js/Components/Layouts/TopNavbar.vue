<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { ChevronDown, LogOut, User as UserIcon, LayoutDashboard, Camera, Calendar, ClipboardCheck, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isApprover = computed(() => Boolean(page.props.auth.isApprover));
const isGlobalAdmin = computed(() => Boolean(page.props.auth.isAdmin));
const inAdminMode = computed(() => props.isAdmin || (isGlobalAdmin.value && typeof route === 'function' && route().current('admin.*')));

// Only regular users (non-admin) see the personal navigation (Dashboard, Absen Sholat, Riwayat, Approval)
const showPersonalNav = computed(() => !isGlobalAdmin.value && !inAdminMode.value);
</script>

<template>
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Left: Logo (Links to admin dashboard for Admin, or personal dashboard for Member) -->
            <div class="flex items-center gap-3">
                <Link :href="isGlobalAdmin ? route('admin.dashboard') : route('dashboard')" class="flex items-center gap-2">
                    <ApplicationLogo />
                </Link>
            </div>

            <!-- Center: Desktop Nav Links (HANYA UNTUK USER PERSONAL) -->
            <nav v-if="showPersonalNav" class="hidden md:flex items-center gap-1">
                <Link
                    :href="route('dashboard')"
                    class="px-3.5 py-2 text-xs font-medium rounded-xl transition flex items-center gap-1.5"
                    :class="route().current('dashboard') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                >
                    <LayoutDashboard class="w-3.5 h-3.5" />
                    <span>Dashboard</span>
                </Link>
                <Link
                    :href="route('member.attendance.create')"
                    class="px-3.5 py-2 text-xs font-medium rounded-xl transition flex items-center gap-1.5"
                    :class="route().current('member.attendance.*') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                >
                    <Camera class="w-3.5 h-3.5" />
                    <span>Absen Sholat</span>
                </Link>
                <Link
                    :href="route('member.history.index')"
                    class="px-3.5 py-2 text-xs font-medium rounded-xl transition flex items-center gap-1.5"
                    :class="route().current('member.history.*') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                >
                    <Calendar class="w-3.5 h-3.5" />
                    <span>Riwayat</span>
                </Link>

                <!-- Approval Menu: ONLY appears for regular users who have active approval duty -->
                <Link
                    v-if="isApprover"
                    :href="route('approvals.index')"
                    class="px-3.5 py-2 text-xs font-medium rounded-xl transition flex items-center gap-1.5"
                    :class="route().current('approvals.*') ? 'text-emerald-700 bg-emerald-50 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50'"
                >
                    <ClipboardCheck class="w-3.5 h-3.5" />
                    <span>Approval</span>
                </Link>
            </nav>

            <!-- Right: User Avatar Dropdown -->
            <div class="flex items-center gap-3">
                <Dropdown align="right" width="52">
                    <template #trigger>
                        <button class="flex items-center gap-2 p-1.5 rounded-xl hover:bg-slate-100 transition">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold flex items-center justify-center text-xs border border-emerald-200">
                                {{ user?.name?.charAt(0) || 'U' }}
                            </div>
                            <span class="text-xs font-semibold text-slate-700 hidden lg:inline-block max-w-[120px] truncate">
                                {{ user?.name }}
                            </span>
                            <ChevronDown class="w-3.5 h-3.5 text-slate-400 hidden lg:inline-block" />
                        </button>
                    </template>

                    <template #content>
                        <div class="px-4 py-2.5 text-xs border-b border-slate-100">
                            <div class="font-bold text-slate-800 truncate">{{ user?.name }}</div>
                            <div class="text-[11px] text-slate-500 truncate">{{ user?.email }}</div>
                            <div v-if="isGlobalAdmin" class="mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">
                                    Administrator
                                </span>
                            </div>
                            <div v-else-if="isApprover" class="mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                    Approver
                                </span>
                            </div>
                        </div>

                        <DropdownLink :href="route('profile.edit')" class="flex items-center gap-2 text-xs">
                            <UserIcon class="w-3.5 h-3.5 text-slate-500" />
                            Profil
                        </DropdownLink>

                        <!-- Approval dropdown link (ONLY for regular User who has approval task) -->
                        <DropdownLink
                            v-if="!isGlobalAdmin && isApprover"
                            :href="route('approvals.index')"
                            class="flex items-center gap-2 text-xs"
                        >
                            <ClipboardCheck class="w-3.5 h-3.5 text-slate-500" />
                            Menu Approval
                        </DropdownLink>

                        <div class="border-t border-slate-100"></div>

                        <DropdownLink :href="route('logout')" method="post" as="button" class="flex items-center gap-2 text-xs text-rose-600">
                            <LogOut class="w-3.5 h-3.5" />
                            Keluar
                        </DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>
    </header>
</template>
