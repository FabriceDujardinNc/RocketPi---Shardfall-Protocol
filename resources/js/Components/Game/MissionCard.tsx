import Progress from '@ui/Progress';
import Button from '@ui/Button';

interface Props {
    title: string;
    description: string;
    progress: number;       // 0–total
    total: number;
    rewardLabel: string;
    completed?: boolean;
    claimed?: boolean;
    onClaim?: () => void;
}

export default function MissionCard({ title, description, progress, total, rewardLabel, completed, claimed, onClaim }: Props) {
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <header className="flex items-start justify-between gap-3 mb-2">
                <div>
                    <h3 className="font-display font-semibold text-sm uppercase tracking-wide text-text-high">{title}</h3>
                    <p className="font-body text-xs text-text-medium mt-1">{description}</p>
                </div>
                <span className="font-display text-xs uppercase tracking-wide text-shard-400 whitespace-nowrap">
                    {rewardLabel}
                </span>
            </header>

            <Progress value={progress} max={total} variant={completed ? 'success' : 'shard'} />

            <div className="mt-3 flex items-center justify-between">
                <span className="font-mono text-xs text-text-low">
                    {Math.min(progress, total)} / {total}
                </span>
                {completed && !claimed && onClaim && (
                    <Button onClick={onClaim} size="sm" variant="shard">Réclamer</Button>
                )}
                {claimed && <span className="font-display text-xs uppercase tracking-wide text-success">Réclamée</span>}
            </div>
        </div>
    );
}
