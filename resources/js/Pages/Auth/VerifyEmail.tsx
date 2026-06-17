import { Link, useForm, usePage } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import SEO from '@/Components/SEO';
import { type FormEventHandler } from 'react';

export default function VerifyEmail() {
    const { props } = usePage<{ flash?: { status?: string } }>();
    const { post, processing } = useForm();

    const resend: FormEventHandler = (e) => {
        e.preventDefault();
        post('/email/resend');
    };

    return (
        <>
            <SEO title="Vérification email" description="Vérifie ton adresse email pour activer ton compte RocketPi." noindex />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2 text-center">
                Vérification email
            </h1>
            <p className="text-text-medium text-sm mb-6 text-center leading-relaxed">
                Un lien de vérification t'a été envoyé. Clique dessus pour activer ton compte.
            </p>

            {props.flash?.status === 'verification-link-sent' && (
                <div className="mb-4 p-3 rounded-md bg-success/10 border border-success/30 text-success text-sm text-center">
                    Lien renvoyé. Vérifie ta boîte mail.
                </div>
            )}

            <form onSubmit={resend} className="flex flex-col gap-3">
                <Button type="submit" loading={processing} fullWidth size="lg" variant="secondary">
                    Renvoyer le lien
                </Button>
                <Link
                    href="/logout"
                    method="post"
                    as="button"
                    className="text-center text-sm text-text-low hover:text-text-medium font-display uppercase tracking-wide"
                >
                    Déconnexion
                </Link>
            </form>
        </>
    );
}

VerifyEmail.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
