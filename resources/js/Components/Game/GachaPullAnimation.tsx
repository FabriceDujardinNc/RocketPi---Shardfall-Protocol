// GachaPullAnimation — squelette pour Phase 2.
// L'animation sera développée avec Framer Motion : reveal séquentiel,
// flash de rareté, particules pour les Légendaires.

import { motion, AnimatePresence } from 'framer-motion';
import RarityBadge from './RarityBadge';
import type { ComponentProps } from 'react';

type Rarity = NonNullable<ComponentProps<typeof RarityBadge>['rarity']>;

interface PullResult {
    operatorId: number;
    name: string;
    rarity: Rarity;
    portraitUrl?: string;
    isNew: boolean;
}

interface Props {
    results: PullResult[];
    onComplete?: () => void;
}

export default function GachaPullAnimation({ results, onComplete }: Props) {
    return (
        <AnimatePresence onExitComplete={onComplete}>
            <motion.div
                className="fixed inset-0 z-modal flex items-center justify-center bg-bg-base/95 backdrop-blur"
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
            >
                <div className="grid grid-cols-5 gap-4 max-w-5xl">
                    {results.map((r, i) => (
                        <motion.div
                            key={i}
                            initial={{ scale: 0, rotate: -10 }}
                            animate={{ scale: 1, rotate: 0 }}
                            transition={{ delay: i * 0.15, type: 'spring' }}
                            className="rounded-lg bg-bg-elev1 border border-border-default p-4 text-center"
                        >
                            <div className="aspect-[3/4] bg-bg-elev2 rounded mb-2 flex items-center justify-center font-display text-2xl text-text-low">
                                {r.portraitUrl ? <img src={r.portraitUrl} alt={r.name} className="w-full h-full object-cover rounded" /> : r.name.slice(0, 2)}
                            </div>
                            <RarityBadge rarity={r.rarity} />
                            <p className="font-display text-xs uppercase tracking-wide text-text-high mt-2">{r.name}</p>
                            {r.isNew && (
                                <p className="font-display text-[10px] uppercase tracking-mega text-shard-400 mt-1">Nouveau</p>
                            )}
                        </motion.div>
                    ))}
                </div>
            </motion.div>
        </AnimatePresence>
    );
}
