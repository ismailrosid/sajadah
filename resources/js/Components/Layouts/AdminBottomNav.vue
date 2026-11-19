<script setup>
import { Link } from '@inertiajs/vue3';
import { LayoutDashboard, Users, Eye, ClipboardCheck, Settings } from 'lucide-vue-next';

const navItems = [
    { name: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard },
    { name: 'User', route: 'admin.users.index', icon: Users },
    { name: 'Absensi', route: 'admin.attendances.index', icon: Eye },
    { name: 'Persetujuan', route: 'admin.approvals.index', icon: ClipboardCheck },
    { name: 'Pengaturan', route: 'admin.settings.edit', icon: Settings },
];
</script>

<template>
    <div class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-slate-900/95 backdrop-blur-md border-t border-slate-800 pb-safe">
        <div class="flex items-center justify-around h-16 px-1">
            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="route(item.route)"
                class="flex flex-col items-center justify-center flex-1 py-1 transition"
                :class="route().current(item.route + '*') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-slate-200'"
            >
                <component :is="item.icon" class="w-4 h-4" />
                <span class="text-[9px] mt-1 truncate">{{ item.name }}</span>
            </Link>
        </div>
    </div>
</template>
