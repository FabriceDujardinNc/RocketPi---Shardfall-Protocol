import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BannerForm, { type BannerFormData } from './BannerForm';
import { ArrowLeft } from 'lucide-react';

interface OperatorOption { codename: string; name: string; rarity: string; faction: string }

interface Props {
    banner: BannerFormData;
    enums: { types: string[] };
    operators: OperatorOption[];
}

export default function BannerEdit({ banner, enums, operators }: Props) {
    return (
        <>
            <Head title={`Admin · Édition ${banner.name}`} />
            <header className="mb-6">
                <Link href={`/admin/banners/${banner.id}`} className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la fiche
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {banner.name}</h1>
            </header>
            <BannerForm
                initial={banner}
                enums={enums}
                operators={operators ?? []}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/banners/${banner.id}` }}
            />
        </>
    );
}

BannerEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
