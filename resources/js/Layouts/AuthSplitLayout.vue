<template>
    <v-app>
        <v-main class="auth-split">
            <div class="auth-split__shell" dir="ltr">
                <aside class="auth-split__promo" :aria-label="appName">
                    <!-- Module grid: faint cells anchored to the outer bottom corner, a few filled with module
                         icons, fading out towards the copy -->
                    <div class="auth-grid" aria-hidden="true">
                        <span
                            v-for="tile in tiles"
                            :key="tile.key"
                            class="auth-grid__tile"
                            :class="{ 'auth-grid__tile--lit': tile.lit }"
                            :style="{ '--col': tile.col, '--row': tile.row }"
                        >
                            <component :is="tile.icon" :size="22" :stroke-width="1.6" />
                        </span>
                    </div>

                    <div class="auth-split__promo-inner" dir="rtl">
                        <BrandMark inverse class="auth-split__promo-logo" />

                        <div class="auth-split__copy">
                            <h1 class="auth-split__headline">
                                <span>{{ $t('auth.login.promo.headline') }}</span>
                                <span>{{ $t('auth.login.promo.headline_2') }}</span>
                            </h1>
                            <p class="auth-split__lede">{{ $t('auth.login.promo.lede') }}</p>

                            <ul class="auth-split__points">
                                <li v-for="point in points" :key="point.key">
                                    <span class="auth-split__point-icon">
                                        <component :is="point.icon" :size="16" :stroke-width="1.8" />
                                    </span>
                                    {{ $t(`auth.login.promo.points.${point.key}`) }}
                                </li>
                            </ul>
                        </div>

                        <p class="auth-split__copyright">
                            {{ $t('auth.login.promo.copyright', { year }) }}
                        </p>
                    </div>
                </aside>

                <section class="auth-split__panel">
                    <div class="auth-split__panel-inner" dir="rtl">
                        <!-- The brand side carries the logo on desktop; on small screens it is hidden, so the
                             logo shows here instead -->
                        <header class="auth-split__panel-head">
                            <Link :href="route('home')" class="text-decoration-none">
                                <BrandMark class="auth-split__logo" />
                            </Link>
                        </header>

                        <div class="auth-split__form">
                            <slot />
                        </div>

                        <footer class="auth-split__footer">
                            <span class="auth-lockup">
                                <span class="auth-lockup__name">{{ $t('app.name') }}</span>
                                <i class="auth-lockup__sep" aria-hidden="true" />
                                <span>{{ $t('auth.login.footer') }}</span>
                            </span>
                        </footer>
                    </div>
                </section>
            </div>
        </v-main>
    </v-app>
</template>

<script setup>
import {
    IconBook2,
    IconBuilding,
    IconCalendar,
    IconCreditCard,
    IconFileText,
    IconLayoutGrid,
    IconReceipt,
    IconSchool,
    IconShieldCheck,
    IconSitemap,
    IconUserPlus,
    IconUsers,
} from '@tabler/icons-vue';
import { Link } from '@inertiajs/vue3';
import BrandMark from '@/Components/BrandMark.vue';

const appName = 'طلابي';
const year = new Date().getFullYear();

/* Value points under the lede */
const points = [
    { key: 'page', icon: IconSchool },
    { key: 'requests', icon: IconUserPlus },
    { key: 'money', icon: IconReceipt },
];

/* Module tiles: col counts from the outer edge, row from the bottom */
const tiles = [
    { key: 'calendar', icon: IconCalendar, col: 1, row: 5 },
    { key: 'dashboard', icon: IconLayoutGrid, col: 0, row: 4, lit: true },
    { key: 'users', icon: IconUsers, col: 3, row: 4 },
    { key: 'register', icon: IconUserPlus, col: 1, row: 3 },
    { key: 'payments', icon: IconCreditCard, col: 2, row: 2, lit: true },
    { key: 'center', icon: IconBuilding, col: 5, row: 2 },
    { key: 'files', icon: IconFileText, col: 0, row: 1 },
    { key: 'roles', icon: IconShieldCheck, col: 1, row: 1 },
    { key: 'departments', icon: IconSitemap, col: 4, row: 1, lit: true },
    { key: 'finance', icon: IconReceipt, col: 5, row: 1 },
    { key: 'classes', icon: IconSchool, col: 2, row: 0 },
    { key: 'subjects', icon: IconBook2, col: 3, row: 0 },
];
</script>

<style scoped>
/* Login, 50 / 50 on desktop. Brand side: deep brand blue, logo at the top, a two-line greeting and lede in the
   middle, the copyright at the bottom, and a module grid fading out of the outer bottom corner. Form side: the
   form centred, the product line at the bottom. */
.auth-split {
    min-height: 100%;
    background: var(--rq-surface);
}

.auth-split__shell {
    display: grid;
    grid-template-columns: 1fr;
    min-height: 100dvh;
}

/* ---------- Brand side ---------- */
.auth-split__promo {
    position: relative;
    display: none;
    overflow: hidden;
    color: var(--rq-text-on-primary);
    background: var(--rq-action-primary);
}

.auth-split__promo-inner {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    width: 100%;
    padding: clamp(40px, 13vh, 150px) clamp(40px, 7vw, 112px) clamp(28px, 6vh, 64px);
}

.auth-split__promo-logo {
    align-self: flex-start;
}

.auth-split__copy {
    max-width: 32rem;
    margin-block: auto;
    padding-block: 0 clamp(110px, 20vh, 220px);
}

/* One line: "أهلًا بك في رقمنة" */
.auth-split__headline {
    display: flex;
    flex-wrap: wrap;
    column-gap: .28em;
    margin: 0 0 16px;
    font-family: var(--rq-font-display);
    font-size: clamp(32px, 3.2vw, 50px);
    font-weight: 700;
    line-height: 1.2;
    color: var(--rq-text-on-primary);
}

.auth-split__lede {
    margin: 0;
    max-width: 30rem;
    font-size: clamp(14px, 1.05vw, 16px);
    line-height: 1.9;
    color: var(--rq-text-on-primary-muted);
}

.auth-split__points {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin: 28px 0 0;
    padding: 0;
    list-style: none;
}

.auth-split__points li {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    color: var(--rq-text-on-primary);
}

.auth-split__point-icon {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: rgba(255, 255, 255, .1);
    color: var(--rq-brand-300);
}

.auth-split__copyright {
    margin: 0;
    font-size: 12px;
    color: var(--rq-text-on-primary-muted);
}

/* ---------- Module grid ---------- */
.auth-grid {
    --cell: clamp(60px, 6.2vw, 90px);
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(to right, rgba(255, 255, 255, .07) 1px, transparent 1px),
        linear-gradient(to top, rgba(255, 255, 255, .07) 1px, transparent 1px);
    background-size: var(--cell) var(--cell);
    background-position: left bottom;
    -webkit-mask-image: radial-gradient(ellipse 72% 56% at 0% 100%, #000000 35%, transparent 100%);
    mask-image: radial-gradient(ellipse 72% 56% at 0% 100%, #000000 35%, transparent 100%);
    pointer-events: none;
}

.auth-grid__tile {
    position: absolute;
    left: calc(var(--col) * var(--cell) + 1px);
    bottom: calc(var(--row) * var(--cell));
    display: flex;
    align-items: center;
    justify-content: center;
    width: calc(var(--cell) - 1px);
    height: calc(var(--cell) - 1px);
    background: rgba(255, 255, 255, .05);
    color: rgba(255, 255, 255, .6);
}

.auth-grid__tile--lit {
    background: color-mix(in srgb, var(--rq-brand-600) 75%, var(--rq-brand-700));
    color: var(--rq-brand-200);
}

/* ---------- Form side ---------- */
.auth-split__panel {
    display: flex;
    justify-content: center;
    padding: 0 clamp(24px, 6vw, 128px);
    background: var(--rq-surface);
}

.auth-split__panel-inner {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 26rem;
    min-height: 100dvh;
}

.auth-split__panel-head {
    padding: 40px 0 24px;
}

.auth-split__logo {
    display: inline-flex;
}

.auth-lockup__name {
    font-family: var(--rq-font-display);
    font-weight: 700;
    color: var(--rq-text-heading);
}

.auth-split__form {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: center;
}

.auth-split__footer {
    padding: 32px 0 clamp(28px, 6vh, 64px);
    font-size: 12px;
    color: var(--rq-text-subtle);
}

.auth-lockup {
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.auth-lockup__sep {
    width: 1px;
    height: 16px;
    background: currentColor;
    opacity: 0.4;
}

@media (min-width: 960px) {
    .auth-split__shell {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }

    .auth-split__promo {
        position: sticky;
        top: 0;
        display: flex;
        height: 100dvh;
    }

    /* The brand side carries the logo and the copyright on desktop */
    .auth-split__panel-head,
    .auth-split__footer {
        display: none;
    }

    .auth-split__form {
        padding-top: 32px;
    }
}
</style>
