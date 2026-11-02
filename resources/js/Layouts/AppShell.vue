<script>
import { mapActions, mapState } from 'pinia';
import FlashStatus from '@/Components/FlashStatus.vue';
import Sidebar from '@/Components/Sidebar.vue';
import ThemeToggler from '@/Components/ThemeToggler.vue';
import UserMenu from '@/Components/UserMenu.vue';
import { useAppStore } from '@/stores/app';

/*
 * rqmana's app shell (side menu, top bar, content area) for the account, instructor and
 * admin pages. rqmana's search, shortcuts, bells, view selector, assistant and tour are not here.
 */
export default {
    components: {
        FlashStatus,
        Sidebar,
        ThemeToggler,
        UserMenu,
    },
    computed: {
        ...mapState(useAppStore, ['sidebarRail', 'sidebarDrawer']),
        railToggleLabel() {
            return this.sidebarRail ? this.$t('sidebar.expand') : this.$t('sidebar.collapse');
        },
        appBarHeight() {
            return this.$vuetify?.display?.mdAndUp ? 64 : 56;
        },
        // Errors of actions that have no form field (publish twice, accept a decided request...).
        actionErrors() {
            const errors = this.$page.props.errors || {};

            return ['class', 'enrollment', 'payment', 'application', 'user']
                .filter((key) => errors[key])
                .map((key) => errors[key]);
        },
    },
    methods: {
        ...mapActions(useAppStore, ['toggleSidebarRail', 'toggleSidebarDrawer']),
    },
};
</script>

<template>
    <v-app id="inspire">
        <Sidebar />

        <v-app-bar
            :height="appBarHeight"
            class="main-bar"
            elevation="0"
        >
            <v-btn
                variant="text"
                size="small"
                class="main-bar__rail-toggle me-3"
                icon
                :aria-label="railToggleLabel"
                :aria-expanded="!sidebarRail"
                @click.stop="toggleSidebarRail()"
            >
                <v-icon icon="tabler:menu-2" />
            </v-btn>

            <v-btn
                variant="text"
                class="main-bar__drawer-toggle me-2 me-md-3"
                icon
                @click.stop="toggleSidebarDrawer()"
                size="small"
                :aria-label="sidebarDrawer ? $t('sidebar.close') : $t('sidebar.open')"
                :aria-expanded="sidebarDrawer"
            >
                <v-icon :icon="sidebarDrawer ? 'tabler:x' : 'tabler:menu-2'" />
            </v-btn>

            <v-spacer />
            <div class="app-bar-actions d-flex align-center">
                <span class="main-bar__desktop-only">
                    <ThemeToggler />
                </span>
                <span class="main-bar__divider" aria-hidden="true" />
                <UserMenu />
            </div>
        </v-app-bar>

        <v-main class="page-wrapper page-wrapper--canvas">
            <v-container fluid>
                <FlashStatus />
                <v-alert
                    v-for="(message, index) in actionErrors"
                    :key="index"
                    type="error"
                    variant="tonal"
                    density="comfortable"
                    class="mb-5"
                    rounded="lg"
                >
                    {{ message }}
                </v-alert>
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>

<style scoped>
/* Content area (spec App shell): 36px above the page, 40px sides, 56px below, 1280px wide at most */
.page-wrapper .v-container {
    padding: 24px 16px 40px;
}

.page-wrapper .v-container:first-child {
    min-height: calc(100vh - 56px);
}

@media (min-width: 960px) {
    .page-wrapper .v-container {
        max-width: calc(1280px + 80px);
        margin-inline: auto;
        padding: 36px 40px 56px;
    }

    .page-wrapper .v-container:first-child {
        min-height: calc(100vh - 64px);
    }
}

.app-bar-actions {
    gap: 4px;
}

/* ---------- Top bar (spec Topbar): white, 64px, 1px hairline underneath ---------- */
/* Spec Topbar: 28px side padding (16px on phones); Vuetify's own 4px inner padding removed */
.main-bar :deep(.v-toolbar__content) {
    padding-inline: 0;
}

/* Vuetify offsets a toolbar's first button by 4px; the menu buttons sit on the padding edge */
.main-bar .main-bar__rail-toggle,
.main-bar .main-bar__drawer-toggle {
    margin-inline-start: 0 !important;
}

.main-bar {
    padding-inline: 16px;
    background: var(--rq-surface) !important;
    border-bottom: 1px solid var(--rq-line-soft) !important;
    color: var(--rq-text-muted);
}

/* Quiet icon buttons: 36px, small corners, muted icon; hover = tint tile + blue icon */
.main-bar :deep(.v-btn--icon) {
    width: 36px;
    height: 36px;
    border-radius: var(--rq-radius-sm) !important;
    background: transparent !important;
    color: var(--rq-text-muted) !important;
    box-shadow: none !important;
    transition: background-color var(--rq-dur) var(--rq-ease), color var(--rq-dur) var(--rq-ease);
}

.main-bar :deep(.v-btn--icon .v-btn__overlay),
.main-bar :deep(.v-btn--icon .v-btn__underlay) {
    display: none;
}

.main-bar :deep(.v-btn--icon .v-icon) {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.main-bar :deep(.v-btn--icon:hover),
.main-bar :deep(.v-btn--icon[aria-expanded="true"]) {
    background: var(--rq-surface-hover) !important;
    color: var(--rq-action-primary) !important;
}

/* Thin separator before the user menu */
.main-bar__divider {
    width: 1px;
    height: 36px;
    margin-inline: 6px 4px;
    background: var(--rq-line-soft);
}

.main-bar__desktop-only,
.main-bar__rail-toggle {
    display: none;
}

.main-bar__drawer-toggle {
    display: inline-flex;
}

@media (min-width: 960px) {
    .main-bar {
        padding-inline: 28px;
    }

    .main-bar__desktop-only {
        display: inline-flex;
    }
}

@media (min-width: 1280px) {
    .main-bar__rail-toggle {
        display: inline-flex;
    }

    .main-bar__drawer-toggle {
        display: none;
    }
}
</style>
