#!/usr/bin/env node
/**
 * Dead-class check.
 *
 * `tailwind.config.js` REPLACES Tailwind's colour, spacing, radius, shadow and z-index
 * scales rather than extending them (see the header comment in that file — utilities that
 * do not exist cannot drift). The trade-off is that a class outside those scales compiles
 * to nothing at all, silently: no build error, no console warning, the class just evaporates.
 *
 * This is how `text-on-action` (the token is `text-action-on`) shipped a 2.90:1 donate
 * button, and how `py-10` shipped a `<main>` with zero padding. See docs/10-UI-UX-AUDIT.md.
 *
 * The compiled stylesheet is the ground truth. This script extracts every class the
 * templates reference and fails if any of them is absent from it.
 *
 * Run AFTER a build:
 *   npx vite build && node scripts/check-dead-classes.mjs
 */

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const ROOT = new URL('..', import.meta.url).pathname.replace(/\/$/, '');
const BUILD_DIR = join(ROOT, 'public/build/assets');
const SOURCE_DIRS = ['resources/views', 'resources/js', 'app/Filament', 'app/Livewire'];
const SOURCE_EXT = /\.(blade\.php|php|js)$/;

/** Classes we deliberately allow even though Tailwind never emits them. */
const ALLOW = new Set([
    'tabular',   // tokens.css — font-variant-numeric helper
    'prose',     // @tailwindcss/typography emits these under its own plugin selectors
    'prose-sm',
]);

function walk(dir, out = []) {
    let entries;
    try {
        entries = readdirSync(dir);
    } catch {
        return out;
    }
    for (const entry of entries) {
        const full = join(dir, entry);
        if (statSync(full).isDirectory()) walk(full, out);
        else if (SOURCE_EXT.test(entry)) out.push(full);
    }
    return out;
}

// ---------------------------------------------------------------------------------------
// 1. Every class selector present in the compiled stylesheet.
// ---------------------------------------------------------------------------------------

const cssFiles = readdirSync(BUILD_DIR).filter((f) => /^app-.*\.css$/.test(f));
if (cssFiles.length === 0) {
    console.error('No built stylesheet in public/build/assets. Run `npx vite build` first.');
    process.exit(2);
}
// Newest build wins — stale hashed files from earlier builds linger in the directory.
const cssFile = cssFiles
    .map((f) => ({ f, mtime: statSync(join(BUILD_DIR, f)).mtimeMs }))
    .sort((a, b) => b.mtime - a.mtime)[0].f;

const css = readFileSync(join(BUILD_DIR, cssFile), 'utf8');

const generated = new Set();
// A class selector: `.` followed by name chars, where `\x` is an escaped literal.
// Escaped `:` `.` `/` `[` `]` are part of the class name; unescaped ones are not.
for (const match of css.matchAll(/(?<![\w\\])\.((?:[^\s.,{}()>+~'"\\[\]]|\\.)+)/g)) {
    let sel = match[1];
    // Strip trailing real pseudo-classes/elements: `.hover\:text-link:hover` -> `hover\:text-link`
    let prev;
    do {
        prev = sel;
        sel = sel.replace(/(?<!\\)::?[a-z-]+(\([^)]*\))?$/, '');
    } while (sel !== prev);
    generated.add(sel.replace(/\\/g, ''));
}

// ---------------------------------------------------------------------------------------
// 2. Every class the templates reference.
// ---------------------------------------------------------------------------------------

// `class="..."`, `:class="..."` (Alpine), `'class' => '...'` (Blade component merges).
const ATTR = /(?::|wire:|x-bind:)?class(?:es)?["']?\s*(?:=>|=|:)\s*(["'])([\s\S]*?)\1/g;

const dead = new Map();

for (const dir of SOURCE_DIRS) {
    for (const file of walk(join(ROOT, dir))) {
        const src = readFileSync(file, 'utf8');
        const lines = src.split('\n');

        for (const match of src.matchAll(ATTR)) {
            const blob = match[2];
            // Line number of the match start.
            const line = src.slice(0, match.index).split('\n').length;

            for (let token of blob.split(/\s+/)) {
                // Blade/Alpine ternaries put quoted class lists inside the attribute —
                // `{{ $x ? 'bg-action text-action-on' : 'border-line' }}`. Strip the quotes
                // so the real class tokens inside are still checked; that is exactly where
                // the `text-on-action` bug hid.
                token = token.replace(/^['"]+|['"]+$/g, '').replace(/^!/, '');

                if (!token) continue;
                if (ALLOW.has(token)) continue;
                // Skip interpolation, PHP/JS operators, and anything not class-shaped.
                if (/[{}$@()?=<>|&!]/.test(token)) continue;
                if (!/^[a-z0-9][a-z0-9:./[\]#%_-]*$/i.test(token)) continue;
                // A bare word with no dash/slash is an app class or a stray keyword, not a utility.
                if (!/[-/]/.test(token)) continue;
                if (generated.has(token)) continue;

                const key = token;
                if (!dead.has(key)) dead.set(key, []);
                dead.get(key).push(`${relative(ROOT, file)}:${line}`);
            }
        }
    }
}

// ---------------------------------------------------------------------------------------
// 3. Report.
// ---------------------------------------------------------------------------------------

if (dead.size === 0) {
    console.log(`✓ No dead classes. Checked against ${cssFile}.`);
    process.exit(0);
}

const total = [...dead.values()].reduce((n, v) => n + v.length, 0);
console.error(`\n✗ ${dead.size} class${dead.size === 1 ? '' : 'es'} generate no CSS (${total} occurrence${total === 1 ? '' : 's'}).`);
console.error('  These compile to nothing — the scales in tailwind.config.js are replacements, not extensions.\n');

for (const [cls, sites] of [...dead].sort((a, b) => b[1].length - a[1].length)) {
    console.error(`  ${cls.padEnd(28)} ×${String(sites.length).padStart(3)}`);
    for (const site of [...new Set(sites)].slice(0, 8)) console.error(`      ${site}`);
    if (new Set(sites).size > 8) console.error(`      … and ${new Set(sites).size - 8} more`);
}
console.error('');
process.exit(1);
