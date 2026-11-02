import './config/vuetify.scss';
import '@mdi/font/css/materialdesignicons.css';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createI18n } from 'vue-i18n';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import ar from './locales/ar.json';
import { vuetify } from './config/vuetify';
import { resolveInitialTheme } from './config/themePreference';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'طلابي';
const locale = 'ar';
const i18n = createI18n({
    legacy: false,
    globalInjection: true,
    locale,
    fallbackLocale: locale,
    messages: { ar },
    pluralRules: {
        ar: (choice) => {
            const n = Math.abs(Number(choice));

            if (n === 0) {
                return 0;
            }

            if (n === 1) {
                return 1;
            }

            if (n === 2) {
                return 2;
            }

            const mod100 = n % 100;

            if (mod100 >= 3 && mod100 <= 10) {
                return 3;
            }

            if (mod100 >= 11 && mod100 <= 99) {
                return 4;
            }

            return 5;
        },
    },
});

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        );

        // A page that sets its own layout (or layout: null) keeps it.
        if (page.default.layout === undefined) {
            page.default.layout = name.startsWith('Admin/') ? AdminLayout : PublicLayout;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        vuetify.theme.change(resolveInitialTheme('light'));

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(vuetify)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#0A7D4F',
    },
});
