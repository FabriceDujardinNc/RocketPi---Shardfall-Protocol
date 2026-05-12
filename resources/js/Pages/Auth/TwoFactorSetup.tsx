import { useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import SEO from '@/Components/SEO';
import { type FormEventHandler } from 'react';

interface Props {
    secret: string;
    qrSvg: string;
    required: boolean;
}

export default function TwoFactorSetup({ secret, qrSvg, required }: Props) {
    const { data, setData, post, processing, errors } = useForm({ code: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/2fa/setup');
    };

    return (
        <>
            <SEO title="Activer la 2FA" description="Configure ton second facteur d'authentification." noindex />

            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2 text-center">
                Authentification à deux facteurs
            </h1>
            <p className="text-center text-text-medium text-sm mb-6 font-mono">
                {required
                    ? 'Activation obligatoire pour les comptes admin.'
                    : 'Renforce la sécurité de ton compte.'}
            </p>

            <Alert variant="info" title="Étapes">
                1. Scanne le QR avec Google Authenticator, Authy, 1Password ou Bitwarden.
                2. Entre le code à 6 chiffres généré par l'app pour confirmer.
            </Alert>

            <div className="mt-6 flex flex-col items-center gap-4">
                <div
                    className="rounded-lg bg-white p-3"
                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                />
                <details className="w-full">
                    <summary className="cursor-pointer font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high">
                        Saisie manuelle du secret
                    </summary>
                    <p className="mt-2 font-mono text-xs text-text-low break-all bg-bg-elev2 border border-border-default rounded p-3 select-all">
                        {secret}
                    </p>
                </details>
            </div>

            <form onSubmit={submit} className="mt-6 flex flex-col gap-3">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Code à 6 chiffres</span>
                    <input
                        type="text"
                        inputMode="numeric"
                        autoComplete="one-time-code"
                        required
                        maxLength={6}
                        value={data.code}
                        onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                        className="h-12 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high font-mono text-2xl tracking-[0.5em] text-center focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.code && <span className="text-danger text-xs">{errors.code}</span>}
                </label>

                <Button type="submit" loading={processing} fullWidth size="lg" variant="shard">
                    Activer la 2FA
                </Button>
            </form>
        </>
    );
}

TwoFactorSetup.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
