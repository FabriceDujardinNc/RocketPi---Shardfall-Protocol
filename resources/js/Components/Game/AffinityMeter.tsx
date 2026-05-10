interface Props {
    level: number;       // 0–10
    xp: number;
    nextLevelXp: number;
}

export default function AffinityMeter({ level, xp, nextLevelXp }: Props) {
    const pct = Math.min(100, (xp / nextLevelXp) * 100);

    return (
        <div>
            <div className="flex justify-between mb-1 font-display text-xs uppercase tracking-wide">
                <span className="text-text-medium">Affinité</span>
                <span className="font-mono text-shard-400">Niv. {level}/10</span>
            </div>
            <div className="h-2 rounded-full bg-bg-elev2 overflow-hidden">
                <div
                    className="h-full bg-gradient-to-r from-shard-400 to-shard-600 transition-all duration-normal ease-out"
                    style={{ width: `${pct}%` }}
                />
            </div>
            <p className="text-xs text-text-low mt-1 font-mono">
                {xp} / {nextLevelXp} XP
            </p>
        </div>
    );
}
