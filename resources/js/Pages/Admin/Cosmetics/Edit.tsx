import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import CosmeticForm, { type CosmeticFormData } from './CosmeticForm';
import Alert from '@ui/Alert';
import { ArrowLeft } from 'lucide-react';

interface Props {
    cosmetic: CosmeticFormData;
    enums: { types: string[]; rarities: string[] };
    operators: Array<{ id: number; name: string; codename: string; faction: string }>;
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function CosmeticEdit({ cosmetic, enums, operators }: Props) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title={`Admin · Édition ${cosmetic.name}`} />
            <header className="mb-6">
                <Link href="/admin/cosmetics" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {cosmetic.name}</h1>
            </header>
            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            <CosmeticForm
                initial={cosmetic}
                enums={enums}
                operators={operators ?? []}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/cosmetics/${cosmetic.slug}` }}
            />
        </>
    );
}

CosmeticEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
