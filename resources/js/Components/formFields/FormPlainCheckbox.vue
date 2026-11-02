<template>
    <v-checkbox
        :model-value="modelValue"
        @update:model-value="emit('update:modelValue', $event)"
        :label="label"
        class="form-plain-checkbox"
        :density="density"
        :hide-details="hideDetails"
        :color="color"
        :disabled="disabled"
        :readonly="readonly"
        :error-messages="errorMessages"
    >
        <template #input="{ inputNode }">
            <div
                class="form-plain-checkbox__input"
                :class="{ 'form-plain-checkbox__input--checked': modelValue }"
            >
                <span class="form-plain-checkbox__box" aria-hidden="true" />
                <svg
                    class="form-plain-checkbox__check"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
                <VnodeRenderer :vnode="inputNode" />
            </div>
        </template>
    </v-checkbox>
</template>

<script setup>

    import './formPlainCheckbox.css';

    const VnodeRenderer = {
        name: 'VnodeRenderer',
        props: {
            vnode: {
                type: Object,
                required: true,
            },
        },
        render() {
            return this.vnode;
        },
    };

    defineProps({
        modelValue: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
            default: '',
        },
        density: {
            type: String,
            default: 'compact',
        },
        hideDetails: {
            type: Boolean,
            default: true,
        },
        color: {
            type: String,
            default: 'primary',
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        readonly: {
            type: Boolean,
            default: false,
        },
        errorMessages: {
            type: [String, Array],
            default: () => [],
        },
    });

    const emit = defineEmits(['update:modelValue']);

</script>
