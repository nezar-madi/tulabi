/*
 * No second change while the first is still being saved. Every action button of the app (publish,
 * accept, delete, save, sign out...) goes through Inertia, so one guard on the router covers them
 * all: a request that changes something (anything but GET) is refused while another one is in
 * flight, whether it came from a double click, a repeated Enter key or two different buttons.
 * While one runs, the page is marked (html[data-busy]) so the buttons show they are waiting
 * (see app.css); the mark waits a moment so a quick save does not flicker.
 */
const STALE_MS = 30_000;
const PENDING_MS = 1_000;
const MARK_AFTER_MS = 150;

const changes = (visit) => Boolean(visit?.method) && visit.method !== 'get';

/**
 * @param {{ on: (name: string, callback: (event: any) => unknown) => unknown }} router
 * @param {{ dataset: Record<string, string> }} root the element that is marked (html)
 * @param {() => number} now
 */
export function installSubmitGuard(router, root = document.documentElement, now = Date.now) {
    let inFlight = 0;
    let startedAt = 0;
    // Allowed by «before» but not started yet (Inertia starts a moment later).
    let pendingAt = 0;
    let markTimer = null;

    const refresh = () => {
        clearTimeout(markTimer);

        if (inFlight > 0) {
            markTimer = setTimeout(() => {
                root.dataset.busy = '1';
            }, MARK_AFTER_MS);
        } else {
            delete root.dataset.busy;
        }
    };

    const busy = () => (inFlight > 0 && now() - startedAt < STALE_MS) || (pendingAt > 0 && now() - pendingAt < PENDING_MS);

    router.on('before', (event) => {
        if (!changes(event.detail?.visit)) {
            return undefined;
        }

        if (busy()) {
            event.preventDefault();

            return false;
        }

        pendingAt = now();

        return undefined;
    });

    router.on('start', (event) => {
        if (changes(event.detail?.visit)) {
            inFlight += 1;
            startedAt = now();
            pendingAt = 0;
            refresh();
        }
    });

    router.on('finish', (event) => {
        if (changes(event.detail?.visit)) {
            inFlight = Math.max(0, inFlight - 1);
            pendingAt = 0;
            refresh();
        }
    });
}
