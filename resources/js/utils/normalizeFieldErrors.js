import { unref } from 'vue';

/**
 * Coerce Inertia / VeeValidate / nested ref error values into a flat string array for UI components.
 */
export function normalizeFieldErrors(messages) {
    const raw = unref(messages);

    if (!raw) {
        return [];
    }

    if (Array.isArray(raw)) {
        return raw.flatMap((item) => {
            if (typeof item === 'string') {
                return item.trim() ? [item] : [];
            }

            if (Array.isArray(item)) {
                return normalizeFieldErrors(item);
            }

            return [];
        });
    }

    if (typeof raw === 'string') {
        return raw.trim() ? [raw] : [];
    }

    return [];
}
