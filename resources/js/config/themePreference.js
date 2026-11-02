const THEME_STORAGE_KEY = 'vuetify-theme';
const ALLOWED_THEMES = ['light', 'dark'];

export function isAllowedTheme(name) {
    return ALLOWED_THEMES.includes(name);
}

/**
 * Read persisted theme; clears corrupt values.
 * @returns {'light'|'dark'|null}
 */
export function readStoredTheme() {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    const stored = localStorage.getItem(THEME_STORAGE_KEY);

    if (stored === null) {
        return null;
    }

    if (isAllowedTheme(stored)) {
        return stored;
    }

    localStorage.removeItem(THEME_STORAGE_KEY);

    return null;
}

/**
 * @param {string} name
 */
export function persistTheme(name) {
    if (!isAllowedTheme(name)) {
        return;
    }

    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(THEME_STORAGE_KEY, name);
    }

    applyDocumentTheme(name);
}

/**
 * Sync html element so early-paint styles match the active theme.
 * @param {'light'|'dark'} name
 */
export function applyDocumentTheme(name) {
    if (typeof document === 'undefined' || !isAllowedTheme(name)) {
        return;
    }

    document.documentElement.dataset.theme = name;
    document.documentElement.style.colorScheme = name;
    document.documentElement.style.backgroundColor =
        name === 'dark' ? '#121212' : '#F8FAFC';
}

/**
 * Prefer stored preference, else server/admin default, else light.
 * @param {string} [serverDefault]
 * @returns {'light'|'dark'}
 */
export function resolveInitialTheme(serverDefault = 'light') {
    const theme = readStoredTheme()
        ?? (isAllowedTheme(serverDefault) ? serverDefault : 'light');

    applyDocumentTheme(theme);

    return theme;
}

export { THEME_STORAGE_KEY, ALLOWED_THEMES };
