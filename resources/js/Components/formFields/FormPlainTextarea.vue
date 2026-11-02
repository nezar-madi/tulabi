<template>
    <div class="form-plain-field">
        <label
            v-if="hasLabel"
            class="form-plain-field__label d-inline-block pb-4 font-weight-medium"
        >
            <span class="me-1">{{ label }}</span>
            <span v-if="showRequiredMark" class="text-error">*</span>
        </label>
        <v-textarea
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            :placeholder="resolvedPlaceholder"
            v-bind="fieldAttrs"
            :error-messages="resolvedErrorMessages"
            :rows="rows"
            :maxlength="maxlength"
            :counter="counter"
            auto-grow
            density="comfortable"
            variant="plain"
            class="form-plain-field__control"
            @blur="emit('blur', $event)"
        />
    </div>
</template>

<script setup>

    import { computed } from 'vue';
    import './formPlainControl.css';
    import { useResolvedErrorMessages } from '@/Components/formFields/useResolvedErrorMessages';

    const props = defineProps({
        modelValue: {
            type: String,
            default: '',
        },
        label: {
            type: String,
            default: '',
        },
        required: {
            type: Boolean,
            default: false,
        },
        placeholder: {
            type: String,
            default: '',
        },
        errorMessages: {
            type: [String, Array, Object],
            default: () => [],
        },
        rows: {
            type: [Number, String],
            default: 6,
        },
        maxlength: {
            type: [Number, String],
            default: undefined,
        },
        counter: {
            type: [Number, String, Boolean],
            default: undefined,
        },
        fieldAttrs: {
            type: Object,
            default: () => ({}),
        },
    });

    const emit = defineEmits(['update:modelValue', 'blur']);

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedPlaceholder = computed(() =>
        props.placeholder !== '' ? props.placeholder : props.label,
    );
    const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
</script>
