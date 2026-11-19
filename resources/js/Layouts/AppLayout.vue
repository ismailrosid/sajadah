<script setup>
import { computed } from 'vue';
import { usePage, Head } from '@inertiajs/vue3';
import TopNavbar from '@/Components/Layouts/TopNavbar.vue';
import AdminSidebar from '@/Components/Layouts/AdminSidebar.vue';
import AdminBottomNav from '@/Components/Layouts/AdminBottomNav.vue';
import MemberBottomNav from '@/Components/Layouts/MemberBottomNav.vue';
import Toast from '@/Components/UI/Toast.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'SAJADA',
    },
    isAdmin: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const isUserAdmin = computed(() => Boolean(page.props.auth?.isAdmin));
const inAdminMode = computed(() => props.isAdmin || isUserAdmin.value);
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-800 flex flex-col font-sans">
        <Head :title="title ? `${title} - SAJADA` : 'SAJADA'" />

        <!-- Toast Notifications -->
        <Toast />

        <!-- Top Navigation -->
        <TopNavbar :is-admin="inAdminMode" />

        <!-- Body Area -->
        <div class="flex-1 flex flex-col md:flex-row">
            <!-- Admin Sidebar if on admin page -->
            <AdminSidebar v-if="props.isAdmin" />

            <!-- Main Content Container -->
            <main class="flex-1 w-full pb-20 md:pb-10">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Nav: Admin Bottom Nav for Admin, Member Bottom Nav for regular User -->
        <AdminBottomNav v-if="props.isAdmin || isUserAdmin" />
        <MemberBottomNav v-else />
    </div>
</template>
