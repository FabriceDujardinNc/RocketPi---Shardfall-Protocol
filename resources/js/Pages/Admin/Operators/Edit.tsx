import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import OperatorForm, { type OperatorFormData } from './OperatorForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    operator: OperatorFormData;
    enums: { factions: string[]; roles: string[]; rarities: string[]; ability_types: string[] };
}

export default function OperatorEdit({ operator, enums }: Props) {
    return (
        <>
            <Head title={`Admin · Édition ${operator.name}`} />
            <header className="mb-6">
                <Link href={`/admin/operators/${operator.id}`} className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la fiche
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {operator.name}</h1>
            </header>
            <OperatorForm
                initial={operator}
                enums={enums}
                submitLabel="Enregistrer les modifications"
                action={{ method: 'put', url: `/admin/operators/${operator.id}` }}
            />
        </>
    );
}

OperatorEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
