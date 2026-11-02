<template>
    <div class="course-content">
        <!-- Description (a text area) -->
        <FormPlainTextarea
            v-model="form.description"
            :label="descriptionLabel"
            :placeholder="$t('content.description_hint')"
            required
            :rows="6"
            :maxlength="5000"
            :error-messages="form.errors.description"
            class="mb-5"
        />

        <!-- What you'll learn / Requirements / Who this is for (bullet lists) -->
        <ListField
            v-for="list in lists"
            :key="list"
            v-model="form[list]"
            :name="list"
            :label="$t(`content.${list}`)"
            :hint="$t(`content.${list}_hint`)"
            :placeholder="$t(`content.${list}_placeholder`)"
            :add-label="$t('content.add_item')"
            :remove-label="$t('content.remove_item')"
            :errors="form.errors"
        />

        <!-- Promotional video (embedded) -->
        <FormPlainTextField
            v-model="form.promo_video_url"
            :label="`${$t('content.video')} (${$t('common.optional')})`"
            placeholder="https://www.youtube.com/watch?v=..."
            :field-attrs="{ name: 'promo_video_url', dir: 'ltr', inputmode: 'url' }"
            :error-messages="form.errors.promo_video_url"
        />
        <p class="teach-hint mt-1">{{ $t('content.video_hint') }}</p>
    </div>
</template>

<script setup>
import ListField from '@/Components/ListField.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

/*
 * The content of a program and of a class, one shape for both: a description, three bullet
 * lists (what the student will learn, requirements, who it is for) and a promotional video.
 * `form` is an Inertia useForm() with description, learn, requirements, audience, promo_video_url.
 */
defineProps({
    form: { type: Object, required: true },
    descriptionLabel: { type: String, required: true },
});

const lists = ['learn', 'requirements', 'audience'];
</script>
