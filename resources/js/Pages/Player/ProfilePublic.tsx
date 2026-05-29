import PlayerLayout from '@/Layouts/PlayerLayout';
import SEO from '@/Components/SEO';

interface User {
    id: number;
    slug: string | null;
    name: string;
    display_name: string | null;
    avatar_url: string | null;
    member_since: string | null;
}

interface Props {
    user: User;
}

export default function ProfilePublic({ user }: Props) {
    const displayName = user.display_name ?? user.name;
    const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';
    const profileUrl = user.slug ? `${baseUrl}/profile/${user.slug}` : baseUrl;

    const personLd = {
        '@context': 'https://schema.org',
        '@type': 'Person',
        name: displayName,
        url: profileUrl,
        ...(user.avatar_url ? { image: user.avatar_url } : {}),
        identifier: user.slug ?? String(user.id),
    };

    return (
        <>
            <SEO
                title={displayName}
                description={`Profil de ${displayName} sur RocketPi: Shardfall Protocol.`}
                type="profile"
                image={user.avatar_url ?? undefined}
                canonical={profileUrl}
                jsonLd={[personLd]}
            />

            <header className="rounded-lg bg-bg-elev1 border border-border-default p-6 md:p-8 flex flex-wrap items-center gap-6 mb-6">
                {user.avatar_url ? (
                    <img src={user.avatar_url} alt="" className="size-20 md:size-24 rounded-full border-2 border-shard-500" />
                ) : (
                    <div className="size-20 md:size-24 rounded-full bg-bg-elev2 border-2 border-border-default flex items-center justify-center font-display text-2xl text-text-low uppercase">
                        {displayName.slice(0, 2)}
                    </div>
                )}
                <div className="flex-1 min-w-0">
                    <p className="font-display text-xs uppercase tracking-mega text-shard-400">Opérateur</p>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1 truncate">
                        {displayName}
                    </h1>
                    {user.member_since && (
                        <p className="font-mono text-sm text-text-medium mt-2">
                            Membre depuis le <span className="text-text-high">{user.member_since}</span>
                        </p>
                    )}
                </div>
            </header>
        </>
    );
}

ProfilePublic.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
