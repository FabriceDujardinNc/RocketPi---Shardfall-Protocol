import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Eye, Pencil } from 'lucide-react';

interface Faction {
    slug: string;
    name: string;
    tagline: string | null;
    color_hue: number;
    operators_count: number;
    banner_image_url: string | null;
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function FactionsIndex({ factions }: { factions: Faction[] }) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title="Admin · Factions" />
            <header className="mb-6">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Factions</h1>
                <p className="font-mono text-xs text-text-low mt-1">{factions.length} factions configurées — clique sur une carte pour voir sa collection d'opérateurs.</p>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <section className="grid md:grid-cols-3 gap-4">
                {factions.map(f => (
                    <article
                        key={f.slug}
                        className="rounded-lg bg-bg-elev1 border border-border-default p-5 flex flex-col gap-4"
                        style={{ borderColor: `oklch(0.55 0.15 ${f.color_hue} / 0.5)` }}
                    >
                        <header className="flex items-center justify-between">
                            <h2 className="font-display font-bold text-xl uppercase tracking-wide" style={{ color: `oklch(0.75 0.18 ${f.color_hue})` }}>
                                {f.name}
                            </h2>
                            <span className="font-mono text-xs text-text-low">{f.operators_count} ops</span>
                        </header>
                        {f.tagline && <p className="text-text-medium text-sm">{f.tagline}</p>}
                        <div className="flex gap-2 mt-auto">
                            <Link href={`/admin/factions/${f.slug}`}><Button size="sm" variant="secondary" icon={<Eye size={12} />}>Collection</Button></Link>
                            <Link href={`/admin/factions/${f.slug}/edit`}><Button size="sm" variant="shard" icon={<Pencil size={12} />}>Éditer</Button></Link>
                        </div>
                    </article>
                ))}
            </section>
        </>
    );
}

FactionsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
