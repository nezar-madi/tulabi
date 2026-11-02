<template>
    <v-app>
        <header class="public-bar">
            <div class="public-bar__inner">
                <Link :href="route('home')" class="text-decoration-none">
                    <BrandMark />
                </Link>

                <div class="d-flex align-center ga-1">
                    <v-btn
                        icon
                        variant="text"
                        size="small"
                        :aria-label="isDark ? $t('theme.light') : $t('theme.dark')"
                        @click="toggleTheme"
                    >
                        <v-icon :icon="isDark ? 'tabler:sun' : 'tabler:moon'" />
                    </v-btn>

                    <template v-if="user">
                        <Link :href="route('account')" class="text-decoration-none">
                            <v-btn class="rq-btn rq-btn--ghost" variant="text">
                                <v-icon icon="tabler:user" start />
                                {{ $t('nav.account') }}
                            </v-btn>
                        </Link>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            :aria-label="$t('nav.logout')"
                            @click="router.post(route('logout'))"
                        >
                            <v-icon icon="tabler:logout" />
                        </v-btn>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-decoration-none">
                            <v-btn class="rq-btn rq-btn--ghost" variant="text">{{ $t('nav.login') }}</v-btn>
                        </Link>
                        <Link :href="route('register')" class="text-decoration-none d-none d-sm-inline">
                            <v-btn class="rq-btn rq-btn--primary">{{ $t('nav.register') }}</v-btn>
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <v-main class="public-main">
            <slot />
        </v-main>
    </v-app>
</template>

<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useTheme } from 'vuetify';
import BrandMark from '@/Components/BrandMark.vue';
import { persistTheme } from '@/config/themePreference';

const page = usePage();
const user = computed(() => page.props.auth?.user || null);

const theme = useTheme();
const isDark = computed(() => theme.global.current.value.dark);

function toggleTheme() {
    const next = isDark.value ? 'light' : 'dark';

    theme.change(next);
    persistTheme(next);
}
</script>

<style scoped>
.public-bar {
    position: sticky;
    top: 0;
    z-index: 10;
    background: var(--rq-surface);
    border-bottom: 1px solid var(--rq-line-soft);
}

.public-bar__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1120px;
    height: var(--rq-topbar-height);
    margin: 0 auto;
    padding: 0 16px;
}

.public-main {
    min-height: 100%;
    background: var(--rq-bg-page);
}
</style>
