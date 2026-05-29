import { Link, router, useForm, usePage } from '@inertiajs/react';
import SEO from '@/Components/SEO';
import Button from '@ui/Button';
import { type FormEventHandler, useState } from 'react';
import { ThumbsUp, ArrowLeft } from 'lucide-react';

interface Idea {
    id: number;
    slug: string;
    title: string;
    body: string;
    status: 'open' | 'accepted' | 'rejected' | 'done';
    votes_count: number;
    author_id: number | null;
    author_name: string;
    created_at: string | null;
    updated_at: string | null;
    voted_by_me: boolean;
    can_edit: boolean;
}

interface Props { idea: Idea }

const STATUS_LABEL: Record<Idea['status'], string> = {
    open:     'Ouverte',
    accepted: 'Acceptée',
    rejected: 'Refusée',
    done:     'Livrée',
};

const STATUS_COLOR: Record<Idea['status'], string> = {
    open:     'text-shard-400 border-shard-500/30 bg-shard-500/10',
    accepted: 'text-warning border-warning/30 bg-warning/10',
    rejected: 'text-text-low border-border-default bg-bg-elev2',
    done:     'text-success border-success/30 bg-success/10',
};

export default function IdeaShow({ idea }: Props) {
    const { props } = usePage<{ auth?: { user?: { id: number } } }>();
    const isAuth = !!props.auth?.user;
    const [editing, setEditing] = useState(false);

    const { data, setData, patch, processing, errors } = useForm({
        title: idea.title,
        body:  idea.body,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(`/idees/${idea.slug}`, { onSuccess: () => setEditing(false) });
    };

    const toggleVote = () => {
        if (!isAuth) { router.visit('/login'); return; }
        if (idea.voted_by_me) {
            router.delete(`/idees/${idea.slug}/vote`, { preserveScroll: true });
        } else {
            router.post(`/idees/${idea.slug}/vote`, {}, { preserveScroll: true });
        }
    };

    const deleteIdea = () => {
        if (!confirm('Supprimer définitivement cette idée ?')) return;
        router.delete(`/idees/${idea.slug}`);
    };

    return (
        <>
            <SEO
                title={idea.title}
                description={idea.body.slice(0, 200)}
                type="article"
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

                <main className="flex-1 mx-auto max-w-3xl w-full px-4 sm:px-6 py-10">
                    <Link href="/idees" className="inline-flex items-center gap-2 font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high mb-6">
                        <ArrowLeft size={14} /> Toutes les idées
                    </Link>

                    {editing && idea.can_edit ? (
                        <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-5 flex flex-col gap-3">
                            <label className="flex flex-col gap-1">
                                <span className="font-display text-xs uppercase tracking-wide text-text-medium">Titre</span>
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
                                <span className="font-display text-xs uppercase tracking-wide text-text-medium">Description</span>
                                <textarea
                                    value={data.body}
                                    onChange={(e) => setData('body', e.target.value)}
                                    maxLength={4000}
                                    required
                                    rows={10}
                                    className="px-3 py-2 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                                />
                                {errors.body && <span className="text-danger text-xs">{errors.body}</span>}
                            </label>
                            <div className="flex gap-2 justify-end">
                                <Button type="button" variant="secondary" onClick={() => setEditing(false)}>Annuler</Button>
                                <Button type="submit" loading={processing} variant="shard">Enregistrer</Button>
                            </div>
                        </form>
                    ) : (
                        <article className="rounded-lg bg-bg-elev1 border border-border-default p-6">
                            <div className="flex flex-wrap items-start gap-4">
                                <button
                                    type="button"
                                    onClick={toggleVote}
                                    className={
                                        'shrink-0 flex flex-col items-center justify-center rounded-md border px-4 py-3 min-w-[80px] transition ' +
                                        (idea.voted_by_me
                                            ? 'bg-shard-500/10 border-shard-500/40 text-shard-400'
                                            : 'border-border-default text-text-medium hover:text-text-high hover:bg-bg-elev2')
                                    }
                                >
                                    <ThumbsUp size={20} />
                                    <span className="font-mono text-lg font-semibold mt-1 tabular-nums">
                                        {idea.votes_count}
                                    </span>
                                </button>
                                <div className="flex-1 min-w-0">
                                    <h1 className="font-display font-bold text-2xl tracking-wide">{idea.title}</h1>
                                    <div className="flex items-center gap-2 flex-wrap mt-2 font-mono text-xs text-text-low">
                                        <span>par {idea.author_name}</span>
                                        {idea.created_at && <span>· proposée le {idea.created_at.slice(0, 10)}</span>}
                                        <span className={'inline-block px-2 py-0.5 rounded border font-display text-[10px] uppercase tracking-mega ' + STATUS_COLOR[idea.status]}>
                                            {STATUS_LABEL[idea.status]}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="mt-6 prose prose-invert max-w-none font-body text-text-medium leading-relaxed whitespace-pre-wrap">
                                {idea.body}
                            </div>
                            {idea.can_edit && idea.status === 'open' && (
                                <div className="mt-6 pt-4 border-t border-border-default flex gap-2 flex-wrap">
                                    <Button onClick={() => setEditing(true)} variant="secondary" size="sm">Modifier</Button>
                                    <Button onClick={deleteIdea} variant="danger" size="sm">Supprimer</Button>
                                </div>
                            )}
                        </article>
                    )}
                </main>

                <footer className="border-t border-border-default py-6 text-center font-mono text-xs text-text-low">
                    RocketPi: Shardfall Protocol
                </footer>
            </div>
        </>
    );
}
