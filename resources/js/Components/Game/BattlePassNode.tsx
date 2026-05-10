interface Props {
    tier: number;
    label: string;
    free?: boolean;
    premium?: boolean;
    unlocked?: boolean;
    claimed?: boolean;
    locked?: boolean;
    onClick?: () => void;
}

export default function BattlePassNode({ tier, label, free, premium, unlocked, claimed, locked, onClick }: Props) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={locked || !unlocked}
            className={
                'flex flex-col items-center gap-2 w-24 p-3 rounded-md border transition-all duration-fast ' +
                (claimed
                    ? 'bg-success/10 border-success/40'
                    : unlocked
                        ? 'bg-bg-elev1 border-shard-500/50 hover:bg-bg-elev2 cursor-pointer'
                        : 'bg-bg-elev1 border-border-default opacity-50 cursor-not-allowed')
            }
        >
            <span className="font-mono text-xs text-text-low">Palier {tier}</span>
            <div className="size-12 rounded bg-bg-elev2 border border-border-default flex items-center justify-center font-display text-xs text-text-medium">
                ?
            </div>
            <span className="font-display text-xs uppercase tracking-wide text-text-high text-center leading-tight">
                {label}
            </span>
            {(free || premium) && (
                <span
                    className={
                        'font-display text-[10px] uppercase tracking-mega ' +
                        (premium ? 'text-rarity-legendary' : 'text-text-low')
                    }
                >
                    {premium ? 'Premium' : 'Gratuit'}
                </span>
            )}
        </button>
    );
}
