import { Check } from 'lucide-react';

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
    const disabled = locked || !unlocked || claimed;
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={`Palier ${tier}${claimed ? ' (réclamé)' : unlocked ? ' — cliquer pour réclamer' : ' (verrouillé)'}`}
            className={
                'flex flex-col items-center gap-2 w-24 p-3 rounded-md border transition-all duration-fast ' +
                (claimed
                    ? 'bg-success/15 border-success/60 cursor-default'
                    : unlocked
                        ? 'bg-shard-500/10 border-shard-500/60 hover:bg-shard-500/20 hover:border-shard-400 cursor-pointer shadow-glow-shard'
                        : 'bg-bg-elev1 border-border-default opacity-50 cursor-not-allowed')
            }
        >
            <span className="font-mono text-xs text-text-low">Palier {tier}</span>
            <div
                className={
                    'size-12 rounded flex items-center justify-center font-display text-xs border ' +
                    (claimed
                        ? 'bg-success/20 border-success/70 text-success'
                        : 'bg-bg-elev2 border-border-default text-text-medium')
                }
            >
                {claimed ? <Check className="size-5" /> : '?'}
            </div>
            <span className="font-display text-xs uppercase tracking-wide text-text-high text-center leading-tight">
                {label}
            </span>
            {(free || premium) && (
                <span
                    className={
                        'font-display text-[10px] uppercase tracking-mega ' +
                        (claimed
                            ? 'text-success'
                            : premium ? 'text-rarity-legendary' : 'text-text-low')
                    }
                >
                    {claimed ? 'Réclamé' : premium ? 'Premium' : 'Gratuit'}
                </span>
            )}
        </button>
    );
}
