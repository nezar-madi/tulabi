<template>
    <!-- The account part of sign-up, shared by «حساب جديد» and «انضم كمدرّس». -->
    <FormPlainTextField
        v-model="form.name"
        :label="$t('fields.name')"
        hide-placeholder
        required
        :field-attrs="{ name: 'name', autocomplete: 'name' }"
        :error-messages="form.errors.name"
        class="mb-3"
    />
    <FormPlainTextField
        v-model="form.phone"
        :label="$t('fields.phone')"
        :placeholder="$t('fields.phone_hint')"
        required
        :field-attrs="{ name: 'phone', autocomplete: 'tel', inputmode: 'tel', dir: 'ltr' }"
        :error-messages="form.errors.phone"
        class="mb-3"
    />
    <FormPlainTextField
        v-model="form.password"
        :label="$t('fields.password')"
        :placeholder="$t('fields.password_hint')"
        type="password"
        revealable
        required
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
        required
        :field-attrs="{ name: 'password_confirmation', autocomplete: 'new-password' }"
        class="mb-3"
    />
    <FormPlainTextField
        v-model="form.email"
        :label="`${$t('fields.email')} (${$t('common.optional')})`"
        :placeholder="$t('fields.email_hint')"
        type="email"
        :field-attrs="{ name: 'email', autocomplete: 'email', dir: 'ltr' }"
        :error-messages="form.errors.email"
        class="mb-3"
    />
</template>

<script setup>
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

defineProps({
    // An Inertia useForm() with name, phone, password, password_confirmation, email.
    form: { type: Object, required: true },
});
</script>
