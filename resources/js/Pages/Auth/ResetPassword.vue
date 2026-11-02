<template>
    <Head :title="$t('auth.reset.title')" />

    <AuthCard
        v-if="valid"
        :title="$t('auth.reset.title')"
        :subtitle="$t('auth.reset.subtitle', { name })"
    >
        <form @submit.prevent="submit">
            <FormPlainTextField
                v-model="form.password"
                :label="$t('fields.new_password')"
                :placeholder="$t('fields.password_hint')"
                type="password"
                revealable
                :field-attrs="{ name: 'password', autocomplete: 'new-password' }"
                :error-messages="form.errors.password"
                class="mb-3"
            />
            <FormPlainTextField
                v-model="form.password_confirmation"
                :label="$t('fields.password_confirmation')"
                hide-placeholder
                type="password"
                revealable
                :field-attrs="{ name: 'password_confirmation', autocomplete: 'new-password' }"
                class="mb-3"
            />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <v-btn type="submit" class="rq-btn rq-btn--primary" block size="large" :loading="form.processing">
                    {{ $t('auth.reset.submit') }}
                </v-btn>
            </div>
        </form>
    </AuthCard>

    <AuthCard v-else :title="$t('auth.reset.invalid_title')" :subtitle="$t('auth.reset.invalid_text')">
        <Link :href="route('login')" class="text-decoration-none">
            <v-btn class="rq-btn rq-btn--primary" block size="large">{{ $t('auth.reset.to_login') }}</v-btn>
        </Link>
    </AuthCard>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    token: { type: String, required: true },
    valid: { type: Boolean, default: false },
    name: { type: String, default: '' },
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('password.update', { token: props.token }), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
