import RankBadge from './RankBadge';

interface Props {
    rank: number;
    name: string;
    score: number;
    avatarUrl?: string | null;
    isCurrentUser?: boolean;
}

export default function LeaderboardRow({ rank, name, score, avatarUrl, isCurrentUser }: Props) {
    return (
        <div
            className={
                'flex items-center gap-4 px-4 py-3 rounded-md border transition-colors duration-fast ' +
                (isCurrentUser
                    ? 'bg-shard-500/10 border-shard-500/40'
                    : 'bg-bg-elev1 border-border-default hover:bg-bg-elev2')
            }
        >
            <RankBadge rank={rank} />
            {avatarUrl ? (
                <img src={avatarUrl} alt="" className="size-10 rounded-full border-2 border-border-default" />
            ) : (
                <div className="size-10 rounded-full bg-bg-elev2 border-2 border-border-default" />
            )}
            <span className="flex-1 font-display font-semibold text-text-high">{name}</span>
            <span className="font-mono text-shard-400 tabular-nums">{score.toLocaleString('fr-FR')}</span>
        </div>
    );
}
