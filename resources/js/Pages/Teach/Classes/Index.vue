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

    <!-- Filters: they live in the address, so a filtered list can be reloaded or shared as it is -->
    <div class="rq-card classes-filters">
        <v-text-field
            v-model="search"
            :label="$t('teach.classes.narrow.search')"
            prepend-inner-icon="tabler:search"
            variant="outlined"
            density="comfortable"
            hide-details
            clearable
            class="classes-filters__search"
            @update:model-value="searchSoon"
        />
        <v-select
            :model-value="narrow.when || null"
            :items="whenItems"
            :label="$t('teach.classes.narrow.when')"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="apply({ when: $event })"
        />
        <v-select
            :model-value="narrow.price || null"
            :items="priceItems"
            :label="$t('teach.classes.narrow.price')"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="apply({ price: $event })"
        />
        <v-select
            :model-value="narrow.type || null"
            :items="typeItems"
            :label="$t('teach.classes.narrow.type')"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="apply({ type: $event })"
        />
        <v-select
            v-if="programs.length"
            :model-value="narrow.program || null"
            :items="programItems"
            :label="$t('teach.classes.narrow.program')"
            variant="outlined"
            density="comfortable"
            hide-details
            @update:model-value="apply({ program: $event })"
        />
        <FormPlainDatePicker
            :model-value="narrow.from || ''"
            :placeholder="$t('teach.classes.narrow.from')"
            :max="narrow.to || undefined"
            class="classes-filters__date"
            @update:model-value="apply({ from: $event })"
        />
        <FormPlainDatePicker
            :model-value="narrow.to || ''"
            :placeholder="$t('teach.classes.narrow.to')"
            :min="narrow.from || undefined"
            class="classes-filters__date"
            @update:model-value="apply({ to: $event })"
        />
        <v-btn
            v-if="filtering"
            class="text-none classes-filters__clear"
            variant="text"
            color="primary"
            prepend-icon="tabler:x"
            @click="clearAll"
        >
            {{ $t('teach.classes.narrow.clear') }}
        </v-btn>
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
import FormPlainDatePicker from '@/Components/formFields/FormPlainDatePicker.vue';

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

const all = (key) => ({ title: t(`teach.classes.narrow.all_${key}`), value: null });
const whenItems = computed(() => [all('when'), ...['upcoming', 'running', 'ended'].map((value) => ({ title: t(`teach.classes.narrow.when_${value}`), value }))]);
const priceItems = computed(() => [all('price'), ...['paid', 'free'].map((value) => ({ title: t(`teach.classes.narrow.price_${value}`), value }))]);
const typeItems = computed(() => [all('type'), ...['in_person', 'online'].map((value) => ({ title: t(`class.type.${value}`), value }))]);
const programItems = computed(() => [
    all('program'),
    ...props.programs.map((program) => ({ title: program.name, value: String(program.id) })),
    { title: t('teach.classes.narrow.no_program'), value: 'none' },
]);

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

.classes-filters {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    align-items: center;
    gap: 10px;
    margin-bottom: var(--rq-space-5);
    padding: 12px;
    border-radius: var(--rq-radius-card);
}

.classes-filters__search {
    grid-column: span 2;
}

/* The date fields come without a label or messages, and as tall as the selects beside them. */
.classes-filters__date :deep(.form-plain-field__label),
.classes-filters__date :deep(.v-input__details) {
    display: none;
}

.classes-filters__date :deep(.v-field),
.classes-filters__date :deep(.v-field__input) {
    min-height: 48px;
}

.classes-filters__clear {
    justify-self: start;
}

@media (max-width: 599.98px) {
    .classes-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
</style>
