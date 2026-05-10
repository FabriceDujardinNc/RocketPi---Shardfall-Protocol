import { cva, type VariantProps } from 'class-variance-authority';
import { type ImgHTMLAttributes, forwardRef } from 'react';

const avatarVariants = cva(
    'rounded-full bg-bg-elev2 border-2 border-border-default object-cover',
    {
        variants: {
            size: {
                sm: 'size-8',
                md: 'size-10',
                lg: 'size-14',
                xl: 'size-20',
            },
            ring: {
                shard: 'border-shard-500',
                gold:  'border-rarity-legendary',
            },
        },
        defaultVariants: { size: 'md' },
    }
);

interface AvatarProps
    extends ImgHTMLAttributes<HTMLImageElement>,
        VariantProps<typeof avatarVariants> {
    fallback?: string;
}

const Avatar = forwardRef<HTMLImageElement, AvatarProps>(
    ({ size, ring, src, alt = '', fallback, className, ...props }, ref) => {
        if (!src && fallback) {
            return (
                <span
                    className={
                        avatarVariants({ size, ring, className }) +
                        ' inline-flex items-center justify-center font-display font-semibold text-text-medium'
                    }
                >
                    {fallback.slice(0, 2).toUpperCase()}
                </span>
            );
        }
        return <img ref={ref} src={src} alt={alt} className={avatarVariants({ size, ring, className })} {...props} />;
    }
);

Avatar.displayName = 'Avatar';
export default Avatar;
