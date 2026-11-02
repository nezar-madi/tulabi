<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import SidebarItem from '@/Components/SidebarItem.vue';
import SidebarGroup from '@/Components/SidebarGroup.vue';
import { useSidebarItems } from '@/composables/useSidebarItems';
import { useAppStore } from '@/stores/app';

const appStore = useAppStore();
const sidebarItems = useSidebarItems();

defineProps({
    items: {
        type: Array,
        required: true,
    },
    nested: {
        type: Boolean,
        default: false,
    },
});

function getSidebarItemUrl(item) {
    if (!item?.routeName) {
        return null;
    }

    try {
        return route(item.routeName, item.routeParams ?? {});
    } catch {
        return null;
    }
}

function visitSidebarItem(item) {
    const url = getSidebarItemUrl(item);

    if (!url) {
        return;
    }

    if (typeof window !== 'undefined' && !window.matchMedia('(min-width: 1280px)').matches) {
        appStore.setSidebarDrawer(false);
    }

    router.visit(url);
}

/* Every route the navigation links to (a record page belongs to its section's list page) */
const navRouteNames = computed(() => new Set((function collect(items) {
    return items.flatMap((entry) => [
        ...(entry.routeName ? [entry.routeName] : []),
        ...(Array.isArray(entry.children) ? collect(entry.children) : []),
    ]);
})(sidebarItems.value)));

function isSidebarItemActive(item) {
    if (!item?.routeName) {
        return false;
    }

    try {
        if (route().current(item.routeName)) {
            return true;
        }

        // students.show / students.edit / students.create → the "الطلاب" item, unless the current
        // route is a nav item of its own (e.g. classes.create has its own entry)
        const current = route().current() || '';

        if (!item.routeName.endsWith('.index') || navRouteNames.value.has(current)) {
            return false;
        }

        return route().current(`${item.routeName.slice(0, -'.index'.length)}.*`);
    } catch {
        return false;
    }
}

function hasChildren(item) {
    return Array.isArray(item.children) && item.children.length > 0;
}

function onItemClick(item) {
    visitSidebarItem(item);
}

function itemDisabled(item) {
    return getSidebarItemUrl(item) === null;
}

function resolveIcon(icon) {
    return icon && String(icon).trim() !== '' ? icon : 'tabler:circle-dot';
}
</script>

<template>
    <template v-for="(item, index) in items" :key="item.id">
        <v-list-subheader
            v-if="!nested && item.sectionKey && item.sectionKey !== items[index - 1]?.sectionKey"
            class="sidebar-section-title"
        >
            {{ $t(item.sectionKey) }}
        </v-list-subheader>

        <SidebarGroup
            v-if="hasChildren(item)"
            :title="$t(item.titleKey)"
            :icon="item.icon || 'tabler:folder'"
        >
            <SidebarNav :items="item.children" nested />
        </SidebarGroup>

        <SidebarItem
            v-else
            :title="$t(item.titleKey)"
            :icon="resolveIcon(item.icon)"
            :active="isSidebarItemActive(item)"
            :disabled="itemDisabled(item)"
            :label="item.label"
            :variant="nested ? 'child' : 'default'"
            @click="onItemClick(item)"
        />
    </template>
</template>
