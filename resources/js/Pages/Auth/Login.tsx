import { Link, router, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import SEO from '@/Components/SEO';
import { type FormEventHandler } from 'react';

interface DevUser {
    id: number;
    email: string;
    name: string;
    display_name: string | null;
    role: string;
    is_banned: boolean;
}

interface PageProps {
    app: { name: string; env: string };
    devUsers: DevUser[] | null;
    [key: string]: unknown;
}

export default function Login() {
    const { props } = usePage<PageProps>();
    const isDev = props.app?.env === 'local';

    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    // Dev quick login : submit le form standard avec flag `dev=1`.
    // Le controller ignore ce flag en prod (env check côté serveur).
    const quickLogin = (email: string) => {
        router.post('/login', { email, dev: true, password: '', remember: true });
    };

    return (
        <>
            <SEO
                title="Connexion"
                description="Connecte-toi à RocketPi: Shardfall Protocol. Accède à ta collection d'opérateurs, ton battle pass et tes classements."
                noindex
            />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8 text-center">
                Connexion
            </h1>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Email</span>
                    <input
                        type="email"
                        autoComplete="username"
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.email && <span className="text-danger text-xs">{errors.email}</span>}
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Mot de passe</span>
                    <input
                        type="password"
                        autoComplete="current-password"
                        required
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.password && <span className="text-danger text-xs">{errors.password}</span>}
                </label>

                <label className="flex items-center gap-2 text-sm text-text-medium">
                    <input
                        type="checkbox"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="accent-shard-500"
                    />
                    Se souvenir de moi
                </label>

                <Button type="submit" loading={processing} fullWidth size="lg">
                    Se connecter
                </Button>

                <div className="flex justify-between items-center text-sm pt-2">
                    <Link href="/forgot-password" className="text-text-medium hover:text-text-high">
                        Mot de passe oublié ?
                    </Link>
                    <Link href="/register" className="text-shard-400 hover:text-shard-300">
                        Créer un compte
                    </Link>
                </div>
            </form>

            {/* ── Dev quick login ───────────────────────────────────── */}
            {isDev && props.devUsers && props.devUsers.length > 0 && (
                <section className="mt-8 p-4 rounded-md border border-warning/30 bg-warning/5">
                    <header className="flex items-center justify-between mb-3">
                        <h3 className="font-display text-xs uppercase tracking-mega text-warning">
                            ⚡ Mode dev — connexion rapide
                        </h3>
                        <span className="font-mono text-[10px] text-text-low">APP_ENV=local</span>
                    </header>
                    <p className="font-mono text-xs text-text-low mb-3">
                        Cliquer pour se connecter sans mot de passe (désactivé en prod).
                    </p>
                    <ul className="flex flex-col gap-1.5">
                        {props.devUsers.map((u) => (
                            <li key={u.id}>
                                <button
                                    type="button"
                                    onClick={() => quickLogin(u.email)}
                                    className="w-full text-left px-3 py-2 rounded-md bg-bg-elev1 border border-border-default hover:border-shard-500/40 hover:bg-bg-elev2 transition-colors duration-fast group"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="font-mono text-xs text-text-high group-hover:text-shard-400 truncate">
                                            {u.email}
                                        </span>
                                        <span className={
                                            'font-display text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded shrink-0 ' +
                                            (u.is_banned        ? 'bg-danger/15 text-danger'   :
                                             u.role === 'super_admin' ? 'bg-rarity-legendary/15 text-rarity-legendary' :
                                             u.role === 'admin'  ? 'bg-shard-500/15 text-shard-400' :
                                                                   'bg-bg-elev3 text-text-medium')
                                        }>
                                            {u.is_banned ? 'BANNI' : u.role.replace('_', ' ')}
                                        </span>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}

Login.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
