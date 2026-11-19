<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Key, Check } from 'lucide-vue-next';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <Key class="w-5 h-5 text-emerald-600" />
                <span>Perbarui Kata Sandi</span>
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Pastikan akun Anda menggunakan kata sandi yang aman dan tidak mudah ditebak.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-4 max-w-xl">
            <div>
                <InputLabel for="current_password" value="Kata Sandi Saat Ini" />

                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="block w-full"
                    autocomplete="current-password"
                />

                <InputError
                    :message="form.errors.current_password"
                    class="mt-1 text-xs"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Kata Sandi Baru" />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="block w-full"
                        autocomplete="new-password"
                    />

                    <InputError :message="form.errors.password" class="mt-1 text-xs" />
                </div>

                <div>
                    <InputLabel
                        for="password_confirmation"
                        value="Konfirmasi Kata Sandi"
                    />

                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="block w-full"
                        autocomplete="new-password"
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-1 text-xs"
                    />
                </div>
            </div>

            <div class="flex items-center gap-4 pt-2">
                <PrimaryButton :disabled="form.processing" class="!bg-emerald-600 hover:!bg-emerald-700 !px-5 !py-2.5 !rounded-xl !text-xs !shadow-md !shadow-emerald-600/20">
                    {{ form.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-semibold text-emerald-600 flex items-center gap-1"
                    >
                        <Check class="w-3.5 h-3.5" />
                        Kata sandi berhasil diperbarui.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
