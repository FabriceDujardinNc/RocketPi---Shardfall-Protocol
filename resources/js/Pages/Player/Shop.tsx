import { Head, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import CurrencyDisplay from '@game/CurrencyDisplay';

interface RewardLine { type: string; amount: number }

interface Pack {
    id: string;
    name: string;
    description: string;
    price_shards: number;
    is_one_shot: boolean;
    rewards: RewardLine[];
    affordable: boolean;
}

interface Props {
    packs: Pack[];
    shards: number;
    credits: number;
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

export default function Shop({ packs, shards, credits }: Props) {
    const { props } = usePage<PageProps>();

    const purchase = (pack: Pack) => {
        const msg = pack.price_shards > 0
            ? `Acheter « ${pack.name} » pour ${pack.price_shards} shards ?`
            : `Récupérer « ${pack.name} » ?`;
        if (!confirm(msg)) return;
        router.post('/shop/purchase', { pack_id: pack.id }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Boutique" />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Marché Shard</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Boutique</h1>
                </div>
                <div className="flex items-center gap-4">
                    <CurrencyDisplay currency="premium" amount={shards} />
                    <CurrencyDisplay currency="soft" amount={credits} />
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.shop && <div className="mb-4"><Alert variant="danger">{props.errors.shop}</Alert></div>}

            <Alert variant="info" title="Phase 3 — boutique en démo">
                Les paiements en € via Stripe arrivent en Phase 5. Pour l'instant tous les packs sont
                achetables en shards in-game (mode dev).
            </Alert>

            <section className="mt-6 grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                {packs.map(pack => (
                    <article
                        key={pack.id}
                        className="rounded-lg bg-bg-elev1 border border-border-default p-5 flex flex-col gap-3 hover:border-shard-500/40 transition-colors duration-fast"
                    >
                        <header>
                            <h3 className="font-display font-semibold text-base uppercase tracking-wide text-text-high">
                                {pack.name}
                            </h3>
                            <p className="font-body text-xs text-text-medium mt-1">{pack.description}</p>
                            {pack.is_one_shot && (
                                <span className="font-display text-[10px] uppercase tracking-mega text-warning mt-1 inline-block">
                                    1 achat unique
                                </span>
                            )}
                        </header>

                        <ul className="flex flex-col gap-1 font-mono text-sm">
                            {pack.rewards.map((r, i) => (
                                <li key={i} className="flex justify-between">
                                    <span className="text-text-medium">{r.type}</span>
                                    <span className="text-shard-400 tabular-nums">{r.amount.toLocaleString('fr-FR')}</span>
                                </li>
                            ))}
                        </ul>

                        <div className="mt-auto pt-3 border-t border-border-default">
                            <Button
                                onClick={() => purchase(pack)}
                                disabled={!pack.affordable}
                                fullWidth
                                variant={pack.price_shards === 0 ? 'shard' : 'primary'}
                            >
                                {pack.price_shards === 0
                                    ? 'Récupérer (gratuit)'
                                    : `Acheter — ${pack.price_shards} ✦`}
                            </Button>
                        </div>
                    </article>
                ))}
            </section>
        </>
    );
}

Shop.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
