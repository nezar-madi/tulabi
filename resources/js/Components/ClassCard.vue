<template>
    <article class="rq-card class-card">
        <img v-if="cls.image_url" :src="cls.image_url" alt="" class="class-card__image" loading="lazy">
        <div class="class-card__head">
            <span class="rq-badge" :class="cls.type === 'online' ? 'rq-badge--info' : ''">
                <v-icon :icon="cls.type === 'online' ? 'tabler:video' : 'tabler:map-pin'" size="14" class="me-1" />
                {{ $t(`class.type.${cls.type}`) }}
            </span>
            <ClassStatusBadge v-if="showStatus" :status="cls.status" :ended="cls.ended" />
        </div>

        <h3 class="class-card__title">{{ cls.title }}</h3>

        <ul class="class-card__facts">
            <li>
                <v-icon icon="tabler:calendar-event" size="16" />
                {{ formatPeriod(cls, t) }}
            </li>
            <li v-if="schedule">
                <v-icon icon="tabler:clock" size="16" />
                {{ schedule }}
            </li>
            <li v-if="cls.type === 'in_person' && cls.location">
                <v-icon icon="tabler:map-pin" size="16" />
                {{ cls.location }}
            </li>
        </ul>

        <div class="class-card__foot">
            <strong class="class-card__price rq-number">{{ formatPrice(cls, t) }}</strong>
            <slot name="actions" />
        </div>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import ClassStatusBadge from '@/Components/ClassStatusBadge.vue';
import { formatPeriod, formatPrice, formatSchedule } from '@/utils/classFormat';

const props = defineProps({
    cls: { type: Object, required: true },
    showStatus: { type: Boolean, default: false },
});

const { t } = useI18n();
const schedule = computed(() => formatSchedule(props.cls, t));
</script>

<style scoped>
.class-card {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 20px;
    border-radius: var(--rq-radius-card);
}

.class-card__image {
    display: block;
    width: calc(100% + 40px);
    max-width: none;
    margin: -20px -20px 4px;
    aspect-ratio: 16 / 10;
    object-fit: cover;
}

.class-card__head {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.class-card__title {
    margin: 0;
    font-family: var(--rq-font-display);
    font-size: 17px;
    font-weight: 600;
    color: var(--rq-text-heading);
}

.class-card__facts {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 14px;
    color: var(--rq-text-muted);
}

.class-card__facts li {
    display: flex;
    align-items: center;
    gap: 6px;
}

.class-card__foot {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-top: auto;
    padding-top: 10px;
    border-top: 1px solid var(--rq-line-soft);
}

.class-card__price {
    font-family: var(--rq-font-display);
    font-size: 17px;
    color: var(--rq-text-number);
}
</style>
