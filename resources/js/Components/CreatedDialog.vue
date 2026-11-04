<template>
    <v-dialog :model-value="modelValue" max-width="440" @update:model-value="emit('update:modelValue', $event)">
        <v-card class="created" role="alertdialog" aria-labelledby="class-created-title" aria-describedby="class-created-text">
            <div class="created__mark" aria-hidden="true">
                <svg viewBox="0 0 52 52" class="created__svg">
                    <circle class="created__ring" cx="26" cy="26" r="24" fill="none" />
                    <path class="created__tick" fill="none" d="M15 27 l7 7 l15 -15" />
                </svg>
            </div>

            <h2 id="class-created-title" class="created__title">{{ heading }}</h2>
            <p class="created__name">{{ title }}</p>
            <p id="class-created-text" class="created__text">{{ text }}</p>

            <div class="created__actions">
                <v-btn class="rq-btn" variant="flat" color="primary" block size="large" autofocus @click="emit('update:modelValue', false)">
                    <v-icon icon="tabler:pencil" start />
                    {{ openLabel }}
                </v-btn>
                <v-btn
                    class="rq-btn"
                    variant="text"
                    color="primary"
                    block
                    size="large"
                    @click="router.visit(anotherHref)"
                >
                    <v-icon icon="tabler:plus" start />
                    {{ anotherLabel }}
                </v-btn>
            </div>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

/*
 * Shown once, right after a new class or program is saved: a check mark, its name, and two ways
 * on: stay on its page (the one behind) or start another.
 */
defineProps({
    modelValue: { type: Boolean, default: false },
    // The name of what was just created.
    title: { type: String, required: true },
    heading: { type: String, required: true },
    text: { type: String, required: true },
    openLabel: { type: String, required: true },
    anotherLabel: { type: String, required: true },
    anotherHref: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);
</script>

<style scoped>
.created {
    padding: 32px 28px 24px;
    border-radius: var(--rq-radius-lg, 16px) !important;
    text-align: center;
}

.created__mark {
    display: grid;
    place-items: center;
    width: 84px;
    height: 84px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: var(--rq-success-tint);
    animation: created-pop 420ms var(--rq-ease, ease-out) both;
}

.created__svg {
    width: 52px;
    height: 52px;
}

.created__ring {
    stroke: var(--rq-success);
    stroke-width: 2.5;
    stroke-dasharray: 151;
    stroke-dashoffset: 151;
    animation: created-draw 520ms ease-out 120ms forwards;
}

.created__tick {
    stroke: var(--rq-success);
    stroke-width: 4;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 34;
    stroke-dashoffset: 34;
    animation: created-draw 320ms ease-out 560ms forwards;
}

.created__title {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 21px;
    font-weight: 700;
    color: var(--rq-text-heading);
}

.created__name {
    margin: 6px 0 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--rq-text-link);
    overflow-wrap: anywhere;
}

.created__text {
    margin: 10px auto 22px;
    max-width: 320px;
    font-size: 14px;
    line-height: 1.8;
    color: var(--rq-text-muted);
}

.created__actions {
    display: flex;
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
}

@keyframes created-draw {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes created-pop {
    from {
        opacity: 0;
        transform: scale(0.6);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .created__mark,
    .created__ring,
    .created__tick {
        animation: none;
        stroke-dashoffset: 0;
    }
}
</style>
