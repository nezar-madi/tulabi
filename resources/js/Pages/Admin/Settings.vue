<template>
    <Head :title="$t('admin.settings.title')" />

    <h1 class="admin-title rq-display">{{ $t('admin.settings.title') }}</h1>

    <section class="rq-card settings-card">
        <form @submit.prevent="save">
            <FormPlainTextField
                v-model="form.applications_notify_email"
                :label="$t('admin.settings.notify_email')"
                :placeholder="$t('admin.settings.notify_email_hint')"
                type="email"
                :field-attrs="{ name: 'applications_notify_email', dir: 'ltr' }"
                :error-messages="form.errors.applications_notify_email"
            />
            <v-btn type="submit" class="rq-btn rq-btn--primary mt-4" :loading="form.processing">
                {{ $t('common.save') }}
            </v-btn>
        </form>
    </section>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

const props = defineProps({
    settings: { type: Object, required: true },
});

const form = useForm({
    applications_notify_email: props.settings.applications_notify_email || '',
});

function save() {
    form.put(route('admin.settings.update'), { preserveScroll: true });
}
</script>

<style scoped src="./adminList.css"></style>

<style scoped>
.settings-card {
    max-width: 560px;
    padding: 24px;
    border-radius: var(--rq-radius-card);
}
</style>
