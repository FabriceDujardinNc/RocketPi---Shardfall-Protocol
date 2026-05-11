import { Head, Link, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import EventForm, { type EventFormData } from './EventForm';
import Alert from '@ui/Alert';
import { ArrowLeft } from 'lucide-react';

interface Props {
    event: EventFormData;
    enums: { types: string[] };
    banners: Array<{ id: number; name: string; type: string }>;
}
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

export default function EventEdit({ event, enums, banners }: Props) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title={`Admin · Édition ${event.name}`} />
            <header className="mb-6">
                <Link href="/admin/events" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour à la liste
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Édition · {event.name}</h1>
            </header>
            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            <EventForm
                initial={event}
                enums={enums}
                banners={banners ?? []}
                submitLabel="Enregistrer"
                action={{ method: 'put', url: `/admin/events/${event.slug}` }}
            />
        </>
    );
}

EventEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
