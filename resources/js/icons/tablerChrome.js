import { h } from 'vue';
import {
    IconArrowLeft,
    IconBrandWhatsapp,
    IconCalendarEvent,
    IconChalkboard,
    IconCheck,
    IconChevronLeft,
    IconCurrencyDollar,
    IconLogin,
    IconLogout,
    IconMapPin,
    IconMenu2,
    IconMoon,
    IconPhone,
    IconReceipt,
    IconSchool,
    IconSearch,
    IconSettings,
    IconSun,
    IconUser,
    IconUserPlus,
    IconUsers,
    IconVideo,
    IconX,
} from '@tabler/icons-vue';

/**
 * Every icon usable as icon="tabler:<name>". An unlisted name renders nothing,
 * so register each new icon here first.
 *
 * @type {Record<string, import('vue').Component>}
 */
export const tablerChromeIcons = {
    'arrow-left': IconArrowLeft,
    'brand-whatsapp': IconBrandWhatsapp,
    'calendar-event': IconCalendarEvent,
    chalkboard: IconChalkboard,
    check: IconCheck,
    'chevron-left': IconChevronLeft,
    'currency-dollar': IconCurrencyDollar,
    login: IconLogin,
    logout: IconLogout,
    'map-pin': IconMapPin,
    'menu-2': IconMenu2,
    moon: IconMoon,
    phone: IconPhone,
    receipt: IconReceipt,
    school: IconSchool,
    search: IconSearch,
    settings: IconSettings,
    sun: IconSun,
    user: IconUser,
    'user-plus': IconUserPlus,
    users: IconUsers,
    video: IconVideo,
    x: IconX,
};

function resolveSize(size) {
    if (typeof size === 'number' && Number.isFinite(size)) {
        return size;
    }

    if (typeof size === 'string') {
        const parsed = Number.parseFloat(size);

        if (Number.isFinite(parsed) && /px|rem|em|%/.test(size) === false && size === String(parsed)) {
            return parsed;
        }

        if (size.trim() !== '') {
            const named = {
                'x-small': 14,
                small: 18,
                default: 22,
                large: 28,
                'x-large': 32,
            };

            if (named[size] !== undefined) {
                return named[size];
            }

            return size;
        }
    }

    // Inherit Vuetify .v-icon font-size.
    return '1em';
}

/**
 * Vuetify icon set backed by @tabler/icons-vue (stroke 1.75, sized like Vuetify icons).
 * Usage: icon="tabler:school"
 */
export const tablerChrome = {
    component: (props) => {
        const iconKey = typeof props.icon === 'string' ? props.icon : null;
        const Icon = iconKey ? tablerChromeIcons[iconKey] : props.icon;

        if (!Icon) {
            return null;
        }

        const size = resolveSize(props.size);

        return h(props.tag || 'span', {
            class: props.class,
            style: props.style,
        }, [
            h(Icon, {
                size,
                stroke: 1.75,
                'aria-hidden': 'true',
            }),
        ]);
    },
};
