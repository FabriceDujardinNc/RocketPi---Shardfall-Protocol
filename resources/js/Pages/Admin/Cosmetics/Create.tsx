import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import CosmeticForm from './CosmeticForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    enums: { types: string[]; rarities: string[] };
    operators: Array<{ id: number; name: string; codename: string; faction: string }>;
}

export default function CosmeticCreate({ enums, operators }: Props) {
    return (
        <>
            <Head title="Admin · Nouveau cosmétique" />
            <header className="mb-6">
                <Link href="/admin/cosmetics" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouveau cosmétique</h1>
            </header>
            <CosmeticForm
                initial={{
                    slug: '', name: '', description: '',
                    type: '', rarity: 'rare', operator_id: null,
                    preview_url: '', asset_url: '',
                    is_active: true, metadata: null,
                }}
                enums={enums}
                operators={operators ?? []}
                submitLabel="Créer"
                action={{ method: 'post', url: '/admin/cosmetics' }}
            />
        </>
    );
}

CosmeticCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
