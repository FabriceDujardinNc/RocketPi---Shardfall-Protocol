import { Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import SEO from '@/Components/SEO';

interface Props {
    codes: string[];
}

export default function TwoFactorRecovery({ codes }: Props) {
    const downloadTxt = () => {
        const blob = new Blob(
            [
                'RocketPi: Shardfall Protocol — Codes de secours 2FA\n',
                '====================================================\n\n',
                ...codes.map((c) => `  ${c}\n`),
                '\nGarde ce fichier dans un endroit sûr.\n',
                "Chaque code n'est valable qu'une seule fois.\n",
            ],
            { type: 'text/plain' },
        );
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'rocketpi-2fa-recovery.txt';
        a.click();
        URL.revokeObjectURL(url);
    };

    return (
        <>
            <SEO title="Codes de secours 2FA" description="Codes de secours à conserver précieusement." noindex />

            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-2 text-center">
                Codes de secours
            </h1>
            <p className="text-center text-text-medium text-sm mb-6 font-mono">
                Affichés une seule fois — sauvegarde-les maintenant.
            </p>

            <Alert variant="warning" title="Important">
                Conserve ces codes en lieu sûr (gestionnaire de mots de passe, papier).
                Chaque code est à usage unique et permet de récupérer ton compte si tu perds
                ton appareil 2FA.
            </Alert>

            {codes.length === 0 ? (
                <p className="mt-6 text-center text-text-medium font-mono text-sm">
                    Codes déjà consultés ou session expirée.
                </p>
            ) : (
                <div className="mt-6 rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <ul className="grid grid-cols-2 gap-2 font-mono text-sm text-text-high">
                        {codes.map((c) => (
                            <li
                                key={c}
                                className="px-3 py-2 rounded bg-bg-elev2 border border-border-default text-center tabular-nums"
                            >
                                {c}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            <div className="mt-6 flex flex-col sm:flex-row gap-3">
                {codes.length > 0 && (
                    <Button onClick={downloadTxt} variant="secondary" fullWidth>
                        Télécharger en .txt
                    </Button>
                )}
                <Link
                    href="/play"
                    className="inline-flex items-center justify-center font-display font-semibold text-sm tracking-wide uppercase h-10 px-6 bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 rounded-md hover:brightness-110 transition-all w-full sm:w-auto sm:flex-1"
                >
                    Continuer
                </Link>
            </div>
        </>
    );
}

TwoFactorRecovery.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
