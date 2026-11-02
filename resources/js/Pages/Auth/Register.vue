<template>
    <Head :title="$t('auth.register.title')" />

    <AuthCard :title="$t('auth.register.title')" :subtitle="$t('auth.register.subtitle')">
        <form @submit.prevent="submit">
            <AccountFields :form="form" />

            <RecaptchaBox ref="captcha" :site-key="recaptchaSiteKey" :error="form.errors['g-recaptcha-response']" />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <v-btn type="submit" class="rq-btn rq-btn--primary" block size="large" :loading="form.processing">
                    {{ $t('auth.register.submit') }}
                </v-btn>
            </div>
        </form>

        <template #footer>
            <div>
                {{ $t('auth.register.have_account') }}
                <Link :href="route('login')" class="auth-page__link">{{ $t('nav.login') }}</Link>
            </div>
            <div class="mt-2">
                <Link :href="route('join')" class="auth-page__link">{{ $t('auth.register.instructor_hint') }}</Link>
            </div>
        </template>
    </AuthCard>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AccountFields from '@/Components/AccountFields.vue';
import AuthCard from '@/Components/AuthCard.vue';
import RecaptchaBox from '@/Components/RecaptchaBox.vue';

defineProps({
    recaptchaSiteKey: { type: String, default: '' },
});

const captcha = ref(null);

const form = useForm({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    email: '',
    'g-recaptcha-response': '',
});

function submit() {
    form['g-recaptcha-response'] = captcha.value?.token() || '';
    form.post(route('register'), {
        onError: () => captcha.value?.reset(),
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>
