<script setup>
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    userSettings: {
        type: Object,
        default: () => ({}),
    },
    calculationMethods: {
        type: Array,
        default: () => [],
    },
    timezones: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const isAdmin = computed(() => Boolean(page.props.auth?.isAdmin));
</script>

<template>
    <AppLayout title="Profil & Konfigurasi Sholat Pribadi" :is-admin="isAdmin">
        <div class="max-w-4xl mx-auto px-4 py-6 space-y-6">
            <!-- Header with Back Button -->
            <div class="flex items-center gap-3">
                <Link
                    :href="isAdmin ? route('admin.dashboard') : route('dashboard')"
                    class="p-2 rounded-xl bg-white border border-slate-200 text-slate-600 hover:bg-slate-100 transition shadow-sm"
                >
                    <ArrowLeft class="w-4 h-4" />
                </Link>
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Profil & Konfigurasi Sholat Pribadi</h1>
                    <p class="text-xs text-slate-500">Kelola informasi akun Anda dan sesuaikan lokasi untuk perhitungan waktu sholat otomatis serta batas absensi harian.</p>
                </div>
            </div>

            <!-- Profile & Prayer Settings Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    :user-settings="userSettings"
                    :calculation-methods="calculationMethods"
                    :timezones="timezones"
                />
            </div>

            <!-- Update Password Card -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
                <UpdatePasswordForm />
            </div>
        </div>
    </AppLayout>
</template>
