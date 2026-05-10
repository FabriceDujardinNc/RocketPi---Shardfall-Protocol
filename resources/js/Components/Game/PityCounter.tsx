interface Props {
    current: number;
    threshold: number;        // 80 légendaire, 10 épique, etc.
    rarity: 'epic' | 'legendary';
    softPity?: number;        // pour légendaire : 60
    label?: string;
}

export default function PityCounter({ current, threshold, rarity, softPity, label }: Props) {
    const pct = Math.min(100, (current / threshold) * 100);
    const isSoftPity = softPity !== undefined && current >= softPity;
    const remaining = Math.max(0, threshold - current);

    const fillClass =
        rarity === 'legendary'
            ? (isSoftPity ? 'bg-rarity-legendary' : 'bg-shard-500')
            : 'bg-rarity-epic';

    return (
        <div>
            <div className="flex justify-between mb-1 font-display text-xs uppercase tracking-wide">
                <span className="text-text-medium">{label ?? `Pity ${rarity === 'legendary' ? 'Légendaire' : 'Épique'}`}</span>
                <span className="font-mono text-text-high">
                    {current} / {threshold}
                </span>
            </div>
            <div className="h-2 rounded-full bg-bg-elev2 overflow-hidden">
                <div className={`h-full transition-all duration-normal ease-out ${fillClass}`} style={{ width: `${pct}%` }} />
            </div>
            {isSoftPity && rarity === 'legendary' && (
                <p className="text-xs text-rarity-legendary mt-1 font-display uppercase tracking-wide">
                    Soft pity actif — chances boostées
                </p>
            )}
            {remaining > 0 && (
                <p className="text-xs text-text-low mt-1 font-mono">{remaining} tirage{remaining > 1 ? 's' : ''} avant garantie</p>
            )}
        </div>
    );
}
