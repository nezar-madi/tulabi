import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { accountSidebarItems, adminSidebarItems } from '@/config/sidebarItems';

/** True on the admin pages (their own menu, sign-out and home). */
export function useIsAdminArea() {
    const page = usePage();

    return computed(() => String(page.component || '').startsWith('Admin/'));
}

/** The side menu for whoever is signed in: the admin's, or the account's. */
export function useSidebarItems() {
    const page = usePage();
    const isAdmin = useIsAdminArea();

    return computed(() => (isAdmin.value ? adminSidebarItems() : accountSidebarItems(page.props.auth?.user)));
}

/** Where "الرئيسية" leads. */
export function useHomeRoute() {
    const page = usePage();
    const isAdmin = useIsAdminArea();

    return computed(() => {
        if (isAdmin.value) {
            return 'admin.dashboard';
        }

        return page.props.auth?.user?.isInstructor ? 'teach.dashboard' : 'student.classes.index';
    });
}
