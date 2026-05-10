import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import BannerCard from '@game/BannerCard';
import CurrencyDisplay from '@game/CurrencyDisplay';

interface Banner {
    id: number;
    name: string;
    tag: string | null;
    subtitle: string | null;
    type: 'permanent' | 'event' | 'faction' | 'collab';
    featured_operator: string | null;
    rate_up_operators: string[] | null;
    banner_image_url: string | null;
    rate_legendary: string;
    rate_epic: string;
    pity_legendary: number;
    pity_epic: number;
    soft_pity_start: number;
    starts_at: string | null;
    ends_at: string | null;
}

interface Props {
    banners: Banner[];
    shards: number;
}

export default function Gacha({ banners, shards }: Props) {
    return (
        <>
            <Head title="Recrutement" />
            <header className="flex items-end justify-between mb-8">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Signal Shard</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Recrutement</h1>
                </div>
                <CurrencyDisplay currency="premium" amount={shards} />
            </header>

            {banners.length === 0 ? (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                    Aucune bannière active.
                </div>
            ) : (
                <div className="grid md:grid-cols-2 gap-6">
                    {banners.map(b => (
                        <Link key={b.id} href={`/gacha/${b.id}`} className="block">
                            <BannerCard
                                name={b.name}
                                description={b.subtitle ?? b.tag ?? undefined}
                                imageUrl={b.banner_image_url ?? undefined}
                                endsAt={b.ends_at}
                                featured={b.type !== 'permanent'}
                            />
                        </Link>
                    ))}
                </div>
            )}
        </>
    );
}

Gacha.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
