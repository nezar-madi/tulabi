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
            @update:model-value="onMenuUpdate"
        >
            <template #activator="{ props: menuProps }">
                <my-text-field
                    :model-value="displayValue"
                    :placeholder="resolvedPlaceholder"
                    readonly
                    :clearable="!disabled"
                    :disabled="disabled"
                    center-affix
                    prepend-inner-icon="mdi-calendar-multiple"
                    v-bind="{ ...fieldAttrs, ...menuProps }"
                    :error-messages="resolvedErrorMessages"
                    :hint="hint"
                    :persistent-hint="persistentHint"
                    @click:clear="onClear"
                />
            </template>
            <v-date-picker
                v-if="menuOpen"
                :model-value="draftPickerValue"
                color="primary"
                multiple
                :max="resolvedMax"
                :min="resolvedMin"
                :allowed-dates="isDateAllowed"
                @update:model-value="onDraftUpdate"
            />
        </v-menu>
    </div>
</template>

<script setup>
    import { computed, ref } from 'vue';
    import { useI18n } from 'vue-i18n';
    import { useDate } from 'vuetify';
    import './formPlainControl.css';
    import './formPlainTemporalPicker.css';
    import { useResolvedErrorMessages } from '@/Components/formFields/useResolvedErrorMessages';

    const props = defineProps({
        modelValue: {
            type: Array,
            default: () => [],
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
        holidayRanges: {
            type: Array,
            default: () => [],
        },
    });

    const emit = defineEmits(['update:modelValue']);

    const { t } = useI18n();
    const adapter = useDate();

    const menuOpen = ref(false);
    const draftPickerValue = ref([]);

    function resolveBound(value) {
        if (!value) {
            return undefined;
        }

        if (value === 'today') {
            return adapter.date();
        }

        const parsed = adapter.parseISO(value);

        return adapter.isValid(parsed) ? parsed : undefined;
    }

    function normalizeIsoDates(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        const unique = new Set();

        for (const entry of value) {
            if (typeof entry !== 'string' || entry.trim() === '') {
                continue;
            }

            const parsed = adapter.parseISO(entry.trim());

            if (adapter.isValid(parsed)) {
                unique.add(adapter.toISO(parsed).slice(0, 10));
            }
        }

        return [...unique].sort();
    }

    function isoListToPickerValue(isoList) {
        return isoList
            .map((iso) => {
                const parsed = adapter.parseISO(iso);

                return adapter.isValid(parsed) ? parsed : null;
            })
            .filter(Boolean);
    }

    function datesToIsoList(value) {
        if (!value) {
            return [];
        }

        const entries = Array.isArray(value) ? value : [value];

        return normalizeIsoDates(entries.map((entry) => {
            const date = adapter.date(entry);

            return adapter.isValid(date) ? adapter.toISO(date).slice(0, 10) : '';
        })).filter((iso) => !isHolidayIso(iso));
    }

    function toIsoDate(value) {
        if (!value) {
            return '';
        }

        if (typeof value === 'string') {
            return value.slice(0, 10);
        }

        const parsed = adapter.date(value);

        return adapter.isValid(parsed) ? adapter.toISO(parsed).slice(0, 10) : '';
    }

    function isHolidayIso(iso) {
        const date = String(iso ?? '').slice(0, 10);

        if (!date) {
            return false;
        }

        return (props.holidayRanges ?? []).some((range) => {
            const start = String(range?.start_date ?? '').slice(0, 10);
            const end = String(range?.end_date ?? '').slice(0, 10);

            return start !== '' && end !== '' && date >= start && date <= end;
        });
    }

    function isDateAllowed(value) {
        const iso = toIsoDate(value);

        return iso !== '' && !isHolidayIso(iso);
    }

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedPlaceholder = computed(() =>
        props.placeholder !== '' ? props.placeholder : props.label,
    );
    const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
    const resolvedMax = computed(() => resolveBound(props.max));
    const resolvedMin = computed(() => resolveBound(props.min));
    const sortedIsoDates = computed(() => normalizeIsoDates(props.modelValue));
    const displayValue = computed(() => {
        const dates = sortedIsoDates.value;

        if (!dates.length) {
            return '';
        }

        if (dates.length === 1) {
            const parsed = adapter.parseISO(dates[0]);

            if (adapter.isValid(parsed)) {
                return adapter.format(parsed, 'fullDate');
            }

            return dates[0];
        }

        return t('classes.manual_dates_selected', { count: dates.length });
    });

    function loadDraftFromModel() {
        draftPickerValue.value = isoListToPickerValue(sortedIsoDates.value);
    }

    function commitDraftToModel() {
        emit('update:modelValue', datesToIsoList(draftPickerValue.value));
    }

    function onDraftUpdate(value) {
        if (!menuOpen.value) {
            return;
        }

        const next = Array.isArray(value) ? value : value ? [value] : [];

        draftPickerValue.value = next.filter((entry) => !isHolidayIso(toIsoDate(entry)));
    }

    function onClear() {
        emit('update:modelValue', []);
        draftPickerValue.value = [];
        menuOpen.value = false;
    }

    function onMenuUpdate(isOpen) {
        if (isOpen) {
            loadDraftFromModel();
        } else {
            commitDraftToModel();
        }
    }
</script>
