import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import MissionForm from './MissionForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    enums: { types: string[]; objective_types: string[]; reward_types: string[] };
}

export default function MissionCreate({ enums }: Props) {
    return (
        <>
            <Head title="Admin · Nouvelle mission" />
            <header className="mb-6">
                <Link href="/admin/missions" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvelle mission</h1>
            </header>
            <MissionForm
                initial={{
                    title: '', description: '',
                    type: '', objective_type: '', objective_target: 1,
                    rewards: [{ type: 'shards', amount: 50 }],
                    xp_reward: 0,
                    is_active: true,
                    available_from: null, available_until: null,
                }}
                enums={enums}
                submitLabel="Créer la mission"
                action={{ method: 'post', url: '/admin/missions' }}
            />
        </>
    );
}

MissionCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
