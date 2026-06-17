import { type InputHTMLAttributes, forwardRef } from 'react';

interface Props extends Omit<InputHTMLAttributes<HTMLInputElement>, 'type'> {
    label?: string;
}

const Radio = forwardRef<HTMLInputElement, Props>(({ label, className, ...props }, ref) => (
    <label className="inline-flex items-center gap-2 text-sm text-text-medium cursor-pointer select-none">
        <input
            ref={ref}
            type="radio"
            className={`size-4 accent-shard-500 focus:ring-2 focus:ring-shard-500 ${className ?? ''}`}
            {...props}
        />
        {label && <span>{label}</span>}
    </label>
));

Radio.displayName = 'Radio';
export default Radio;
