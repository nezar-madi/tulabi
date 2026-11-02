<template>
    <div
        class="form-plain-field form-plain-field--select"
        :class="{ 'form-plain-field--multiple': multiple && chips }"
    >
        <label
            v-if="hasLabel"
            class="form-plain-field__label d-inline-block pb-4 font-weight-medium"
        >
            <span class="me-1">{{ label }}</span>
            <span v-if="showRequiredMark" class="text-error">*</span>
        </label>
        <v-select
            v-if="!searchable"
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            v-bind="controlAttrs"
            :placeholder="placeholder"
            :disabled="disabled"
            :items="items"
            :item-title="itemTitle"
            :item-value="itemValue"
            :error-messages="resolvedErrorMessages"
            :hint="hint"
            :persistent-hint="persistentHint"
            :clearable="clearable"
            :menu-props="menuProps"
            :loading="loading"
            :no-data-text="noDataText || undefined"
            :multiple="multiple"
            :chips="chips"
            :closable-chips="closableChips"
            center-affix
            density="comfortable"
            variant="plain"
            class="form-plain-field__control"
            @update:menu="onMenuUpdate"
            @blur="emit('blur', $event)"
        />
        <v-autocomplete
            v-else
            :model-value="modelValue"
            @update:model-value="emit('update:modelValue', $event)"
            v-bind="controlAttrs"
            :placeholder="placeholder"
            :disabled="disabled"
            :items="items"
            :item-title="itemTitle"
            :item-value="itemValue"
            :error-messages="resolvedErrorMessages"
            :hint="hint"
            :persistent-hint="persistentHint"
            :clearable="clearable"
            :menu-props="menuProps"
            :loading="loading"
            :no-data-text="noDataText || undefined"
            :multiple="multiple"
            :chips="chips"
            :closable-chips="closableChips"
            :custom-filter="remoteSearch ? allowAllItems : undefined"
            :auto-select-first="false"
            center-affix
            density="comfortable"
            variant="plain"
            class="form-plain-field__control"
            @update:search="onSearch"
            @update:menu="onMenuUpdate"
            @blur="emit('blur', $event)"
        />
    </div>
</template>

<script setup>
    import { computed } from 'vue';
    import './formPlainControl.css';
    import './formPlainSelectOverlay.css';
    import { useResolvedErrorMessages } from '@/Components/formFields/useResolvedErrorMessages';
    import { commitOverlayFieldBlur, overlayControlAttrs } from '@/Components/formFields/plainFieldValidation';

    const props = defineProps({
        modelValue: {
            type: [String, Number, Array, null],
            default: null,
        },
        label: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        required: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        items: {
            type: Array,
            required: true,
        },
        itemTitle: {
            type: String,
            default: 'title',
        },
        itemValue: {
            type: String,
            default: 'value',
        },
        errorMessages: {
            type: [String, Array, Object],
            default: () => [],
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
        searchable: {
            type: Boolean,
            default: false,
        },
        // The parent filters the items itself (usually on the server) from update:search;
        // otherwise typing filters the items in the list.
        remoteSearch: {
            type: Boolean,
            default: false,
        },
        clearable: {
            type: Boolean,
            default: false,
        },
        multiple: {
            type: Boolean,
            default: false,
        },
        chips: {
            type: Boolean,
            default: false,
        },
        closableChips: {
            type: Boolean,
            default: false,
        },
        loading: {
            type: Boolean,
            default: false,
        },
        noDataText: {
            type: String,
            default: '',
        },
    });

    const emit = defineEmits(['update:modelValue', 'update:search', 'blur']);

    function allowAllItems() {
        return true;
    }

    function onSearch(value) {
        emit('update:search', value ?? '');
    }

    const menuProps = {
        location: 'bottom start',
        scrollStrategy: 'reposition',
        zIndex: 2600,
    };

    const controlAttrs = computed(() => overlayControlAttrs(props.fieldAttrs));

    function onMenuUpdate(open) {
        if (!open) {
            commitOverlayFieldBlur(props.fieldAttrs);
        }
    }

    const hasLabel = computed(() => props.label.trim() !== '');
    const showRequiredMark = computed(() => props.required && hasLabel.value);
    const resolvedErrorMessages = useResolvedErrorMessages(() => props.errorMessages);
</script>
