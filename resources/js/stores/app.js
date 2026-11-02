import { defineStore } from 'pinia';

const LG_BREAKPOINT = '(min-width: 1280px)';

function initialSidebarDrawer() {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
        return true;
    }

    return window.matchMedia(LG_BREAKPOINT).matches;
}

export const useAppStore = defineStore('app', {
    state: () => ({
        sidebarDrawer: initialSidebarDrawer(),
        // Desktop starts collapsed to icons (rail); the toggle expands it
        sidebarRail: initialSidebarDrawer(),
    }),

    actions: {
        toggleSidebarRail() {
            this.sidebarRail = !this.sidebarRail;
        },

        toggleSidebarDrawer() {
            this.sidebarDrawer = !this.sidebarDrawer;
            this.sidebarRail = false;
        },

        setSidebarRail(value) {
            this.sidebarRail = Boolean(value);
        },

        setSidebarDrawer(value) {
            this.sidebarDrawer = Boolean(value);
        },
    },
});
