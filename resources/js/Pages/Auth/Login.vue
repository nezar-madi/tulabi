<template>
    <Head :title="$t('auth.login.title')" />

    <AuthCard :title="$t('auth.login.title')" :subtitle="$t('auth.login.subtitle')">
        <form @submit.prevent="submit">
            <FormPlainTextField
                v-model="form.phone"
                :label="$t('fields.phone')"
                :placeholder="$t('fields.phone_hint')"
                :field-attrs="{ name: 'phone', autocomplete: 'username', inputmode: 'tel', dir: 'ltr' }"
                :error-messages="form.errors.phone"
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
                class="mb-2"
            />

            <div class="d-flex align-center justify-space-between ga-3 mb-2">
                <FormPlainCheckbox v-model="form.remember" :label="$t('auth.login.remember')" />
                <Link :href="route('password.request')" class="auth-page__link text-decoration-none">
                    {{ $t('auth.login.forgot') }}
                </Link>
            </div>

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <v-btn type="submit" class="rq-btn rq-btn--primary" block size="large" :loading="form.processing">
                    {{ $t('auth.login.submit') }}
                </v-btn>
            </div>
        </form>

        <template #footer>
            {{ $t('auth.login.no_account') }}
            <Link :href="route('register')" class="auth-page__link">{{ $t('auth.login.register') }}</Link>
        </template>
    </AuthCard>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import FormPlainCheckbox from '@/Components/formFields/FormPlainCheckbox.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const form = useForm({
    phone: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>
