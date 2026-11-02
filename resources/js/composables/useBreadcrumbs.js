import { computed, unref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useHomeRoute, useSidebarItems } from '@/composables/useSidebarItems';

/**
 * Breadcrumb trail for the current page, built from the sidebar navigation:
 *   الرئيسية › [group] › [section page] › [current page]
 * The section page is the nav item whose route matches the current route, or whose route shares
 * its prefix (students.show → students.index). `currentLabel` names the page itself when it isn't
 * a nav item (a record's name, "إضافة طالب"...).
 */
export function useBreadcrumbs(currentLabel) {
    const { t } = useI18n();
    const sidebarItems = useSidebarItems();
    const homeRoute = useHomeRoute();

    function safeRoute(name, params) {
        try {
            return route(name, params ?? {});
        } catch {
            return null;
        }
    }

    function currentRouteName() {
        try {
            return route().current() || '';
        } catch {
            return '';
        }
    }

    function flatten(items, parent = null) {
        return items.flatMap((item) => {
            const self = item.routeName ? [{ item, parent }] : [];
            const children = Array.isArray(item.children) ? flatten(item.children, item) : [];

            return [...self, ...children];
        });
    }

    function prefix(name) {
        return String(name).split('.').slice(0, -1).join('.');
    }

    return computed(() => {
        const entries = flatten(sidebarItems.value);
        const current = currentRouteName();
        const label = unref(currentLabel) ?? '';
        const home = { title: t('nav.home'), href: safeRoute(homeRoute.value) };

        // A group can share its first child's route (Finance): prefer the entry nested in a group
        const byDepth = (a, b) => (b.parent ? 1 : 0) - (a.parent ? 1 : 0);

        let match = entries.filter(({ item }) => item.routeName === current).sort(byDepth)[0];
        const exact = Boolean(match);

        if (!match && current) {
            // Record pages (show/edit/create) belong to their section's list page (*.index)
            const currentPrefix = prefix(current);
            const siblings = entries
                .filter(({ item }) => currentPrefix && prefix(item.routeName) === currentPrefix)
                .sort(byDepth);
            match = siblings.find(({ item }) => item.routeName.endsWith('.index')) ?? siblings[0];
        }

        if (!match) {
            return label ? [home, { title: label }] : [home];
        }

        const trail = [home];

        if (match.parent) {
            trail.push({
                title: t(match.parent.titleKey),
                href: match.parent.routeName ? safeRoute(match.parent.routeName) : null,
            });
        }

        const itemTitle = t(match.item.titleKey);

        if (exact) {
            trail.push({ title: itemTitle });
        } else {
            trail.push({ title: itemTitle, href: safeRoute(match.item.routeName, match.item.routeParams) });

            if (label && label !== itemTitle) {
                trail.push({ title: label });
            }
        }

        return trail;
    });
}
