import { Head, router, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { Plus, Save, Trash2, Star } from 'lucide-react';
import { useState } from 'react';

interface RewardLine { type: string; amount: number }

interface DailyLoginReward {
    id: number;
    day_number: number;
    label: string | null;
    is_milestone: boolean;
    rewards: RewardLine[];
}

interface PageProps { flash?: { status?: string }; [key: string]: unknown }

const inputCls = 'h-8 px-2 rounded bg-bg-elev2 border border-border-default text-text-high text-xs focus:outline-none focus:ring-1 focus:ring-shard-500';

const REWARD_TYPE_SUGGESTIONS = [
    'shards', 'credits',
    'tickets_standard', 'tickets_premium',
    'tokens_rare_choice', 'tokens_epic_choice', 'tokens_legendary_choice',
];

export default function DailyLoginRewardsIndex({ rewards }: { rewards: DailyLoginReward[] }) {
    const { props } = usePage<PageProps>();
    return (
        <>
            <Head title="Admin · Daily login" />
            <header className="mb-6">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Récompenses de connexion quotidienne</h1>
                <p className="font-mono text-xs text-text-low mt-1">
                    Les jours non configurés ici retombent sur la récompense par défaut (100 credits).
                </p>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <CreateRow />

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden mt-6">
                <table className="w-full text-sm">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left w-16">Jour</th>
                            <th className="px-3 py-2 text-left">Label</th>
                            <th className="px-3 py-2 text-left">Récompenses</th>
                            <th className="px-3 py-2 text-left w-24">Milestone</th>
                            <th className="px-3 py-2 text-right w-48">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rewards.length === 0
                            ? <tr><td colSpan={5} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucune récompense configurée — utilise « Ajouter ».</td></tr>
                            : rewards.map(r => <Row key={r.id} reward={r} />)
                        }
                    </tbody>
                </table>
            </section>
        </>
    );
}

function CreateRow() {
    const [open, setOpen] = useState(false);
    const { data, setData, errors, processing, post, reset } = useForm({
        day_number: 1,
        label: '',
        is_milestone: false,
        rewards: [{ type: 'shards', amount: 50 }],
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/daily-login-rewards', {
            preserveScroll: true,
            onSuccess: () => { reset(); setOpen(false); },
        });
    };

    if (!open) {
        return <Button variant="shard" icon={<Plus size={14} />} onClick={() => setOpen(true)}>Ajouter un jour</Button>;
    }

    return (
        <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-5 mb-2 space-y-4">
            <h2 className="font-display text-sm uppercase tracking-wide text-shard-400">Nouveau jour</h2>
            <div className="grid md:grid-cols-3 gap-3">
                <Field label="Jour (1-365)" error={errors.day_number} required>
                    <input type="number" min={1} max={365} value={data.day_number} onChange={e => setData('day_number', parseInt(e.target.value || '0'))} className={inputCls + ' w-full'} />
                </Field>
                <Field label="Label éditorial" error={errors.label}>
                    <input value={data.label} onChange={e => setData('label', e.target.value)} className={inputCls + ' w-full'} placeholder="Mi-mois, etc." maxLength={64} />
                </Field>
                <Field label="Milestone">
                    <label className="flex items-center gap-2 h-8 mt-1 text-xs">
                        <input type="checkbox" checked={data.is_milestone} onChange={e => setData('is_milestone', e.target.checked)} />
                        Affiché en spécial
                    </label>
                </Field>
            </div>
            <RewardEditor
                rewards={data.rewards}
                onChange={(rewards) => setData('rewards', rewards)}
                errors={errors as Record<string, string>}
            />
            <div className="flex gap-2">
                <Button type="submit" variant="shard" loading={processing} icon={<Save size={12} />}>Créer</Button>
                <Button type="button" variant="ghost" onClick={() => setOpen(false)}>Annuler</Button>
            </div>
        </form>
    );
}

function Row({ reward }: { reward: DailyLoginReward }) {
    const [editing, setEditing] = useState(false);
    const { data, setData, errors, processing, put } = useForm({
        day_number: reward.day_number,
        label: reward.label ?? '',
        is_milestone: reward.is_milestone,
        rewards: reward.rewards ?? [],
    });

    const save = () => put(`/admin/daily-login-rewards/${reward.id}`, {
        preserveScroll: true,
        onSuccess: () => setEditing(false),
    });

    const destroy = () => {
        if (!confirm(`Supprimer la récompense du jour ${reward.day_number} ?`)) return;
        router.delete(`/admin/daily-login-rewards/${reward.id}`);
    };

    if (!editing) {
        return (
            <tr className="border-t border-border-default">
                <td className="px-3 py-2 font-display text-text-high">{reward.day_number}</td>
                <td className="px-3 py-2 font-mono text-xs text-text-medium">{reward.label ?? '—'}</td>
                <td className="px-3 py-2 font-mono text-xs">
                    {reward.rewards.map((r, i) => <span key={i} className="block text-text-medium">+{r.amount} {r.type}</span>)}
                </td>
                <td className="px-3 py-2">{reward.is_milestone && <Star size={12} className="text-rarity-legendary" />}</td>
                <td className="px-3 py-2 text-right">
                    <div className="inline-flex gap-1">
                        <Button size="sm" variant="secondary" onClick={() => setEditing(true)}>Éditer</Button>
                        <Button size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={destroy}>Suppr</Button>
                    </div>
                </td>
            </tr>
        );
    }

    return (
        <tr className="border-t border-border-default bg-bg-elev2/30">
            <td className="px-3 py-2"><input type="number" min={1} max={365} value={data.day_number} onChange={e => setData('day_number', parseInt(e.target.value || '0'))} className={inputCls + ' w-14'} /></td>
            <td className="px-3 py-2"><input value={data.label} onChange={e => setData('label', e.target.value)} className={inputCls + ' w-full'} /></td>
            <td className="px-3 py-2"><RewardEditor rewards={data.rewards} onChange={r => setData('rewards', r)} errors={errors as Record<string, string>} /></td>
            <td className="px-3 py-2"><input type="checkbox" checked={data.is_milestone} onChange={e => setData('is_milestone', e.target.checked)} /></td>
            <td className="px-3 py-2 text-right">
                <div className="inline-flex gap-1">
                    <Button size="sm" variant="shard" loading={processing} onClick={save} icon={<Save size={12} />}>Sauver</Button>
                    <Button size="sm" variant="ghost" onClick={() => setEditing(false)}>Annuler</Button>
                </div>
            </td>
        </tr>
    );
}

function RewardEditor({ rewards, onChange, errors }: {
    rewards: RewardLine[];
    onChange: (next: RewardLine[]) => void;
    errors: Record<string, string>;
}) {
    return (
        <div className="space-y-1">
            {rewards.map((r, i) => (
                <div key={i} className="flex gap-2 items-center">
                    <input
                        value={r.type}
                        onChange={(e) => onChange(rewards.map((x, j) => j === i ? { ...x, type: e.target.value } : x))}
                        className={inputCls + ' flex-1'}
                        list="dl-reward-types"
                    />
                    <input
                        type="number"
                        min={1}
                        value={r.amount}
                        onChange={(e) => onChange(rewards.map((x, j) => j === i ? { ...x, amount: parseInt(e.target.value || '0') } : x))}
                        className={inputCls + ' w-20'}
                    />
                    <Button type="button" size="sm" variant="danger" icon={<Trash2 size={10} />} onClick={() => onChange(rewards.filter((_, j) => j !== i))}>—</Button>
                </div>
            ))}
            <Button type="button" size="sm" variant="ghost" icon={<Plus size={10} />} onClick={() => onChange([...rewards, { type: 'credits', amount: 100 }])}>Ajouter</Button>
            <datalist id="dl-reward-types">
                {REWARD_TYPE_SUGGESTIONS.map(t => <option key={t} value={t} />)}
            </datalist>
            {errors.rewards && <p className="text-danger text-xs font-mono">{errors.rewards}</p>}
        </div>
    );
}

function Field({ label, error, required, children }: { label: string; error?: string; required?: boolean; children: React.ReactNode }) {
    return (
        <label className="flex flex-col gap-1">
            <span className="font-display text-xs uppercase tracking-wide text-text-low">{label}{required && <span className="text-danger ml-1">*</span>}</span>
            {children}
            {error && <span className="text-danger text-xs font-mono">{error}</span>}
        </label>
    );
}

DailyLoginRewardsIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
