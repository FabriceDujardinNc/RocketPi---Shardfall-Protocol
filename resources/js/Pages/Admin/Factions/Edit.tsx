import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import { ArrowLeft } from 'lucide-react';

interface Faction {
    slug: string;
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
    accent_class: string | null;
    banner_image_url: string | null;
    icon_url: string | null;
}

const inputCls = 'h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500 w-full';

export default function FactionEdit({ faction }: { faction: Faction }) {
    const { data, setData, errors, processing, put } = useForm({
        name:             faction.name,
        tagline:          faction.tagline ?? '',
        lore:             faction.lore ?? '',
        color_hue:        faction.color_hue,
        accent_class:     faction.accent_class ?? '',
        banner_image_url: faction.banner_image_url ?? '',
        icon_url:         faction.icon_url ?? '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/factions/${faction.slug}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Admin · Édition ${faction.slug}`} />
            <header className="mb-6">
                <Link href="/admin/factions" className="font-mono text-xs text-text-low hover:text-text-medium inline-flex items-center gap-1 mb-2">
                    <ArrowLeft size={12} /> Retour aux factions
                </Link>
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide" style={{ color: `oklch(0.75 0.18 ${data.color_hue})` }}>
                    {faction.slug} · Édition
                </h1>
                <p className="font-mono text-xs text-text-low mt-1">
                    Le slug est immuable (référencé par l'enum `operators.faction`). On édite uniquement le contenu éditorial.
                </p>
            </header>

            <form onSubmit={submit} className="space-y-6 max-w-3xl">
                <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Identité</legend>
                    <div className="grid md:grid-cols-2 gap-4 mt-2">
                        <Field label="Nom affiché" error={errors.name} required>
                            <input value={data.name} onChange={e => setData('name', e.target.value)} className={inputCls} maxLength={64} />
                        </Field>
                        <Field label="Tagline" error={errors.tagline} hint="Une phrase synthétique.">
                            <input value={data.tagline} onChange={e => setData('tagline', e.target.value)} className={inputCls} maxLength={255} />
                        </Field>
                        <Field label="Hue OKLCH (0-360)" error={errors.color_hue} required hint="Couleur d'accent — change l'aperçu.">
                            <input type="number" min={0} max={360} value={data.color_hue} onChange={e => setData('color_hue', parseInt(e.target.value || '0'))} className={inputCls} />
                        </Field>
                        <Field label="Classe d'accent (utility)" error={errors.accent_class} hint="ex: shard-cyan, ferro-rust">
                            <input value={data.accent_class} onChange={e => setData('accent_class', e.target.value)} className={inputCls} maxLength={32} />
                        </Field>
                    </div>
                </fieldset>

                <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Lore de la faction</legend>
                    <textarea value={data.lore} onChange={e => setData('lore', e.target.value)} maxLength={5000}
                        className={inputCls + ' h-40 mt-2'} placeholder="Background, doctrine, philosophie..." />
                    <p className="text-text-low text-xs font-mono mt-1">{(data.lore ?? '').length} / 5000</p>
                </fieldset>

                <fieldset className="rounded-lg bg-bg-elev1 border border-border-default p-5">
                    <legend className="font-display text-xs uppercase tracking-wide text-shard-400 px-2">Visuels</legend>
                    <div className="grid md:grid-cols-2 gap-4 mt-2">
                        <Field label="URL image bannière" error={errors.banner_image_url}>
                            <input value={data.banner_image_url} onChange={e => setData('banner_image_url', e.target.value)} className={inputCls} placeholder="https://..." />
                        </Field>
                        <Field label="URL icône" error={errors.icon_url}>
                            <input value={data.icon_url} onChange={e => setData('icon_url', e.target.value)} className={inputCls} placeholder="https://..." />
                        </Field>
                    </div>
                </fieldset>

                <div className="flex gap-3">
                    <Button type="submit" variant="shard" loading={processing}>Enregistrer</Button>
                    <Button type="button" variant="ghost" onClick={() => window.history.back()}>Annuler</Button>
                </div>
            </form>
        </>
    );
}

function Field({ label, error, hint, required, children }: { label: string; error?: string; hint?: string; required?: boolean; children: React.ReactNode }) {
    return (
        <label className="flex flex-col gap-1">
            <span className="font-display text-xs uppercase tracking-wide text-text-low">{label}{required && <span className="text-danger ml-1">*</span>}</span>
            {children}
            {hint && !error && <span className="text-text-low text-xs font-mono">{hint}</span>}
            {error && <span className="text-danger text-xs font-mono">{error}</span>}
        </label>
    );
}

FactionEdit.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
