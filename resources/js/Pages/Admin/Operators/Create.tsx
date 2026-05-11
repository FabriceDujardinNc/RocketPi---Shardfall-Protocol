import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import OperatorForm from './OperatorForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    enums: { factions: string[]; roles: string[]; rarities: string[]; ability_types: string[] };
}

export default function OperatorCreate({ enums }: Props) {
    return (
        <>
            <Head title="Admin · Nouvel opérateur" />
            <header className="mb-6">
                <Link href="/admin/operators" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvel opérateur</h1>
            </header>
            <OperatorForm
                initial={{
                    name: '', codename: '', faction: '', role: '', rarity: '',
                    lore: '', portrait_url: '',
                    stat_hp: 100, stat_damage: 50, stat_mobility: 50,
                    weapon_name: '', weapon_description: '',
                    abilities: [],
                    is_available: true, is_rate_up: false,
                    sort_order: 0,
                }}
                enums={enums}
                submitLabel="Créer l'opérateur"
                action={{ method: 'post', url: '/admin/operators' }}
            />
        </>
    );
}

OperatorCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
