interface Props {
    name: string;
    description?: string;
    imageUrl?: string;
    endsAt?: string | null;
    onClick?: () => void;
    featured?: boolean;
}

export default function BannerCard({ name, description, imageUrl, endsAt, onClick, featured }: Props) {
    return (
        <article
            onClick={onClick}
            className={
                'relative flex flex-col rounded-lg overflow-hidden border-2 cursor-pointer transition-all duration-fast hover:scale-[1.01] ' +
                (featured ? 'border-shard-500/60 shadow-glow-shard' : 'border-border-default')
            }
        >
            <div className="aspect-[16/7] bg-bg-elev2 relative">
                {imageUrl ? (
                    <img src={imageUrl} alt={name} loading="lazy" className="w-full h-full object-cover" />
                ) : (
                    <div className="absolute inset-0 flex items-center justify-center font-display font-bold uppercase text-text-low">
                        {name}
                    </div>
                )}
                {featured && (
                    <span className="absolute top-3 left-3 font-display text-xs uppercase tracking-mega text-shard-400 bg-bg-base/80 px-2 py-1 rounded">
                        Rate-up
                    </span>
                )}
            </div>
            <div className="p-4 bg-bg-elev1">
                <h3 className="font-display font-bold text-base uppercase tracking-wide text-text-high">{name}</h3>
                {description && <p className="font-body text-sm text-text-medium mt-1">{description}</p>}
                {endsAt && (
                    <p className="font-mono text-xs text-text-low mt-2">
                        Termine : {endsAt}
                    </p>
                )}
            </div>
        </article>
    );
}
