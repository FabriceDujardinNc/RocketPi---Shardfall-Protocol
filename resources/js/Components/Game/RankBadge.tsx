interface Props {
    rank: number;
}

export default function RankBadge({ rank }: Props) {
    const colorClass =
        rank === 1 ? 'bg-rarity-legendary text-bg-base shadow-glow-legendary' :
        rank === 2 ? 'bg-rarity-epic text-bg-base' :
        rank === 3 ? 'bg-rarity-rare text-bg-base' :
        'bg-bg-elev2 text-text-medium border border-border-default';

    return (
        <span
            className={`inline-flex items-center justify-center min-w-9 h-9 px-2 rounded-md font-display font-bold text-sm tabular-nums ${colorClass}`}
        >
            #{rank}
        </span>
    );
}
