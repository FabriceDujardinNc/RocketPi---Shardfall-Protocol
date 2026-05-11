import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import MissionForm, { type MissionFormData } from './MissionForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    mission: MissionFormData;
    enums: { types: string[]; objective_types: string[]; reward_types: string[] };
}

export default function MissionEdit({ mission, enums }: Props) {
    return (
        <>
            <Head title={`Admin · Édition ${mission.title}`} />
            <header className="mb-6">
                <Link href={`/admin/missions/${mission.slug}`} className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la fiche
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {mission.title}</h1>
            </header>
            <MissionForm
                initial={mission}
                enums={enums}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/missions/${mission.slug}` }}
            />
        </>
    );
}

MissionEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
