<template>
    <Head :title="$t('join.title')" />

    <AuthCard :title="$t('join.title')" :subtitle="$t('join.subtitle')" wide>
        <v-alert v-if="previous" type="info" variant="tonal" density="comfortable" class="mb-5" rounded="lg">
            {{ $t('join.previous_rejected') }}
        </v-alert>

        <form @submit.prevent="submit">
            <template v-if="!signedIn">
                <p class="rq-eyebrow mb-1">{{ $t('join.account_section') }}</p>
                <p class="join__hint mb-4">{{ $t('join.account_hint') }}</p>
                <AccountFields :form="form" />
            </template>
            <p v-else class="join__hint mb-4">{{ $t('join.signed_in_hint') }}</p>

            <p class="rq-eyebrow mb-4" :class="{ 'mt-4': !signedIn }">{{ $t('join.teaching_section') }}</p>
            <FormPlainTextField
                v-model="form.specialty"
                :label="$t('fields.specialty')"
                :placeholder="$t('fields.specialty_hint')"
                required
                :field-attrs="{ name: 'specialty' }"
                :error-messages="form.errors.specialty"
                class="mb-3"
            />
            <FormPlainTextField
                v-model="form.city"
                :label="$t('fields.city')"
                hide-placeholder
                required
                :field-attrs="{ name: 'city', autocomplete: 'address-level2' }"
                :error-messages="form.errors.city"
                class="mb-3"
            />
            <FormPlainTextarea
                v-model="form.bio"
                :label="`${$t('fields.bio')} (${$t('common.optional')})`"
                :placeholder="$t('fields.bio_hint')"
                :rows="3"
                :maxlength="1000"
                :error-messages="form.errors.bio"
                class="mb-3"
            />

            <RecaptchaBox
                v-if="!signedIn"
                ref="captcha"
                :site-key="recaptchaSiteKey"
                :error="form.errors['g-recaptcha-response']"
            />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <v-btn type="submit" class="rq-btn rq-btn--primary" block size="large" :loading="form.processing">
                    {{ $t('join.submit') }}
                </v-btn>
            </div>
        </form>

        <template v-if="!signedIn" #footer>
            <Link :href="route('login')" class="auth-page__link">{{ $t('join.have_account') }}</Link>
        </template>
    </AuthCard>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AccountFields from '@/Components/AccountFields.vue';
import AuthCard from '@/Components/AuthCard.vue';
import RecaptchaBox from '@/Components/RecaptchaBox.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    signedIn: { type: Boolean, default: false },
    recaptchaSiteKey: { type: String, default: '' },
    previous: { type: Object, default: null },
});

const captcha = ref(null);

const form = useForm({
    name: '',
    phone: '',
    password: '',
    password_confirmation: '',
    email: '',
    specialty: props.previous?.specialty || '',
    city: props.previous?.city || '',
    bio: props.previous?.bio || '',
    'g-recaptcha-response': '',
});

function submit() {
    form['g-recaptcha-response'] = captcha.value?.token() || '';
    form
        .transform((data) => (props.signedIn
            ? { specialty: data.specialty, city: data.city, bio: data.bio }
            : data))
        .post(route('join'), {
            onError: () => captcha.value?.reset(),
            onFinish: () => form.reset('password', 'password_confirmation'),
        });
}
</script>

<style scoped>
.join__hint {
    font-size: 14px;
    color: var(--rq-text-muted);
}
</style>
