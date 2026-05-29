import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import SEO from '@/Components/SEO';
import Button from '@ui/Button';
import { type FormEventHandler, useState } from 'react';
import { ThumbsUp } from 'lucide-react';

interface IdeaRow {
    id: number;
    slug: string;
    title: string;
    status: 'open' | 'accepted' | 'rejected' | 'done';
    votes_count: number;
    author_name: string;
    created_at: string | null;
    voted_by_me: boolean;
}

interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    ideas: Paginated<IdeaRow>;
    statusFilter: 'open' | 'accepted' | 'rejected' | 'done' | null;
    counts: Record<'open' | 'accepted' | 'rejected' | 'done', number>;
}

const STATUS_LABEL: Record<IdeaRow['status'], string> = {
    open:     'Ouverte',
    accepted: 'Acceptée',
    rejected: 'Refusée',
    done:     'Livrée',
};

const STATUS_COLOR: Record<IdeaRow['status'], string> = {
    open:     'text-shard-400 border-shard-500/30 bg-shard-500/10',
    accepted: 'text-warning border-warning/30 bg-warning/10',
    rejected: 'text-text-low border-border-default bg-bg-elev2',
    done:     'text-success border-success/30 bg-success/10',
};

const FILTERS: { key: 'open' | 'accepted' | 'rejected' | 'done' | null; label: string }[] = [
    { key: null,       label: 'Toutes' },
    { key: 'open',     label: 'Ouvertes' },
    { key: 'accepted', label: 'Acceptées' },
    { key: 'done',     label: 'Livrées' },
    { key: 'rejected', label: 'Refusées' },
];

export default function IdeasIndex({ ideas, statusFilter, counts }: Props) {
    const { props } = usePage<{ auth?: { user?: { id: number } } }>();
    const isAuth = !!props.auth?.user;
    const [showForm, setShowForm] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({ title: '', body: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/idees', { onSuccess: () => { reset(); setShowForm(false); } });
    };

    const setFilter = (key: typeof statusFilter) => {
        router.get('/idees', key ? { status: key } : {}, { preserveScroll: true });
    };

    const toggleVote = (idea: IdeaRow) => {
        if (!isAuth) {
            router.visit('/login');
            return;
        }
        if (idea.voted_by_me) {
            router.delete(`/idees/${idea.slug}/vote`, { preserveScroll: true });
        } else {
            router.post(`/idees/${idea.slug}/vote`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <SEO
                title="Idées de la communauté"
                description="Les idées proposées par les joueurs pour le développement de RocketPi. Vote pour celles que tu veux voir arriver, ou propose la tienne."
            />

            <div className="min-h-screen bg-bg-base text-text-high flex flex-col">
                <header className="border-b border-border-default">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 h-16 flex items-center justify-between">
                        <Link href="/" className="font-display font-bold text-lg uppercase tracking-wide">
                            ROCKETPI<span className="text-shard-500">.</span>
                        </Link>
                        <nav className="flex items-center gap-3 font-display text-sm uppercase tracking-wide">
                            <Link href="/dons" className="text-text-medium hover:text-text-high">Dons</Link>
                            {isAuth ? (
                                <Link href="/play" className="text-shard-400 hover:text-shard-300">Jouer</Link>
                            ) : (
                                <>
                                    <Link href="/login" className="text-text-medium hover:text-text-high">Connexion</Link>
                                    <Link href="/register" className="text-shard-400 hover:text-shard-300">Rejoindre</Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="flex-1 mx-auto max-w-4xl w-full px-4 sm:px-6 py-10">
                    <header className="mb-8 flex flex-wrap justify-between items-end gap-3">
                        <div>
                            <p className="font-display text-xs uppercase tracking-mega text-shard-400">Communauté</p>
                            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">
                                Idées de développement
                            </h1>
                        </div>
                        {isAuth && (
                            <Button onClick={() => setShowForm(v => !v)} variant="shard">
                                {showForm ? 'Annuler' : 'Proposer une idée'}
                            </Button>
                        )}
                    </header>

                    {showForm && isAuth && (
                        <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-5 mb-6 flex flex-col gap-3">
                            <label className="flex flex-col gap-1">
                                <span className="font-display text-xs uppercase tracking-wide text-text-medium">Titre (max 120 caractères)</span>
                                <input
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    maxLength={120}
                                    required
                                    className="h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                                />
                                {errors.title && <span className="text-danger text-xs">{errors.title}</span>}
                            </label>
                            <label className="flex flex-col gap-1">
                                <span className="font-display text-xs uppercase tracking-wide text-text-medium">Description (jusqu'à 4 000 caractères)</span>
                                <textarea
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    maxLength={4000}
                                    required
                                    rows={6}
                                    className="px-3 py-2 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                                />
                                {errors.body && <span className="text-danger text-xs">{errors.body}</span>}
                            </label>
                            <div className="flex justify-end">
                                <Button type="submit" loading={processing} variant="shard">Publier</Button>
                            </div>
                        </form>
                    )}

                    <section className="flex flex-wrap items-center gap-2 mb-4">
                        {FILTERS.map(f => {
                            const active = (statusFilter ?? null) === f.key;
                            const count = f.key ? counts[f.key] : Object.values(counts).reduce((a, b) => a + b, 0);
                            return (
                                <button
                                    key={f.label}
                                    type="button"
                                    onClick={() => setFilter(f.key)}
                                    className={
                                        'px-3 py-1.5 rounded-md font-display text-xs uppercase tracking-wide transition ' +
                                        (active
                                            ? 'bg-shard-500/10 border border-shard-500/30 text-shard-400'
                                            : 'border border-border-default text-text-medium hover:text-text-high hover:bg-bg-elev1')
                                    }
                                >
                                    {f.label} <span className="text-text-low">({count})</span>
                                </button>
                            );
                        })}
                    </section>

                    {ideas.data.length === 0 ? (
                        <div className="rounded-lg bg-bg-elev1 border border-border-default p-8 text-center font-body text-sm text-text-medium">
                            Aucune idée pour ce filtre.
                            {isAuth && <p className="mt-3 text-xs">Sois le premier à proposer la tienne !</p>}
                        </div>
                    ) : (
                        <ul className="space-y-3">
                            {ideas.data.map(idea => (
                                <li key={idea.id} className="rounded-lg bg-bg-elev1 border border-border-default p-4 flex items-start gap-4">
                                    <button
                                        type="button"
                                        onClick={() => toggleVote(idea)}
                                        className={
                                            'shrink-0 flex flex-col items-center justify-center rounded-md border px-3 py-2 min-w-[60px] transition ' +
                                            (idea.voted_by_me
                                                ? 'bg-shard-500/10 border-shard-500/40 text-shard-400'
                                                : 'border-border-default text-text-medium hover:text-text-high hover:bg-bg-elev2')
                                        }
                                        aria-label={idea.voted_by_me ? 'Retirer mon vote' : 'Voter'}
                                    >
                                        <ThumbsUp size={16} />
                                        <span className="font-mono text-sm font-semibold mt-1 tabular-nums">
                                            {idea.votes_count}
                                        </span>
                                    </button>
                                    <div className="flex-1 min-w-0">
                                        <Link
                                            href={`/idees/${idea.slug}`}
                                            className="font-display font-semibold text-lg text-text-high hover:text-shard-400 transition block"
                                        >
                                            {idea.title}
                                        </Link>
                                        <div className="flex items-center gap-2 flex-wrap mt-1 font-mono text-xs text-text-low">
                                            <span>par {idea.author_name}</span>
                                            {idea.created_at && <span>· {idea.created_at}</span>}
                                            <span
                                                className={'inline-block px-2 py-0.5 rounded border font-display text-[10px] uppercase tracking-mega ' + STATUS_COLOR[idea.status]}
                                            >
                                                {STATUS_LABEL[idea.status]}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}

                    {ideas.links.length > 3 && (
                        <nav className="flex flex-wrap gap-1 mt-6 justify-center">
                            {ideas.links.map((l, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    onClick={() => l.url && router.visit(l.url, { preserveScroll: true })}
                                    disabled={!l.url}
                                    className={
                                        'px-3 py-1 rounded font-mono text-xs ' +
                                        (l.active
                                            ? 'bg-shard-500/20 text-shard-400'
                                            : l.url
                                                ? 'text-text-medium hover:bg-bg-elev2'
                                                : 'text-text-low cursor-not-allowed')
                                    }
                                    dangerouslySetInnerHTML={{ __html: l.label }}
                                />
                            ))}
                        </nav>
                    )}
                </main>

                <footer className="border-t border-border-default py-6 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol
                </footer>
            </div>
        </>
    );
}
