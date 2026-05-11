import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Save } from 'lucide-react';

interface SettingRow {
    key: string;
    type: 'bool' | 'string' | 'int' | 'json';
    label: string | null;
    description: string | null;
    value: boolean | string | number | unknown;
}

interface Props { settings: SettingRow[] }
interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function AdminSettings({ settings }: Props) {
    const { props } = usePage<PageProps>();

    const { data, setData, errors, processing, patch } = useForm<{ values: Record<string, unknown> }>({
        values: Object.fromEntries(settings.map(s => [s.key, s.value])),
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        patch('/admin/settings', { preserveScroll: true });
    };

    return (
        <>
            <Head title="Admin · Paramètres" />
            <header className="mb-6 flex justify-between items-end flex-wrap gap-3">
                <div>
                    <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Paramètres globaux</h1>
                    <p className="font-mono text-xs text-text-low mt-1">
                        Kill-switches et flags du jeu. Cache invalidé automatiquement à chaque save.
                    </p>
                </div>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <form onSubmit={submit} className="space-y-3 max-w-3xl">
                {settings.map(s => (
                    <div key={s.key} className="rounded-lg bg-bg-elev1 border border-border-default p-4 grid md:grid-cols-[2fr_1fr] gap-4 items-center">
                        <div>
                            <p className="font-display text-sm uppercase tracking-wide text-text-high">{s.label ?? s.key}</p>
                            <p className="font-mono text-xs text-text-low mt-0.5">{s.key} · {s.type}</p>
                            {s.description && <p className="text-text-medium text-xs mt-1">{s.description}</p>}
                        </div>
                        <div>
                            {s.type === 'bool' ? (
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox"
                                        checked={Boolean(data.values[s.key])}
                                        onChange={e => setData('values', { ...data.values, [s.key]: e.target.checked })}
                                    />
                                    <span className={'font-display text-xs uppercase tracking-wide ' + (data.values[s.key] ? 'text-success' : 'text-text-low')}>
                                        {data.values[s.key] ? 'ON' : 'OFF'}
                                    </span>
                                </label>
                            ) : s.type === 'int' ? (
                                <input type="number" value={Number(data.values[s.key] ?? 0)}
                                    onChange={e => setData('values', { ...data.values, [s.key]: parseInt(e.target.value || '0') })}
                                    className={inputCls} />
                            ) : (
                                <input value={String(data.values[s.key] ?? '')}
                                    onChange={e => setData('values', { ...data.values, [s.key]: e.target.value })}
                                    className={inputCls} maxLength={500} />
                            )}
                            {errors[`values.${s.key}`] && <p className="text-danger text-xs font-mono mt-1">{errors[`values.${s.key}`]}</p>}
                        </div>
                    </div>
                ))}

                <div className="pt-3">
                    <Button type="submit" variant="shard" loading={processing} icon={<Save size={14} />}>Enregistrer</Button>
                </div>
            </form>
        </>
    );
}

AdminSettings.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
