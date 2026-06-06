import { EditorState, StateField, StateEffect } from '@codemirror/state';
import { EditorView, Decoration, lineNumbers, keymap } from '@codemirror/view';
import {
    foldGutter,
    codeFolding,
    foldKeymap,
    syntaxHighlighting,
    defaultHighlightStyle,
} from '@codemirror/language';
import { json } from '@codemirror/lang-json';

/**
 * Read-only CodeMirror viewer for API responses: line numbers, collapsible
 * objects/arrays (code folding), JSON syntax highlighting and a self-contained
 * search (highlight all matches + step through them) driven by an external box.
 */

// Decoration layer for search matches (independent of @codemirror/search, which
// only highlights while its own panel is open).
const setMatches = StateEffect.define();
const matchField = StateField.define({
    create() {
        return Decoration.none;
    },
    update(deco, tr) {
        deco = deco.map(tr.changes);
        for (const effect of tr.effects) {
            if (effect.is(setMatches)) deco = effect.value;
        }
        return deco;
    },
    provide: (f) => EditorView.decorations.from(f),
});

const matchMark = Decoration.mark({ class: 'cm-find-match' });
const currentMark = Decoration.mark({ class: 'cm-find-match cm-find-current' });

function buildTheme() {
    const dark = document.documentElement.classList.contains('dark');

    return EditorView.theme(
        {
            '&': {
                backgroundColor: 'transparent',
                color: dark ? '#e5e7eb' : '#1f2937',
                fontSize: '12px',
            },
            '.cm-gutters': {
                backgroundColor: 'transparent',
                color: dark ? '#6b7280' : '#9ca3af',
                border: 'none',
            },
            '.cm-activeLine': { backgroundColor: 'transparent' },
            '.cm-activeLineGutter': { backgroundColor: 'transparent' },
            '.cm-scroller': { fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace', lineHeight: '1.5' },
            '.cm-find-match': {
                backgroundColor: dark ? 'rgba(251,191,36,0.30)' : '#fde68a',
                borderRadius: '2px',
            },
            '.cm-find-current': {
                backgroundColor: dark ? 'rgba(251,191,36,0.85)' : '#fbbf24',
                outline: '1px solid #f59e0b',
            },
        },
        { dark }
    );
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('responseViewer', (content, isJson) => ({
        view: null,
        query: '',
        matches: [],
        current: -1,

        init() {
            this.view = new EditorView({
                doc: content ?? '',
                parent: this.$refs.editor,
                extensions: [
                    lineNumbers(),
                    foldGutter(),
                    codeFolding(),
                    syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
                    isJson ? json() : [],
                    matchField,
                    keymap.of(foldKeymap),
                    EditorView.lineWrapping,
                    EditorState.readOnly.of(true),
                    EditorView.editable.of(false),
                    buildTheme(),
                ],
            });
        },

        /** Scan the document for every occurrence of the query and highlight them. */
        search() {
            if (!this.view) return;

            const needle = this.query.toLowerCase();
            this.matches = [];

            if (needle !== '') {
                const haystack = this.view.state.doc.toString().toLowerCase();
                let from = haystack.indexOf(needle);
                while (from !== -1) {
                    this.matches.push({ from, to: from + needle.length });
                    from = haystack.indexOf(needle, from + needle.length);
                }
            }

            this.current = this.matches.length ? 0 : -1;
            this.applyDecorations();
            this.scrollToCurrent();
        },

        /** Move to the next/previous match, (re)searching first if the query changed. */
        find(direction = 1) {
            if (!this.view) return;

            if (this.query !== this.lastQuery || this.matches.length === 0) {
                this.search();
                return;
            }

            const n = this.matches.length;
            this.current = (this.current + direction + n) % n;
            this.applyDecorations();
            this.scrollToCurrent();
        },

        applyDecorations() {
            this.lastQuery = this.query;

            const decorations = this.matches.map((m, i) =>
                (i === this.current ? currentMark : matchMark).range(m.from, m.to)
            );

            this.view.dispatch({
                effects: setMatches.of(Decoration.set(decorations, true)),
            });
        },

        scrollToCurrent() {
            const match = this.matches[this.current];
            if (!match) return;

            this.view.dispatch({
                effects: EditorView.scrollIntoView(match.from, { y: 'center' }),
            });
        },

        destroy() {
            this.view?.destroy();
            this.view = null;
        },
    }));
});
