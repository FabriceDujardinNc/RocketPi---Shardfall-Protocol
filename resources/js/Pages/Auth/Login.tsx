import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import { type FormEventHandler } from 'react';

export default function Login() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/login', { onFinish: () => reset('password') });
    };

    return (
        <>
            <Head title="Connexion" />
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
        </>
    );
}

Login.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
