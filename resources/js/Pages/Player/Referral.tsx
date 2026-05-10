import { Head, router, usePage } from '@inertiajs/react';
import PlayerLayout from '@/Layouts/PlayerLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import { useState } from 'react';

interface RewardLine { type: string; amount: number }

interface PendingReward {
    id: number;
    trigger: string;
    reward_type: string;
    rewards: RewardLine[];
    created_at: string;
    referee: { id: number; name: string; display_name: string | null } | null;
}

interface RefereeSummary {
    id: number;
    status: 'pending' | 'validated' | 'rewarded' | 'flagged';
    created_at: string;
    validated_at: string | null;
    referee: { id: number; name: string; level: number; verified: boolean };
}

interface Props {
    referralCode: string;
    referralLink: string;
    referredCount: number;
    validatedCount: number;
    pendingRewards: PendingReward[];
    referrals: RefereeSummary[];
}

interface PageProps {
    flash?: { status?: string };
    errors: Record<string, string>;
    [key: string]: unknown;
}

const TRIGGER_LABEL: Record<string, string> = {
    referee_email_verified: 'Pack starter (filleul)',
    referee_level_5:        'Filleul niveau 5',
    referee_level_15:       'Filleul niveau 15',
    referee_level_30:       'Filleul niveau 30',
    referee_first_purchase: 'Premier achat filleul',
};

const STATUS_LABEL: Record<RefereeSummary['status'], { label: string; color: string }> = {
    pending:   { label: 'En attente',   color: 'text-warning' },
    validated: { label: 'Validé',       color: 'text-shard-400' },
    rewarded:  { label: 'Récompensé',   color: 'text-success' },
    flagged:   { label: 'Suspect',      color: 'text-danger' },
};

export default function Referral({ referralCode, referralLink, referredCount, validatedCount, pendingRewards, referrals }: Props) {
    const { props } = usePage<PageProps>();
    const [copied, setCopied] = useState(false);
    const [codeCopied, setCodeCopied] = useState(false);

    const copy = async () => {
        await navigator.clipboard.writeText(referralLink);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const copyCode = async () => {
        await navigator.clipboard.writeText(referralCode);
        setCodeCopied(true);
        setTimeout(() => setCodeCopied(false), 2000);
    };

    const shareText = `Rejoins-moi sur RocketPi — Shardfall Protocol avec mon code ${referralCode} et débloque tes récompenses de filleul.`;

    const share = async () => {
        // Web Share API : ouvre le menu de partage natif (mobile + Edge desktop).
        // Fallback : copie le lien dans le presse-papier.
        if (typeof navigator.share === 'function') {
            try {
                await navigator.share({
                    title: 'RocketPi — Shardfall Protocol',
                    text:  shareText,
                    url:   referralLink,
                });
                return;
            } catch (err) {
                // L'utilisateur a annulé le partage — on n'enchaîne pas sur le copy.
                if (err instanceof DOMException && err.name === 'AbortError') return;
            }
        }
        await copy();
    };

    const encodedText = encodeURIComponent(shareText);
    const encodedUrl  = encodeURIComponent(referralLink);
    const socialLinks = [
        { label: 'WhatsApp', href: `https://wa.me/?text=${encodedText}%20${encodedUrl}` },
        { label: 'Telegram', href: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}` },
        { label: 'X / Twitter', href: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}` },
        { label: 'Email', href: `mailto:?subject=${encodeURIComponent('RocketPi — viens jouer')}&body=${encodedText}%20${encodedUrl}` },
    ];

    const claim = (id: number) => router.post(`/referral/${id}/claim`, {}, { preserveScroll: true });

    return (
        <>
            <Head title="Parrainage" />

            <header className="mb-8">
                <p className="font-display text-xs uppercase tracking-mega text-shard-400">Signal Shard</p>
                <h1 className="font-display font-bold text-3xl uppercase tracking-wide mt-1">Parrainage</h1>
            </header>

            {props.flash?.status && <div className="mb-4"><Alert variant="success">{props.flash.status}</Alert></div>}
            {props.errors.referral && <div className="mb-4"><Alert variant="danger">{props.errors.referral}</Alert></div>}

            <section className="rounded-lg bg-bg-elev1 border border-shard-500/30 p-6 mb-6">
                <p className="font-display text-xs uppercase tracking-wide text-text-low">Ton code</p>
                <div className="flex items-center gap-3 flex-wrap mt-2">
                    <p className="font-mono text-2xl text-shard-400 tracking-widest">{referralCode}</p>
                    <button
                        type="button"
                        onClick={copyCode}
                        className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-shard-400 transition"
                    >
                        {codeCopied ? 'Code copié !' : 'Copier le code'}
                    </button>
                </div>
                <p className="font-mono text-sm text-text-medium mt-4 break-all">{referralLink}</p>

                <div className="mt-4 flex gap-2 flex-wrap">
                    <Button onClick={share} variant="shard" size="sm">
                        Partager
                    </Button>
                    <Button onClick={copy} variant="secondary" size="sm">
                        {copied ? 'Lien copié !' : 'Copier le lien'}
                    </Button>
                </div>

                <div className="mt-4 pt-4 border-t border-border-default/50">
                    <p className="font-display text-xs uppercase tracking-wide text-text-low mb-2">Partager via</p>
                    <div className="flex gap-2 flex-wrap">
                        {socialLinks.map(s => (
                            <a
                                key={s.label}
                                href={s.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex items-center h-8 px-3 rounded-md bg-bg-elev2 hover:bg-bg-elev1 border border-border-default font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high transition"
                            >
                                {s.label}
                            </a>
                        ))}
                    </div>
                </div>
            </section>

            <section className="grid md:grid-cols-3 gap-4 mb-6">
                <Stat label="Filleuls inscrits" value={referredCount} />
                <Stat label="Filleuls validés" value={validatedCount} accent="shard" />
                <Stat label="Récompenses en attente" value={pendingRewards.length} accent="warning" />
            </section>

            {pendingRewards.length > 0 && (
                <section className="mb-6">
                    <h2 className="font-display text-sm uppercase tracking-wide text-shard-400 mb-3">
                        Récompenses à réclamer
                    </h2>
                    <ul className="flex flex-col gap-3">
                        {pendingRewards.map(r => (
                            <li key={r.id} className="rounded-lg bg-bg-elev1 border border-warning/30 p-4 flex items-center justify-between flex-wrap gap-3">
                                <div>
                                    <p className="font-display text-sm uppercase tracking-wide text-text-high">
                                        {TRIGGER_LABEL[r.trigger] ?? r.trigger}
                                    </p>
                                    {r.referee && (
                                        <p className="font-mono text-xs text-text-low mt-0.5">
                                            via {r.referee.display_name ?? r.referee.name}
                                        </p>
                                    )}
                                    <ul className="mt-2 flex gap-3 flex-wrap text-xs font-mono">
                                        {r.rewards.map((rw, i) => (
                                            <li key={i} className="text-shard-400">
                                                <span className="text-text-high">{rw.amount}</span> {rw.type}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                                <Button variant="shard" onClick={() => claim(r.id)}>Réclamer</Button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <section>
                <h2 className="font-display text-sm uppercase tracking-wide text-text-medium mb-3">
                    Tes filleuls <span className="font-mono text-xs text-text-low">({referrals.length})</span>
                </h2>
                {referrals.length === 0 ? (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default p-6 text-center text-text-medium font-mono text-sm">
                        Pas encore de filleul. Partage ton lien ci-dessus.
                    </div>
                ) : (
                    <div className="rounded-lg bg-bg-elev1 border border-border-default overflow-hidden">
                        <table className="w-full text-sm">
                            <thead className="bg-bg-elev2 font-display text-xs uppercase tracking-wide text-text-low">
                                <tr>
                                    <th className="px-3 py-2 text-left">Filleul</th>
                                    <th className="px-3 py-2 text-left">Niveau</th>
                                    <th className="px-3 py-2 text-left">Email vérifié</th>
                                    <th className="px-3 py-2 text-left">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                {referrals.map(r => {
                                    const st = STATUS_LABEL[r.status];
                                    return (
                                        <tr key={r.id} className="border-t border-border-default hover:bg-bg-elev2/50">
                                            <td className="px-3 py-2 text-text-high">{r.referee.name}</td>
                                            <td className="px-3 py-2 font-mono text-text-medium">{r.referee.level}</td>
                                            <td className="px-3 py-2">
                                                {r.referee.verified
                                                    ? <span className="text-success font-display text-xs uppercase">Oui</span>
                                                    : <span className="text-text-low font-display text-xs uppercase">Non</span>}
                                            </td>
                                            <td className="px-3 py-2">
                                                <span className={`font-display text-xs uppercase tracking-wide ${st.color}`}>
                                                    {st.label}
                                                </span>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </section>
        </>
    );
}

function Stat({ label, value, accent = 'high' }: { label: string; value: number; accent?: 'high' | 'shard' | 'warning' }) {
    const color = accent === 'shard' ? 'text-shard-400' : accent === 'warning' ? 'text-warning' : 'text-text-high';
    return (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4">
            <p className="font-display text-xs uppercase tracking-wide text-text-low">{label}</p>
            <p className={`font-display text-3xl font-bold mt-2 ${color}`}>{value}</p>
        </div>
    );
}

Referral.layout = (page: React.ReactNode) => <PlayerLayout>{page}</PlayerLayout>;
