import { useForm } from '@inertiajs/react';
import Button from '@ui/Button';
import { Plus, Trash2 } from 'lucide-react';

interface Ability {
    name: string;
    type: 'active' | 'passive' | 'ultimate';
    description: string;
}

interface LoreUnlock {
    level: 0 | 2 | 5 | 8 | 10;
    title: string;
    snippet: string | null;
}

const LORE_LEVELS: Array<LoreUnlock['level']> = [0, 2, 5, 8, 10];

const LORE_DEFAULT_TITLES: Record<LoreUnlock['level'], string> = {
    0:  'Présentation',
    2:  'Origines',
    5:  "L'incident Shardfall",
    8:  'Vie privée',
    10: 'Confidence ultime',
};

export interface OperatorFormData {
    id?: number;
    slug?: string;
    name: string;
    codename: string;
    faction: string;
    role: string;
    rarity: string;
    lore: string | null;
    portrait_url: string | null;
    stat_hp: number;
    stat_damage: number;
    stat_mobility: number;
    weapon_name: string | null;
    weapon_description: string | null;
    abilities: Ability[] | null;
    lore_unlocks: LoreUnlock[] | null;
    is_available: boolean;
    is_rate_up: boolean;
    sort_order: number;
}

interface Props {
    initial: OperatorFormData;
    enums: { factions: string[]; roles: string[]; rarities: string[]; ability_types: string[] };
    submitLabel: string;
    action: { method: 'post' | 'put'; url: string };
}

export default function OperatorForm({ initial, enums, submitLabel, action }: Props) {
    const ensureLore = (existing: LoreUnlock[] | null | undefined): LoreUnlock[] => {
        const byLevel = new Map((existing ?? []).map(u => [u.level, u]));
        return LORE_LEVELS.map(level => byLevel.get(level) ?? {
            level,
            title: LORE_DEFAULT_TITLES[level],
            snippet: '',
        });
    };

    const { data, setData, errors, processing, post, put } = useForm({
        ...initial,
        abilities: initial.abilities ?? [],
        lore_unlocks: ensureLore(initial.lore_unlocks),
    });

    const onSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const handler = action.method === 'put' ? put : post;
        handler(action.url, { preserveScroll: true });
    };

    const setAbility = (idx: number, patch: Partial<Ability>) => {
        const next = [...(data.abilities ?? [])];
        next[idx] = { ...next[idx], ...patch };
        setData('abilities', next);
    };
    const addAbility = () => setData('abilities', [...(data.abilities ?? []), { name: '', type: 'active', description: '' }]);
    const removeAbility = (idx: number) => setData('abilities', (data.abilities ?? []).filter((_, i) => i !== idx));

    return (
        <form onSubmit={onSubmit} className="space-y-6 max-w-4xl">
            {/* Identité */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Nom" error={errors.name} required>
                        <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={inputCls} maxLength={32} />
                    </Field>
                    <Field label="Codename" error={errors.codename} required>
                        <input value={data.codename} onChange={(e) => setData('codename', e.target.value)} className={inputCls} maxLength={16} placeholder="VX-01" />
                    </Field>
                    <Field label="Faction" error={errors.faction} required>
                        <select value={data.faction} onChange={(e) => setData('faction', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.factions.map(f => <option key={f} value={f}>{f}</option>)}
                        </select>
                    </Field>
                    <Field label="Rôle" error={errors.role} required>
                        <select value={data.role} onChange={(e) => setData('role', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.roles.map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </Field>
                    <Field label="Rareté" error={errors.rarity} required>
                        <select value={data.rarity} onChange={(e) => setData('rarity', e.target.value)} className={inputCls}>
                            <option value="">—</option>
                            {enums.rarities.map(r => <option key={r} value={r}>{r}</option>)}
                        </select>
                    </Field>
                    <Field label="Ordre d'affichage" error={errors.sort_order}>
                        <input type="number" min={0} value={data.sort_order} onChange={(e) => setData('sort_order', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="URL portrait (image)" error={errors.portrait_url} hint="https://… (peut être vide)">
                        <input value={data.portrait_url ?? ''} onChange={(e) => setData('portrait_url', e.target.value || null)} className={inputCls} placeholder="https://..." />
                    </Field>
                </div>
            </fieldset>

            {/* Stats */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Stats de base</legend>
                <div className="grid md:grid-cols-3 gap-4 mt-2">
                    <Field label="HP" error={errors.stat_hp} required>
                        <input type="number" min={1} value={data.stat_hp} onChange={(e) => setData('stat_hp', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Dégâts" error={errors.stat_damage} required>
                        <input type="number" min={1} value={data.stat_damage} onChange={(e) => setData('stat_damage', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                    <Field label="Mobilité" error={errors.stat_mobility} required>
                        <input type="number" min={1} value={data.stat_mobility} onChange={(e) => setData('stat_mobility', parseInt(e.target.value || '0'))} className={inputCls} />
                    </Field>
                </div>
            </fieldset>

            {/* Arme */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Arme signature</legend>
                <div className="grid md:grid-cols-2 gap-4 mt-2">
                    <Field label="Nom de l'arme" error={errors.weapon_name}>
                        <input value={data.weapon_name ?? ''} onChange={(e) => setData('weapon_name', e.target.value || null)} className={inputCls} maxLength={64} />
                    </Field>
                    <Field label="" error={errors.weapon_description}>
                        <textarea value={data.weapon_description ?? ''} onChange={(e) => setData('weapon_description', e.target.value || null)}
                            className={inputCls + ' h-20'} placeholder="Description de l'arme..." />
                    </Field>
                </div>
            </fieldset>

            {/* Capacités */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Capacités</legend>
                <div className="space-y-3 mt-2">
                    {(data.abilities ?? []).map((ab, idx) => (
                        <div key={idx} className="grid md:grid-cols-[1fr_auto_2fr_auto] gap-2 items-start">
                            <input value={ab.name} onChange={(e) => setAbility(idx, { name: e.target.value })}
                                className={inputCls} placeholder="Nom de la capacité" maxLength={64} />
                            <select value={ab.type} onChange={(e) => setAbility(idx, { type: e.target.value as Ability['type'] })} className={inputCls}>
                                {enums.ability_types.map(t => <option key={t} value={t}>{t}</option>)}
                            </select>
                            <textarea value={ab.description} onChange={(e) => setAbility(idx, { description: e.target.value })}
                                className={inputCls + ' h-12'} placeholder="Description..." />
                            <Button type="button" size="sm" variant="danger" icon={<Trash2 size={12} />} onClick={() => removeAbility(idx)}>Suppr</Button>
                        </div>
                    ))}
                    {errors['abilities'] && <p className="text-danger text-xs font-mono">{errors['abilities']}</p>}
                    <Button type="button" size="sm" variant="secondary" icon={<Plus size={12} />} onClick={addAbility}>Ajouter une capacité</Button>
                </div>
            </fieldset>

            {/* Lore */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Lore (présentation)</legend>
                <textarea value={data.lore ?? ''} onChange={(e) => setData('lore', e.target.value || null)}
                    className={inputCls + ' h-40 mt-2'} maxLength={5000} placeholder="Histoire du personnage, background..." />
                {errors.lore && <p className="text-danger text-xs font-mono mt-1">{errors.lore}</p>}
                <p className="text-text-low text-xs font-mono mt-1">{(data.lore ?? '').length} / 5000</p>
            </fieldset>

            {/* Lore débloqué progressivement par affinité */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Lore progressif (paliers d'affinité)</legend>
                <p className="text-text-low text-xs font-mono mt-1 mb-4">
                    Chaque palier se débloque côté joueur quand son affinité avec l'opérateur atteint le niveau correspondant.
                </p>
                <div className="space-y-4">
                    {(data.lore_unlocks ?? []).map((u, idx) => (
                        <div key={u.level} className="rounded-md bg-bg-elev2/50 border border-border-default p-3">
                            <div className="flex items-center justify-between mb-2">
                                <span className="font-display text-xs uppercase tracking-wide text-shard-400">Niveau {u.level}</span>
                                <span className="font-mono text-xs text-text-low">{(u.snippet ?? '').length} / 3000</span>
                            </div>
                            <input
                                value={u.title}
                                onChange={(e) => {
                                    const next = [...(data.lore_unlocks ?? [])];
                                    next[idx] = { ...u, title: e.target.value };
                                    setData('lore_unlocks', next);
                                }}
                                className={inputCls + ' mb-2'}
                                placeholder="Titre du palier"
                                maxLength={64}
                            />
                            <textarea
                                value={u.snippet ?? ''}
                                onChange={(e) => {
                                    const next = [...(data.lore_unlocks ?? [])];
                                    next[idx] = { ...u, snippet: e.target.value || null };
                                    setData('lore_unlocks', next);
                                }}
                                className={inputCls + ' h-24'}
                                placeholder="Contenu débloqué à ce palier..."
                                maxLength={3000}
                            />
                            {errors[`lore_unlocks.${idx}.title`]   && <p className="text-danger text-xs font-mono mt-1">{errors[`lore_unlocks.${idx}.title`]}</p>}
                            {errors[`lore_unlocks.${idx}.snippet`] && <p className="text-danger text-xs font-mono mt-1">{errors[`lore_unlocks.${idx}.snippet`]}</p>}
                        </div>
                    ))}
                </div>
            </fieldset>

            {/* Flags */}
            <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Disponibilité</legend>
                <div className="flex flex-wrap gap-6 mt-2">
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data.is_available} onChange={(e) => setData('is_available', e.target.checked)} />
                        <span>Disponible (apparaît dans les bannières)</span>
                    </label>
                    <label className="flex items-center gap-2 text-sm">
                        <input type="checkbox" checked={data.is_rate_up} onChange={(e) => setData('is_rate_up', e.target.checked)} />
                        <span>Rate-up actif</span>
                    </label>
                </div>
            </fieldset>

            <div className="flex gap-3 pt-2">
                <Button type="submit" variant="shard" loading={processing}>{submitLabel}</Button>
                <Button type="button" variant="ghost" onClick={() => window.history.back()}>Annuler</Button>
            </div>
        </form>
    );
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

function Field({ label, error, hint, required, children }: { label: string; error?: string; hint?: string; required?: boolean; children: React.ReactNode }) {
    return (
        <label className="flex flex-col gap-1">
            {label && (
                <span className="font-display text-xs uppercase tracking-wide text-text-low">
                    {label}{required && <span className="text-danger ml-1">*</span>}
                </span>
            )}
            {children}
            {hint && !error && <span className="text-text-low text-xs font-mono">{hint}</span>}
            {error && <span className="text-danger text-xs font-mono">{error}</span>}
        </label>
    );
}
