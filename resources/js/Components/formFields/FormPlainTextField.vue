<template>
    <div class="form-plain-field">
        <label
            v-if="hasLabel"
            class="form-plain-field__label d-inline-block pb-4 font-weight-medium"
        >
            <span class="me-1">{{ label }}</span>
            <span v-if="showRequiredMark" class="text-error">*</span>
        </label>
        <my-text-field
            v-model="displayModel"
            :placeholder="resolvedPlaceholder"
            v-bind="fieldAttrs"
            :disabled="disabled"
            :error-messages="resolvedErrorMessages"
            :type="resolvedType"
            :hint="hint"
            :persistent-hint="persistentHint"
            @blur="emit('blur', $event)"
        >
            <template v-if="revealable && isPasswordType" #append-inner>
                <button
                    type="button"
                    class="form-plain-field__reveal"
                    :aria-label="revealed ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                    :aria-pressed="revealed"
                    tabindex="-1"
                    @click.stop.prevent="revealed = !revealed"
                >
                    <v-icon
                        :icon="revealed ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
                        size="20"
                    />
                </button>
            </template>
        </my-text-field>
    </div>
</template>

<script setup>

    import { computed, ref, watch } from 'vue';
    import './formPlainControl.css';

    const props = defineProps({
        modelValue: {
            type: [String, Number, null],
            default: '',
        },
        label: {
            type: String,
            default: '',
        },
        // The label doubles as the placeholder unless one is given; this keeps the field empty.
        hidePlaceholder: {
            type: Boolean,
            default: false,
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
        type: {
            type: String,
            default: 'text',
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
        revealable: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits(['update:modelValue', 'blur']);

    const revealed = ref(false);

    const isPasswordType = computed(() => props.type === 'password');

    watch(
        () => props.type,
        () => {
            revealed.value = false;
        },
    );

    const resolvedType = computed(() => {
        if (props.revealable && isPasswordType.value && revealed.value) {
            return 'text';
        }

        return props.type;
    });

    const displayModel = computed({
        get() {
            if (props.modelValue === null || props.modelValue === undefined) {
                return '';
            }

            return String(props.modelValue);
        },
        set(value) {
            if (props.type === 'number') {
                const trimmed = String(value ?? '').trim();

                if (trimmed === '') {
                    emit('update:modelValue', null);

                    return;
                }

                const parsed = Number(trimmed);
                emit('update:modelValue', Number.isFinite(parsed) ? parsed : trimmed);

                return;
            }

            emit('update:modelValue', value ?? '');
        },
    });

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedPlaceholder = computed(() => {
        if (props.hidePlaceholder) {
            return '';
        }

        return props.placeholder !== '' ? props.placeholder : props.label;
    });
    const resolvedErrorMessages = computed(() => {
        const raw = props.errorMessages;

        if (!raw) {
            return [];
        }

        if (Array.isArray(raw)) {
            return raw.flatMap((item) => {
                if (typeof item === 'string') {
                    return item.trim() ? [item] : [];
                }

                if (Array.isArray(item)) {
                    return item.filter((nested) => typeof nested === 'string' && nested.trim());
                }

                return [];
            });
        }

        if (typeof raw === 'string') {
            return raw.trim() ? [raw] : [];
        }

        return [];
    });
</script>
