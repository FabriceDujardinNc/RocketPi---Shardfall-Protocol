import { router } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@ui/Button';
import { Plus, X } from 'lucide-react';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';

interface OperatorOption {
    id: number;
    name: string;
    codename: string;
    rarity: Rarity;
}

interface PaletteEntry { slot: string; hex: string }

export interface SkinFormValues {
    operator_id: number | null;
    name: string;
    slug: string;
    rarity: Rarity;
    palette_json: PaletteEntry[];
    is_active: boolean;
    is_default: boolean;
}

interface Props {
    initial: Partial<SkinFormValues> & { id?: number };
    operators: OperatorOption[];
    enums: { rarities: Rarity[] };
    submitLabel: string;
    submitUrl: string;
    method?: 'post' | 'put';
}

export default function SkinForm({ initial, operators, enums, submitLabel, submitUrl, method = 'post' }: Props) {
    const [form, setForm] = useState<SkinFormValues>({
        operator_id:  initial.operator_id ?? null,
        name:         initial.name ?? '',
        slug:         initial.slug ?? '',
        rarity:       initial.rarity ?? 'rare',
        palette_json: Array.isArray(initial.palette_json) ? initial.palette_json : [],
        is_active:    initial.is_active ?? true,
        is_default:   initial.is_default ?? false,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const addPalette = () => setForm({ ...form, palette_json: [...form.palette_json, { slot: 'primary', hex: '#ffffff' }] });
    const updatePalette = (idx: number, key: keyof PaletteEntry, value: string) => {
        const next = form.palette_json.map((p, i) => i === idx ? { ...p, [key]: value } : p);
        setForm({ ...form, palette_json: next });
    };
    const removePalette = (idx: number) =>
        setForm({ ...form, palette_json: form.palette_json.filter((_, i) => i !== idx) });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setBusy(true);
        const payload = {
            ...form,
            palette_json: form.palette_json.length ? form.palette_json : null,
        };
        const opts = {
            onError: (errs: Record<string, string>) => setErrors(errs),
            onFinish: () => setBusy(false),
        };
        // Cast via unknown : router.put/post veulent FormDataConvertible mais
        // notre palette_json est un array d'objets {slot,hex} qui passe en JSON.
        if (method === 'put') router.put(submitUrl, payload as unknown as Record<string, never>, opts);
        else                  router.post(submitUrl, payload as unknown as Record<string, never>, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid md:grid-cols-2 gap-4">
                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Opérateur</label>
                    <select
                        value={form.operator_id ?? ''}
                        onChange={(e) => setForm({ ...form, operator_id: e.target.value ? Number(e.target.value) : null })}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm"
                        required
                    >
                        <option value="">— Sélectionner —</option>
                        {operators.map(op => (
                            <option key={op.id} value={op.id}>{op.name} ({op.codename}) · {op.rarity}</option>
                        ))}
                    </select>
                    {errors.operator_id && <p className="text-danger text-xs mt-1">{errors.operator_id}</p>}
                </div>

                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Nom</label>
                    <input
                        type="text"
                        value={form.name}
                        onChange={(e) => setForm({ ...form, name: e.target.value })}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm"
                        required
                        maxLength={128}
                    />
                    {errors.name && <p className="text-danger text-xs mt-1">{errors.name}</p>}
                </div>

                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Slug (optionnel — auto si vide)</label>
                    <input
                        type="text"
                        value={form.slug}
                        onChange={(e) => setForm({ ...form, slug: e.target.value })}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high font-mono text-sm"
                        maxLength={96}
                    />
                    {errors.slug && <p className="text-danger text-xs mt-1">{errors.slug}</p>}
                </div>

                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Rareté</label>
                    <select
                        value={form.rarity}
                        onChange={(e) => setForm({ ...form, rarity: e.target.value as Rarity })}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm"
                    >
                        {enums.rarities.map(r => <option key={r} value={r}>{r}</option>)}
                    </select>
                </div>
            </div>

            <fieldset className="rounded-lg border border-border-default p-4">
                <legend className="text-xs font-display uppercase tracking-wide text-text-low px-2">Palette (utilisée par le prompt Meshy)</legend>
                <div className="space-y-2">
                    {form.palette_json.length === 0 && <p className="text-text-low text-sm italic">Aucune palette — facultatif (le prompt utilisera les couleurs de la faction par défaut).</p>}
                    {form.palette_json.map((p, i) => (
                        <div key={i} className="flex gap-2 items-center">
                            <input
                                type="text"
                                placeholder="slot (primary, accent, trim...)"
                                value={p.slot}
                                onChange={(e) => updatePalette(i, 'slot', e.target.value)}
                                className="flex-1 h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm"
                            />
                            <input
                                type="text"
                                placeholder="#ff00aa"
                                value={p.hex}
                                onChange={(e) => updatePalette(i, 'hex', e.target.value)}
                                className="w-32 h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high font-mono text-sm"
                            />
                            <input
                                type="color"
                                value={/^#[0-9a-fA-F]{6}$/.test(p.hex) ? p.hex : '#ffffff'}
                                onChange={(e) => updatePalette(i, 'hex', e.target.value)}
                                className="h-9 w-9 rounded border border-border-default bg-bg-elev2"
                            />
                            <Button type="button" size="sm" variant="ghost" icon={<X size={12} />} onClick={() => removePalette(i)} />
                        </div>
                    ))}
                </div>
                <Button type="button" size="sm" variant="ghost" icon={<Plus size={12} />} onClick={addPalette} className="mt-2">
                    Ajouter une entrée palette
                </Button>
            </fieldset>

            <div className="flex gap-6">
                <label className="inline-flex items-center gap-2 text-sm text-text-medium">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
                    Actif
                </label>
                <label className="inline-flex items-center gap-2 text-sm text-text-medium">
                    <input type="checkbox" checked={form.is_default} onChange={(e) => setForm({ ...form, is_default: e.target.checked })} />
                    Skin par défaut de l'opérateur
                </label>
            </div>

            <Button type="submit" variant="shard" disabled={busy}>
                {busy ? 'Enregistrement…' : submitLabel}
            </Button>
        </form>
    );
}
