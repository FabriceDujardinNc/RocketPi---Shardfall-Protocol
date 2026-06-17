import { type HTMLAttributes, forwardRef } from 'react';

type Props = HTMLAttributes<HTMLDivElement>;

const Skeleton = forwardRef<HTMLDivElement, Props>(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={`animate-pulse bg-bg-elev2 rounded-md ${className ?? ''}`}
        {...props}
    />
));

Skeleton.displayName = 'Skeleton';
export default Skeleton;
