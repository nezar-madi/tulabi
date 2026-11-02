<script setup>
import { computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { useAppStore } from '@/stores/app';
import BrandMark from '@/Components/BrandMark.vue';
import SidebarNav from '@/Components/SidebarNav.vue';
import { useHomeRoute, useIsAdminArea, useSidebarItems } from '@/composables/useSidebarItems';

const appStore = useAppStore();
const { sidebarDrawer, sidebarRail } = storeToRefs(appStore);
const { setSidebarDrawer } = appStore;

const sidebarItems = useSidebarItems();
const homeRoute = useHomeRoute();
const isAdmin = useIsAdminArea();
// After the logo, as rqmana shows a center's name: where the menu belongs.
const areaLabel = computed(() => (isAdmin.value ? 'admin.title' : null));

function logout() {
    router.post(route(isAdmin.value ? 'admin.logout' : 'logout'));
}

function onDrawerModelUpdate(value) {
    const normalizedValue = Boolean(value);
    if (sidebarDrawer.value === normalizedValue) {
        return;
    }
    setSidebarDrawer(normalizedValue);
}
</script>

<template>
    <v-navigation-drawer
        :model-value="sidebarDrawer"
        @update:model-value="onDrawerModelUpdate"
        elevation="0"
        mobile-breakpoint="lg"
        app
        expand-on-hover
        :rail="sidebarRail"
        rail-width="72"
        width="240"
        class="main-sidebar"
    >
        <Link :href="route(homeRoute)" class="sidebar-brand text-decoration-none">
            <BrandMark mark-only class="sidebar-brand__mark" />
            <div class="sidebar-brand__full">
                <BrandMark class="sidebar-brand__logo" />
                <span
                    v-if="areaLabel"
                    class="sidebar-brand__name text-truncate"
                >
                    {{ $t(areaLabel) }}
                </span>
            </div>
        </Link>

        <v-list
            nav
            density="comfortable"
            class="sidebar-scroll-container app-overlay-scrollbar"
            prepend-gap="12"
        >
            <SidebarNav :items="sidebarItems" />
        </v-list>

        <template #append>
            <div class="sidebar-footer">
                <v-btn
                    variant="text"
                    block
                    class="sidebar-footer__logout text-none"
                    prepend-icon="tabler:logout"
                    @click="logout"
                >
                    <span class="sidebar-footer__label">{{ $t('nav.logout') }}</span>
                </v-btn>
            </div>
        </template>
    </v-navigation-drawer>
</template>

<style scoped>
/* Spec Sidebar: white column, 1px hairline at its inline end, 64px brand row */
.main-sidebar {
    background: var(--rq-surface) !important;
    border-inline-end: 1px solid var(--rq-line-soft) !important;
    border-inline-start: 0 !important;
}

/* ---------- Brand ---------- */
.sidebar-brand {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    height: var(--rq-topbar-height);
    padding-inline: 20px;
    overflow: hidden;
    border-bottom: 1px solid var(--rq-line-soft);
    background: var(--rq-surface);
}

/* Logo, then the center name after a thin divider */
.sidebar-brand__full {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.sidebar-brand__logo {
    display: flex;
}

.sidebar-brand__name {
    max-width: 120px;
    padding-inline-start: 10px;
    border-inline-start: 1px solid var(--rq-line-row);
    font-family: var(--rq-font-display);
    font-size: var(--rq-text-label);
    line-height: 1.5;
    color: var(--rq-text-muted);
}

.sidebar-brand__mark {
    display: none;
}

/* Rail (collapsed, not hovered): mark only, centred */
.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-brand {
    justify-content: center;
    padding-inline: 0;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-brand__full {
    display: none;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-brand__mark {
    display: flex;
}

/* ---------- Navigation ---------- */
.sidebar-scroll-container {
    height: calc(100% - var(--rq-topbar-height));
    overflow-y: auto;
    overflow-x: hidden;
    padding: 20px 14px 24px !important;
    background: transparent;
}

/* Thinner scrollbar than the shared overlay one.
   Chromium ignores ::-webkit-scrollbar sizing while scrollbar-width is set, so unset it there;
   Firefox keeps "thin" (its narrowest option). */
@supports selector(::-webkit-scrollbar) {
    .sidebar-scroll-container.app-overlay-scrollbar {
        scrollbar-width: auto;
        scrollbar-color: auto;
    }
}

.sidebar-scroll-container.app-overlay-scrollbar::-webkit-scrollbar {
    width: 2px;
}

.sidebar-scroll-container.app-overlay-scrollbar::-webkit-scrollbar-thumb {
    border-radius: 2px;
}

.sidebar-scroll-container :deep(.v-list-item) {
    position: relative;
    min-height: 38px;
    margin-bottom: 2px;
    padding-inline: 12px !important;
    border-radius: var(--rq-radius-sm) !important;
    color: var(--rq-text-body);
    transition: background-color var(--rq-dur-fast) var(--rq-ease), color var(--rq-dur-fast) var(--rq-ease);
}

.sidebar-scroll-container :deep(.v-list-item__overlay) {
    display: none;
}

/* Icons are blue; on the active row they turn white with the text */
.sidebar-scroll-container :deep(.v-list-item .v-icon) {
    color: var(--rq-action-primary);
    opacity: 1;
    font-size: 17px;
}

.sidebar-scroll-container :deep(.v-list-item-title) {
    font-family: var(--rq-font-display);
    font-size: 11px;
    font-weight: 400;
    line-height: 1.5;
}

.sidebar-scroll-container :deep(.v-list-item:hover) {
    background: var(--rq-surface-hover);
}

/* Active page: solid blue row, white text and icon (spec "active selection") */
.sidebar-scroll-container :deep(.v-list-item--active:not(.v-list-group__header)),
.sidebar-scroll-container :deep(.v-list-item--active:not(.v-list-group__header):hover) {
    background: var(--rq-action-primary);
    color: var(--rq-text-on-primary);
}

.sidebar-scroll-container :deep(.v-list-item--active:not(.v-list-group__header) .v-list-item-title) {
    font-weight: 600;
}

.sidebar-scroll-container :deep(.v-list-item--active:not(.v-list-group__header) .v-icon) {
    color: inherit;
}

/* A group whose child is the current page: blue text, no fill */
.sidebar-scroll-container :deep(.v-list-group__header.v-list-item--active) {
    background: transparent;
    color: var(--rq-action-primary);
}

.sidebar-scroll-container :deep(.v-list-group__header.v-list-item--active:hover) {
    background: var(--rq-surface-hover);
}

.sidebar-scroll-container :deep(.v-list-group__header .v-list-item__append .v-icon) {
    color: var(--rq-text-subtle);
    font-size: 16px;
}

/* Nested items: indented under the parent's label, a small light-blue dot before each */
.sidebar-scroll-container :deep(.v-list-group__items .v-list-item__prepend) {
    display: none;
}

.sidebar-scroll-container :deep(.v-list-group__items .v-list-item) {
    min-height: 34px;
    padding-inline-start: 42px !important;
}

.sidebar-scroll-container :deep(.v-list-group__items .v-list-item)::before {
    content: '';
    position: absolute;
    inset-inline-start: 22px;
    top: 50%;
    width: 5px;
    height: 5px;
    margin-top: -2.5px;
    border-radius: 50%;
    background: var(--rq-brand-300);
}

.sidebar-scroll-container :deep(.v-list-group__items .v-list-item--active)::before {
    background: currentColor;
}

.sidebar-scroll-container :deep(.v-list-group__items .v-list-item-title) {
    font-size: 11px;
}

/* Section titles (الهيكل الأكاديمي، الأشخاص، العمليات): small subtle labels */
.sidebar-scroll-container :deep(.sidebar-section-title) {
    min-height: 0;
    margin-top: 22px;
    padding: 0 12px 6px !important;
    font-family: var(--rq-font-display);
    font-size: 11px;
    font-weight: 400;
    letter-spacing: .5px;
    color: var(--rq-text-subtle);
}

/* Rail (icons only): the title collapses to a short centred hairline between icon groups */
.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) :deep(.sidebar-section-title) {
    height: 1px;
    width: 24px;
    margin: 10px auto 12px;
    padding: 0 !important;
    overflow: hidden;
    font-size: 0;
    background: var(--rq-line);
}

/* ---------- Footer: logout, above a hairline ---------- */
.sidebar-footer {
    padding: 16px;
    border-top: 1px solid var(--rq-line-soft);
}

/* Ghost button: muted, 12px semibold, start-aligned */
.sidebar-footer .sidebar-footer__logout.v-btn {
    justify-content: flex-start;
    height: 41px !important;
    padding-inline: 18px !important;
    color: var(--rq-text-muted) !important;
    font-size: 12px;
    font-weight: 600;
}

.sidebar-footer__logout :deep(.v-btn__prepend .v-icon) {
    font-size: 18px;
}

.sidebar-footer__logout:hover {
    background: var(--rq-surface-hover);
    color: var(--rq-action-primary) !important;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-footer__label {
    display: none;
}

/* Rail: the sign-out icon sits on the same centre line as the nav icons */
.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-footer {
    padding-inline: 10px;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-footer .sidebar-footer__logout.v-btn {
    justify-content: center;
    padding-inline: 0 !important;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-footer__logout :deep(.v-btn__prepend) {
    margin-inline: 0;
}

/* Rail: centre the icons */
.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-scroll-container {
    padding-inline: 10px !important;
}

/* Rail: each item is a centred icon — no side padding, the icon box spans the row */
/* Vuetify lays list items out as a grid whose (hidden) text column still takes space: use a centred row */
.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-scroll-container :deep(.v-list-item) {
    display: flex !important;
    justify-content: center;
    padding-inline: 0 !important;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-scroll-container :deep(.v-list-item__prepend) {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-inline-end: 0 !important;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-scroll-container :deep(.v-list-item__prepend > .v-list-item__spacer) {
    display: none;
}

.v-navigation-drawer--rail:not(.v-navigation-drawer--is-hovering) .sidebar-scroll-container :deep(:is(.v-list-item__content, .v-list-item__append)) {
    display: none;
}

</style>
