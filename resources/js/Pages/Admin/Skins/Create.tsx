import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import SkinForm from './SkinForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    operators: Array<{ id: number; name: string; codename: string; faction: string; rarity: 'common' | 'rare' | 'epic' | 'legendary' }>;
    enums: { rarities: Array<'common' | 'rare' | 'epic' | 'legendary'> };
}

export default function SkinCreate({ operators, enums }: Props) {
    return (
        <>
            <Head title="Admin · Nouveau skin" />
            <Link href="/admin/skins" className="text-text-low hover:text-text-high inline-flex items-center gap-1 text-sm mb-3">
                <ArrowLeft className="h-4 w-4" /> Retour liste skins
            </Link>
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-6">Nouveau skin</h1>

            <SkinForm
                initial={{}}
                operators={operators}
                enums={enums}
                submitLabel="Créer le skin"
                submitUrl="/admin/skins"
                method="post"
            />
        </>
    );
}

SkinCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
