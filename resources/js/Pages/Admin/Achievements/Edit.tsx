import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import AchievementForm, { type AchievementFormData } from './AchievementForm';
import Alert from '@ui/Alert';
import { ArrowLeft } from 'lucide-react';

interface Props {
    achievement: AchievementFormData;
    enums: { categories: string[] };
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function AchievementEdit({ achievement, enums }: Props) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title={`Admin · Édition ${achievement.title}`} />
            <header className="mb-6">
                <Link href="/admin/achievements" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {achievement.title}</h1>
            </header>
            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            <AchievementForm
                initial={achievement}
                enums={enums}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/achievements/${achievement.key}` }}
            />
        </>
    );
}

AchievementEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
