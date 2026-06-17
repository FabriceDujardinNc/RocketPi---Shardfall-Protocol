import { Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import SEO from '@/Components/SEO';
import { type FormEventHandler } from 'react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/register', { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <>
            <SEO
                title="Inscription"
                description="Crée ton compte RocketPi pour jouer, voter pour les prochaines idées de développement, et soutenir le jeu."
                noindex
            />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-2 text-center">
                Créer un compte
            </h1>
            <p className="text-center text-text-medium text-sm mb-8 font-mono">
                Un compte est nécessaire pour jouer et proposer des idées.
            </p>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Pseudo</span>
                    <input
                        type="text"
                        autoComplete="username"
                        required
                        maxLength={80}
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.name && <span className="text-danger text-xs">{errors.name}</span>}
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Email</span>
                    <input
                        type="email"
                        autoComplete="email"
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
                        autoComplete="new-password"
                        required
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.password && <span className="text-danger text-xs">{errors.password}</span>}
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Confirmer le mot de passe</span>
                    <input
                        type="password"
                        autoComplete="new-password"
                        required
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>

                <Button type="submit" loading={processing} fullWidth size="lg" variant="shard">
                    S'inscrire
                </Button>

                <div className="text-center text-sm pt-2">
                    <span className="text-text-medium">Déjà un compte ? </span>
                    <Link href="/login" className="text-shard-400 hover:text-shard-300">Connexion</Link>
                </div>
            </form>
        </>
    );
}

Register.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
