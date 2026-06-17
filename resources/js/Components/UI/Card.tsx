import { cva, type VariantProps } from 'class-variance-authority';
import { type HTMLAttributes, forwardRef } from 'react';

const cardVariants = cva(
    'rounded-lg border transition-colors duration-fast',
    {
        variants: {
            variant: {
                default: 'bg-bg-elev1 border-border-default',
                elevated:'bg-bg-elev2 border-border-default shadow-el2',
                shard:   'bg-bg-elev1 border-shard-500/40 shadow-glow-shard',
            },
            padding: {
                none: '',
                sm: 'p-4',
                md: 'p-6',
                lg: 'p-8',
            },
            hoverable: {
                true: 'hover:border-shard-500/60 cursor-pointer',
            },
        },
        defaultVariants: { variant: 'default', padding: 'md' },
    }
);

interface CardProps
    extends HTMLAttributes<HTMLDivElement>,
        VariantProps<typeof cardVariants> {}

const Card = forwardRef<HTMLDivElement, CardProps>(
    ({ variant, padding, hoverable, className, ...props }, ref) => (
        <div ref={ref} className={cardVariants({ variant, padding, hoverable, className })} {...props} />
    )
);

Card.displayName = 'Card';
export default Card;
