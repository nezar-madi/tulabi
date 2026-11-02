<template>
    <Head :title="$t('account.title')" />

    <div class="account">
        <h1 class="account__title rq-display">{{ $t('account.title') }}</h1>

        <FlashStatus />

        <!-- Where the instructor side stands -->
        <section v-if="instructor" class="rq-card account__card account__card--accent">
            <span class="rq-icon-tile"><v-icon icon="tabler:school" /></span>
            <div>
                <h2 class="account__card-title">{{ $t('account.instructor_title') }}</h2>
                <p class="account__text">{{ instructor.specialty }} · {{ instructor.city }}</p>
                <p class="account__text">{{ $t('account.instructor_text') }}</p>
            </div>
        </section>

        <section v-else-if="application?.status === 'pending'" class="rq-card account__card">
            <span class="rq-icon-tile rq-icon-tile--amber"><v-icon icon="tabler:hourglass" /></span>
            <div>
                <h2 class="account__card-title">{{ $t('account.application.pending_title') }}</h2>
                <p class="account__text">{{ $t('account.application.pending_text', { specialty: application.specialty }) }}</p>
            </div>
        </section>

        <section v-else-if="application?.status === 'rejected'" class="rq-card account__card">
            <span class="rq-icon-tile rq-icon-tile--rose"><v-icon icon="tabler:x" /></span>
            <div class="flex-grow-1">
                <h2 class="account__card-title">{{ $t('account.application.rejected_title') }}</h2>
                <p v-if="application.rejection_reason" class="account__text">
                    {{ $t('account.application.rejected_reason', { reason: application.rejection_reason }) }}
                </p>
                <Link :href="route('join')" class="text-decoration-none">
                    <v-btn class="rq-btn mt-3" variant="outlined" color="primary">{{ $t('account.application.apply_again') }}</v-btn>
                </Link>
            </div>
        </section>

        <section v-else class="rq-card account__card">
            <span class="rq-icon-tile"><v-icon icon="tabler:chalkboard" /></span>
            <div class="flex-grow-1">
                <h2 class="account__card-title">{{ $t('account.join_title') }}</h2>
                <p class="account__text">{{ $t('account.join_text') }}</p>
                <Link :href="route('join')" class="text-decoration-none">
                    <v-btn class="rq-btn mt-3" variant="outlined" color="primary">{{ $t('nav.join') }}</v-btn>
                </Link>
            </div>
        </section>

        <section class="rq-card account__card">
            <span class="rq-icon-tile rq-icon-tile--teal"><v-icon icon="tabler:calendar-event" /></span>
            <div>
                <h2 class="account__card-title">{{ $t('account.student_title') }}</h2>
                <p class="account__text">{{ $t('account.student_text') }}</p>
            </div>
        </section>

        <div class="account__forms">
            <section class="rq-card account__form">
                <h2 class="account__card-title mb-4">{{ $t('account.profile') }}</h2>
                <form @submit.prevent="saveProfile">
                    <FormPlainTextField
                        v-model="profileForm.name"
                        :label="$t('fields.name')"
                        hide-placeholder
                        :field-attrs="{ name: 'name', autocomplete: 'name' }"
                        :error-messages="profileForm.errors.name"
                        class="mb-3"
                    />
                    <FormPlainTextField
                        v-model="profileForm.email"
                        :label="`${$t('fields.email')} (${$t('common.optional')})`"
                        :placeholder="$t('fields.email_hint')"
                        type="email"
                        :field-attrs="{ name: 'email', autocomplete: 'email', dir: 'ltr' }"
                        :error-messages="profileForm.errors.email"
                        class="mb-3"
                    />
                    <div class="account__phone">
                        <span>{{ $t('fields.phone') }}</span>
                        <strong dir="ltr">{{ profile.phone }}</strong>
                    </div>
                    <p class="account__note">{{ $t('account.phone_note') }}</p>
                    <v-btn type="submit" class="rq-btn rq-btn--primary mt-4" :loading="profileForm.processing">
                        {{ $t('common.save') }}
                    </v-btn>
                </form>
            </section>

            <section class="rq-card account__form">
                <h2 class="account__card-title mb-4">{{ $t('account.change_password') }}</h2>
                <form @submit.prevent="savePassword">
                    <FormPlainTextField
                        v-model="passwordForm.current_password"
                        :label="$t('fields.current_password')"
                        hide-placeholder
                        type="password"
                        revealable
                        :field-attrs="{ name: 'current_password', autocomplete: 'current-password' }"
                        :error-messages="passwordForm.errors.current_password"
                        class="mb-3"
                    />
                    <FormPlainTextField
                        v-model="passwordForm.password"
                        :label="$t('fields.new_password')"
                        :placeholder="$t('fields.password_hint')"
                        type="password"
                        revealable
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
                        :field-attrs="{ name: 'password_confirmation', autocomplete: 'new-password' }"
                        class="mb-3"
                    />
                    <v-btn type="submit" class="rq-btn rq-btn--primary mt-1" :loading="passwordForm.processing">
                        {{ $t('common.save') }}
                    </v-btn>
                </form>
            </section>
        </div>
    </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import FlashStatus from '@/Components/FlashStatus.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    profile: { type: Object, required: true },
    instructor: { type: Object, default: null },
    application: { type: Object, default: null },
});

const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email || '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function saveProfile() {
    profileForm.put(route('account.update'), { preserveScroll: true });
}

function savePassword() {
    passwordForm.put(route('account.password'), {
        preserveScroll: true,
        onFinish: () => passwordForm.reset(),
    });
}
</script>

<style scoped>
.account {
    max-width: 880px;
    margin: 0 auto;
    padding: 32px 16px 64px;
}

.account__title {
    margin: 0 0 20px;
    font-size: 28px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.account__card {
    display: flex;
    gap: 16px;
    align-items: flex-start;
    padding: 20px;
    margin-bottom: 12px;
    border-radius: var(--rq-radius-card);
}

.account__card--accent {
    border-inline-start: 3px solid var(--rq-brand-accent);
}

.account__card-title {
    margin: 0 0 4px;
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-card-title);
    font-weight: 600;
    color: var(--rq-text-heading);
}

.account__text {
    margin: 0;
    line-height: var(--rq-lh-body);
    color: var(--rq-text-muted);
}

.account__forms {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 12px;
    margin-top: 24px;
}

.account__form {
    padding: 24px;
    border-radius: var(--rq-radius-card);
}

.account__phone {
    display: flex;
    justify-content: space-between;
    padding: 10px 12px;
    border-radius: var(--rq-radius-sm);
    background: var(--rq-surface-chrome);
    font-size: 14px;
}

.account__note {
    margin: 6px 0 0;
    font-size: var(--rq-text-caption);
    color: var(--rq-text-subtle);
}
</style>
