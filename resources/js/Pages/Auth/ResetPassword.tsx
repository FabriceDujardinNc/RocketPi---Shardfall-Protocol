import { useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import SEO from '@/Components/SEO';
import { type FormEventHandler } from 'react';

interface Props {
    token: string;
    email: string;
}

export default function ResetPassword({ token, email }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reset-password', { onFinish: () => reset('password', 'password_confirmation') });
    };

    return (
        <>
            <SEO title="Nouveau mot de passe" description="Définis un nouveau mot de passe pour ton compte RocketPi." noindex />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8 text-center">
                Nouveau mot de passe
            </h1>

            <form onSubmit={submit} className="flex flex-col gap-4">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Email</span>
                    <input
                        type="email"
                        readOnly
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-medium"
                    />
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Mot de passe</span>
                    <input
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.password && <span className="text-danger text-xs">{errors.password}</span>}
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Confirmer</span>
                    <input
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>

                <Button type="submit" loading={processing} fullWidth size="lg">
                    Réinitialiser
                </Button>
            </form>
        </>
    );
}

ResetPassword.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
