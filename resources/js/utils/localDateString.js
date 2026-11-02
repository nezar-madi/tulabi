/** Local calendar date as YYYY-MM-DD (avoids UTC off-by-one from toISOString). */
export function localDateString(date = new Date()) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

/** Calendar date N days before `date`, as YYYY-MM-DD. */
export function localDateStringDaysAgo(days = 1, date = new Date()) {
    const shifted = new Date(date.getFullYear(), date.getMonth(), date.getDate() - days);

    return localDateString(shifted);
}

/** Normalize picker/ISO values to YYYY-MM-DD when possible. */
export function toDateOnlyString(value) {
    const raw = String(value ?? '').trim();
    if (!raw) {
        return '';
    }

    const match = raw.match(/^(\d{4}-\d{2}-\d{2})/);

    return match ? match[1] : raw;
}
