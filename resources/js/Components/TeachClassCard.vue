<template>
    <!-- An instructor's class, drawn like a ticket: the image with a calendar leaf, the facts, a torn edge, the price and tools. -->
    <article class="rq-card tcard" :class="[`tcard--${tone}`, { 'tcard--muted': muted }]">
        <Link :href="route('teach.classes.edit', cls.id)" class="tcard__media" tabindex="-1" aria-hidden="true">
            <img v-if="cls.image_url" :src="cls.image_url" alt="" loading="lazy">
            <span v-else-if="cls.video_embed_url" class="tcard__noimage">
                <v-icon icon="tabler:player-play" size="28" />
                <small>{{ $t('teach.classes.card.video_only') }}</small>
            </span>
            <span v-else class="tcard__noimage">
                <v-icon icon="tabler:photo" size="28" />
                <small>{{ $t('teach.classes.card.no_image') }}</small>
            </span>

            <span class="tcard__chip tcard__chip--type">
                <v-icon :icon="cls.type === 'online' ? 'tabler:video' : 'tabler:map-pin'" size="13" />
                {{ $t(`class.type.${cls.type}`) }}
            </span>
            <span class="tcard__chip tcard__chip--status">{{ statusLabel }}</span>
        </Link>

        <div class="tcard__body">
            <!-- The calendar leaf, overlapping the image's bottom edge -->
            <div class="tcard__leaf" aria-hidden="true">
                <span class="tcard__leaf-month">{{ leaf.month }}</span>
                <span class="tcard__leaf-day rq-number">{{ leaf.day }}</span>
            </div>

            <span v-if="countdown" class="tcard__countdown" :class="`tcard__countdown--${countdown.tone}`">
                <v-icon :icon="countdown.icon" size="14" />
                {{ countdown.text }}
            </span>

            <h3 class="tcard__title">
                <Link :href="route('teach.classes.edit', cls.id)">{{ cls.title }}</Link>
            </h3>

            <ul class="tcard__facts">
                <li>
                    <v-icon icon="tabler:calendar-event" size="15" />
                    {{ formatPeriod(cls, t) }}
                </li>
                <li v-if="schedule">
                    <v-icon icon="tabler:clock" size="15" />
                    {{ schedule }}
                </li>
            </ul>

            <!-- Seats: accepted students against the seats (and the minimum when the class can suspend itself) -->
            <div v-if="cls.status !== 'draft' && cls.status !== 'canceled'" class="tcard__seats">
                <div class="tcard__seats-head">
                    <span>
                        <v-icon icon="tabler:users" size="15" class="tcard__students-icon" />
                        {{ seatsText }}
                    </span>
                    <Link
                        v-if="cls.requested_count > 0"
                        :href="route('teach.classes.students', cls.id)"
                        class="tcard__requests"
                    >
                        {{ $t('teach.classes.card.requests', { count: cls.requested_count }) }}
                    </Link>
                </div>
                <span v-if="cls.seats" class="tcard__meter" :class="{ 'tcard__meter--full': fill >= 100 }">
                    <span class="tcard__meter-fill" :style="{ width: `${fill}%` }" />
                    <span
                        v-if="cls.auto_suspend && cls.min_seats"
                        class="tcard__meter-min"
                        :style="{ insetInlineStart: `${Math.min(100, (cls.min_seats / cls.seats) * 100)}%` }"
                        :title="$t('teach.classes.card.min', { count: cls.min_seats })"
                    />
                </span>
            </div>
        </div>

        <!-- The ticket's torn edge -->
        <div class="tcard__tear" aria-hidden="true" />

        <footer class="tcard__foot">
            <strong class="tcard__price rq-number" :class="{ 'tcard__price--paid': Number(cls.price) > 0 }">{{ formatPrice(cls, t) }}</strong>

            <div class="tcard__tools">
                <v-btn
                    v-if="cls.status !== 'draft'"
                    icon
                    variant="text"
                    size="small"
                    class="tcard__tool tcard__tool--students"
                    :aria-label="$t('teach.students.open')"
                    @click="router.visit(route('teach.classes.students', cls.id))"
                >
                    <v-icon icon="tabler:users" size="18" />
                    <v-tooltip activator="parent" location="top">{{ $t('teach.students.open') }}</v-tooltip>
                </v-btn>
                <v-btn
                    v-if="publicUrl"
                    icon
                    variant="text"
                    size="small"
                    class="tcard__tool"
                    :href="publicUrl"
                    target="_blank"
                    rel="noopener"
                    :aria-label="$t('teach.classes.card.view')"
                >
                    <v-icon icon="tabler:external-link" size="18" />
                    <v-tooltip activator="parent" location="top">{{ $t('teach.classes.card.view') }}</v-tooltip>
                </v-btn>
                <v-btn
                    icon
                    variant="text"
                    size="small"
                    class="tcard__tool"
                    :aria-label="$t('teach.classes.edit')"
                    @click="router.visit(route('teach.classes.edit', cls.id))"
                >
                    <v-icon icon="tabler:pencil" size="18" />
                    <v-tooltip activator="parent" location="top">{{ $t('teach.classes.edit') }}</v-tooltip>
                </v-btn>
            </div>
        </footer>
    </article>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { formatPeriod, formatPrice, formatSchedule } from '@/utils/classFormat';

const props = defineProps({
    cls: { type: Object, required: true },
    slug: { type: String, default: null },
    /** Today in Libya, "Y-m-d" (from the server). */
    today: { type: String, required: true },
});

const { t } = useI18n();

const schedule = computed(() => formatSchedule(props.cls, t));

// Visitors can open it only when it is listed (published/closed) and has its image.
const publicUrl = computed(() => (props.slug && props.cls.image_url && ['published', 'closed'].includes(props.cls.status)
    ? route('classes.show', { slug: props.slug, class: props.cls.id })
    : null));

const tone = computed(() => {
    if (props.cls.status === 'canceled') {
        return 'danger';
    }

    if (props.cls.ended || props.cls.status === 'draft') {
        return 'neutral';
    }

    return { published: 'success', closed: 'info', suspended: 'warning' }[props.cls.status] || 'neutral';
});
const muted = computed(() => props.cls.status === 'canceled' || props.cls.ended);

const statusLabel = computed(() => (props.cls.ended && props.cls.status !== 'canceled'
    ? t('class.ended')
    : t(`class.status.${props.cls.status}`)));

// "Y-m-d" at noon UTC stays the same calendar day in Tripoli.
const leaf = computed(() => {
    if (!props.cls.starts_on) {
        return { month: '—', day: '؟' };
    }

    const date = new Date(`${props.cls.starts_on}T12:00:00Z`);

    return {
        month: new Intl.DateTimeFormat('ar-LY', { month: 'long', timeZone: 'Africa/Tripoli' }).format(date),
        day: String(date.getUTCDate()),
    };
});

function daysBetween(from, to) {
    return Math.round((Date.parse(`${to}T00:00:00Z`) - Date.parse(`${from}T00:00:00Z`)) / 86400000);
}

const countdown = computed(() => {
    if (!props.cls.starts_on || props.cls.status === 'canceled' || props.cls.status === 'draft') {
        return null;
    }

    if (props.cls.ended) {
        return { tone: 'done', icon: 'tabler:circle-check', text: t('teach.classes.card.ended') };
    }

    const days = daysBetween(props.today, props.cls.starts_on);

    if (days > 1) {
        // Arabic counting: يومين، 3-10 أيام، 11+ يومًا.
        const key = days === 2 ? 'in_two_days' : (days <= 10 ? 'in_days_few' : 'in_days');

        return { tone: 'soon', icon: 'tabler:hourglass', text: t(`teach.classes.card.${key}`, { count: days }) };
    }

    if (days === 1) {
        return { tone: 'soon', icon: 'tabler:hourglass', text: t('teach.classes.card.tomorrow') };
    }

    if (days === 0) {
        return { tone: 'now', icon: 'tabler:player-play', text: t('teach.classes.card.today') };
    }

    return { tone: 'now', icon: 'tabler:player-play', text: t('teach.classes.card.running') };
});

const accepted = computed(() => props.cls.accepted_count ?? 0);
const fill = computed(() => (props.cls.seats ? Math.min(100, Math.round((accepted.value / props.cls.seats) * 100)) : 0));
const seatsText = computed(() => (props.cls.seats
    ? t('teach.classes.card.seats_of', { count: accepted.value, seats: props.cls.seats })
    : t('teach.classes.card.seats_open', { count: accepted.value })));
</script>

<style scoped>
.tcard {
    --tcard-accent: var(--rq-text-subtle);
    --tcard-accent-tint: var(--rq-surface-tint);
    position: relative;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: var(--rq-radius-card);
    transition: box-shadow var(--rq-dur) var(--rq-ease), transform var(--rq-dur) var(--rq-ease);
}

.tcard:hover {
    box-shadow: 0 10px 28px -18px rgb(15 23 42 / 0.45);
    transform: translateY(-2px);
}

.tcard--success { --tcard-accent: var(--rq-success); --tcard-accent-tint: var(--rq-success-tint); }
.tcard--info { --tcard-accent: var(--rq-info); --tcard-accent-tint: var(--rq-info-tint); }
.tcard--warning { --tcard-accent: var(--rq-warning); --tcard-accent-tint: var(--rq-warning-tint); }
.tcard--danger { --tcard-accent: var(--rq-danger); --tcard-accent-tint: var(--rq-danger-tint); }

.tcard--muted .tcard__media img {
    filter: grayscale(0.7);
}

/* ---------- Image and its chips ---------- */
.tcard__media {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    aspect-ratio: 2 / 1;
    overflow: hidden;
    background: var(--rq-surface-tint);
    color: var(--rq-text-subtle);
    text-decoration: none;
}

.tcard__media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform var(--rq-dur) var(--rq-ease);
}

.tcard:hover .tcard__media img {
    transform: scale(1.04);
}

.tcard__noimage {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    font-size: 12px;
}

.tcard__chip {
    position: absolute;
    top: 10px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: var(--rq-radius-pill);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.5;
    color: #fff;
    backdrop-filter: blur(6px);
}

.tcard__chip--type {
    inset-inline-start: 10px;
    background: rgb(15 23 42 / 0.6);
}

.tcard__chip--status {
    inset-inline-end: 10px;
    background: var(--tcard-accent);
}

.tcard--neutral .tcard__chip--status {
    background: rgb(15 23 42 / 0.6);
}

/* ---------- The calendar leaf ---------- */
.tcard__leaf {
    position: absolute;
    top: 0;
    inset-inline-end: 16px;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 50px;
    padding: 4px 0 3px;
    overflow: hidden;
    border-radius: 10px;
    background: var(--rq-surface);
    box-shadow: 0 6px 16px -8px rgb(15 23 42 / 0.45);
    transform: translateY(-50%);
}

.tcard__leaf-month {
    font-size: 10px;
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
    color: var(--rq-text-muted);
}

.tcard__leaf-day {
    font-family: var(--rq-font-display);
    font-size: 19px;
    font-weight: 700;
    line-height: 1.15;
    color: var(--rq-text-heading);
}

/* ---------- Body ---------- */
.tcard__body {
    position: relative;
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 6px;
    padding: 12px 16px 14px;
}

.tcard__countdown {
    min-height: 18px;
    display: inline-flex;
    align-items: center;
    align-self: flex-start;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
}

.tcard__countdown--soon { color: var(--rq-text-link); }
.tcard__countdown--now { color: var(--rq-success); }
.tcard__countdown--done { color: var(--rq-text-subtle); }

.tcard__title {
    margin: 0;
    padding-inline-end: 58px;
    font-family: var(--rq-font-display);
    font-size: 16px;
    font-weight: 600;
    line-height: 1.5;
}

.tcard__title a {
    color: var(--rq-text-heading);
    text-decoration: none;
}

.tcard__title a:hover {
    color: var(--rq-text-link);
}

.tcard__facts {
    display: flex;
    flex-direction: column;
    gap: 3px;
    margin: 0;
    padding: 0;
    list-style: none;
    font-size: 13px;
    color: var(--rq-text-muted);
}

.tcard__facts li {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ---------- Seats ---------- */
.tcard__seats {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: auto;
    padding-top: 6px;
}

.tcard__seats-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 13px;
    color: var(--rq-text-body);
}

.tcard__seats-head > span {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.tcard__requests {
    padding: 1px 9px;
    border-radius: var(--rq-radius-pill);
    background: var(--rq-warning-tint);
    font-size: 12px;
    font-weight: 600;
    color: var(--rq-warning);
    text-decoration: none;
}

.tcard__requests:hover {
    text-decoration: underline;
}

.tcard__meter {
    position: relative;
    display: block;
    height: 6px;
    border-radius: 999px;
    background: var(--rq-surface-tint);
}

.tcard__meter-fill {
    position: absolute;
    inset-block: 0;
    inset-inline-start: 0;
    border-radius: inherit;
    background: var(--rq-action-primary);
    transition: width var(--rq-dur) var(--rq-ease);
}

.tcard__meter--full .tcard__meter-fill {
    background: var(--rq-success);
}

.tcard__meter-min {
    position: absolute;
    top: -3px;
    width: 2px;
    height: 12px;
    border-radius: 1px;
    background: var(--rq-warning);
    transform: translateX(50%);
}

/* ---------- Torn edge: a dashed line with a notch cut on each side ---------- */
.tcard__tear {
    position: relative;
    height: 0;
    margin-inline: 14px;
    border-top: 1.5px dashed var(--rq-line-strong);
}

.tcard__tear::before,
.tcard__tear::after {
    position: absolute;
    top: -9px;
    width: 18px;
    height: 18px;
    border: 1px solid var(--rq-line);
    border-radius: 50%;
    background: var(--rq-bg-page);
    content: "";
}

.tcard__tear::before { inset-inline-start: -24px; }
.tcard__tear::after { inset-inline-end: -24px; }

/* ---------- Footer ---------- */
.tcard__foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding-block: 6px;
    padding-inline: 16px 6px;
}

.tcard__price {
    font-family: var(--rq-font-display);
    font-size: 17px;
    font-weight: 700;
    color: var(--rq-text-number);
}

/* A paid class: the amount in green («مجاني» stays plain). */
.tcard__price--paid {
    color: var(--rq-success);
}

/* Students, in the violet of «من برنامج تدريبي» on the class form. */
.tcard__students-icon,
.tcard__tool.tcard__tool--students {
    color: var(--rq-accent-violet);
}

.tcard__tools {
    display: flex;
    gap: 2px;
}

.tcard__tool {
    color: var(--rq-text-muted);
}

.tcard__tool:hover {
    color: var(--rq-text-link);
}
</style>
