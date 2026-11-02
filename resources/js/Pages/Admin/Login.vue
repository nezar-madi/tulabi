<template>
    <Head :title="$t('admin.login.title')" />

    <v-app>
        <v-main class="admin-login">
            <AuthCard :title="$t('admin.login.title')">
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
                        <v-btn type="submit" class="rq-btn rq-btn--primary" block size="large" :loading="form.processing">
                            {{ $t('admin.login.submit') }}
                        </v-btn>
                    </div>
                </form>
            </AuthCard>
        </v-main>
    </v-app>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

defineOptions({ layout: null });

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

<style scoped>
.admin-login {
    min-height: 100%;
    background: var(--rq-bg-page);
}
</style>
