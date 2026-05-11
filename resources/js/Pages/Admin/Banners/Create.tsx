import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import BannerForm from './BannerForm';
import { ArrowLeft } from 'lucide-react';

interface OperatorOption { codename: string; name: string; rarity: string; faction: string }

interface Props {
    enums: { types: string[] };
    operators: OperatorOption[];
}

export default function BannerCreate({ enums, operators }: Props) {
    return (
        <>
            <Head title="Admin · Nouvelle bannière" />
            <header className="mb-6">
                <Link href="/admin/banners" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvelle bannière</h1>
            </header>
            <BannerForm
                initial={{
                    name: '', tag: '', subtitle: '', type: '',
                    featured_operator: '', rate_up_operators: [],
                    banner_image_url: '',
                    rate_legendary: 0.02, rate_epic: 0.08, rate_rare: 0.30, rate_common: 0.60,
                    pity_legendary: 80, soft_pity_start: 60, pity_epic: 10,
                    starts_at: null, ends_at: null,
                    is_active: false,
                }}
                enums={enums}
                operators={operators ?? []}
                submitLabel="Créer la bannière"
                action={{ method: 'post', url: '/admin/banners' }}
            />
        </>
    );
}

BannerCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
