import { useForm } from '@inertiajs/react';
import { Flag } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';
import Button from '@ui/Button';

interface Props {
    reportedId: number;
    reportedName: string;
    matchSessionId?: number;
    /** Variante d'affichage : `button` (visible) ou `icon-only` (compact). */
    variant?: 'button' | 'icon';
}

const REASONS = [
    { value: 'cheat', label: 'Triche' },
    { value: 'toxic', label: 'Comportement toxique' },
    { value: 'afk',   label: 'AFK / absent' },
    { value: 'smurf', label: 'Smurf (multi-compte)' },
    { value: 'other', label: 'Autre' },
] as const;

/**
 * Bouton de signalement joueur (Phase 4).
 *
 * Ouvre un modal pour choisir une raison + description optionnelle.
 * POST vers `/reports` (rate limit 5/h côté serveur, unique constraint
 * BDD anti-doublon).
 */
export default function ReportButton({ reportedId, reportedName, matchSessionId, variant = 'button' }: Props) {
    const [open, setOpen] = useState(false);
    const [done, setDone] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm<{
        reported_id: number;
        match_session_id: number | null;
        reason: '' | 'cheat' | 'toxic' | 'afk' | 'smurf' | 'other';
        description: string;
    }>({
        reported_id: reportedId,
        match_session_id: matchSessionId ?? null,
        reason: '',
        description: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/reports', {
            preserveScroll: true,
            onSuccess: () => { setDone(true); setTimeout(() => { setOpen(false); setDone(false); reset(); }, 1500); },
        });
    };

    const trigger = variant === 'icon' ? (
        <button
            type="button"
            onClick={() => setOpen(true)}
            aria-label={`Signaler ${reportedName}`}
            className="inline-flex items-center justify-center size-8 rounded-md text-text-low hover:text-danger hover:bg-bg-elev2 transition-colors duration-fast"
        >
            <Flag size={14} />
        </button>
    ) : (
        <button
            type="button"
            onClick={() => setOpen(true)}
            className="inline-flex items-center gap-1.5 font-display text-xs uppercase tracking-wide text-text-low hover:text-danger transition-colors duration-fast"
        >
            <Flag size={12} />
            Signaler
        </button>
    );

    return (
        <>
            {trigger}

            {open && (
                <div className="fixed inset-0 z-modal flex items-center justify-center p-4" role="dialog" aria-modal="true">
                    <div
                        className="absolute inset-0 bg-bg-base/80 backdrop-blur-sm"
                        onClick={() => !processing && setOpen(false)}
                        aria-hidden="true"
                    />
                    <form
                        onSubmit={submit}
                        className="relative w-full max-w-md rounded-lg bg-bg-elev1 border border-border-default p-5 shadow-el3"
                    >
                        <header className="mb-4">
                            <p className="font-display text-[10px] uppercase tracking-mega text-danger">Signalement</p>
                            <h2 className="font-display font-bold text-lg uppercase tracking-wide text-text-high mt-1">
                                Signaler {reportedName}
                            </h2>
                        </header>

                        {done ? (
                            <div className="rounded bg-success/10 border border-success/40 p-4 text-center">
                                <p className="font-display text-sm uppercase tracking-wide text-success">
                                    ✓ Transmis aux modérateurs
                                </p>
                            </div>
                        ) : (
                            <>
                                <div className="flex flex-col gap-2 mb-3">
                                    <label className="font-display text-[10px] uppercase tracking-mega text-text-low">
                                        Raison
                                    </label>
                                    <div className="grid grid-cols-2 gap-1.5">
                                        {REASONS.map((r) => (
                                            <button
                                                key={r.value}
                                                type="button"
                                                onClick={() => setData('reason', r.value)}
                                                className={
                                                    'px-3 py-2 rounded border font-display text-xs uppercase tracking-wide transition-colors duration-fast ' +
                                                    (data.reason === r.value
                                                        ? 'bg-danger/15 text-danger border-danger/40'
                                                        : 'bg-bg-elev2 text-text-medium border-border-default hover:text-text-high')
                                                }
                                            >
                                                {r.label}
                                            </button>
                                        ))}
                                    </div>
                                    {errors.reason && <span className="text-danger text-xs">{errors.reason}</span>}
                                </div>

                                <label className="flex flex-col gap-1 mb-4">
                                    <span className="font-display text-[10px] uppercase tracking-mega text-text-low">
                                        Description (optionnel)
                                    </span>
                                    <textarea
                                        rows={3}
                                        maxLength={2000}
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Plus de contexte aidera la modération."
                                        className="font-body text-sm px-3 py-2 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                                    />
                                    {errors.description && <span className="text-danger text-xs">{errors.description}</span>}
                                </label>

                                {(() => {
                                    // Cast pour autoriser des clés side-effect (report, reported_id) absentes
                                    // du form type strict — elles peuvent être renvoyées par Laravel.
                                    const extra = errors as Record<string, string | undefined>;
                                    const extraMsg = extra.report ?? extra.reported_id;
                                    return extraMsg ? (
                                        <p className="text-danger text-xs font-mono mb-3">{extraMsg}</p>
                                    ) : null;
                                })()}

                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        onClick={() => setOpen(false)}
                                        variant="ghost"
                                        size="sm"
                                        disabled={processing}
                                        fullWidth
                                    >
                                        Annuler
                                    </Button>
                                    <Button
                                        type="submit"
                                        variant="danger"
                                        size="sm"
                                        loading={processing}
                                        disabled={!data.reason}
                                        fullWidth
                                    >
                                        Envoyer
                                    </Button>
                                </div>
                            </>
                        )}
                    </form>
                </div>
            )}
        </>
    );
}
