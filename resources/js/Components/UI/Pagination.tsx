import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
}

export default function Pagination({ links }: Props) {
    if (!links || links.length <= 3) return null;

    return (
        <nav className="flex items-center justify-center gap-1 mt-6" aria-label="Pagination">
            {links.map((link, i) => {
                const label = link.label.replace('&laquo;', '').replace('&raquo;', '').trim();
                const isPrev = i === 0;
                const isNext = i === links.length - 1;

                const baseClass =
                    'inline-flex items-center justify-center min-w-9 h-9 px-3 rounded-md font-display text-sm transition-colors duration-fast ';
                const stateClass = link.active
                    ? 'bg-shard-500 text-text-on-shard'
                    : link.url
                        ? 'bg-bg-elev1 text-text-medium border border-border-default hover:text-text-high hover:bg-bg-elev2'
                        : 'bg-bg-elev1 text-text-low border border-border-default opacity-50 cursor-not-allowed';

                if (!link.url) {
                    return <span key={i} className={baseClass + stateClass}>{label}</span>;
                }

                return (
                    <Link key={i} href={link.url} preserveScroll className={baseClass + stateClass}>
                        {isPrev ? <ChevronLeft size={14} /> : isNext ? <ChevronRight size={14} /> : label}
                    </Link>
                );
            })}
        </nav>
    );
}
