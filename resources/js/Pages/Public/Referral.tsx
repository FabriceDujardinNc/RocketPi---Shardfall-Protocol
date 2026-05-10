import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';

interface Props {
    code: string;
}

export default function Referral({ code }: Props) {
    return (
        <>
            <Head title={`Invitation ${code}`} />
            <div className="text-center">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400 mb-2">
                    Signal Shard
                </p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-4">
                    Tu as été invité
                </h1>
                <p className="font-mono text-lg text-text-high tracking-widest mb-6">{code}</p>
                <p className="text-text-medium text-sm mb-8 leading-relaxed">
                    Crée ton compte pour recevoir <strong className="text-shard-400">5 tirages gratuits</strong>,
                    un Opérateur Rare et 500 monnaie premium dès la vérification de ton email.
                </p>
                <Link href="/register">
                    <Button size="lg" variant="shard" fullWidth>
                        Activer le code
                    </Button>
                </Link>
            </div>
        </>
    );
}

Referral.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
