import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AccessoryForm from './AccessoryForm';
import { ArrowLeft } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';
type Slot = 'head' | 'face' | 'back' | 'hands' | 'legs';

interface Props {
    operators: Array<{ id: number; name: string; codename: string; faction: string; rarity: Rarity }>;
    enums: { slots: Slot[]; rarities: Rarity[]; sockets: Record<Slot, string> };
}

export default function AccessoryCreate({ operators, enums }: Props) {
    return (
        <>
            <Head title="Admin · Nouvel accessoire" />
            <Link href="/admin/accessories" className="text-text-low hover:text-text-high inline-flex items-center gap-1 text-sm mb-3">
                <ArrowLeft className="h-4 w-4" /> Retour liste accessoires
            </Link>
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-6">Nouvel accessoire</h1>

            <AccessoryForm
                initial={{}}
                operators={operators}
                enums={enums}
                submitLabel="Créer l'accessoire"
                submitUrl="/admin/accessories"
                method="post"
            />
        </>
    );
}

AccessoryCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
