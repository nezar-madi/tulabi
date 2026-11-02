<template>
    <div class="form-plain-field form-plain-field--time-picker">
        <label
            v-if="hasLabel"
            class="form-plain-field__label d-inline-block pb-4 font-weight-medium"
        >
            <span class="me-1">{{ label }}</span>
            <span v-if="showRequiredMark" class="text-error">*</span>
        </label>
        <v-menu
            v-model="menuOpen"
            :disabled="disabled"
            :close-on-content-click="false"
            location="bottom start"
            offset="8"
        >
            <template #activator="{ props: menuProps }">
                <my-text-field
                    :model-value="displayValue"
                    :placeholder="resolvedPlaceholder"
                    readonly
                    :clearable="clearable && !disabled"
                    :disabled="disabled"
                    center-affix
                    prepend-inner-icon="mdi-clock-outline"
                    v-bind="{ ...fieldAttrs, ...menuProps }"
                    :error-messages="resolvedErrorMessages"
                    :hint="hint"
                    :persistent-hint="persistentHint"
                    @click:clear="onClear"
                    @keydown="emit('keydown', $event)"
                />
            </template>
            <v-time-picker
                :model-value="pickerValue"
                color="primary"
                format="24hr"
                :max="max"
                :min="min"
                @update:model-value="onPickerUpdate"
            />
        </v-menu>
    </div>
</template>

<script setup>

    import { computed, ref } from 'vue';
    import './formPlainControl.css';
    import './formPlainTemporalPicker.css';
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
        min: {
            type: String,
            default: undefined,
        },
        max: {
            type: String,
            default: undefined,
        },
        fieldAttrs: {
            type: Object,
            default: () => ({}),
        },
        hint: {
            type: String,
            default: '',
        },
        persistentHint: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        clearable: {
            type: Boolean,
            default: true,
        },
    });

    const emit = defineEmits(['update:modelValue', 'keydown']);

    const menuOpen = ref(false);

    function formatTimeForDisplay(value) {
        if (!value) {
            return '';
        }

        const match = value.trim().match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/);

        if (!match) {
            return value;
        }

        const hour = Number(match[1]);
        const minute = match[2];

        if (Number.isNaN(hour)) {
            return value;
        }

        return `${String(hour).padStart(2, '0')}:${minute}`;
    }

    function normalizeTimeValue(value) {
        if (!value) {
            return '';
        }

        const formatted = formatTimeForDisplay(value);

        return formatted || value;
    }

    function isCompleteTime(value) {
        return /^\d{2}:\d{2}$/.test(value);
    }

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedPlaceholder = computed(() =>
        props.placeholder !== '' ? props.placeholder : props.label,
    );
    const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
    const pickerValue = computed(() => (props.modelValue ? props.modelValue : null));
    const displayValue = computed(() => formatTimeForDisplay(props.modelValue));

    function onPickerUpdate(value) {
        if (!value) {
            emit('update:modelValue', '');
            menuOpen.value = false;

            return;
        }

        const next = normalizeTimeValue(value);
        emit('update:modelValue', next);

        if (isCompleteTime(next)) {
            menuOpen.value = false;
        }
    }

    function onClear() {
        emit('update:modelValue', '');
        menuOpen.value = false;
    }
</script>
