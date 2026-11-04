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
            :required="listsRequired"
            :hint="$t(`content.${list}_hint`)"
            :placeholder="$t(`content.${list}_placeholder`)"
            :add-label="$t('content.add_item')"
            :remove-label="$t('content.remove_item')"
            :errors="form.errors"
        />
    </div>
</template>

<script setup>
import ListField from '@/Components/ListField.vue';
import FormPlainTextarea from '@/Components/formFields/FormPlainTextarea.vue';

/*
 * The content of a program and of a class, one shape for both: a description and three bullet
 * lists (what the student will learn, requirements, who it is for). The promotional image or
 * video is chosen apart (PromoMediaChoice).
 * `form` is an Inertia useForm() with description, learn, requirements, audience.
 */
defineProps({
    form: { type: Object, required: true },
    descriptionLabel: { type: String, required: true },
    // A class needs at least one item in each list; a program may leave them empty.
    listsRequired: { type: Boolean, default: false },
});

const lists = ['learn', 'requirements', 'audience'];
</script>
