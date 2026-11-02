import { h } from 'vue';
import {
    IconArrowLeft,
    IconArrowRight,
    IconBrandFacebook,
    IconBrandInstagram,
    IconBrandLinkedin,
    IconBrandTelegram,
    IconBrandTiktok,
    IconBrandWhatsapp,
    IconBrandX,
    IconBrandYoutube,
    IconCalendarEvent,
    IconCash,
    IconChalkboard,
    IconCheck,
    IconChevronLeft,
    IconCircle,
    IconCircleCheck,
    IconClock,
    IconCopy,
    IconCurrencyDollar,
    IconExternalLink,
    IconFileInvoice,
    IconFileText,
    IconHourglass,
    IconInbox,
    IconKey,
    IconLayoutDashboard,
    IconLogin,
    IconLogout,
    IconMapPin,
    IconMenu2,
    IconMoon,
    IconNote,
    IconPencil,
    IconPhone,
    IconPlus,
    IconReceipt,
    IconSchool,
    IconSearch,
    IconSettings,
    IconShieldLock,
    IconSun,
    IconUpload,
    IconUser,
    IconUserCheck,
    IconUserMinus,
    IconUserOff,
    IconUserPlus,
    IconUsers,
    IconVideo,
    IconWallet,
    IconWorld,
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
    'arrow-right': IconArrowRight,
    'brand-facebook': IconBrandFacebook,
    'brand-instagram': IconBrandInstagram,
    'brand-linkedin': IconBrandLinkedin,
    'brand-telegram': IconBrandTelegram,
    'brand-tiktok': IconBrandTiktok,
    'brand-whatsapp': IconBrandWhatsapp,
    'brand-x': IconBrandX,
    'brand-youtube': IconBrandYoutube,
    'calendar-event': IconCalendarEvent,
    cash: IconCash,
    chalkboard: IconChalkboard,
    check: IconCheck,
    'chevron-left': IconChevronLeft,
    circle: IconCircle,
    'circle-check': IconCircleCheck,
    clock: IconClock,
    copy: IconCopy,
    'currency-dollar': IconCurrencyDollar,
    'external-link': IconExternalLink,
    'file-invoice': IconFileInvoice,
    'file-text': IconFileText,
    hourglass: IconHourglass,
    inbox: IconInbox,
    key: IconKey,
    'layout-dashboard': IconLayoutDashboard,
    login: IconLogin,
    logout: IconLogout,
    'map-pin': IconMapPin,
    'menu-2': IconMenu2,
    moon: IconMoon,
    note: IconNote,
    pencil: IconPencil,
    phone: IconPhone,
    plus: IconPlus,
    receipt: IconReceipt,
    school: IconSchool,
    search: IconSearch,
    settings: IconSettings,
    'shield-lock': IconShieldLock,
    sun: IconSun,
    upload: IconUpload,
    user: IconUser,
    'user-check': IconUserCheck,
    'user-minus': IconUserMinus,
    'user-off': IconUserOff,
    'user-plus': IconUserPlus,
    users: IconUsers,
    video: IconVideo,
    wallet: IconWallet,
    world: IconWorld,
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
