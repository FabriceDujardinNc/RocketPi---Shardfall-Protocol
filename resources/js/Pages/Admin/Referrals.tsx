import { Head, Link, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import Pagination from '@ui/Pagination';
import { useState } from 'react';

type Status = 'pending' | 'validated' | 'rewarded' | 'flagged';

interface RefereeData { id: number; name: string; email: string; display_name: string | null; account_level: number; email_verified_at: string | null }
interface ReferrerData { id: number; name: string; email: string; display_name: string | null }

interface ReferralRow {
    id: number;
    status: Status;
    same_ip_as_referrer: boolean;
    referee_ip: string | null;
    flagged_at: string | null;
    flag_reason: string | null;
    created_at: string;
    referrer: ReferrerData | null;
    referee: RefereeData | null;
}

interface Props {
    referrals: {
        data: ReferralRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
        from: number;
        to: number;
        total: number;
    };
    filters: { status?: Status; q?: string };
    stats: { total: number; pending: number; validated: number; rewarded: number; flagged: number; same_ip: number };
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

const STATUS_COLORS: Record<Status, string> = {
    pending:   'bg-bg-elev3 text-text-medium',
    validated: 'bg-shard-500/15 text-shard-400',
    rewarded:  'bg-success/15 text-success',
    flagged:   'bg-danger/15 text-danger',
};

export default function AdminReferrals({ referrals, filters, stats }: Props) {
    const { props } = usePage<PageProps>();
    const [form, setForm] = useState({ status: filters.status ?? '', q: filters.q ?? '' });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const params = Object.fromEntries(Object.entries(form).filter(([_, v]) => v !== ''));
        router.get('/admin/referrals', params, { preserveState: true });
    };

    const flag = (r: ReferralRow) => {
        const reason = prompt(`Raison du flag pour parrainage #${r.id} ?`, 'Multi-compte suspecté');
        if (!reason) return;
        router.post(`/admin/referrals/${r.id}/flag`, { reason }, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Admin · Parrainages" />

            <header className="mb-6">
                <h1 className="font-display font-bold text-2xl uppercase tracking-wide">Parrainages</h1>
                <p className="font-mono text-xs text-text-low mt-1">Audit + détection de patterns suspects</p>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}

            <section className="grid md:grid-cols-6 gap-3 mb-6">
                <Stat label="Total" value={stats.total} />
                <Stat label="En attente" value={stats.pending} />
                <Stat label="Validés" value={stats.validated} accent="shard" />
                <Stat label="Récompensés" value={stats.rewarded} accent="success" />
                <Stat label="Suspects" value={stats.flagged} accent="danger" />
                <Stat label="Même IP" value={stats.same_ip} accent="warning" />
            </section>

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-4 mb-6 grid md:grid-cols-3 gap-3">
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Email parrain ou filleul</span>
                    <input
                        type="search"
                        value={form.q}
                        onChange={(e) => setForm({ ...form, q: e.target.value })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                </label>
                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-low">Statut</span>
                    <select
                        value={form.status}
                        onChange={(e) => setForm({ ...form, status: e.target.value as Status | '' })}
                        className="h-9 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high text-sm focus:outline-none focus:ring-2 focus:ring-shard-500"
                    >
                        <option value="">Tous</option>
                        <option value="pending">En attente</option>
                        <option value="validated">Validés</option>
                        <option value="rewarded">Récompensés</option>
                        <option value="flagged">Suspects</option>
                    </select>
                </label>
                <div className="flex items-end">
                    <Button type="submit" size="sm" variant="shard">Filtrer</Button>
                </div>
            </form>

            <section className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[700px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-2 text-left">ID</th>
                            <th className="px-3 py-2 text-left">Date</th>
                            <th className="px-3 py-2 text-left">Parrain</th>
                            <th className="px-3 py-2 text-left">Filleul</th>
                            <th className="px-3 py-2 text-left">Statut</th>
                            <th className="px-3 py-2 text-left">Flags</th>
                            <th className="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {referrals.data.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-3 py-12 text-center text-text-medium font-mono text-sm">Aucun parrainage.</td>
                            </tr>
                        ) : referrals.data.map(r => (
                            <tr key={r.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                <td className="px-3 py-2 font-mono text-text-low">{r.id}</td>
                                <td className="px-3 py-2 font-mono text-xs text-text-medium">{new Date(r.created_at).toLocaleDateString('fr-FR')}</td>
                                <td className="px-3 py-2">
                                    {r.referrer ? (
                                        <Link href={`/admin/players/${r.referrer.id}`} className="text-shard-400 hover:text-shard-300 text-xs">
                                            {r.referrer.email}
                                        </Link>
                                    ) : '—'}
                                </td>
                                <td className="px-3 py-2">
                                    {r.referee ? (
                                        <Link href={`/admin/players/${r.referee.id}`} className="text-shard-400 hover:text-shard-300 text-xs">
                                            {r.referee.email}
                                        </Link>
                                    ) : '—'}
                                    <span className="font-mono text-[10px] text-text-low ml-2">Lv.{r.referee?.account_level ?? 1}</span>
                                </td>
                                <td className="px-3 py-2">
                                    <span className={`font-display text-xs uppercase tracking-wide px-2 py-0.5 rounded ${STATUS_COLORS[r.status]}`}>
                                        {r.status}
                                    </span>
                                </td>
                                <td className="px-3 py-2">
                                    {r.same_ip_as_referrer && (
                                        <span className="font-display text-[10px] uppercase px-1.5 py-0.5 rounded bg-warning/15 text-warning border border-warning/40">
                                            Même IP
                                        </span>
                                    )}
                                </td>
                                <td className="px-3 py-2 text-right">
                                    {r.status !== 'flagged' && (
                                        <Button size="sm" variant="danger" onClick={() => flag(r)}>Flag</Button>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </section>

            <p className="font-mono text-xs text-text-low mt-3">
                {referrals.from}–{referrals.to} sur {referrals.total}
            </p>

            <Pagination links={referrals.links} />
        </>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'shard' | 'success' | 'warning' | 'danger' }) {
    const color =
        accent === 'shard'   ? 'text-shard-400' :
        accent === 'success' ? 'text-success'   :
        accent === 'warning' ? 'text-warning'   :
        accent === 'danger'  ? 'text-danger'    :
                               'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-3">
            <p className="font-display text-[10px] uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-xl font-bold mt-1 ${color}`}>{value}</p>
        </div>
    );
}

AdminReferrals.layout = (page: React.ReactNode) => <AdminLayout>{page}</AdminLayout>;
