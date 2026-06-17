interface Props {
    value: number;          // 0–100
    max?: number;
    label?: string;
    variant?: 'shard' | 'success' | 'warning' | 'danger';
}

export default function Progress({ value, max = 100, label, variant = 'shard' }: Props) {
    const pct = Math.max(0, Math.min(100, (value / max) * 100));
    const fillClass =
        variant === 'success' ? 'bg-success' :
        variant === 'warning' ? 'bg-warning' :
        variant === 'danger'  ? 'bg-danger'  :
        'bg-shard-500';

    return (
        <div className="w-full">
            {label && (
                <div className="flex justify-between mb-1 font-display text-xs uppercase tracking-wide text-text-medium">
                    <span>{label}</span>
                    <span className="font-mono">{Math.round(pct)}%</span>
                </div>
            )}
            <div className="h-2 rounded-full bg-bg-elev2 overflow-hidden">
                <div className={`h-full ${fillClass} transition-all duration-normal ease-out`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}
