import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AchievementForm from './AchievementForm';
import { ArrowLeft } from 'lucide-react';

export default function AchievementCreate({ enums }: { enums: { categories: string[] } }) {
    return (
        <>
            <Head title="Admin · Nouvel achievement" />
            <header className="mb-6">
                <Link href="/admin/achievements" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvel achievement</h1>
            </header>
            <AchievementForm
                initial={{
                    key: '', title: '', description: '', icon_url: '',
                    category: '', is_hidden: false, rewards: [],
                }}
                enums={enums}
                submitLabel="Créer"
                action={{ method: 'post', url: '/admin/achievements' }}
            />
        </>
    );
}

AchievementCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
