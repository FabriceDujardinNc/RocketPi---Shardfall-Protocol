import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';

interface IdeaRow {
    id: number;
    slug: string;
    title: string;
    body_preview: string;
    status: 'open' | 'accepted' | 'rejected' | 'done';
    votes_count: number;
    author_id: number | null;
    author_name: string | null;
    author_email: string | null;
    created_at: string;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    ideas: Paginated<IdeaRow>;
    statusFilter: 'open' | 'accepted' | 'rejected' | 'done' | null;
}

const STATUSES: ('open' | 'accepted' | 'rejected' | 'done')[] = ['open', 'accepted', 'rejected', 'done'];

const STATUS_LABEL: Record<IdeaRow['status'], string> = {
    open:     'Ouverte',
    accepted: 'Acceptée',
    rejected: 'Refusée',
    done:     'Livrée',
};

const STATUS_COLOR: Record<IdeaRow['status'], string> = {
    open:     'text-shard-400 bg-shard-500/10',
    accepted: 'text-warning bg-warning/10',
    rejected: 'text-text-low bg-bg-elev2',
    done:     'text-success bg-success/10',
};

export default function AdminIdeas({ ideas, statusFilter }: Props) {
    const setFilter = (key: typeof statusFilter) => {
        router.get('/admin/ideas', key ? { status: key } : {}, { preserveScroll: true });
    };

    const updateStatus = (slug: string, status: string) => {
        router.patch(`/admin/ideas/${slug}/status`, { status }, { preserveScroll: true });
    };

    const deleteIdea = (slug: string) => {
        if (!confirm('Supprimer cette idée ?')) return;
        router.delete(`/admin/ideas/${slug}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Admin · Idées" />
            <h1 className="font-display font-bold text-2xl uppercase tracking-wide mb-6">Idées</h1>

            <section className="flex flex-wrap items-center gap-2 mb-4">
                <button
                    type="button"
                    onClick={() => setFilter(null)}
                    className={'px-3 py-1.5 rounded-md font-display text-xs uppercase tracking-wide ' +
                        (statusFilter === null
                            ? 'bg-shard-500/10 border border-shard-500/30 text-shard-400'
                            : 'border border-border-default text-text-medium hover:text-text-high')}
                >
                    Toutes
                </button>
                {STATUSES.map(s => (
                    <button
                        key={s}
                        type="button"
                        onClick={() => setFilter(s)}
                        className={'px-3 py-1.5 rounded-md font-display text-xs uppercase tracking-wide ' +
                            (statusFilter === s
                                ? 'bg-shard-500/10 border border-shard-500/30 text-shard-400'
                                : 'border border-border-default text-text-medium hover:text-text-high')}
                    >
                        {STATUS_LABEL[s]}
                    </button>
                ))}
            </section>

            <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-x-auto">
                <table className="w-full text-sm min-w-[900px]">
                    <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                        <tr>
                            <th className="px-3 py-3 text-left">ID</th>
                            <th className="px-3 py-3 text-left">Titre / Auteur</th>
                            <th className="px-3 py-3 text-right">Votes</th>
                            <th className="px-3 py-3 text-left">Statut</th>
                            <th className="px-3 py-3 text-left">Date</th>
                            <th className="px-3 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ideas.data.length === 0 ? (
                            <tr><td colSpan={6} className="px-4 py-12 text-center text-text-medium">Aucune idée.</td></tr>
                        ) : ideas.data.map(i => (
                            <tr key={i.id} className="border-t border-border-default align-top">
                                <td className="px-3 py-3 font-mono text-text-low">{i.id}</td>
                                <td className="px-3 py-3 max-w-md">
                                    <Link href={`/idees/${i.slug}`} className="font-display font-semibold text-text-high hover:text-shard-400">
                                        {i.title}
                                    </Link>
                                    <p className="font-mono text-xs text-text-low mt-1">
                                        {i.author_name} <span className="text-text-low">{i.author_email && `· ${i.author_email}`}</span>
                                    </p>
                                    <p className="font-body text-xs text-text-medium mt-2 line-clamp-2">{i.body_preview}</p>
                                </td>
                                <td className="px-3 py-3 text-right font-mono text-shard-400 tabular-nums">{i.votes_count}</td>
                                <td className="px-3 py-3">
                                    <span className={'inline-block px-2 py-0.5 rounded font-display text-[10px] uppercase tracking-mega ' + STATUS_COLOR[i.status]}>
                                        {STATUS_LABEL[i.status]}
                                    </span>
                                </td>
                                <td className="px-3 py-3 font-mono text-xs text-text-low whitespace-nowrap">
                                    {i.created_at.slice(0, 10)}
                                </td>
                                <td className="px-3 py-3">
                                    <div className="flex flex-wrap gap-1">
                                        {STATUSES.filter(s => s !== i.status).map(s => (
                                            <button
                                                key={s}
                                                type="button"
                                                onClick={() => updateStatus(i.slug, s)}
                                                className="font-display text-[10px] uppercase tracking-wide px-2 py-1 rounded border border-border-default text-text-medium hover:text-text-high hover:bg-bg-elev2"
                                            >
                                                → {STATUS_LABEL[s]}
                                            </button>
                                        ))}
                                        <button
                                            type="button"
                                            onClick={() => deleteIdea(i.slug)}
                                            className="font-display text-[10px] uppercase tracking-wide px-2 py-1 rounded border border-danger/30 text-danger hover:bg-danger/10"
                                        >
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

AdminIdeas.layout = (p: React.ReactNode) => <AdminLayout>{p}</AdminLayout>;
