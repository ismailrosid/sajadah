<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Users, SlidersHorizontal, Eye, ClipboardCheck, Settings, ArrowLeft, Shield } from 'lucide-vue-next';

const page = usePage();

const navItems = [
    { name: 'Dashboard', route: 'admin.dashboard', icon: LayoutDashboard },
    { name: 'Manajemen User', route: 'admin.users.index', icon: Users },
    { name: 'Konfigurasi Approver', route: 'admin.approval-config.index', icon: SlidersHorizontal },
    { name: 'Monitoring Absensi', route: 'admin.attendances.index', icon: Eye },
    { name: 'Monitoring Persetujuan', route: 'admin.approvals.index', icon: ClipboardCheck },
    { name: 'Pengaturan Aplikasi', route: 'admin.settings.edit', icon: Settings },
];
</script>

<template>
    <aside class="w-64 bg-slate-900 text-slate-300 flex-shrink-0 min-h-[calc(100vh-4rem)] flex flex-col justify-between p-4 border-r border-slate-800 hidden md:flex">
        <div class="space-y-6">
            <!-- Admin Header -->
            <div class="p-3 bg-slate-800/60 rounded-xl border border-slate-700/60 flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center flex-shrink-0">
                    <Shield class="w-4 h-4" />
                </div>
                <div class="truncate">
                    <div class="text-[10px] uppercase tracking-wider font-semibold text-emerald-400">
                        Admin Console
                    </div>
                    <div class="font-bold text-white text-xs truncate">
                        SAJADA Management
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="route(item.route)"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-xs font-semibold transition"
                    :class="route().current(item.route + '*') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-900/30 font-bold' : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/80'"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    <span>{{ item.name }}</span>
                </Link>
            </nav>
        </div>

        <!-- Admin Console Footer -->
        <div class="pt-4 border-t border-slate-800 text-center">
            <span class="text-[11px] text-slate-500 font-medium tracking-wide">
                SAJADA Console &bull; Admin
            </span>
        </div>
    </aside>
</template>
