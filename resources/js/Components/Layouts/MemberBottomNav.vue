<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Home, Calendar, Camera, ClipboardCheck } from 'lucide-vue-next';

const page = usePage();
const hasApprovalDuty = computed(() => Boolean(page.props.auth?.isApprover || page.props.auth?.hasApprovalDuty));
</script>

<template>
    <div class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200 pb-safe">
        <div class="flex items-center justify-around h-16 relative px-2">
            <!-- Dashboard -->
            <Link
                :href="route('dashboard')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition"
                :class="route().current('dashboard') ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
            >
                <Home class="w-5 h-5" />
                <span class="text-[10px] mt-1">Beranda</span>
            </Link>

            <!-- Center Action FAB: Absen Sholat -->
            <div class="relative -top-3.5 flex-1 flex flex-col items-center justify-center">
                <Link
                    :href="route('member.attendance.create')"
                    class="w-12 h-12 rounded-full flex items-center justify-center ring-4 transition transform active:scale-95 focus:outline-none"
                    :class="route().current('member.attendance.*')
                        ? 'bg-gradient-to-tr from-emerald-600 to-teal-500 text-white shadow-md shadow-emerald-700/30 ring-emerald-100 scale-105'
                        : 'bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 border border-slate-200/80 ring-white shadow-xs'"
                >
                    <Camera class="w-5 h-5" />
                </Link>
                <span
                    class="text-[10px] mt-0.5 transition"
                    :class="route().current('member.attendance.*') ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
                >
                    Absen
                </span>
            </div>

            <!-- Riwayat -->
            <Link
                :href="route('member.history.index')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition"
                :class="route().current('member.history.*') ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
            >
                <Calendar class="w-5 h-5" />
                <span class="text-[10px] mt-1">Riwayat</span>
            </Link>

            <!-- Approval (Hanya muncul jika memiliki tugas approval) -->
            <Link
                v-if="hasApprovalDuty"
                :href="route('approvals.index')"
                class="flex flex-col items-center justify-center flex-1 py-1 transition"
                :class="(route().current('approvals.*') || route().current('admin.approvals.*')) ? 'text-emerald-600 font-bold' : 'text-slate-400 hover:text-slate-600'"
            >
                <ClipboardCheck class="w-5 h-5" />
                <span class="text-[10px] mt-1">Approval</span>
            </Link>
        </div>
    </div>
</template>
