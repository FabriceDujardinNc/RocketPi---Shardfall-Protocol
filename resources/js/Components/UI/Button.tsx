import { cva, type VariantProps } from 'class-variance-authority';
import { Loader2 } from 'lucide-react';
import { type ButtonHTMLAttributes, forwardRef } from 'react';

const buttonVariants = cva(
    // Base classes — jamais de valeurs hardcodées
    'inline-flex items-center justify-center gap-2 font-display font-semibold tracking-wide uppercase rounded-md transition-all duration-fast ease-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-shard-500 focus-visible:ring-offset-2 focus-visible:ring-offset-bg-base disabled:pointer-events-none disabled:opacity-50 active:translate-y-px',
    {
        variants: {
            variant: {
                primary:   'bg-shard-500 text-text-on-shard border border-shard-400 shadow-glow-shard shadow-el1 hover:bg-shard-600',
                secondary: 'bg-bg-elev2 text-text-high border border-border-default shadow-el1 hover:bg-bg-elev3',
                ghost:     'text-text-medium hover:text-text-high hover:bg-bg-elev1',
                danger:    'bg-danger text-bg-base border border-danger shadow-el1 hover:brightness-110',
                shard:     'bg-gradient-to-b from-shard-400 to-shard-600 text-text-on-shard border border-shard-300 shadow-glow-shard shadow-el2 hover:brightness-110',
            },
            size: {
                sm: 'h-7  px-3 text-xs  gap-1.5',
                md: 'h-9  px-4 text-sm  gap-2',
                lg: 'h-12 px-6 text-base gap-2.5',
            },
            fullWidth: {
                true: 'w-full',
            },
        },
        defaultVariants: {
            variant: 'primary',
            size: 'md',
        },
    }
);

interface ButtonProps
    extends ButtonHTMLAttributes<HTMLButtonElement>,
        VariantProps<typeof buttonVariants> {
    loading?: boolean;
    icon?: React.ReactNode;
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
    ({ variant, size, fullWidth, loading, icon, children, disabled, className, ...props }, ref) => (
        <button
            ref={ref}
            disabled={disabled || loading}
            className={buttonVariants({ variant, size, fullWidth, className })}
            {...props}
        >
            {loading ? <Loader2 className="animate-spin" size={size === 'sm' ? 12 : 14} /> : icon}
            {children}
        </button>
    )
);

Button.displayName = 'Button';
export default Button;
