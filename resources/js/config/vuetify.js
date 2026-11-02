import { createVuetify } from 'vuetify';
import { aliases, mdi } from 'vuetify/iconsets/mdi';
import { ar } from 'vuetify/locale';
import { tablerChrome } from '@/icons/tablerChrome';
import { theme as themeConfig } from './vuetifyTheme';
import { readStoredTheme } from './themePreference';

const theme = {
    ...themeConfig,
    defaultTheme: readStoredTheme() ?? themeConfig.defaultTheme,
};

export const vuetifyOptions = {
    locale: {
        locale: 'ar',
        fallback: 'ar',
        messages: { ar },
        rtl: {
            ar: true,
        },
    },
    theme,
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
            tabler: tablerChrome,
        },
    },
    defaults: {
        // Same as rqmana: colour-only feedback (no ripple), flat surfaces, hairlines instead of shadows
        global: {
            ripple: false,
        },
        VBtn: { elevation: 0 },
        VCard: { elevation: 0 },
        VSheet: { elevation: 0 },
        VTabs: { color: 'primary', sliderColor: 'primary', height: 44 },
        VProgressLinear: { bgOpacity: 0.1 },
        VTooltip: { openOnFocus: false },
    },
};

export const vuetify = createVuetify(vuetifyOptions);
