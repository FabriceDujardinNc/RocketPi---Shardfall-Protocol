import { Head, Link, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import { type FormEventHandler } from 'react';

export default function ForgotPassword() {
    const { props } = usePage<{ flash?: { status?: string } }>();
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <>
            <Head title="Mot de passe oublié" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2 text-center">
                Mot de passe oublié
            </h1>
            <p className="text-center text-text-medium text-sm mb-8">
                On t'envoie un lien de réinitialisation par email.
            </p>

            {props.flash?.status && (
                <div className="mb-4 p-3 rounded-md bg-success/10 border border-success/30 text-success text-sm">
                    {props.flash.status}
                </div>
            )}

            <form onSubmit={submit} className="flex flex-col gap-4">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Email</span>
                    <input
                        type="email"
                        required
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.email && <span className="text-danger text-xs">{errors.email}</span>}
                </label>

                <Button type="submit" loading={processing} fullWidth size="lg">
                    Envoyer le lien
                </Button>

                <div className="text-center text-sm pt-2">
                    <Link href="/login" className="text-text-medium hover:text-text-high">
                        Retour à la connexion
                    </Link>
                </div>
            </form>
        </>
    );
}

ForgotPassword.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
