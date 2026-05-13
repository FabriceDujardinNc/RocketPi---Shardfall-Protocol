import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { ArrowLeft, Pencil, Archive, RotateCcw, Power, PowerOff } from 'lucide-react';

interface Banner {
    id: number;
    slug: string;
    name: string;
    tag: string | null;
    subtitle: string | null;
    type: string;
    featured_operator: string | null;
    rate_up_operators: string[] | null;
    banner_image_url: string | null;
    rate_legendary: string;
    rate_epic: string;
    rate_rare: string;
    rate_common: string;
    pity_legendary: number;
    soft_pity_start: number;
    pity_epic: number;
    starts_at: string | null;
    ends_at: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function BannerShow({ banner }: { banner: Banner }) {
    const { props } = usePage<PageProps>();
    const archive = () => { if (confirm(`Archiver « ${banner.name} » ?`)) router.delete(`/admin/banners/${banner.slug}`); };
    const restore = () => router.post(`/admin/banners/${banner.slug}/restore`);
    const toggle = () => router.post(`/admin/banners/${banner.slug}/activate`);

    return (
        <>
            <Head title={`Admin · ${banner.name}`} />
            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <Link href="/admin/banners" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                        <ArrowLeft size={12} /> Retour à la liste
                    </Link>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">{banner.name}</h1>
                    {banner.subtitle && <p className="font-mono text-xs text-text-low mt-1">{banner.subtitle}</p>}
                </div>
                <div className="flex gap-2 flex-wrap">
                    {!banner.deleted_at && (
                        <>
                            <Link href={`/admin/banners/${banner.slug}/edit`}><Button variant="secondary" icon={<Pencil size={14} />}>Éditer</Button></Link>
                            <Button variant={banner.is_active ? 'danger' : 'shard'} icon={banner.is_active ? <PowerOff size={14} /> : <Power size={14} />} onClick={toggle}>
                                {banner.is_active ? 'Désactiver' : 'Activer'}
                            </Button>
                            <Button variant="danger" icon={<Archive size={14} />} onClick={archive}>Archiver</Button>
                        </>
                    )}
                    {banner.deleted_at && (
                        <Button variant="shard" icon={<RotateCcw size={14} />} onClick={restore}>Restaurer</Button>
                    )}
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {banner.deleted_at && <div className="mb-4"><Alert variant="warning">Bannière archivée le {new Date(banner.deleted_at).toLocaleDateString('fr-FR')}.</Alert></div>}

            <div className="grid lg:grid-cols-2 gap-6">
                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Identité</h2>
                    <Row k="Type" v={banner.type} />
                    <Row k="Tag" v={banner.tag ?? '—'} />
                    <Row k="État" v={banner.is_active ? '✓ Active' : '✗ Inactive'} />
                    <Row k="Featured" v={banner.featured_operator ?? '—'} />
                    <Row k="Période" v={`${banner.starts_at ? new Date(banner.starts_at).toLocaleString('fr-FR') : '—'} → ${banner.ends_at ? new Date(banner.ends_at).toLocaleString('fr-FR') : '∞'}`} />
                    {banner.banner_image_url && (
                        <div className="mt-3"><img src={banner.banner_image_url} alt={banner.name} className="rounded-md border border-border-default w-full" /></div>
                    )}
                </section>

                <section className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Taux & Pity</h2>
                    <Row k="Légendaire" v={(parseFloat(banner.rate_legendary) * 100).toFixed(2) + ' %'} />
                    <Row k="Épique" v={(parseFloat(banner.rate_epic) * 100).toFixed(2) + ' %'} />
                    <Row k="Rare" v={(parseFloat(banner.rate_rare) * 100).toFixed(2) + ' %'} />
                    <Row k="Commun" v={(parseFloat(banner.rate_common) * 100).toFixed(2) + ' %'} />
                    <Row k="Hard pity Lég." v={`${banner.pity_legendary} tirages`} />
                    <Row k="Soft pity dém." v={`${banner.soft_pity_start} tirages`} />
                    <Row k="Hard pity Épique" v={`${banner.pity_epic} tirages`} />
                </section>

                {(banner.rate_up_operators?.length ?? 0) > 0 && (
                    <section className="rounded-lg bg-bg-elev1 border border-border-default p-5 lg:col-span-2">
                        <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">Opérateurs rate-up</h2>
                        <div className="flex flex-wrap gap-2">
                            {banner.rate_up_operators!.map(code => (
                                <span key={code} className="font-mono text-xs px-2 py-1 rounded bg-shard-500/10 text-shard-400 border border-shard-500/40">{code}</span>
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
}

function Row({ k, v }: { k: string; v: string }) {
    return (
        <div className="flex justify-between font-mono text-sm py-1 border-b border-border-default/40 last:border-0">
            <span className="text-text-low uppercase tracking-wide text-xs">{k}</span>
            <span className="text-text-high">{v}</span>
        </div>
    );
}

BannerShow.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
