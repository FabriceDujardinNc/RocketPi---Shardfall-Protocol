import { type SelectHTMLAttributes, forwardRef } from 'react';

interface Props extends SelectHTMLAttributes<HTMLSelectElement> {
    invalid?: boolean;
}

const Select = forwardRef<HTMLSelectElement, Props>(({ invalid, className, children, ...props }, ref) => (
    <select
        ref={ref}
        className={
            'w-full h-10 px-3 rounded-md bg-bg-elev1 border text-text-high transition-colors duration-fast focus:outline-none focus:ring-2 focus:ring-shard-500 focus:border-shard-500 disabled:opacity-50 ' +
            (invalid ? 'border-danger focus:ring-danger' : 'border-border-default ') +
            (className ?? '')
        }
        {...props}
    >
        {children}
    </select>
));

Select.displayName = 'Select';
export default Select;
