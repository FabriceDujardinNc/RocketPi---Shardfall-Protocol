import { cva, type VariantProps } from 'class-variance-authority';
import { type InputHTMLAttributes, forwardRef } from 'react';

const inputVariants = cva(
    'w-full rounded-md bg-bg-elev1 border text-text-high placeholder:text-text-low transition-colors duration-fast focus:outline-none focus:ring-2 focus:ring-shard-500 focus:border-shard-500 disabled:opacity-50 disabled:cursor-not-allowed',
    {
        variants: {
            size: {
                sm: 'h-8 px-3 text-xs',
                md: 'h-10 px-3 text-sm',
                lg: 'h-12 px-4 text-base',
            },
            invalid: {
                true:  'border-danger focus:ring-danger focus:border-danger',
                false: 'border-border-default',
            },
        },
        defaultVariants: { size: 'md', invalid: false },
    }
);

interface InputProps
    extends Omit<InputHTMLAttributes<HTMLInputElement>, 'size'>,
        VariantProps<typeof inputVariants> {}

const Input = forwardRef<HTMLInputElement, InputProps>(
    ({ size, invalid, className, ...props }, ref) => (
        <input ref={ref} className={inputVariants({ size, invalid, className })} {...props} />
    )
);

Input.displayName = 'Input';
export default Input;
