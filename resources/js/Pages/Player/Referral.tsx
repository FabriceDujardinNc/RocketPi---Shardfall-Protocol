import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import { useState } from 'react';

interface Props {
    referralCode: string;
    referralLink: string;
    referredCount: number;
    pendingRewards: { id: number; label: string }[];
}

export default function Referral({ referralCode, referralLink, referredCount, pendingRewards }: Props) {
    const [copied, setCopied] = useState(false);

    const copy = async () => {
        await navigator.clipboard.writeText(referralLink);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    return (
        <>
            <Head title="Parrainage" />
            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Signal Shard</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Parrainage</h1>
            </header>

            <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 mb-6">
                <p className="font-display text-xs uppercase tracking-wide text-text-low">Ton code</p>
                <p className="font-mono text-2xl text-shard-400 tracking-widest mt-2">{referralCode}</p>
                <p className="font-mono text-sm text-text-medium mt-4 break-all">{referralLink}</p>
                <Button onClick={copy} variant="secondary" size="sm" className="mt-4">
                    {copied ? 'Copié !' : 'Copier le lien'}
                </Button>
            </section>

            <section className="grid md:grid-cols-2 gap-4">
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Filleuls actifs</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">{referredCount}</p>
                </div>
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">Récompenses en attente</p>
                    <p className="font-display text-3xl font-bold text-text-high mt-2">{pendingRewards.length}</p>
                </div>
            </section>
        </>
    );
}

Referral.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
