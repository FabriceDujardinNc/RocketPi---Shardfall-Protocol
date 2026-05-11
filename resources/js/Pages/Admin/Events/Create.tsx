import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import EventForm from './EventForm';
import { ArrowLeft } from 'lucide-react';

interface Props {
    enums: { types: string[] };
    banners: Array<{ id: number; name: string; type: string }>;
}

export default function EventCreate({ enums, banners }: Props) {
    const inOneWeek = new Date();
    inOneWeek.setDate(inOneWeek.getDate() + 7);
    const inThreeWeeks = new Date();
    inThreeWeeks.setDate(inThreeWeeks.getDate() + 21);

    return (
        <>
            <Head title="Admin · Nouvel événement" />
            <header className="mb-6">
                <Link href="/admin/events" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Nouvel événement</h1>
            </header>
            <EventForm
                initial={{
                    name: '', lore: '', banner_image_url: '',
                    type: '', banner_id: null,
                    rewards_pool: [],
                    starts_at: inOneWeek.toISOString().slice(0, 16),
                    ends_at:   inThreeWeeks.toISOString().slice(0, 16),
                    is_active: false,
                }}
                enums={enums}
                banners={banners ?? []}
                submitLabel="Créer l'événement"
                action={{ method: 'post', url: '/admin/events' }}
            />
        </>
    );
}

EventCreate.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
