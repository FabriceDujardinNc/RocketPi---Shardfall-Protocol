import { Head, Link, router, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Pagination from '@ui/Pagination';
import { useState, type FormEventHandler } from 'react';

interface Report {
    id: number;
    reason: 'cheat' | 'toxic' | 'afk' | 'smurf' | 'other';
    description: string | null;
    status: 'pending' | 'reviewed' | 'dismissed' | 'sanctioned';
    admin_notes: string | null;
    reviewed_at: string | null;
    created_at: string;
    reporter: { id: number; name: string; display_name: string | null; slug: string | null } | null;
    reported: { id: number; name: string; display_name: string | null; slug: string | null; is_banned: boolean } | null;
    session: { id: number; mode: string; rank_type: string; started_at: string } | null;
    reviewer: { id: number; name: string; display_name: string | null } | null;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    reports: Paginated<Report>;
    counts: Record<string, number>;
    filters: { status: string; reason: string };
    reasons: string[];
    statuses: string[];
}

const REASON_LABEL: Record<string, string> = {
    cheat: 'Triche',
    toxic: 'Toxicité',
    afk: 'AFK',
    smurf: 'Smurf',
    other: 'Autre',
};

const STATUS_LABEL: Record<string, string> = {
    pending: 'En attente',
    reviewed: 'Vu',
    dismissed: 'Rejeté',
    sanctioned: 'Sanctionné',
};

const STATUS_COLOR: Record<string, string> = {
    pending: 'text-warning',
    reviewed: 'text-info',
    dismissed: 'text-text-low',
    sanctioned: 'text-danger',
};

export default function ModerationIndex({ reports, counts, filters, reasons, statuses }: Props) {
    const [activeReport, setActiveReport] = useState<Report | null>(null);

    const setFilter = (key: 'status' | 'reason', value: string) => {
        router.get('/admin/moderation', { ...filters, [key]: value }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Modération" />

            <header className="mb-6 flex items-end justify-between flex-wrap gap-4">
                <div>
                    <p className="font-display text-xs uppercase tracking-mega text-danger">Modération</p>
                    <h1 className="font-display font-bold text-2xl sm:text-3xl uppercase tracking-wide mt-1">
                        Signalements
                    </h1>
                </div>
            </header>

            {/* Status filter tabs */}
            <div className="flex flex-wrap gap-2 mb-4">
                {['all', ...statuses].map(s => (
                    <button
                        key={s}
                        type="button"
                        onClick={() => setFilter('status', s)}
                        className={
                            'px-3 py-1.5 rounded-md font-display text-xs uppercase tracking-wide transition-colors duration-fast ' +
                            (filters.status === s
                                ? 'bg-shard-500/15 text-shard-400 border border-shard-500/40'
                                : 'bg-bg-elev1 text-text-medium border border-border-default hover:text-text-high')
                        }
                    >
                        {s === 'all' ? 'Tous' : STATUS_LABEL[s] ?? s}
                        {s !== 'all' && counts[s] !== undefined && (
                            <span className="ml-1.5 text-text-low">({counts[s]})</span>
                        )}
                    </button>
                ))}
            </div>

            {/* Reason filter */}
            <div className="flex flex-wrap gap-2 mb-6">
                <button
                    type="button"
                    onClick={() => setFilter('reason', '')}
                    className={
                        'px-2 py-1 rounded font-mono text-[11px] uppercase ' +
                        (!filters.reason ? 'bg-bg-elev2 text-text-high' : 'text-text-low hover:text-text-medium')
                    }
                >
                    Toutes raisons
                </button>
                {reasons.map(r => (
                    <button
                        key={r}
                        type="button"
                        onClick={() => setFilter('reason', r)}
                        className={
                            'px-2 py-1 rounded font-mono text-[11px] uppercase ' +
                            (filters.reason === r ? 'bg-bg-elev2 text-text-high' : 'text-text-low hover:text-text-medium')
                        }
                    >
                        {REASON_LABEL[r] ?? r}
                    </button>
                ))}
            </div>

            {/* Reports table */}
            {reports.data.length === 0 ? (
                <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium font-mono text-sm">
                    Aucun signalement avec ces filtres.
                </div>
            ) : (
                <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                    <table className="w-full text-sm min-w-[800px]">
                        <thead className="bg-bg-elev2 font-display text-[11px] uppercase tracking-mega text-text-low">
                            <tr>
                                <th className="text-left px-3 py-3">Reporter</th>
                                <th className="text-left px-3 py-3">Signalé</th>
                                <th className="text-left px-3 py-3">Raison</th>
                                <th className="text-left px-3 py-3">Statut</th>
                                <th className="text-left px-3 py-3">Date</th>
                                <th className="text-right px-3 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {reports.data.map((r) => (
                                <tr key={r.id} className="border-t border-border-default hover:bg-bg-elev2/30">
                                    <td className="px-3 py-3">
                                        {r.reporter ? (
                                            <Link
                                                href={r.reporter.slug ? `/profile/${r.reporter.slug}` : '#'}
                                                className="font-display text-text-high hover:text-shard-400"
                                            >
                                                {r.reporter.display_name ?? r.reporter.name}
                                            </Link>
                                        ) : '—'}
                                    </td>
                                    <td className="px-3 py-3">
                                        {r.reported ? (
                                            <div className="flex items-center gap-2">
                                                <Link
                                                    href={`/admin/players/${r.reported.id}`}
                                                    className="font-display text-text-high hover:text-shard-400"
                                                >
                                                    {r.reported.display_name ?? r.reported.name}
                                                </Link>
                                                {r.reported.is_banned && (
                                                    <span className="font-display text-[10px] uppercase tracking-mega text-danger bg-danger/10 px-1.5 py-0.5 rounded">
                                                        Banni
                                                    </span>
                                                )}
                                            </div>
                                        ) : '—'}
                                    </td>
                                    <td className="px-3 py-3 font-mono text-xs text-text-medium">
                                        {REASON_LABEL[r.reason] ?? r.reason}
                                    </td>
                                    <td className={`px-3 py-3 font-display text-xs uppercase tracking-mega ${STATUS_COLOR[r.status]}`}>
                                        {STATUS_LABEL[r.status] ?? r.status}
                                    </td>
                                    <td className="px-3 py-3 font-mono text-xs text-text-low">
                                        {new Date(r.created_at).toLocaleDateString('fr-FR')}
                                    </td>
                                    <td className="px-3 py-3 text-right">
                                        <button
                                            type="button"
                                            onClick={() => setActiveReport(r)}
                                            className="font-display text-xs uppercase tracking-wide text-shard-400 hover:text-shard-300"
                                        >
                                            {r.status === 'pending' || r.status === 'reviewed' ? 'Traiter →' : 'Détails →'}
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <Pagination links={reports.links} />

            {/* Modal détails / action */}
            {activeReport && (
                <ReportModal report={activeReport} onClose={() => setActiveReport(null)} />
            )}
        </>
    );
}

function ReportModal({ report, onClose }: { report: Report; onClose: () => void }) {
    const dismissForm = useForm({ notes: '' });
    const sanctionForm = useForm({ ban_reason: '', notes: '' });

    const dismiss: FormEventHandler = (e) => {
        e.preventDefault();
        dismissForm.post(`/admin/moderation/${report.id}/dismiss`, { onSuccess: onClose });
    };

    const sanction: FormEventHandler = (e) => {
        e.preventDefault();
        sanctionForm.post(`/admin/moderation/${report.id}/sanction`, { onSuccess: onClose });
    };

    const isClosed = report.status === 'dismissed' || report.status === 'sanctioned';

    return (
        <div className="fixed inset-0 z-modal flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div
                className="absolute inset-0 bg-bg-base/80 backdrop-blur-sm"
                onClick={onClose}
                aria-hidden="true"
            />
            <div className="relative w-full max-w-2xl rounded-lg bg-bg-elev1 border border-border-default p-6 shadow-el3 max-h-[90vh] overflow-y-auto">
                <header className="flex items-start justify-between gap-2 mb-4">
                    <div>
                        <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Signalement #{report.id}</p>
                        <h2 className="font-display font-bold text-xl uppercase tracking-wide mt-1">
                            {REASON_LABEL[report.reason] ?? report.reason}
                        </h2>
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="font-display text-text-medium hover:text-text-high"
                        aria-label="Fermer"
                    >
                        ✕
                    </button>
                </header>

                <dl className="grid grid-cols-2 gap-3 mb-4 text-sm">
                    <dt className="text-text-low font-mono">Reporter</dt>
                    <dd className="text-text-high">{report.reporter?.display_name ?? report.reporter?.name ?? '—'}</dd>
                    <dt className="text-text-low font-mono">Signalé</dt>
                    <dd className="text-text-high">{report.reported?.display_name ?? report.reported?.name ?? '—'}</dd>
                    <dt className="text-text-low font-mono">Session</dt>
                    <dd className="text-text-high">
                        {report.session ? `#${report.session.id} (${report.session.mode}/${report.session.rank_type})` : '—'}
                    </dd>
                    <dt className="text-text-low font-mono">Date</dt>
                    <dd className="text-text-high">{new Date(report.created_at).toLocaleString('fr-FR')}</dd>
                </dl>

                {report.description && (
                    <div className="mb-4 rounded bg-bg-elev2 border border-border-default p-3">
                        <p className="font-display text-[10px] uppercase tracking-mega text-text-low mb-1">Description du reporter</p>
                        <p className="font-body text-sm text-text-high whitespace-pre-wrap">{report.description}</p>
                    </div>
                )}

                {isClosed ? (
                    <div className="rounded bg-bg-elev2 border border-border-default p-3">
                        <p className="font-display text-[10px] uppercase tracking-mega text-text-low mb-1">Résolu</p>
                        <p className={`font-display text-sm uppercase tracking-wide ${STATUS_COLOR[report.status]}`}>
                            {STATUS_LABEL[report.status]}
                        </p>
                        {report.reviewer && (
                            <p className="font-mono text-xs text-text-low mt-1">
                                par {report.reviewer.display_name ?? report.reviewer.name}, le {new Date(report.reviewed_at!).toLocaleString('fr-FR')}
                            </p>
                        )}
                        {report.admin_notes && (
                            <p className="font-body text-sm text-text-medium mt-2 whitespace-pre-wrap">{report.admin_notes}</p>
                        )}
                    </div>
                ) : (
                    <div className="grid sm:grid-cols-2 gap-4">
                        {/* Dismiss */}
                        <form onSubmit={dismiss} className="rounded bg-bg-elev2 border border-border-default p-3 flex flex-col gap-2">
                            <p className="font-display text-xs uppercase tracking-mega text-text-low">Rejeter</p>
                            <textarea
                                rows={3}
                                placeholder="Notes (optionnel)"
                                value={dismissForm.data.notes}
                                onChange={(e) => dismissForm.setData('notes', e.target.value)}
                                className="font-mono text-xs px-2 py-1.5 rounded bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                            />
                            <Button type="submit" variant="ghost" size="sm" loading={dismissForm.processing} fullWidth>
                                Rejeter
                            </Button>
                        </form>

                        {/* Sanction */}
                        <form onSubmit={sanction} className="rounded bg-danger/5 border border-danger/30 p-3 flex flex-col gap-2">
                            <p className="font-display text-xs uppercase tracking-mega text-danger">Sanctionner (ban)</p>
                            <input
                                type="text"
                                required
                                placeholder="Raison du ban (visible audit)"
                                value={sanctionForm.data.ban_reason}
                                onChange={(e) => sanctionForm.setData('ban_reason', e.target.value)}
                                className="font-mono text-xs px-2 py-1.5 rounded bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-danger"
                            />
                            {sanctionForm.errors.ban_reason && (
                                <span className="text-danger text-xs">{sanctionForm.errors.ban_reason}</span>
                            )}
                            <textarea
                                rows={2}
                                placeholder="Notes internes (optionnel)"
                                value={sanctionForm.data.notes}
                                onChange={(e) => sanctionForm.setData('notes', e.target.value)}
                                className="font-mono text-xs px-2 py-1.5 rounded bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-danger"
                            />
                            <Button type="submit" variant="danger" size="sm" loading={sanctionForm.processing} fullWidth>
                                Bannir le joueur
                            </Button>
                        </form>
                    </div>
                )}
            </div>
        </div>
    );
}

ModerationIndex.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
