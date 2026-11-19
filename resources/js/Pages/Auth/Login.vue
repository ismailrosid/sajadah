<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LogIn, KeyRound, Loader2 } from 'lucide-vue-next';

defineProps({
    canResetPassword: {
        type: Boolean,
        default: true,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk Akun - SAJADA" />

        <div class="mb-5 text-center">
            <h1 class="text-xl font-bold tracking-tight text-slate-900">
                Masuk ke SAJADA
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Silakan masuk dengan akun terdaftar Anda
            </p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-semibold p-3 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Alamat Email" class="text-xs font-bold text-slate-700" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="nama@email.com"
                />
                <InputError class="mt-1 text-xs" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Kata Sandi" class="text-xs font-bold text-slate-700" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[11px] font-medium text-emerald-600 hover:text-emerald-700"
                    >
                        Lupa kata sandi?
                    </Link>
                </div>

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full text-xs rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500 py-2.5 px-3"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Masukkan kata sandi"
                />
                <InputError class="mt-1 text-xs" :message="form.errors.password" />
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-2 cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="text-emerald-600 focus:ring-emerald-500 rounded" />
                    <span class="text-xs text-slate-600">Ingat saya di perangkat ini</span>
                </label>
            </div>

            <div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20 transition-all flex items-center justify-center gap-2 active:scale-[0.98]"
                >
                    <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                    <LogIn v-else class="w-4 h-4" />
                    <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk Sekarang' }}</span>
                </button>
            </div>

            <div class="pt-2 text-center text-xs text-slate-500">
                Belum punya akun?
                <Link
                    :href="route('register')"
                    class="font-bold text-emerald-600 hover:text-emerald-700 ml-1 underline underline-offset-2"
                >
                    Daftar di sini
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
