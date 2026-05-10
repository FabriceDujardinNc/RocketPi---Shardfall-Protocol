import { Head } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface Props {
    user: {
        id: number;
        name: string;
        display_name: string | null;
        avatar_url: string | null;
        account_level: number;
    };
}

export default function ProfilePublic({ user }: Props) {
    return (
        <>
            <Head title={user.display_name ?? user.name} />
            <header className="rounded-lg bg-bg-elev1 border border-border-default p-8 flex items-center gap-6">
                {user.avatar_url ? (
                    <img src={user.avatar_url} alt="" className="size-20 rounded-full border-2 border-shard-500" />
                ) : (
                    <div className="size-20 rounded-full bg-bg-elev2 border-2 border-border-default" />
                )}
                <div>
                    <h1 className="font-display font-bold text-3xl uppercase tracking-wide">
                        {user.display_name ?? user.name}
                    </h1>
                    <p className="font-mono text-sm text-text-medium mt-1">Niveau {user.account_level}</p>
                </div>
            </header>
        </>
    );
}

ProfilePublic.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
