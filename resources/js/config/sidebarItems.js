/*
 * The side menu (rqmana's shape: id, titleKey, icon, routeName, sectionKey, label).
 * One menu for an account (the instructor part only for instructors) and one for the admin.
 */

/** @param {{ isInstructor?: boolean, pendingRequests?: number } | null} user */
export function accountSidebarItems(user) {
    const items = [];

    if (user?.isInstructor) {
        const pending = user.pendingRequests || 0;

        items.push(
            { id: 'teach-dashboard', titleKey: 'teach.nav.dashboard', icon: 'tabler:layout-dashboard', routeName: 'teach.dashboard', sectionKey: 'sidebar.sections.teaching' },
            {
                id: 'teach-requests',
                titleKey: 'teach.nav.requests',
                icon: 'tabler:inbox',
                routeName: 'teach.requests.index',
                sectionKey: 'sidebar.sections.teaching',
                label: pending > 0 ? { text: String(pending), color: 'warning' } : null,
            },
            { id: 'teach-classes', titleKey: 'teach.nav.classes', icon: 'tabler:chalkboard', routeName: 'teach.classes.index', sectionKey: 'sidebar.sections.teaching' },
            { id: 'teach-students', titleKey: 'teach.nav.students', icon: 'tabler:users', routeName: 'teach.students.index', sectionKey: 'sidebar.sections.teaching' },
            { id: 'teach-profile', titleKey: 'teach.nav.profile', icon: 'tabler:id-badge-2', routeName: 'teach.profile.edit', sectionKey: 'sidebar.sections.teaching' },
        );
    }

    items.push(
        { id: 'student-classes', titleKey: 'nav.my_classes', icon: 'tabler:school', routeName: 'student.classes.index', sectionKey: 'sidebar.sections.learning' },
        { id: 'student-payments', titleKey: 'nav.my_payments', icon: 'tabler:receipt', routeName: 'student.payments.index', sectionKey: 'sidebar.sections.learning' },
        { id: 'account', titleKey: 'nav.account', icon: 'tabler:user-cog', routeName: 'account', sectionKey: 'sidebar.sections.account' },
    );

    if (!user?.isInstructor) {
        items.push({ id: 'join', titleKey: 'nav.join', icon: 'tabler:user-plus', routeName: 'join', sectionKey: 'sidebar.sections.account' });
    }

    return items;
}

export function adminSidebarItems() {
    return [
        { id: 'admin-dashboard', titleKey: 'admin.nav.dashboard', icon: 'tabler:layout-dashboard', routeName: 'admin.dashboard', sectionKey: 'sidebar.sections.admin' },
        { id: 'admin-applications', titleKey: 'admin.nav.applications', icon: 'tabler:file-text', routeName: 'admin.applications.index', sectionKey: 'sidebar.sections.admin' },
        { id: 'admin-instructors', titleKey: 'admin.nav.instructors', icon: 'tabler:school', routeName: 'admin.instructors.index', sectionKey: 'sidebar.sections.people' },
        { id: 'admin-students', titleKey: 'admin.nav.students', icon: 'tabler:users', routeName: 'admin.students.index', sectionKey: 'sidebar.sections.people' },
        { id: 'admin-settings', titleKey: 'admin.nav.settings', icon: 'tabler:settings', routeName: 'admin.settings.edit', sectionKey: 'sidebar.sections.settings' },
        { id: 'admin-two-factor', titleKey: 'admin.nav.two_factor', icon: 'tabler:shield-lock', routeName: 'admin.two-factor.setup', sectionKey: 'sidebar.sections.settings' },
    ];
}
