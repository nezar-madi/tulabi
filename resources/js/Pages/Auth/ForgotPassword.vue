<template>
    <Head :title="$t('auth.forgot.title')" />

    <AuthCard :title="$t('auth.forgot.title')" :subtitle="$t('auth.forgot.subtitle')">
        <form @submit.prevent="submit">
            <FormPlainTextField
                v-model="form.phone"
                :label="$t('fields.phone')"
                :placeholder="$t('fields.phone_hint')"
                :field-attrs="{ name: 'phone', autocomplete: 'username', inputmode: 'tel', dir: 'ltr' }"
                :error-messages="form.errors.phone"
                class="mb-3"
            />

            <p class="auth-page__hint mt-2 mb-0">{{ $t('auth.forgot.no_email') }}</p>

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="form.processing" :disabled="form.processing">
                    {{ $t('auth.forgot.submit') }}
                </CtaButton>
            </div>
        </form>

        <template #footer>
            <Link :href="route('login')" class="auth-page__link">{{ $t('auth.forgot.back') }}</Link>
        </template>
    </AuthCard>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import CtaButton from '@/Components/CtaButton.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const form = useForm({ phone: '' });

function submit() {
    form.post(route('password.email'));
}
</script>
