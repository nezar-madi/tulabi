import { formatDate } from '@/utils/dates';

// The Libyan week starts on Saturday (Carbon numbers: 0 = Sunday ... 6 = Saturday).
export const WEEK_ORDER = [6, 0, 1, 2, 3, 4, 5];

/**
 * "17:00" -> "5:00 م" (12-hour, as people read times in Libya).
 * @param {string|null} value
 * @param {(key: string) => string} t
 */
export function formatTime(value, t) {
    if (!value) {
        return '';
    }

    const [hours, minutes] = value.split(':').map(Number);
    const suffix = hours < 12 ? t('time.am') : t('time.pm');
    const hour12 = hours % 12 === 0 ? 12 : hours % 12;

    return `${hour12}:${String(minutes).padStart(2, '0')} ${suffix}`;
}

/**
 * @param {number[]} days
 * @param {(key: string) => string} t
 */
export function formatDays(days, t) {
    return WEEK_ORDER.filter((day) => (days || []).includes(day))
        .map((day) => t(`days.${day}`))
        .join('، ');
}

/**
 * When a course meets: daily, the chosen weekdays, or the dates picked by hand
 * (the first few, then how many more).
 */
export function formatScheduleDays(cls, t, show = 4) {
    if (cls.schedule_type === 'daily') {
        return t('class.daily');
    }

    if (cls.schedule_type === 'manual') {
        const dates = [...(cls.manual_dates || [])].sort();
        const shown = dates.slice(0, show).map((date) => formatDay(date)).join('، ');
        const more = dates.length - show;

        return more > 0 ? `${shown} ${t('class.more_dates', { count: more })}` : shown;
    }

    return formatDays(cls.days, t);
}

/**
 * A date as written in Libya, from "Y-m-d" (a calendar date, no time zone).
 * @param {string|null} value
 */
export function formatDay(value) {
    if (!value) {
        return '';
    }

    // Noon UTC keeps the same calendar day in Tripoli.
    return formatDate(`${value}T12:00:00Z`);
}

/**
 * @param {{ price: string, currency: string }} cls
 * @param {(key: string, values?: object) => string} t
 */
export function formatPrice(cls, t) {
    if (Number(cls.price) === 0) {
        return t('class.free');
    }

    return t('class.price', { price: cls.price, currency: cls.currency === 'LYD' ? 'د.ل' : cls.currency });
}

/**
 * "السبت، الإثنين · 5:00 م - 7:00 م"
 */
export function formatSchedule(cls, t) {
    const parts = [];
    const days = formatScheduleDays(cls, t);

    if (days) {
        parts.push(days);
    }

    if (cls.start_time) {
        parts.push(t('class.time_range', { from: formatTime(cls.start_time, t), to: formatTime(cls.end_time, t) }));
    }

    return parts.join(' · ');
}

export function formatPeriod(cls, t) {
    if (cls.ends_on && cls.ends_on !== cls.starts_on) {
        return t('class.from_to', { from: formatDay(cls.starts_on), to: formatDay(cls.ends_on) });
    }

    return t('class.starts', { date: formatDay(cls.starts_on) });
}
