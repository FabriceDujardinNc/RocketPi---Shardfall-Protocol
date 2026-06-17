import { Head, Link, useForm } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import { type FormEventHandler, useState } from 'react';

interface Props {
    user: {
        id: number;
        name: string;
        email: string;
        display_name: string | null;
        slug: string | null;
        avatar_url: string | null;
    };
}

export default function Profile({ user }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        display_name: user.display_name ?? '',
        avatar_url:   user.avatar_url ?? '',
    });
    const [copied, setCopied] = useState(false);

    const publicUrl = user.slug
        ? `${window.location.origin}/profile/${user.slug}`
        : null;

    const copyPublicUrl = async () => {
        if (!publicUrl) return;
        await navigator.clipboard.writeText(publicUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch('/profile');
    };

    return (
        <>
            <Head title="Profil" />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-8">Profil</h1>

            <section className="rounded-lg bg-bg-elev1 border border-border-default p-6 mb-6">
                <p className="font-display text-xs uppercase tracking-wide text-text-low">Identifiants</p>
                <dl className="mt-4 grid grid-cols-2 gap-4 font-mono text-sm">
                    <dt className="text-text-low">Email</dt><dd className="text-text-high">{user.email}</dd>
                    <dt className="text-text-low">Pseudo (login)</dt><dd className="text-text-high">{user.name}</dd>
                </dl>
            </section>

            {publicUrl && (
                <section className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6 mb-6">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low">URL publique de ton profil</p>
                    <p className="font-mono text-sm text-shard-400 break-all mt-2">{publicUrl}</p>
                    <p className="font-mono text-xs text-text-low mt-2">
                        Cette URL change si tu modifies ton pseudo affiché.
                    </p>
                    <div className="flex gap-2 mt-4 flex-wrap">
                        <Button onClick={copyPublicUrl} variant="secondary" size="sm">
                            {copied ? 'Copié !' : 'Copier le lien'}
                        </Button>
                        <Link
                            href={`/profile/${user.slug}`}
                            className="inline-flex items-center justify-center h-9 px-4 rounded-md bg-bg-elev2 hover:bg-bg-elev1 border border-border-default font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high transition"
                        >
                            Voir mon profil public
                        </Link>
                    </div>
                </section>
            )}

            <form onSubmit={submit} className="rounded-lg bg-bg-elev1 border border-border-default p-6 flex flex-col gap-4">
                <p className="font-display text-xs uppercase tracking-wide text-text-low">Personnalisation</p>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">Pseudo affiché</span>
                    <input
                        type="text"
                        maxLength={50}
                        value={data.display_name}
                        onChange={(e) => setData('display_name', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.display_name && <span className="text-danger text-xs">{errors.display_name}</span>}
                </label>

                <label className="flex flex-col gap-1">
                    <span className="font-display text-xs uppercase tracking-wide text-text-medium">URL Avatar</span>
                    <input
                        type="url"
                        value={data.avatar_url}
                        onChange={(e) => setData('avatar_url', e.target.value)}
                        className="h-10 px-3 rounded-md bg-bg-elev2 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                    />
                    {errors.avatar_url && <span className="text-danger text-xs">{errors.avatar_url}</span>}
                </label>

                <Button type="submit" loading={processing}>Enregistrer</Button>
            </form>
        </>
    );
}

Profile.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
