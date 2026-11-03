<template>
    <Head :title="$t('teach.classes.title')" />

    <PageIntro :title="$t('teach.classes.title')">
        <template #actions>
            <CtaButton icon="tabler:plus" @click="router.visit(route('teach.classes.create'))">
                {{ $t('teach.classes.new') }}
            </CtaButton>
        </template>
    </PageIntro>

    <v-alert v-if="!hasSlug" type="info" variant="tonal" density="comfortable" class="mb-4" rounded="lg">
        {{ $t('teach.classes.needs_slug') }}
        <Link :href="route('teach.profile.edit')">{{ $t('teach.nav.profile') }}</Link>
    </v-alert>

    <!-- Tabs, with the search on the same line. Filters live in the address, so a filtered list can be reloaded or shared. -->
    <div class="cl-head">
        <div class="teach-tabs classes-tabs">
            <button
                v-for="tab in tabs"
                :key="tab"
                type="button"
                class="teach-tabs__tab"
                :class="{ 'teach-tabs__tab--active': filter === tab }"
                @click="apply({ filter: tab })"
            >
                {{ $t(`teach.classes.filters.${tab}`) }}
            </button>
        </div>

        <div class="cl-search">
            <v-icon icon="tabler:search" size="17" class="cl-search__icon" />
            <input
                v-model="search"
                type="search"
                class="cl-search__input"
                :placeholder="$t('teach.classes.narrow.search')"
                :aria-label="$t('teach.classes.narrow.search')"
                @input="searchSoon(search)"
            >
        </div>
    </div>

    <!-- Filter chips, then the number of classes found -->
    <div class="cl-toolbar">
        <div class="cl-chips">
            <FilterChip
                :label="$t('teach.classes.narrow.when')"
                icon="tabler:hourglass"
                :items="whenItems"
                :model-value="narrow.when || null"
                @update:model-value="apply({ when: $event })"
            />
            <FilterChip
                :label="$t('teach.classes.narrow.dates')"
                icon="tabler:calendar-event"
                :display="datesText"
            >
                <div class="cl-dates">
                    <FormPlainDatePicker
                        :model-value="narrow.from || ''"
                        :label="$t('teach.classes.narrow.from')"
                        :max="narrow.to || undefined"
                        @update:model-value="apply({ from: $event })"
                    />
                    <FormPlainDatePicker
                        :model-value="narrow.to || ''"
                        :label="$t('teach.classes.narrow.to')"
                        :min="narrow.from || undefined"
                        @update:model-value="apply({ to: $event })"
                    />
                    <button v-if="narrow.from || narrow.to" type="button" class="cl-link" @click="apply({ from: undefined, to: undefined })">
                        {{ $t('teach.classes.narrow.clear_dates') }}
                    </button>
                </div>
            </FilterChip>
            <FilterChip
                :label="$t('teach.classes.narrow.price')"
                icon="tabler:cash"
                :items="priceItems"
                :model-value="narrow.price || null"
                @update:model-value="apply({ price: $event })"
            />
            <FilterChip
                :label="$t('teach.classes.narrow.type')"
                icon="tabler:map-pin"
                :items="typeItems"
                :model-value="narrow.type || null"
                @update:model-value="apply({ type: $event })"
            />
            <FilterChip
                v-if="programs.length"
                :label="$t('teach.classes.narrow.program')"
                icon="tabler:layout-grid"
                :items="programItems"
                :model-value="narrow.program || null"
                @update:model-value="apply({ program: $event })"
            />

            <button v-if="filtering" type="button" class="cl-link" @click="clearAll">
                <v-icon icon="tabler:x" size="15" />
                {{ $t('teach.classes.narrow.clear') }}
            </button>
        </div>

        <span class="cl-count">{{ countText }}</span>
    </div>

    <p v-if="classes.data.length === 0" class="rq-card teach-empty">
        {{ filtering ? $t('teach.classes.narrow.empty') : $t('teach.classes.empty') }}
    </p>

    <div class="class-grid">
        <TeachClassCard v-for="cls in classes.data" :key="cls.id" :cls="cls" :slug="slug" :today="today" />
    </div>

    <Pager :paginator="classes" />
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CtaButton from '@/Components/CtaButton.vue';
import PageIntro from '@/Components/PageIntro.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TeachClassCard from '@/Components/TeachClassCard.vue';
import Pager from '@/Components/Pager.vue';
import FilterChip from '@/Components/FilterChip.vue';
import FormPlainDatePicker from '@/Components/formFields/FormPlainDatePicker.vue';
import { formatDay } from '@/utils/classFormat';

const props = defineProps({
    filter: { type: String, required: true },
    // The filters in use: q, when, from, to, price, type, program.
    narrow: { type: Object, default: () => ({}) },
    programs: { type: Array, default: () => [] },
    classes: { type: Object, required: true },
    hasSlug: { type: Boolean, default: false },
    slug: { type: String, default: null },
    today: { type: String, required: true },
});

const { t } = useI18n();

const tabs = ['active', 'draft', 'canceled'];
const KEYS = ['q', 'when', 'from', 'to', 'price', 'type', 'program'];

// Each chip lists its choices; picking the chosen one again clears it.
const whenItems = computed(() => ['upcoming', 'running', 'ended'].map((value) => ({ title: t(`teach.classes.narrow.when_${value}`), value })));
const priceItems = computed(() => ['paid', 'free'].map((value) => ({ title: t(`teach.classes.narrow.price_${value}`), value })));
const typeItems = computed(() => ['in_person', 'online'].map((value) => ({ title: t(`class.type.${value}`), value })));
const programItems = computed(() => [
    ...props.programs.map((program) => ({ title: program.name, value: String(program.id) })),
    { title: t('teach.classes.narrow.no_program'), value: 'none' },
]);

// Arabic counting: لا فصول، فصل واحد، فصلان، 3-10 فصول، 11+ فصلًا.
const countText = computed(() => {
    const count = props.classes.total ?? 0;
    const key = count <= 2 ? `count_${count}` : (count <= 10 ? 'count_few' : 'count_many');

    return t(`teach.classes.narrow.${key}`, { count });
});

// "1 ديسمبر – 31 يناير", "من 1 ديسمبر" or "حتى 31 يناير".
const datesText = computed(() => {
    const { from, to } = props.narrow;

    if (from && to) {
        return `${formatDay(from)} – ${formatDay(to)}`;
    }

    if (from) {
        return t('teach.classes.narrow.since', { date: formatDay(from) });
    }

    return to ? t('teach.classes.narrow.until', { date: formatDay(to) }) : '';
});

const filtering = computed(() => KEYS.some((key) => props.narrow[key]));

function apply(change) {
    const next = { filter: props.filter, ...props.narrow, ...change };
    const params = { filter: next.filter === 'active' ? undefined : next.filter };

    KEYS.forEach((key) => {
        params[key] = next[key] || undefined;
    });

    router.get(route('teach.classes.index'), params, { preserveState: true, preserveScroll: true, replace: true });
}

function clearAll() {
    search.value = '';
    apply(Object.fromEntries(KEYS.map((key) => [key, undefined])));
}

const search = ref(props.narrow.q || '');
let timer = null;

function searchSoon(value) {
    clearTimeout(timer);
    timer = setTimeout(() => apply({ q: value || undefined }), 350);
}

onBeforeUnmount(() => clearTimeout(timer));
</script>

<style scoped src="../teach.css"></style>

<style scoped>
/* The tabs are buttons here (they keep the filters in the address). */
.classes-tabs .teach-tabs__tab {
    border-top: 0;
    border-inline: 0;
    background: none;
    cursor: pointer;
}

/* ---------- Tabs and search on one line, under it the chips and the count ---------- */
.cl-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 16px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--rq-line-soft);
}

.cl-head .teach-tabs {
    flex: 1 1 auto;
    margin-bottom: -1px;
    border-bottom: 0;
}

.cl-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 12px;
    margin-bottom: var(--rq-space-5);
}

.cl-search {
    position: relative;
    flex: 0 1 280px;
    min-width: 200px;
    margin-bottom: 6px;
}

.cl-search__icon {
    position: absolute;
    top: 50%;
    inset-inline-start: 12px;
    color: var(--rq-text-subtle);
    transform: translateY(-50%);
    pointer-events: none;
}

.cl-search__input {
    width: 100%;
    height: 36px;
    padding-inline: 36px 14px;
    border: 1px solid var(--rq-line);
    border-radius: var(--rq-radius-pill);
    background: var(--rq-surface);
    font: inherit;
    font-size: 14px;
    color: var(--rq-text-body);
    transition: border-color var(--rq-dur-fast) var(--rq-ease), box-shadow var(--rq-dur-fast) var(--rq-ease);
}

.cl-search__input::placeholder {
    color: var(--rq-text-subtle);
}

.cl-search__input:hover {
    border-color: var(--rq-line-strong);
}

.cl-search__input:focus {
    outline: none;
    border-color: var(--rq-action-primary);
    box-shadow: var(--rq-focus-ring);
}

.cl-chips {
    display: flex;
    flex: 1 1 0;
    min-width: 0;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.cl-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 6px 8px;
    border: 0;
    border-radius: 8px;
    background: none;
    font-family: var(--rq-font-display);
    font-size: 13px;
    font-weight: 600;
    color: var(--rq-text-link);
    cursor: pointer;
}

.cl-link:hover {
    background: var(--rq-surface-hover);
}

.cl-count {
    margin-inline-start: auto;
    font-size: 13px;
    color: var(--rq-text-muted);
    white-space: nowrap;
}

/* The dates chip's panel */
.cl-dates {
    display: flex;
    flex-direction: column;
    gap: 4px;
    width: 260px;
    padding: 8px 6px 4px;
}

.cl-dates .cl-link {
    align-self: flex-start;
}

@media (max-width: 599.98px) {
    .cl-search {
        flex-basis: 100%;
        margin-bottom: 12px;
    }
}
</style>
