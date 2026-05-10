import { cva, type VariantProps } from 'class-variance-authority';

const factionVariants = cva(
    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-display text-xs uppercase tracking-wide font-semibold',
    {
        variants: {
            faction: {
                orbit: 'bg-orbit/15 text-orbit border border-orbit/40',
                ferro: 'bg-ferro/15 text-ferro border border-ferro/40',
                veil:  'bg-veil/15  text-veil  border border-veil/40',
            },
        },
        defaultVariants: { faction: 'orbit' },
    }
);

interface Props extends VariantProps<typeof factionVariants> {
    label?: string;
}

const LABELS = { orbit: 'ORBIT', ferro: 'FERRO', veil: 'VEIL' };

export default function FactionBadge({ faction = 'orbit', label }: Props) {
    return <span className={factionVariants({ faction })}>{label ?? LABELS[faction ?? 'orbit']}</span>;
}
