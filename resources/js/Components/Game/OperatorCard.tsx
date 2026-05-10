import { cva, type VariantProps } from 'class-variance-authority';
import RarityBadge from './RarityBadge';
import FactionBadge from './FactionBadge';
import type { ComponentProps } from 'react';

type Rarity = NonNullable<ComponentProps<typeof RarityBadge>['rarity']>;
type Faction = NonNullable<ComponentProps<typeof FactionBadge>['faction']>;

const cardVariants = cva(
    'relative flex flex-col rounded-lg overflow-hidden border-2 transition-all duration-fast cursor-pointer hover:scale-[1.02]',
    {
        variants: {
            rarity: {
                common:    'border-rarity-common/40    bg-bg-elev1',
                rare:      'border-rarity-rare/40      bg-bg-elev1 shadow-el1',
                epic:      'border-rarity-epic/50      bg-bg-elev1 shadow-glow-epic',
                legendary: 'border-rarity-legendary/60 bg-bg-elev1 shadow-glow-legendary',
            },
        },
        defaultVariants: { rarity: 'common' },
    }
);

interface Props extends VariantProps<typeof cardVariants> {
    name: string;
    role: string;
    rarity: Rarity;
    faction: Faction;
    portraitUrl?: string;
    level?: number;
    onClick?: () => void;
}

export default function OperatorCard({ name, role, rarity, faction, portraitUrl, level, onClick }: Props) {
    return (
        <article className={cardVariants({ rarity })} onClick={onClick}>
            <div className="aspect-[3/4] bg-bg-elev2 relative">
                {portraitUrl ? (
                    <img src={portraitUrl} alt={name} className="w-full h-full object-cover" />
                ) : (
                    <div className="absolute inset-0 flex items-center justify-center font-display font-bold text-4xl uppercase text-text-low">
                        {name.slice(0, 2)}
                    </div>
                )}
                <div className="absolute top-2 left-2 right-2 flex items-start justify-between gap-2">
                    <FactionBadge faction={faction} />
                    <RarityBadge rarity={rarity} />
                </div>
                {level !== undefined && (
                    <span className="absolute bottom-2 right-2 font-mono text-xs px-2 py-0.5 rounded bg-bg-base/80 text-text-high border border-border-default">
                        Lv.{level}
                    </span>
                )}
            </div>
            <div className="p-3">
                <h3 className="font-display font-bold text-sm uppercase tracking-wide text-text-high">{name}</h3>
                <p className="font-mono text-xs text-text-medium mt-0.5">{role}</p>
            </div>
        </article>
    );
}
