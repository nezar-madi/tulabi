<template>
    <v-tooltip :text="tooltipText" location="bottom">
        <template #activator="{ props: tooltipProps }">
            <v-btn
                v-bind="tooltipProps"
                size="small"
                icon
                :aria-label="tooltipText"
                @click="toggleTheme"
            >
                <v-icon :icon="isDark ? 'tabler:sun' : 'tabler:moon'" />
            </v-btn>
        </template>
    </v-tooltip>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { vuetify } from '@/config/vuetify';
import { persistTheme } from '@/config/themePreference';

const { t } = useI18n();

const isDark = computed(() => {
    const theme = vuetify.theme;
    const current = theme.current?.value ?? theme.global?.current?.value;

    if (current?.dark !== undefined) {
        return current.dark;
    }

    const name = theme.name?.value ?? theme.global?.name?.value;

    return name === 'dark';
});

const tooltipText = computed(() => (
    isDark.value ? t('theme.switchToLight') : t('theme.switchToDark')
));

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
</script>
