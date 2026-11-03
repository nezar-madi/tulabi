<template>
    <div class="list-field">
        <p class="form-plain-field__label font-weight-medium mb-1">
            <span class="me-1">{{ label }}</span>
            <span v-if="required" class="text-error">*</span>
        </p>
        <p v-if="hint" class="teach-hint mt-0">{{ hint }}</p>

        <ul v-if="modelValue.length" class="list-field__items">
            <li v-for="(item, index) in modelValue" :key="keys[index]" class="list-field__item">
                <span class="list-field__bullet" aria-hidden="true" />
                <FormPlainTextField
                    :model-value="item"
                    :placeholder="placeholder"
                    :field-attrs="{ name: `${name}.${index}`, maxlength: maxLength, 'data-list-item': `${name}-${index}` }"
                    :error-messages="errors[`${name}.${index}`]"
                    class="list-field__input"
                    @update:model-value="setItem(index, $event)"
                    @keydown.enter.prevent="add(index + 1)"
                />
                <v-btn
                    icon
                    variant="text"
                    size="small"
                    :aria-label="removeLabel"
                    @click="remove(index)"
                >
                    <v-icon icon="tabler:x" />
                </v-btn>
            </li>
        </ul>

        <v-btn
            v-if="modelValue.length < max"
            class="rq-btn"
            variant="tonal"
            color="primary"
            size="small"
            @click="add(modelValue.length)"
        >
            <v-icon icon="tabler:plus" start />
            {{ addLabel }}
        </v-btn>
        <small v-if="errors[name]" class="text-error d-block mt-1">{{ errors[name] }}</small>
    </div>
</template>

<script setup>
import { nextTick, ref, watch } from 'vue';
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

/*
 * A list of short text items (a bullet list on the public page): add, edit, remove; Enter adds
 * the next one. Items keep a key of their own so editing or removing one never moves the others.
 */
const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    name: { type: String, required: true },
    label: { type: String, required: true },
    required: { type: Boolean, default: false },
    hint: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    addLabel: { type: String, default: '' },
    removeLabel: { type: String, default: '' },
    // The form's errors, keyed like Laravel's: `name` and `name.N`.
    errors: { type: Object, default: () => ({}) },
    max: { type: Number, default: 15 },
    maxLength: { type: Number, default: 200 },
});

const emit = defineEmits(['update:modelValue']);

let counter = 0;
const nextKey = () => {
    counter += 1;

    return counter;
};
const keys = ref(props.modelValue.map(nextKey));

// The list was replaced from outside (a program chosen as the template): new keys.
watch(() => props.modelValue.length, (length) => {
    if (keys.value.length !== length) {
        keys.value = props.modelValue.map(nextKey);
    }
});

function setItem(index, value) {
    const next = [...props.modelValue];
    next[index] = value;
    emit('update:modelValue', next);
}

function add(at) {
    const next = [...props.modelValue];
    next.splice(at, 0, '');
    keys.value.splice(at, 0, nextKey());
    emit('update:modelValue', next);

    nextTick(() => {
        const el = document.querySelector(`[data-list-item="${props.name}-${at}"]`);

        (el?.matches('input') ? el : el?.querySelector('input'))?.focus();
    });
}

function remove(index) {
    const next = [...props.modelValue];
    next.splice(index, 1);
    keys.value.splice(index, 1);
    emit('update:modelValue', next);
}
</script>

<style scoped>
.list-field {
    margin-bottom: 20px;
}

.list-field__items {
    display: flex;
    flex-direction: column;
    gap: 2px;
    margin: 0 0 6px;
    padding: 0;
    list-style: none;
}

.list-field__item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.list-field__bullet {
    flex-shrink: 0;
    width: 7px;
    height: 7px;
    margin-top: 22px;
    border-radius: 50%;
    background: var(--rq-text-subtle);
}

.list-field__input {
    flex: 1 1 auto;
}
</style>
