import { Head } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface Props {
    settings: { maintenance: boolean; gacha_enabled: boolean; shop_enabled: boolean };
}

export default function AdminSettings({ settings }: Props) {
    return (
        <>
            <Head title="Admin · Paramètres" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-8">Paramètres globaux</h1>
            <section className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                <ul className="flex flex-col gap-4 text-sm">
                    <Toggle label="Mode maintenance" value={settings.maintenance} />
                    <Toggle label="Gacha activé" value={settings.gacha_enabled} />
                    <Toggle label="Boutique activée" value={settings.shop_enabled} />
                </ul>
            </section>
        </>
    );
}

function Toggle({ label, value }: { label: string; value: boolean }) {
    return (
        <li className="flex items-center justify-between">
            <span className="font-display uppercase text-xs tracking-wide text-text-medium">{label}</span>
            <span className={`font-display text-xs uppercase tracking-wide ${value ? 'text-success' : 'text-text-low'}`}>
                {value ? 'ON' : 'OFF'}
            </span>
        </li>
    );
}

AdminSettings.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
