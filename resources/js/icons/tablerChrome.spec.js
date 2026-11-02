import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';
import { tablerChromeIcons } from './tablerChrome';

/**
 * Only icons listed in tablerChrome.js can be shown; any other "tabler:name" renders
 * as nothing. Every name used in
 * the frontend or sent by the server must be registered.
 */
const root = fileURLToPath(new URL('../../../', import.meta.url));
const SCAN = [
    ['resources/js', /\.(vue|js)$/],
    ['app', /\.php$/],
    ['config', /\.php$/],
];

function filesIn(dir, pattern) {
    return readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);
        if (statSync(path).isDirectory()) {
            return filesIn(path, pattern);
        }

        return pattern.test(name) && !name.endsWith('.spec.js') ? [path] : [];
    });
}

describe('tabler icons', () => {
    it('every tabler: icon used is registered', () => {
        const missing = new Set();
        for (const [dir, pattern] of SCAN) {
            for (const file of filesIn(join(root, dir), pattern)) {
                for (const [, name] of readFileSync(file, 'utf8').matchAll(/tabler:([a-z0-9]+(?:-[a-z0-9]+)*)/g)) {
                    if (!tablerChromeIcons[name]) {
                        missing.add(`tabler:${name}  (${file.slice(root.length)})`);
                    }
                }
            }
        }

        expect([...missing], 'Import the icon from @tabler/icons-vue and add it to tablerChromeIcons').toEqual([]);
    });
});
