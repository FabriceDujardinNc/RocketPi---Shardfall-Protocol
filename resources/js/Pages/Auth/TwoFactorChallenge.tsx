import { useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import SEO from '@/Components/SEO';
import { type FormEventHandler, useState } from 'react';

export default function TwoFactorChallenge() {
    const [mode, setMode] = useState<'totp' | 'recovery'>('totp');
    const { data, setData, post, processing, errors, reset } = useForm({
        code: '',
        recovery: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/2fa/challenge', { onFinish: () => reset() });
    };

    return (
        <>
            <SEO title="Vérification 2FA" description="Entre ton code à 6 chiffres pour continuer." noindex />

            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2 text-center">
                Vérification 2FA
            </h1>
            <p className="text-center text-text-medium text-sm mb-8 font-mono">
                {mode === 'totp'
                    ? "Entre le code à 6 chiffres de ton application d'authentification."
                    : 'Entre un code de secours (format XXXXX-XXXXX).'}
            </p>

            <form onSubmit={submit} className="flex flex-col gap-3">
                {mode === 'totp' ? (
                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">
                            Code TOTP
                        </span>
                        <input
                            type="text"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            autoFocus
                            required
                            maxLength={6}
                            value={data.code}
                            onChange={(e) => setData('code', e.target.value.replace(/\D/g, ''))}
                            className="h-12 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high font-mono text-2xl tracking-[0.5em] text-center focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                    </label>
                ) : (
                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">
                            Code de secours
                        </span>
                        <input
                            type="text"
                            autoFocus
                            required
                            maxLength={32}
                            value={data.recovery}
                            onChange={(e) => setData('recovery', e.target.value.toUpperCase())}
                            placeholder="XXXXX-XXXXX"
                            className="h-12 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high font-mono text-lg tracking-wider text-center focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                    </label>
                )}

                {errors.code && <span className="text-danger text-xs">{errors.code}</span>}

                <Button type="submit" loading={processing} fullWidth size="lg" variant="shard">
                    Vérifier
                </Button>

                <button
                    type="button"
                    onClick={() => { setMode(mode === 'totp' ? 'recovery' : 'totp'); reset(); }}
                    className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high mt-2"
                >
                    {mode === 'totp' ? 'Utiliser un code de secours →' : '← Revenir au code TOTP'}
                </button>
            </form>
        </>
    );
}

TwoFactorChallenge.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
