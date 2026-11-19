<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { UserPlus, Loader2, MapPin, Mail, User, Lock, Phone } from 'lucide-vue-next';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    city: 'Jakarta',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Daftar Akun Baru - SAJADA" />

        <div class="mb-5 text-center">
            <h1 class="text-xl font-bold tracking-tight text-slate-900">
                Buat Akun SAJADA
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Aplikasi pencatat dan pemantau konsistensi ibadah sholat lima waktu pribadi Anda
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <!-- Nama Lengkap -->
            <div>
                <InputLabel for="name" value="Nama Lengkap" class="text-xs font-bold text-slate-700" />
                <div class="relative mt-1">
                    <TextInput
                        id="name"
                        type="text"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Contoh: Muhammad Ilham"
                    />
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
                <InputLabel for="email" value="Alamat Email" class="text-xs font-bold text-slate-700" />
                <div class="relative mt-1">
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="nama@email.com"
                    />
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.email" />
            </div>

            <!-- Kota Domisili (Untuk Perhitungan Jadwal Sholat) -->
            <div>
                <InputLabel for="city" value="Kota Domisili (Jadwal Sholat)" class="text-xs font-bold text-slate-700" />
                <div class="relative mt-1">
                    <TextInput
                        id="city"
                        type="text"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.city"
                        required
                        placeholder="Contoh: Jakarta, Bandung, Surabaya, Medan"
                    />
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Jadwal sholat otomatis disesuaikan dengan kota domisili Anda</p>
                <InputError class="mt-1 text-xs" :message="form.errors.city" />
            </div>

            <!-- Nomor WhatsApp / Telepon -->
            <div>
                <InputLabel for="phone" value="Nomor WhatsApp / Telepon (Opsional)" class="text-xs font-bold text-slate-700" />
                <div class="relative mt-1">
                    <TextInput
                        id="phone"
                        type="tel"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.phone"
                        placeholder="081234567890"
                    />
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.phone" />
            </div>

            <!-- Password -->
            <div>
                <InputLabel for="password" value="Kata Sandi" class="text-xs font-bold text-slate-700" />
                <div class="relative mt-1">
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                    />
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.password" />
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <InputLabel
                    for="password_confirmation"
                    value="Konfirmasi Kata Sandi"
                    class="text-xs font-bold text-slate-700"
                />
                <div class="relative mt-1">
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi kata sandi"
                    />
                </div>
                <InputError class="mt-1 text-xs" :message="form.errors.password_confirmation" />
            </div>

            <!-- Tombol Submit -->
            <div class="pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 active:scale-[0.98]"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <UserPlus v-else class="w-4 h-4" />
                    <span>{{ form.processing ? 'Memproses Pendaftaran...' : 'Daftar Akun Sekarang' }}</span>
                </button>
            </div>

            <!-- Footer Login Link -->
            <div class="pt-3 text-center text-xs text-slate-500">
                Sudah memiliki akun?
                <Link
                    :href="route('login')"
                    class="font-bold text-emerald-600 hover:text-emerald-700 ml-1 underline underline-offset-2"
                >
                    Masuk di sini
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
