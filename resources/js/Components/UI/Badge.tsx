import { cva, type VariantProps } from 'class-variance-authority';
import { type HTMLAttributes, forwardRef } from 'react';

const badgeVariants = cva(
    'inline-flex items-center gap-1 px-2 py-0.5 rounded-md font-display text-xs uppercase tracking-wide font-semibold',
    {
        variants: {
            variant: {
                default: 'bg-bg-elev2 text-text-medium border border-border-default',
                shard:   'bg-shard-500/15 text-shard-400 border border-shard-500/30',
                success: 'bg-success/15 text-success border border-success/30',
                warning: 'bg-warning/15 text-warning border border-warning/30',
                danger:  'bg-danger/15 text-danger  border border-danger/30',
                info:    'bg-info/15 text-info border border-info/30',
            },
        },
        defaultVariants: { variant: 'default' },
    }
);

interface BadgeProps
    extends HTMLAttributes<HTMLSpanElement>,
        VariantProps<typeof badgeVariants> {}

const Badge = forwardRef<HTMLSpanElement, BadgeProps>(
    ({ variant, className, ...props }, ref) => (
        <span ref={ref} className={badgeVariants({ variant, className })} {...props} />
    )
);

Badge.displayName = 'Badge';
export default Badge;
