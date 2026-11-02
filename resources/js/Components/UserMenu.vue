<template>
    <v-menu
        v-if="person"
        location="bottom end"
        :close-on-content-click="true"
        max-width="300"
    >
        <template #activator="{ props: menuProps }">
            <button
                v-bind="menuProps"
                type="button"
                class="user-chip"
                :aria-label="$t('userMenu.ariaLabel')"
            >
                <span class="user-chip__avatar">{{ initials }}</span>
                <span class="user-chip__meta">
                    <span class="user-chip__name text-truncate">{{ person.name }}</span>
                    <span v-if="roleLabel" class="user-chip__role text-truncate">{{ roleLabel }}</span>
                </span>
            </button>
        </template>

        <v-card class="user-menu-card" min-width="260" max-width="300" elevation="0">
            <div class="user-menu-card__header px-4 py-3">
                <span class="user-chip__avatar user-chip__avatar--large">{{ initials }}</span>
                <div class="user-menu-card__meta min-w-0">
                    <div class="user-menu-card__name font-weight-bold text-truncate">
                        {{ person.name }}
                    </div>
                    <div
                        v-if="person.email"
                        class="user-menu-card__email text-medium-emphasis text-truncate"
                    >
                        {{ person.email }}
                    </div>
                </div>
            </div>

            <v-divider />

            <v-list class="user-menu-card__list pa-0" density="compact" nav>
                <v-list-item
                    class="user-menu-card__theme px-4 ma-0"
                    rounded="md"
                    @click="toggleTheme"
                >
                    <template #prepend>
                        <v-icon :icon="isDark ? 'tabler:sun' : 'tabler:moon'" size="small" />
                    </template>
                    <v-list-item-title>{{ themeLabel }}</v-list-item-title>
                </v-list-item>
                <template v-for="item in links" :key="item.route">
                    <v-divider class="my-0 border-opacity-80" />
                    <v-list-item
                        rounded="md"
                        class="px-4 ma-0"
                        @click="router.visit(route(item.route))"
                    >
                        <template #prepend>
                            <v-icon :icon="item.icon" size="small" />
                        </template>
                        <v-list-item-title>{{ $t(item.label) }}</v-list-item-title>
                    </v-list-item>
                </template>
                <v-divider class="my-0 border-opacity-80" />
                <v-list-item
                    rounded="md"
                    class="px-4 ma-0"
                    base-color="error"
                    @click="logout"
                >
                    <template #prepend>
                        <v-icon icon="tabler:logout" size="small" />
                    </template>
                    <v-list-item-title>{{ $t('nav.logout') }}</v-list-item-title>
                </v-list-item>
            </v-list>
        </v-card>
    </v-menu>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useIsAdminArea } from '@/composables/useSidebarItems';
import { vuetify } from '@/config/vuetify';
import { persistTheme } from '@/config/themePreference';

const page = usePage();
const { t } = useI18n();
const isAdmin = useIsAdminArea();

// The admin on the admin pages, the account everywhere else.
const person = computed(() => (isAdmin.value ? page.props.auth?.admin : page.props.auth?.user) ?? null);

const isDark = computed(() => {
    const theme = vuetify.theme;
    const current = theme.current?.value ?? theme.global?.current?.value;

    if (current?.dark !== undefined) {
        return current.dark;
    }

    const name = theme.name?.value ?? theme.global?.name?.value;

    return name === 'dark';
});

const themeLabel = computed(() => (
    isDark.value ? t('theme.switchToLight') : t('theme.switchToDark')
));

/* Avatar letter: the first letter of the name ("سارة العمري" -> "س") */
const initials = computed(() => String(person.value?.name || '').trim().charAt(0));

const roleLabel = computed(() => {
    if (isAdmin.value) {
        return t('userMenu.roles.admin');
    }

    return page.props.auth?.user?.isInstructor ? t('userMenu.roles.instructor') : t('userMenu.roles.student');
});

// Switching between the instructor's and the student's side, and the account page.
const links = computed(() => {
    if (isAdmin.value) {
        return [{ route: 'admin.two-factor.setup', icon: 'tabler:shield-lock', label: 'admin.nav.two_factor' }];
    }

    const items = [{ route: 'account', icon: 'tabler:user-cog', label: 'nav.account' }];

    if (page.props.auth?.user?.isInstructor) {
        items.push({ route: 'teach.dashboard', icon: 'tabler:chalkboard', label: 'nav.teach' });
        items.push({ route: 'student.classes.index', icon: 'tabler:school', label: 'nav.student_view' });
    }

    return items;
});

function currentThemeName() {
    const theme = vuetify.theme;

    return theme.name?.value ?? theme.global?.name?.value ?? 'light';
}

function toggleTheme() {
    const theme = vuetify.theme;

    if (typeof theme.toggle === 'function') {
        theme.toggle();
        persistTheme(currentThemeName());

        return;
    }

    const nameRef = theme.name ?? theme.global?.name;

    if (nameRef) {
        nameRef.value = nameRef.value === 'dark' ? 'light' : 'dark';
        persistTheme(nameRef.value);
    }
}

function logout() {
    router.post(route(isAdmin.value ? 'admin.logout' : 'logout'));
}
</script>

<style scoped>
/* Top bar user chip (spec Topbar): initials avatar, name and role, after a hairline */
.user-chip {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    margin-inline-start: 4px;
    padding: 4px 6px;
    border: 0;
    border-radius: var(--rq-radius-sm);
    background: transparent;
    color: inherit;
    font: inherit;
    text-align: start;
    cursor: pointer;
}

.user-chip:focus-visible {
    outline: none;
    box-shadow: var(--rq-focus-ring);
}

.user-chip__avatar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--rq-surface-tint);
    color: var(--rq-action-primary);
    font-family: var(--rq-font-display);
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

/* The bigger letter avatar at the top of the open menu */
.user-chip__avatar--large {
    width: 44px;
    height: 44px;
    font-size: 18px;
}

.user-chip__avatar--photo {
    object-fit: cover;
    box-shadow: 0 0 0 1px var(--rq-line);
}

.user-chip__meta {
    display: none;
    flex-direction: column;
    min-width: 0;
    max-width: 150px;
    line-height: 1.3;
}

.user-chip__name {
    font-family: var(--rq-font-display);
    font-size: 13px;
    font-weight: 500;
    color: var(--rq-text-body);
}

.user-chip__role {
    font-size: 12px;
    color: var(--rq-text-subtle);
}

@media (min-width: 960px) {
    .user-chip__meta {
        display: flex;
    }
}

.user-menu-card {
    box-shadow: none;
    border: 1px solid var(--rq-line);
    overflow: hidden;
}

.user-menu-card__header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-menu-card__name {
    font-size: 0.85rem;
    line-height: 1.2;
}

.user-menu-card__email {
    font-size: 0.8rem;
}

.user-menu-card :deep(.v-list-item-title) {
    font-size: 0.75rem;
}

.user-menu-card__list :deep(.v-list-item--density-compact.v-list-item--one-line) {
    min-height: 40px;
}

.user-menu-card__support-count {
    min-width: 1.25rem;
    padding: 0 6px;
    border-radius: 999px;
    background: var(--rq-action-primary);
    color: #FFFFFF;
    font-size: 0.7rem;
    font-weight: 700;
    line-height: 1.25rem;
    text-align: center;
}

.user-menu-card__meta {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
}

@media (min-width: 960px) {
    .user-menu-card__theme {
        display: none !important;
    }
}
</style>
