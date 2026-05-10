import { cva, type VariantProps } from 'class-variance-authority';

const rarityVariants = cva(
    'inline-flex items-center px-2 py-0.5 rounded-md font-display text-xs uppercase tracking-wide font-semibold',
    {
        variants: {
            rarity: {
                common:    'bg-rarity-common/15    text-rarity-common    border border-rarity-common/40',
                rare:      'bg-rarity-rare/15      text-rarity-rare      border border-rarity-rare/40',
                epic:      'bg-rarity-epic/15      text-rarity-epic      border border-rarity-epic/40',
                legendary: 'bg-rarity-legendary/20 text-rarity-legendary border border-rarity-legendary/50 shadow-glow-legendary',
            },
        },
        defaultVariants: { rarity: 'common' },
    }
);

interface Props extends VariantProps<typeof rarityVariants> {
    label?: string;
}

const LABELS = {
    common: 'Commun',
    rare: 'Rare',
    epic: 'Épique',
    legendary: 'Légendaire',
};

export default function RarityBadge({ rarity = 'common', label }: Props) {
    return <span className={rarityVariants({ rarity })}>{label ?? LABELS[rarity ?? 'common']}</span>;
}
