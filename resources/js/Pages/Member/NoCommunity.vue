<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Users, Info } from 'lucide-vue-next';
</script>

<template>
    <AppLayout title="Belum Bergabung Komunitas">
        <div class="max-w-md mx-auto px-4 py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-4">
                <Users class="w-8 h-8" />
            </div>
            <h1 class="text-xl font-bold text-slate-900 mb-2">
                Belum Terdaftar di Komunitas
            </h1>
            <p class="text-xs text-slate-500 leading-relaxed mb-6">
                Anda belum terdaftar atau disetujui di komunitas manapun. Hubungi pengurus komunitas atau DKM masjid Anda untuk mendapatkan undangan bergabung.
            </p>
            <div class="p-4 rounded-xl bg-slate-100 text-left text-xs text-slate-600 flex items-start gap-3">
                <Info class="w-5 h-5 text-slate-400 flex-shrink-0 mt-0.5" />
                <div>
                    Jika Anda adalah admin komunitas atau DKM, masuk menggunakan akun pengurus untuk mengonfigurasi masjid dan absensi.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
