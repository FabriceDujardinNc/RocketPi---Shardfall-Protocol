import { router } from '@inertiajs/react';
import { useState } from 'react';
import Button from '@ui/Button';

type Rarity = 'common' | 'rare' | 'epic' | 'legendary';
type Slot = 'head' | 'face' | 'back' | 'hands' | 'legs';

interface OperatorOption {
    id: number;
    name: string;
    codename: string;
    rarity: Rarity;
}

export interface AccessoryFormValues {
    name: string;
    slug: string;
    slot: Slot;
    rarity: Rarity;
    socket_name: string;
    is_active: boolean;
    operator_ids: number[];
    default_operator_id: number | null;
}

interface Props {
    initial: Partial<AccessoryFormValues> & { id?: number };
    operators: OperatorOption[];
    enums: { slots: Slot[]; rarities: Rarity[]; sockets: Record<Slot, string> };
    submitLabel: string;
    submitUrl: string;
    method?: 'post' | 'put';
}

export default function AccessoryForm({ initial, operators, enums, submitLabel, submitUrl, method = 'post' }: Props) {
    const [form, setForm] = useState<AccessoryFormValues>({
        name:        initial.name ?? '',
        slug:        initial.slug ?? '',
        slot:        initial.slot ?? 'head',
        rarity:      initial.rarity ?? 'common',
        socket_name: initial.socket_name ?? enums.sockets[initial.slot ?? 'head'] ?? 'Head_Top',
        is_active:   initial.is_active ?? true,
        operator_ids:        initial.operator_ids ?? [],
        default_operator_id: initial.default_operator_id ?? null,
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const changeSlot = (slot: Slot) => {
        setForm({ ...form, slot, socket_name: enums.sockets[slot] ?? form.socket_name });
    };

    const toggleOperator = (id: number) => {
        const has = form.operator_ids.includes(id);
        const operator_ids = has
            ? form.operator_ids.filter(x => x !== id)
            : [...form.operator_ids, id];
        const default_operator_id = form.default_operator_id === id && has
            ? null
            : form.default_operator_id;
        setForm({ ...form, operator_ids, default_operator_id });
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        setBusy(true);
        const payload = { ...form };
        const opts = {
            onError: (errs: Record<string, string>) => setErrors(errs),
            onFinish: () => setBusy(false),
        };
        // Cast via unknown : operator_ids[] est un number[] que router.* veut
        // FormDataConvertible — JSON sérialise correctement côté Inertia.
        if (method === 'put') router.put(submitUrl, payload as unknown as Record<string, never>, opts);
        else                  router.post(submitUrl, payload as unknown as Record<string, never>, opts);
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid md:grid-cols-2 gap-4">
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
                </div>

                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Slot</label>
                    <select
                        value={form.slot}
                        onChange={(e) => changeSlot(e.target.value as Slot)}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm"
                    >
                        {enums.slots.map(s => <option key={s} value={s}>{s}</option>)}
                    </select>
                </div>

                <div>
                    <label className="block text-xs font-display uppercase tracking-wide text-text-low mb-1">Socket (point d'attache rig)</label>
                    <input
                        type="text"
                        value={form.socket_name}
                        onChange={(e) => setForm({ ...form, socket_name: e.target.value })}
                        className="w-full h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high font-mono text-sm"
                        required
                        maxLength={32}
                    />
                    {errors.socket_name && <p className="text-danger text-xs mt-1">{errors.socket_name}</p>}
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

                <label className="inline-flex items-center gap-2 text-sm text-text-medium mt-7">
                    <input type="checkbox" checked={form.is_active} onChange={(e) => setForm({ ...form, is_active: e.target.checked })} />
                    Actif
                </label>
            </div>

            <fieldset className="rounded-lg border border-border-default p-4">
                <legend className="text-xs font-display uppercase tracking-wide text-text-low px-2">
                    Opérateurs compatibles ({form.operator_ids.length})
                </legend>
                <p className="text-xs text-text-low mb-3">Coche un opérateur pour qu'il puisse équiper cet accessoire. Coche "défaut" pour qu'il soit équipé sans choix joueur.</p>
                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                    {operators.map(op => {
                        const checked = form.operator_ids.includes(op.id);
                        const isDefault = form.default_operator_id === op.id;
                        return (
                            <div key={op.id} className={`p-2 rounded-md border ${checked ? 'border-shard-500/60 bg-shard-500/5' : 'border-border-default bg-bg-elev2'}`}>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={checked}
                                        onChange={() => toggleOperator(op.id)}
                                    />
                                    <span className="text-sm text-text-high">{op.name}</span>
                                    <span className="text-xs text-text-low font-mono">({op.codename})</span>
                                </label>
                                {checked && (
                                    <label className="flex items-center gap-2 mt-1 text-xs cursor-pointer">
                                        <input
                                            type="radio"
                                            name="default_operator"
                                            checked={isDefault}
                                            onChange={() => setForm({ ...form, default_operator_id: op.id })}
                                        />
                                        <span className={isDefault ? 'text-success' : 'text-text-low'}>défaut</span>
                                    </label>
                                )}
                            </div>
                        );
                    })}
                </div>
                {form.default_operator_id !== null && (
                    <Button type="button" size="sm" variant="ghost"
                        onClick={() => setForm({ ...form, default_operator_id: null })}
                        className="mt-3">
                        Retirer le défaut
                    </Button>
                )}
            </fieldset>

            <Button type="submit" variant="shard" disabled={busy}>
                {busy ? 'Enregistrement…' : submitLabel}
            </Button>
        </form>
    );
}
