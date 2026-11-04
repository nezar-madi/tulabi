<template>
    <div class="pmc">
        <!-- One of two, never both: tap the chosen one again to have neither -->
        <div class="pmc__choices" role="radiogroup" :aria-label="$t('content.media_label')">
            <button
                v-for="option in options"
                :key="option.value"
                type="button"
                role="radio"
                class="pmc__choice"
                :class="{ 'pmc__choice--on': modelValue === option.value }"
                :aria-checked="modelValue === option.value"
                :disabled="readOnly"
                @click="emit('update:modelValue', modelValue === option.value ? '' : option.value)"
            >
                <span class="rq-icon-tile pmc__tile" :class="option.tile"><v-icon :icon="option.icon" /></span>
                <span class="pmc__text">
                    <strong>{{ $t(`content.media_${option.value}`) }}</strong>
                    <small>{{ $t(`content.media_${option.value}_hint`) }}</small>
                </span>
                <v-icon v-if="modelValue === option.value" icon="tabler:circle-check" class="pmc__check" />
            </button>
        </div>

        <!-- The image: each form brings its own picker (it keeps the chosen file and the saved one) -->
        <div v-if="modelValue === 'image'" class="pmc__body">
            <slot name="image" />
        </div>

        <!-- The video: a link, shown embedded on the public page -->
        <div v-else-if="modelValue === 'video'" class="pmc__body">
            <FormPlainTextField
                v-model="form.promo_video_url"
                :label="$t('content.video')"
                placeholder="https://www.youtube.com/watch?v=..."
                :disabled="readOnly"
                :field-attrs="{ name: 'promo_video_url', dir: 'ltr', inputmode: 'url' }"
                :error-messages="form.errors.promo_video_url"
            />
            <p class="teach-hint mt-1">{{ $t('content.video_hint') }}</p>
        </div>

        <!-- An error about the choice itself (a public class needs one of them) -->
        <small v-if="modelValue !== 'image' && form.errors.image" class="text-error d-block mt-2">{{ form.errors.image }}</small>
        <small v-if="modelValue !== 'video' && form.errors.promo_video_url" class="text-error d-block mt-2">{{ form.errors.promo_video_url }}</small>
    </div>
</template>

<script setup>
import FormPlainTextField from '@/Components/formFields/FormPlainTextField.vue';

/*
 * A promotional image or a promotional video for a class or a program: one of them, never both,
 * and neither is fine until the class or program is shown to visitors. v-model is the choice
 * ('image', 'video' or ''); the form (a useForm with promo_video_url) holds the video link.
 */
defineProps({
    modelValue: { type: String, default: '' },
    form: { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const options = [
    { value: 'image', icon: 'tabler:photo', tile: 'rq-icon-tile--teal' },
    { value: 'video', icon: 'tabler:video', tile: 'rq-icon-tile--sky' },
];
</script>

<style scoped>
.pmc__choices {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.pmc__choice {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    padding: 12px 14px;
    border: 1.5px solid var(--rq-line);
    border-radius: var(--rq-radius-md);
    background: var(--rq-surface);
    font: inherit;
    text-align: start;
    color: var(--rq-text-body);
    cursor: pointer;
    transition: border-color var(--rq-dur-fast) var(--rq-ease), background-color var(--rq-dur-fast) var(--rq-ease);
}

.pmc__choice:hover:not(:disabled) {
    border-color: var(--rq-line-strong);
    background: var(--rq-surface-hover);
}

.pmc__choice:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

.pmc__choice:disabled {
    cursor: default;
    opacity: 0.6;
}

.pmc__choice--on,
.pmc__choice--on:hover:not(:disabled) {
    border-color: var(--rq-action-primary);
    background: var(--rq-icon-tile);
}

.pmc__tile {
    flex-shrink: 0;
}

.pmc__text {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}

.pmc__text strong {
    font-family: var(--rq-font-display);
    font-size: 15px;
    font-weight: 600;
    color: var(--rq-text-heading);
}

.pmc__text small {
    font-size: 12px;
    line-height: 1.5;
    color: var(--rq-text-muted);
}

.pmc__check {
    flex-shrink: 0;
    color: var(--rq-success);
}

.pmc__body {
    margin-top: 16px;
}

@media (max-width: 599.98px) {
    .pmc__choices {
        grid-template-columns: minmax(0, 1fr);
    }
}
</style>
