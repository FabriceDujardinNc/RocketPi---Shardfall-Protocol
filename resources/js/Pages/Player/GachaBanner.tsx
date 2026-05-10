import { Head, Link } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';

interface Props {
    banner: { slug: string };
}

export default function GachaBanner({ banner }: Props) {
    return (
        <>
            <Head title={`Bannière ${banner.slug}`} />
            <Link href="/gacha" className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high">
                ← Toutes les bannières
            </Link>
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-2 mb-8">
                {banner.slug}
            </h1>
            <div className="rounded-lg bg-bg-elev1 border border-border-default p-12 text-center text-text-medium">
                Animation de tirage — Phase 2.
            </div>
        </>
    );
}

GachaBanner.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
