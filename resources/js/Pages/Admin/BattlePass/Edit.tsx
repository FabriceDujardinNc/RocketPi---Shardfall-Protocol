import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SeasonForm, { type SeasonFormData } from './SeasonForm';
import TiersEditor, { type Tier } from './TiersEditor';
import Alert from '@ui/Alert';
import { ArrowLeft } from 'lucide-react';

interface Props {
    battlePass: SeasonFormData & { tiers: Tier[] };
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function BattlePassEdit({ battlePass }: Props) {
    const { props } = usePage<PageProps>();

    const seasonInitial: SeasonFormData = {
        id: battlePass.id,
        name: battlePass.name,
        season_number: battlePass.season_number,
        total_tiers: battlePass.total_tiers,
        premium_price_shards: battlePass.premium_price_shards,
        starts_at: battlePass.starts_at,
        ends_at: battlePass.ends_at,
        is_active: battlePass.is_active,
    };

    return (
        <>
            <Head title={`Admin · Édition ${battlePass.name}`} />
            <header className="mb-6">
                <Link href="/admin/battle-passes" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour aux saisons
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {battlePass.name}</h1>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <section className="mb-10">
                <h2 className="font-display font-bold text-lg uppercase tracking-wide mb-4">Métadonnées</h2>
                <SeasonForm
                    initial={seasonInitial}
                    submitLabel="Enregistrer les métadonnées"
                    action={{ method: 'put', url: `/admin/battle-passes/${battlePass.slug}` }}
                />
            </section>

            <section>
                <TiersEditor battlePassSlug={battlePass.slug!} tiers={battlePass.tiers ?? []} />
            </section>
        </>
    );
}

BattlePassEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
