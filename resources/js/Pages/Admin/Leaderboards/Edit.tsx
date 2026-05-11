import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SeasonForm, { type SeasonFormData } from './SeasonForm';
import Alert from '@ui/Alert';
import { ArrowLeft } from 'lucide-react';

interface Props {
    season: SeasonFormData;
    enums: { types: string[]; factions: string[] };
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function LeaderboardEdit({ season, enums }: Props) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title={`Admin · Édition ${season.name}`} />
            <header className="mb-6">
                <Link href={`/admin/leaderboards/${season.slug}`} className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la fiche
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {season.name}</h1>
            </header>
            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            <SeasonForm
                initial={season}
                enums={enums}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/leaderboards/${season.slug}` }}
            />
        </>
    );
}

LeaderboardEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
