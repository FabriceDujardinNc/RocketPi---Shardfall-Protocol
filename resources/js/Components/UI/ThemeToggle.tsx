import { useEffect, useState } from 'react';
import { Sun, Moon } from 'lucide-react';
import { applyTheme, persistTheme, resolveInitialTheme, type Theme } from '@/theme';

/**
 * Bouton de bascule clair/sombre.
 *
 * Reflète l'état actuel via aria-pressed et l'icône. Persist le choix
 * en localStorage et applique la classe `.theme-light` sur <html>.
 */
export default function ThemeToggle() {
    const [theme, setTheme] = useState<Theme>('dark');

    useEffect(() => {
        // Lecture initiale post-mount (le applyTheme global dans app.tsx
        // s'est déjà occupé du rendu, ici on synchronise juste le state React).
        setTheme(resolveInitialTheme());
    }, []);

    const toggle = () => {
        const next: Theme = theme === 'dark' ? 'light' : 'dark';
        setTheme(next);
        applyTheme(next);
        persistTheme(next);
    };

    const isDark = theme === 'dark';

    return (
        <button
            type="button"
            onClick={toggle}
            aria-pressed={!isDark}
            aria-label={isDark ? 'Passer en mode clair' : 'Passer en mode sombre'}
            title={isDark ? 'Mode clair' : 'Mode sombre'}
            className="inline-flex items-center justify-center size-9 rounded-md text-text-medium hover:text-text-high hover:bg-bg-elev2 transition-colors duration-fast"
        >
            {isDark ? <Moon size={16} /> : <Sun size={16} />}
        </button>
    );
}
