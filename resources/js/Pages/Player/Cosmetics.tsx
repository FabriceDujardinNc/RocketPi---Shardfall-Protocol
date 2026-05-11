import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import RarityBadge from '@game/RarityBadge';

interface CosmeticItem {
    id: number;
    slug: string;
    name: string;
    description: string | null;
    type: 'skin' | 'title' | 'voiceline' | 'banner' | 'border';
    rarity: 'common' | 'rare' | 'epic' | 'legendary';
    preview_url: string | null;
    is_equipped: boolean;
    unlocked_at: string;
    source: string | null;
    operator: { name: string; codename: string } | null;
}

interface Props {
    inventory: CosmeticItem[];
    totalCount: number;
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

const TYPE_LABELS: Record<string, string> = {
    all: 'Tous',
    skin: 'Skins',
    title: 'Titres',
    voiceline: 'Voicelines',
    banner: 'Bannières',
    border: 'Bordures',
};

const TYPE_ORDER = ['all', 'skin', 'title', 'voiceline', 'banner', 'border'] as const;

export default function Cosmetics({ inventory, totalCount }: Props) {
    const { props } = usePage<PageProps>();
    const [tab, setTab] = useState<string>('all');

    const filtered = useMemo(
        () => (tab === 'all' ? inventory : inventory.filter(c => c.type === tab)),
        [inventory, tab],
    );

    const byType: Record<string, number> = useMemo(() => {
        const out: Record<string, number> = {};
        inventory.forEach(c => { out[c.type] = (out[c.type] ?? 0) + 1; });
        return out;
    }, [inventory]);

    const toggleEquip = (item: CosmeticItem) => {
        const url = item.is_equipped
            ? `/cosmetics/${item.slug}/unequip`
            : `/cosmetics/${item.slug}/equip`;
        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Cosmétiques" />

            <header className="mb-6">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Vestiaire</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Cosmétiques</h1>
                <p className="font-body text-sm text-text-medium mt-2">
                    {totalCount} {totalCount > 1 ? 'cosmétiques débloqués' : 'cosmétique débloqué'}. Un actif par catégorie (un skin actif par opérateur).
                </p>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.cosmetic && <div className="mb-4"><Alert variant="danger">{props.errors.cosmetic}</Alert></div>}

            <div className="inline-flex items-center gap-1 rounded-md bg-bg-elev1 border border-border-default p-1">
                {TYPE_ORDER.map(t => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={
                            'px-4 h-8 rounded-sm font-display text-xs uppercase tracking-wide transition-colors duration-fast ' +
                            (tab === t
                                ? 'bg-shard-500/15 text-shard-400'
                                : 'text-text-medium hover:text-text-high')
                        }
                    >
                        {t === 'all'
                            ? `${TYPE_LABELS[t]} (${inventory.length})`
                            : `${TYPE_LABELS[t]} (${byType[t] ?? 0})`}
                    </button>
                ))}
            </div>

            <section className="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {filtered.map(item => (
                    <article
                        key={item.id}
                        className={
                            'rounded-lg border p-4 flex flex-col gap-3 transition-colors duration-fast ' +
                            (item.is_equipped
                                ? 'bg-shard-500/10 border-shard-500'
                                : 'bg-bg-elev1 border-border-default hover:border-shard-500/40')
                        }
                    >
                        <header className="flex items-start justify-between gap-2">
                            <div>
                                <h3 className="font-display font-semibold text-base uppercase tracking-wide text-text-high">
                                    {item.name}
                                </h3>
                                {item.operator && (
                                    <p className="font-mono text-[11px] text-text-medium mt-1">
                                        {item.operator.codename} — {item.operator.name}
                                    </p>
                                )}
                            </div>
                            <RarityBadge rarity={item.rarity} />
                        </header>

                        {item.preview_url && (
                            <div className="aspect-square w-full rounded bg-bg-elev2 overflow-hidden">
                                <img src={item.preview_url} alt={item.name} loading="lazy" className="w-full h-full object-cover" />
                            </div>
                        )}

                        {item.description && (
                            <p className="font-body text-xs text-text-medium line-clamp-2">{item.description}</p>
                        )}

                        <div className="flex justify-between items-center text-[10px] font-display uppercase tracking-mega text-text-low">
                            <span>{TYPE_LABELS[item.type]}</span>
                            {item.source && <span>via {item.source}</span>}
                        </div>

                        <div className="mt-auto pt-2 border-t border-border-default">
                            <Button
                                onClick={() => toggleEquip(item)}
                                variant={item.is_equipped ? 'ghost' : 'primary'}
                                fullWidth
                                size="sm"
                            >
                                {item.is_equipped ? 'Retirer' : 'Équiper'}
                            </Button>
                        </div>
                    </article>
                ))}

                {filtered.length === 0 && (
                    <div className="col-span-full text-center font-body text-sm text-text-medium py-12">
                        Aucun cosmétique dans cette catégorie. Continue à pull et à monter l'affinité de tes opérateurs !
                    </div>
                )}
            </section>
        </>
    );
}

Cosmetics.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
