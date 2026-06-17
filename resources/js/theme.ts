/**
 * Gestion du thème clair/sombre.
 *
 * Sources prioritaires (du plus prioritaire au moins) :
 *   1. localStorage('rocketpi.theme') = 'dark' | 'light'
 *   2. matchMedia('(prefers-color-scheme: light)') de l'OS
 *   3. Défaut : dark (cohérent avec l'identité Shardfall)
 *
 * Le thème est appliqué via la classe `.theme-light` sur <html>.
 * Les tokens CSS surchargés sont définis dans resources/css/app.css.
 */

export type Theme = 'dark' | 'light';
const STORAGE_KEY = 'rocketpi.theme';

export function resolveInitialTheme(): Theme {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored === 'dark' || stored === 'light') return stored;
    } catch {
        // SSR ou localStorage inaccessible (mode privé strict).
    }
    if (typeof window !== 'undefined' && window.matchMedia?.('(prefers-color-scheme: light)').matches) {
        return 'light';
    }
    return 'dark';
}

export function applyTheme(theme: Theme): void {
    if (typeof document === 'undefined') return;
    document.documentElement.classList.toggle('theme-light', theme === 'light');
}

export function persistTheme(theme: Theme): void {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Ignore : localStorage indisponible, choix non persisté.
    }
}
