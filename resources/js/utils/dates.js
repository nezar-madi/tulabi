// Dates come from the server in UTC (ISO 8601) and are shown on Libya's calendar.
const TIME_ZONE = 'Africa/Tripoli';

/**
 * @param {string|null|undefined} iso
 * @returns {string} e.g. "2 نوفمبر 2026"
 */
export function formatDate(iso) {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat('ar-LY-u-nu-latn', {
        timeZone: TIME_ZONE,
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
}
