<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-50 py-8 px-4 font-sans selection:bg-emerald-500 selection:text-white">
        <div class="w-full overflow-hidden bg-white px-6 py-6 sm:px-8 sm:py-8 shadow-xl shadow-slate-200/60 sm:max-w-md rounded-2xl border border-slate-200/80">
            <div class="mb-6 flex justify-center">
                <Link href="/" class="flex justify-center">
                    <ApplicationLogo />
                </Link>
            </div>

            <slot />
        </div>

        <div class="mt-6 text-center text-xs text-slate-400">
            SAJADA &bull; Build Consistency, Strengthen Community
        </div>
    </div>
</template>
