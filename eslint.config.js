// ESLint flat config (ESLint 9+) — RocketPi
// Enforce le Design System : interdit les valeurs hardcodées dans le JSX/TSX.

import js from '@eslint/js';
import tseslint from 'typescript-eslint';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';

export default tseslint.config(
    {
        ignores: [
            'node_modules',
            'public/build',
            'public/game',
            'storybook-static',
            'vendor',
            'storage',
            '*.config.js',
            '*.config.ts',
        ],
    },

    js.configs.recommended,
    ...tseslint.configs.recommended,

    {
        files: ['resources/js/**/*.{ts,tsx}'],

        languageOptions: {
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
                ecmaFeatures: { jsx: true },
            },
            globals: {
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                console: 'readonly',
                fetch: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                URL: 'readonly',
                URLSearchParams: 'readonly',
                Element: 'readonly',
                HTMLElement: 'readonly',
                HTMLInputElement: 'readonly',
                HTMLButtonElement: 'readonly',
                HTMLImageElement: 'readonly',
                HTMLDivElement: 'readonly',
                HTMLTableElement: 'readonly',
                HTMLTableSectionElement: 'readonly',
                HTMLTableRowElement: 'readonly',
                HTMLTableCellElement: 'readonly',
                HTMLSelectElement: 'readonly',
            },
        },

        plugins: {
            react,
            'react-hooks': reactHooks,
        },

        settings: {
            react: { version: 'detect' },
        },

        rules: {
            ...react.configs.recommended.rules,
            ...reactHooks.configs.recommended.rules,

            // React 17+ JSX transform — pas besoin d'importer React
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            // Texte français autorisé tel quel (accents, apostrophes…)
            'react/no-unescaped-entities': 'off',

            // ── Design System enforcement ────────────────────────────
            // Interdit les valeurs hex hardcodées dans le JSX (couleurs).
            // Toutes les couleurs doivent venir des tokens Tailwind 4 @theme.
            'no-restricted-syntax': [
                'error',
                {
                    selector: "JSXAttribute[name.name='className'] Literal[value=/(?:bg|text|border|from|to|via|ring|outline|fill|stroke)-\\[#/]",
                    message: '❌ Couleur hex hardcodée interdite dans className. Utilise un token du Design System (bg-shard-500, text-text-high, etc.)',
                },
                {
                    selector: "JSXAttribute[name.name='className'] Literal[value=/(?:m|p|w|h|gap|inset|top|bottom|left|right|space|size)(?:-[a-z])?-\\[/]",
                    message: '❌ Valeur arbitraire d\'espacement/taille interdite (ex: mt-[13px]). Utilise l\'échelle 4px du Design System.',
                },
                {
                    selector: "JSXAttribute[name.name='style'] Property[key.name=/^(color|background|backgroundColor|borderColor)$/] Literal[value=/^#/]",
                    message: '❌ Couleur hex hardcodée dans style={{}}. Utilise les classes Tailwind issues du Design System.',
                },
            ],

            // TypeScript — autoriser any en dev mais warn
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/no-unused-vars': [
                'warn',
                { argsIgnorePattern: '^_', varsIgnorePattern: '^_' },
            ],
        },
    },
);
