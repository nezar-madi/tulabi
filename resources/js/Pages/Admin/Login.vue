<template>
    <Head :title="$t('admin.login.title')" />

    <AuthCard :title="$t('admin.login.title')" large>
        <form @submit.prevent="submit">
            <FormPlainTextField
                v-model="form.email"
                :label="$t('admin.login.email')"
                hide-placeholder
                type="email"
                :field-attrs="{ name: 'email', autocomplete: 'username', dir: 'ltr' }"
                :error-messages="form.errors.email"
                class="mb-3"
            />
            <FormPlainTextField
                v-model="form.password"
                :label="$t('fields.password')"
                hide-placeholder
                type="password"
                revealable
                :field-attrs="{ name: 'password', autocomplete: 'current-password' }"
                :error-messages="form.errors.password"
                class="mb-3"
            />
            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="form.processing" :disabled="form.processing">
                    {{ $t('admin.login.submit') }}
                </CtaButton>
            </div>
        </form>
    </AuthCard>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import CtaButton from '@/Components/CtaButton.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('admin.login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>
