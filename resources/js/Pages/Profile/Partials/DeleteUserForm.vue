<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';
import { Trash2 } from 'lucide-vue-next';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-base font-bold text-rose-700 flex items-center gap-2">
                <Trash2 class="w-5 h-5 text-rose-600" />
                <span>Hapus Akun</span>
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Setelah akun Anda dihapus, semua sumber daya dan data riwayat ibadah Anda akan dihapus secara permanen.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion" class="!px-4 !py-2.5 !rounded-xl !text-xs !bg-rose-600 hover:!bg-rose-700">
            Hapus Akun Saya
        </DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 bg-white rounded-2xl">
                <h2 class="text-base font-bold text-slate-900">
                    Apakah Anda yakin ingin menghapus akun Anda?
                </h2>

                <p class="mt-1 text-xs text-slate-500 leading-relaxed">
                    Setelah akun Anda dihapus, seluruh data tidak dapat dipulihkan. Masukkan kata sandi Anda untuk mengonfirmasi penghapusan akun secara permanen.
                </p>

                <div class="mt-4">
                    <InputLabel
                        for="password"
                        value="Kata Sandi"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full text-xs"
                        placeholder="Masukkan Kata Sandi Anda"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-1 text-xs" />
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <SecondaryButton @click="closeModal" class="!px-4 !py-2 !rounded-xl !text-xs">
                        Batal
                    </SecondaryButton>

                    <DangerButton
                        class="!px-4 !py-2 !rounded-xl !text-xs !bg-rose-600 hover:!bg-rose-700"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Ya, Hapus Akun
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
