<template>
    <Head :title="$t('join.title')" />

    <AuthCard :title="$t('join.title')" :subtitle="$t('join.subtitle')" wide>
        <v-alert v-if="previous" type="info" variant="tonal" density="comfortable" class="mb-5" rounded="lg">
            {{ $t('join.previous_rejected') }}
        </v-alert>

        <form @submit.prevent="submit">
            <FormPlainTextField
                v-if="needsName"
                v-model="form.name"
                :label="$t('fields.name')"
                hide-placeholder
                required
                :field-attrs="{ name: 'name', autocomplete: 'name' }"
                :error-messages="form.errors.name"
                class="mb-3"
            />

            <p class="rq-eyebrow mb-4">{{ $t('join.teaching_section') }}</p>
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

            <div class="auth-page__actions auth-page__actions--full mt-6">
                <CtaButton type="submit" block size="large" icon="" :loading="form.processing" :disabled="form.processing">
                    {{ $t('join.submit') }}
                </CtaButton>
            </div>
        </form>
    </AuthCard>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/AuthCard.vue';
import CtaButton from '@/Components/CtaButton.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    needsName: { type: Boolean, default: false },
    previous: { type: Object, default: null },
});

const form = useForm({
    name: '',
    specialty: props.previous?.specialty || '',
    city: props.previous?.city || '',
    bio: props.previous?.bio || '',
});

function submit() {
    form
        .transform((data) => (props.needsName ? data : { specialty: data.specialty, city: data.city, bio: data.bio }))
        .post(route('join'));
}
</script>
