<template>
    <v-dialog :model-value="modelValue" max-width="440" @update:model-value="emit('update:modelValue', $event)">
        <v-card class="created" role="alertdialog" aria-labelledby="class-created-title" aria-describedby="class-created-text">
            <div class="created__mark" aria-hidden="true">
                <svg viewBox="0 0 52 52" class="created__svg">
                    <circle class="created__ring" cx="26" cy="26" r="24" fill="none" />
                    <path class="created__tick" fill="none" d="M15 27 l7 7 l15 -15" />
                </svg>
            </div>

            <h2 id="class-created-title" class="created__title">{{ $t('teach.classes.created.title') }}</h2>
            <p class="created__name">{{ title }}</p>
            <p id="class-created-text" class="created__text">{{ $t('teach.classes.created.text') }}</p>

            <div class="created__actions">
                <v-btn class="rq-btn" variant="flat" color="primary" block size="large" @click="emit('update:modelValue', false)">
                    <v-icon icon="tabler:pencil" start />
                    {{ $t('teach.classes.created.open') }}
                </v-btn>
                <Link :href="route('teach.classes.create')" class="created__another">
                    <v-icon icon="tabler:plus" size="18" />
                    {{ $t('teach.classes.created.another') }}
                </Link>
            </div>
        </v-card>
    </v-dialog>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

/*
 * Shown once, right after a new class is saved: a check mark, the class's name, and two ways on:
 * stay on its draft (the page behind) or start another class.
 */
defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, required: true },
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

.created__another {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px;
    border-radius: var(--rq-radius-pill);
    font-family: var(--rq-font-display);
    font-size: 14px;
    font-weight: 600;
    color: var(--rq-action-primary);
    text-decoration: none;
    transition: background-color var(--rq-dur-fast, 150ms) var(--rq-ease, ease);
}

.created__another:hover {
    background: var(--rq-icon-tile);
}

.created__another:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
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
