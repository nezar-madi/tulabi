<template>
    <v-btn
        :type="type"
        :form="form"
        :color="color"
        :variant="variant"
        :rounded="rounded"
        :elevation="elevation"
        :ripple="ripple"
        class="app-cta-button text-none"
        :class="ctaButtonClass"
        :loading="loading"
        :disabled="disabled"
        :block="block"
        @click="emit('click', $event)"
    >

        <template v-if="icon" #prepend>
            <v-icon :icon="icon" size="16" />
        </template>

        <span class="app-cta-button__label">
            <slot />
        </span>

    </v-btn>
</template>

<script setup>

    import { computed } from 'vue';

    const props = defineProps({
        type: {
            type: String,
            default: 'button',
        },
        form: {
            type: String,
            default: undefined,
        },
        color: {
            type: String,
            default: 'primary',
        },
        variant: {
            type: String,
            default: 'flat',
        },
        icon: {
            type: String,
            default: 'mdi-plus',
        },
        loading: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        block: {
            type: Boolean,
            default: false,
        },
        elevation: {
            type: [Number, String],
            default: 0,
        },
        textHoverEffect: {
            type: Boolean,
            default: false,
        },
        rounded: {
            type: String,
            default: 'lg',
        },
        ripple: {
            type: Boolean,
            default: false,
        },
    });

    const emit = defineEmits(['click']);

    const ctaButtonClass = computed(() => {
        return {
            'app-cta-button--text-no-hover':
                props.variant === 'text' && !props.textHoverEffect,
        };
    });

</script>

<style scoped>
    /* Raqmana button: 4px corners, display font, flat. Hover changes colour only — no shadow, no scale. */
    .app-cta-button {
        min-height: 40px;
        padding-inline: 18px !important;
        padding-block: 10px !important;
        border-radius: var(--rq-radius-sm) !important;
        font-family: var(--rq-font-display);
        font-weight: 600;
        font-size: 13px;
        line-height: 1.2;
        letter-spacing: 0;
        box-shadow: none !important;
        transition:
            background-color var(--rq-dur) var(--rq-ease),
            color var(--rq-dur) var(--rq-ease),
            border-color var(--rq-dur) var(--rq-ease);
    }

    .app-cta-button.v-btn--size-small {
        min-height: 32px;
        padding-inline: 12px !important;
        padding-block: 6px !important;
        font-size: 12px;
    }

    .app-cta-button__label {
        font: inherit;
    }

    /* Primary solid: exact brand blue, darker on hover (instead of Vuetify's overlay tint) */
    .app-cta-button.v-btn--variant-flat.bg-primary {
        background-color: var(--rq-action-primary) !important;
        color: var(--rq-text-on-primary) !important;
    }

    .app-cta-button.v-btn--variant-flat.bg-primary:hover {
        background-color: var(--rq-action-primary-hover) !important;
    }

    .app-cta-button.v-btn--variant-flat :deep(.v-btn__overlay) {
        display: none;
    }

    .app-cta-button.v-btn--variant-flat:not(.bg-primary):hover {
        filter: brightness(0.92);
    }

    /* Tonal: light brand tile */
    .app-cta-button.v-btn--variant-tonal {
        background: var(--rq-surface-hover);
    }

    .app-cta-button.v-btn--variant-tonal :deep(.v-btn__underlay),
    .app-cta-button.v-btn--variant-tonal :deep(.v-btn__overlay) {
        display: none;
    }

    .app-cta-button.v-btn--variant-tonal:hover {
        background: var(--rq-icon-tile);
        box-shadow: inset 0 0 0 1px var(--rq-line) !important;
    }

    .app-cta-button.v-btn--variant-tonal.text-primary {
        color: var(--rq-text-heading) !important;
    }

    /* Outlined: navy hairline */
    .app-cta-button.v-btn--variant-outlined {
        border-color: var(--rq-line-strong);
    }

    .app-cta-button.v-btn--variant-outlined:hover {
        border-color: currentColor;
    }

    /* Text: quiet by default; opt-in light tint on hover */
    .app-cta-button.v-btn--variant-text :deep(.v-btn__overlay) {
        display: none;
    }

    .app-cta-button.v-btn--variant-text:not(.app-cta-button--text-no-hover):hover {
        background: var(--rq-surface-hover);
    }

    .app-cta-button.v-btn--disabled {
        opacity: 0.45;
    }
</style>
