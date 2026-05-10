interface Props {
    checked: boolean;
    onChange: (checked: boolean) => void;
    label?: string;
    disabled?: boolean;
}

export default function Toggle({ checked, onChange, label, disabled }: Props) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={checked}
            disabled={disabled}
            onClick={() => onChange(!checked)}
            className={
                'inline-flex items-center gap-3 disabled:opacity-50 disabled:cursor-not-allowed group ' +
                (disabled ? '' : 'cursor-pointer')
            }
        >
            <span
                className={
                    'relative inline-block w-10 h-6 rounded-full transition-colors duration-fast ' +
                    (checked ? 'bg-shard-500' : 'bg-bg-elev2 border border-border-default')
                }
            >
                <span
                    className={
                        'absolute top-0.5 size-5 rounded-full bg-text-high transition-transform duration-fast ' +
                        (checked ? 'translate-x-4' : 'translate-x-0.5')
                    }
                />
            </span>
            {label && (
                <span className="text-sm font-display uppercase tracking-wide text-text-medium group-hover:text-text-high">
                    {label}
                </span>
            )}
        </button>
    );
}
