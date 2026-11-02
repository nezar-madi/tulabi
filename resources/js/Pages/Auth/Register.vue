<template>
    <Head :title="$t('auth.register.title')" />

    <AuthCard :title="$t('auth.register.title')" :subtitle="$t(`auth.register.subtitle_${step}`)">
        <v-alert v-if="forInstructor" type="info" variant="tonal" density="comfortable" class="mb-5" rounded="lg">
            {{ $t('auth.register.for_instructor') }}
        </v-alert>

        <!-- 1. the phone -->
        <form v-if="step === 'phone'" @submit.prevent="sendCode">
            <FormPlainTextField
                v-model="phoneForm.phone"
                :label="$t('fields.phone')"
                :placeholder="$t('fields.phone_hint')"
                required
                :field-attrs="{ name: 'phone', autocomplete: 'tel', inputmode: 'tel', dir: 'ltr' }"
                :error-messages="phoneForm.errors.phone"
                class="mb-3"
            />

            <RecaptchaBox ref="captcha" :site-key="recaptchaSiteKey" :error="phoneForm.errors['g-recaptcha-response']" />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="phoneForm.processing" :disabled="phoneForm.processing">
                    {{ $t('auth.register.send_code') }}
                </CtaButton>
            </div>
        </form>

        <!-- 2. the code sent to the phone -->
        <form v-else-if="step === 'code'" @submit.prevent="verify">
            <p class="register__phone">
                {{ $t('auth.register.code_sent_to') }}
                <strong dir="ltr">{{ phone }}</strong>
            </p>
            <FormPlainTextField
                v-model="codeForm.code"
                :label="$t('auth.register.code')"
                hide-placeholder
                required
                :field-attrs="{ name: 'code', autocomplete: 'one-time-code', inputmode: 'numeric', dir: 'ltr', maxlength: 6 }"
                :error-messages="codeForm.errors.code"
                class="mb-3"
            />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="codeForm.processing" :disabled="codeForm.processing">
                    {{ $t('auth.register.verify') }}
                </CtaButton>
            </div>

            <div class="register__links mt-4">
                <button type="button" class="register__link" :disabled="seconds > 0 || resending" @click="resend">
                    {{ seconds > 0 ? $t('auth.register.resend_in', { seconds }) : $t('auth.register.resend') }}
                </button>
                <button type="button" class="register__link" @click="restart">{{ $t('auth.register.change_phone') }}</button>
            </div>
        </form>

        <!-- 3. the password -->
        <form v-else @submit.prevent="savePassword">
            <p class="register__phone">
                <v-icon icon="tabler:circle-check" size="18" color="success" />
                {{ $t('auth.register.phone_verified') }}
                <strong dir="ltr">{{ phone }}</strong>
            </p>
            <FormPlainTextField
                v-model="passwordForm.password"
                :label="$t('fields.password')"
                :placeholder="$t('fields.password_hint')"
                type="password"
                revealable
                required
                :field-attrs="{ name: 'password', autocomplete: 'new-password' }"
                :error-messages="passwordForm.errors.password"
                class="mb-3"
            />
            <FormPlainTextField
                v-model="passwordForm.password_confirmation"
                :label="$t('fields.password_confirmation')"
                hide-placeholder
                type="password"
                revealable
                required
                :field-attrs="{ name: 'password_confirmation', autocomplete: 'new-password' }"
                class="mb-3"
            />

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="passwordForm.processing" :disabled="passwordForm.processing">
                    {{ $t('auth.register.submit') }}
                </CtaButton>
            </div>
        </form>

        <template #footer>
            <div>
                {{ $t('auth.register.have_account') }}
                <Link :href="route('login')" class="auth-page__link">{{ $t('nav.login') }}</Link>
            </div>
            <div v-if="!forInstructor" class="mt-2">
                <Link :href="route('join')" class="auth-page__link">{{ $t('auth.register.instructor_hint') }}</Link>
            </div>
        </template>
    </AuthCard>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import CtaButton from '@/Components/CtaButton.vue';
import RecaptchaBox from '@/Components/RecaptchaBox.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    // phone | code | password
    step: { type: String, default: 'phone' },
    phone: { type: String, default: null },
    resendIn: { type: Number, default: 0 },
    forInstructor: { type: Boolean, default: false },
    recaptchaSiteKey: { type: String, default: '' },
});

const captcha = ref(null);
const resending = ref(false);

const phoneForm = useForm({ phone: '', 'g-recaptcha-response': '' });
const codeForm = useForm({ code: '' });
const passwordForm = useForm({ password: '', password_confirmation: '' });

// The wait before another code may be asked for, counted down on the page.
const seconds = ref(props.resendIn);
let timer = null;

function tick() {
    clearInterval(timer);
    timer = null;

    if (seconds.value > 0) {
        timer = setInterval(() => {
            seconds.value -= 1;

            if (seconds.value <= 0) {
                clearInterval(timer);
                timer = null;
            }
        }, 1000);
    }
}

watch(() => props.resendIn, (value) => {
    seconds.value = value;
    tick();
}, { immediate: true });

onBeforeUnmount(() => clearInterval(timer));

function sendCode() {
    phoneForm['g-recaptcha-response'] = captcha.value?.token() || '';
    phoneForm.post(route('register'), {
        onError: () => captcha.value?.reset(),
    });
}

function verify() {
    codeForm.post(route('register.verify'), {
        onSuccess: () => codeForm.reset(),
    });
}

function resend() {
    resending.value = true;
    router.post(route('register.resend'), {}, {
        preserveScroll: true,
        onFinish: () => { resending.value = false; },
    });
}

function restart() {
    router.post(route('register.restart'));
}

function savePassword() {
    passwordForm.post(route('register.password'), {
        onFinish: () => passwordForm.reset('password', 'password_confirmation'),
    });
}
</script>

<style scoped>
.register__phone {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin-bottom: 16px;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.register__phone strong {
    color: var(--rq-text);
}

.register__links {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 8px;
}

.register__link {
    background: none;
    border: 0;
    padding: 0;
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--rq-brand-700);
    cursor: pointer;
}

.register__link:disabled {
    color: var(--rq-text-muted);
    cursor: default;
}
</style>
