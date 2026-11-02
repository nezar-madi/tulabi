<template>
    <div class="form-plain-field form-plain-field--date-picker">
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
                    :clearable="!disabled"
                    :disabled="disabled"
                    center-affix
                    prepend-inner-icon="mdi-calendar-outline"
                    v-bind="{ ...controlAttrs, ...menuProps }"
                    :error-messages="resolvedErrorMessages"
                    :hint="hint"
                    :persistent-hint="persistentHint"
                    @click:clear="onClear"
                    @keydown="emit('keydown', $event)"
                    @blur="emit('blur', $event)"
                />
            </template>
            <v-date-picker
                ref="pickerRef"
                v-model:view-mode="pickerViewMode"
                v-model:year="pickerYear"
                :model-value="pickerValue"
                header-date-format="fullDateWithWeekday"
                color="primary"
                :max="resolvedMax"
                :min="resolvedMin"
                @update:model-value="onPickerUpdate"
            />
        </v-menu>
    </div>
</template>

<script setup>

    import { computed, nextTick, ref, watch } from 'vue';
    import { useDate } from 'vuetify';
    import './formPlainControl.css';
    import './formPlainTemporalPicker.css';
    import { useResolvedErrorMessages } from '@/Components/formFields/useResolvedErrorMessages';
    import { commitOverlayFieldBlur, overlayControlAttrs } from '@/Components/formFields/plainFieldValidation';
    import { localDateString, toDateOnlyString } from '@/utils/localDateString';

    const props = defineProps({
        openYearsAgo: {
            type: Number,
            default: 0,
        },
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
        max: {
            type: String,
            default: undefined,
        },
        min: {
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
    });

    const emit = defineEmits(['update:modelValue', 'keydown', 'blur']);

    const adapter = useDate();
    const menuOpen = ref(false);
    const pickerViewMode = ref('month');
    const pickerYear = ref(undefined);
    const pickerRef = ref(null);

    // The year list does not scroll to the chosen year by itself. Scroll only the list
    // (never the page behind it). The menu scales in as it opens, so measure in real
    // pixels, and center once more after the animation has settled.
    function scrollToActiveYear(attempt = 0, settled = false) {
        const list = pickerRef.value?.$el?.querySelector?.('.v-date-picker-years');
        const active = list?.querySelector('.v-btn--active');

        if (!list || !active || list.clientHeight === 0) {
            if (attempt < 10) {
                window.setTimeout(() => scrollToActiveYear(attempt + 1, settled), 50);
            }

            return;
        }

        const listBox = list.getBoundingClientRect();
        const activeBox = active.getBoundingClientRect();
        const scale = listBox.height > 0 && list.offsetHeight > 0 ? listBox.height / list.offsetHeight : 1;

        list.scrollTop += (activeBox.top - listBox.top) / scale - (list.clientHeight - active.offsetHeight) / 2;

        if (!settled) {
            window.setTimeout(() => scrollToActiveYear(0, true), 300);
        }
    }

    // Birth dates and the like: an empty field opens on the year list around a likely
    // year instead of the current month, so nobody pages back year by year.
    watch(menuOpen, (open) => {
        if (!open) {
            return;
        }

        if (!props.modelValue && props.openYearsAgo > 0) {
            pickerYear.value = new Date().getFullYear() - props.openYearsAgo;
            pickerViewMode.value = 'year';
            nextTick(() => scrollToActiveYear());

            return;
        }

        pickerYear.value = undefined;
        pickerViewMode.value = 'month';
    });
    const controlAttrs = computed(() => overlayControlAttrs(props.fieldAttrs));

    watch(menuOpen, (open, wasOpen) => {
        if (wasOpen && !open) {
            commitOverlayFieldBlur(props.fieldAttrs);
        }
    });

    function resolveBound(value) {
        if (!value) {
            return undefined;
        }

        if (value === 'today' || value === 'yesterday') {
            const date = adapter.date();

            if (value === 'yesterday') {
                const cloned = new Date(date);

                if (!Number.isNaN(cloned.getTime())) {
                    cloned.setDate(cloned.getDate() - 1);

                    return cloned;
                }
            }

            return date;
        }

        const parsed = adapter.parseISO(value);

        return adapter.isValid(parsed) ? parsed : undefined;
    }

    function toLocalDateOnly(value) {
        const date = adapter.date(value);

        if (!adapter.isValid(date)) {
            return '';
        }

        if (date instanceof Date && !Number.isNaN(date.getTime())) {
            return localDateString(date);
        }

        if (date && typeof date.getFullYear === 'function') {
            return localDateString(date);
        }

        return toDateOnlyString(adapter.toISO(date)) || '';
    }

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedPlaceholder = computed(() =>
        props.placeholder !== '' ? props.placeholder : props.label,
    );
    const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
    const resolvedMax = computed(() => resolveBound(props.max));
    const resolvedMin = computed(() => resolveBound(props.min));
    const displayValue = computed(() => {
        if (!props.modelValue) {
            return '';
        }

        const parsed = adapter.parseISO(props.modelValue);

        if (!adapter.isValid(parsed)) {
            return props.modelValue;
        }

        return adapter.format(parsed, 'fullDate');
    });
    const pickerValue = computed(() => {
        if (!props.modelValue) {
            return null;
        }

        const parsed = adapter.parseISO(props.modelValue);

        return adapter.isValid(parsed) ? parsed : null;
    });

    function onPickerUpdate(value) {
        if (!value) {
            emit('update:modelValue', '');
        } else {
            emit('update:modelValue', toLocalDateOnly(value));
        }

        menuOpen.value = false;
        emit('blur');
    }

    function onClear() {
        emit('update:modelValue', '');
        menuOpen.value = false;
        emit('blur');
    }
</script>
