import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { installSubmitGuard } from './submitGuard';

// A router with the three events the guard listens to.
function fakeRouter() {
    const handlers = {};

    return {
        on: (name, callback) => {
            handlers[name] = callback;
        },
        // Returns false when the guard refused the visit.
        visit(method) {
            const event = { detail: { visit: { method } }, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; } };
            const result = handlers.before(event);

            return !(result === false || event.defaultPrevented);
        },
        start: (method) => handlers.start({ detail: { visit: { method } } }),
        finish: (method) => handlers.finish({ detail: { visit: { method } } }),
    };
}

describe('submit guard', () => {
    let router;
    let root;
    let clock;

    beforeEach(() => {
        vi.useFakeTimers();
        router = fakeRouter();
        root = { dataset: {} };
        clock = 1_000;
        installSubmitGuard(router, root, () => clock);
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('refuses a second change while the first is running, and allows it after', () => {
        expect(router.visit('post')).toBe(true);
        router.start('post');
        expect(router.visit('post')).toBe(false);
        expect(router.visit('delete')).toBe(false);

        router.finish('post');
        expect(router.visit('put')).toBe(true);
    });

    it('refuses a second press that comes before the first has started', () => {
        expect(router.visit('post')).toBe(true);
        expect(router.visit('post')).toBe(false);

        // The first one never started: the wait is only a moment.
        clock += 1_500;
        expect(router.visit('post')).toBe(true);
    });

    it('never blocks reading a page', () => {
        router.visit('post');
        router.start('post');

        expect(router.visit('get')).toBe(true);
        router.start('get');
        router.finish('get');
        expect(router.visit('get')).toBe(true);
        // A finished read does not free the change that is still running.
        expect(router.visit('post')).toBe(false);
    });

    it('does not wait on a request that never came back', () => {
        router.visit('post');
        router.start('post');
        expect(router.visit('post')).toBe(false);

        clock += 31_000;
        expect(router.visit('post')).toBe(true);
    });

    it('marks the page busy after a moment, and clears it when the change ends', () => {
        router.visit('post');
        router.start('post');
        expect(root.dataset.busy).toBeUndefined();

        vi.advanceTimersByTime(200);
        expect(root.dataset.busy).toBe('1');

        router.finish('post');
        expect(root.dataset.busy).toBeUndefined();
    });

    it('does not flicker the mark for a quick change', () => {
        router.visit('post');
        router.start('post');
        vi.advanceTimersByTime(50);
        router.finish('post');
        vi.advanceTimersByTime(500);

        expect(root.dataset.busy).toBeUndefined();
    });
});
