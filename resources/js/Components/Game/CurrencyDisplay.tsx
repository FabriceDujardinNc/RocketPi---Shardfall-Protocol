import { Gem, Coins, Cog } from 'lucide-react';

type Currency = 'premium' | 'soft' | 'fragments';

interface Props {
    currency: Currency;
    amount: number;
    label?: string;
}

const CONFIG = {
    premium:   { icon: Gem,   color: 'text-shard-400',         label: 'Cristaux' },
    soft:      { icon: Coins, color: 'text-rarity-legendary',  label: 'Crédits' },
    fragments: { icon: Cog,   color: 'text-rarity-epic',       label: 'Fragments' },
};

export default function CurrencyDisplay({ currency, amount, label }: Props) {
    const cfg = CONFIG[currency];
    const Icon = cfg.icon;

    return (
        <span className="inline-flex items-center gap-1.5 font-mono text-sm text-text-high">
            <Icon size={14} className={cfg.color} />
            <span className="tabular-nums">{amount.toLocaleString('fr-FR')}</span>
            <span className="text-text-low font-display text-xs uppercase tracking-wide">
                {label ?? cfg.label}
            </span>
        </span>
    );
}
