import { readdirSync, readFileSync } from 'node:fs';
import { baseCompile } from '@intlify/message-compiler';
import { describe, expect, it } from 'vitest';

/**
 * Every translation string must compile with vue-i18n's message syntax. A bare @ is
 * read as a link to another message, and { } | are special too: one broken string
 * stops the page that uses it.
 * Write them literally as {'@'}, {'{'}, {'}'} and {'|'}.
 */
const dir = new URL('./', import.meta.url);

/** @returns {Array<[string, string]>} [key path, message] */
function messagesOf(node, prefix = '') {
    if (typeof node === 'string') {
        return [[prefix, node]];
    }
    if (node && typeof node === 'object') {
        return Object.entries(node).flatMap(([key, value]) => messagesOf(value, prefix ? `${prefix}.${key}` : key));
    }

    return [];
}

function compileErrors(message) {
    const errors = [];
    try {
        baseCompile(message, { onError: (error) => errors.push(error.message) });
    } catch (error) {
        errors.push(error.message);
    }

    return errors;
}

describe('translation strings', () => {
    const files = readdirSync(dir).filter((file) => file.endsWith('.json'));

    it('finds the locale files', () => {
        expect(files.length).toBeGreaterThan(0);
    });

    for (const file of files) {
        it(`every message in ${file} compiles`, () => {
            const messages = messagesOf(JSON.parse(readFileSync(new URL(file, dir), 'utf8')));
            const broken = messages
                .map(([key, text]) => [key, text, compileErrors(text)])
                .filter(([, , errors]) => errors.length > 0)
                .map(([key, text, errors]) => `${key}: ${errors[0]}  →  ${text}`);

            expect(broken, "Write a literal @ { } | as {'@'} {'{'} {'}'} {'|'}").toEqual([]);
        });
    }

    it('catches a bare @ (the check itself works)', () => {
        expect(compileErrors('اكتب @ ثم الاسم')).not.toEqual([]);
        expect(compileErrors("اكتب {'@'} ثم الاسم")).toEqual([]);
    });
});
