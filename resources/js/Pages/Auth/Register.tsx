import { Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import Button from '@ui/Button';
import Alert from '@ui/Alert';
import SEO from '@/Components/SEO';
import { type FormEventHandler, useState } from 'react';

interface FactionOption {
    slug: 'ORBIT' | 'FERRO' | 'VEIL';
    name: string;
    tagline: string | null;
    lore: string | null;
    color_hue: number;
    accent_class: string | null;
}

interface Props {
    referralCode?: string | null;
    factions: FactionOption[];
}

const FACTION_VISUAL: Record<string, { ring: string; bg: string; text: string }> = {
    ORBIT: { ring: 'border-orbit/60', bg: 'bg-orbit/10', text: 'text-orbit' },
    FERRO: { ring: 'border-ferro/60', bg: 'bg-ferro/10', text: 'text-ferro' },
    VEIL:  { ring: 'border-veil/60',  bg: 'bg-veil/10',  text: 'text-veil' },
};

export default function Register({ referralCode, factions }: Props) {
    const [step, setStep] = useState<'faction' | 'credentials'>('faction');
    const { data, setData, post, processing, errors, reset } = useForm<{
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        faction: '' | 'ORBIT' | 'FERRO' | 'VEIL';
        referral_code: string;
    }>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        faction: '',
        referral_code: referralCode ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/register', { onFinish: () => reset('password', 'password_confirmation') });
    };

    const goToCredentials = () => {
        if (!data.faction) return;
        setStep('credentials');
    };

    return (
        <>
            <SEO
                title="Inscription"
                description="Crée ton compte RocketPi et choisis ton allégeance parmi ORBIT, FERRO ou VEIL. Recrutement gacha, classements compétitifs, battle pass — F2P intégral."
                noindex
            />
            <h1 className="font-display font-bold text-3xl uppercase tracking-wide mb-2 text-center">
                Rejoindre le Protocole
            </h1>
            <p className="text-center text-text-medium text-sm mb-8 font-mono">
                {referralCode ? `Invité par le code ${referralCode}` : 'Recrutement par Signal Shard'}
            </p>

            {/* Stepper */}
            <div className="flex items-center justify-center gap-2 mb-6 font-display text-[10px] uppercase tracking-mega">
                <span className={step === 'faction' ? 'text-shard-400' : 'text-text-low'}>1. Allégeance</span>
                <span className="text-text-low">·</span>
                <span className={step === 'credentials' ? 'text-shard-400' : 'text-text-low'}>2. Identité</span>
            </div>

            {step === 'faction' && (
                <section className="flex flex-col gap-4">
                    <Alert variant="warning">
                        Ton allégeance est <strong>définitive</strong>. Un Opérateur ne peut servir qu'une seule faction.
                        Choisis avec soin — il n'y aura pas de retour en arrière.
                    </Alert>

                    {errors.faction && <Alert variant="danger">{errors.faction}</Alert>}

                    <div className="grid sm:grid-cols-3 gap-3">
                        {factions.map((f) => {
                            const v = FACTION_VISUAL[f.slug] ?? { ring: 'border-shard-500', bg: 'bg-shard-500/10', text: 'text-shard-400' };
                            const selected = data.faction === f.slug;
                            return (
                                <button
                                    type="button"
                                    key={f.slug}
                                    onClick={() => setData('faction', f.slug)}
                                    className={
                                        'text-left rounded-lg border-2 p-4 transition-all duration-fast hover:scale-[1.01] ' +
                                        (selected
                                            ? `${v.ring} ${v.bg} shadow-el2`
                                            : 'border-border-default bg-bg-elev1 hover:border-border-strong')
                                    }
                                >
                                    <p className={`font-display font-bold text-base uppercase tracking-mega ${v.text}`}>
                                        {f.name}
                                    </p>
                                    {f.tagline && (
                                        <p className="font-mono text-[11px] text-text-medium mt-1">{f.tagline}</p>
                                    )}
                                    {f.lore && (
                                        <p className="font-body text-xs text-text-medium mt-3 leading-relaxed line-clamp-5">
                                            {f.lore}
                                        </p>
                                    )}
                                    {selected && (
                                        <p className={`mt-3 font-display text-[10px] uppercase tracking-mega ${v.text}`}>
                                            ✓ Allégeance choisie
                                        </p>
                                    )}
                                </button>
                            );
                        })}
                    </div>

                    <Button onClick={goToCredentials} disabled={!data.faction} fullWidth size="lg" variant="shard">
                        Confirmer — Étape 2
                    </Button>

                    <div className="text-center text-sm pt-2">
                        <span className="text-text-medium">Déjà un compte ? </span>
                        <Link href="/login" className="text-shard-400 hover:text-shard-300">Connexion</Link>
                    </div>
                </section>
            )}

            {step === 'credentials' && (
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <div className="rounded-md bg-bg-elev1 border border-border-default p-3 flex items-center justify-between">
                        <div>
                            <p className="font-display text-[10px] uppercase tracking-mega text-text-low">Allégeance</p>
                            <p className={`font-display font-bold uppercase tracking-wide ${FACTION_VISUAL[data.faction]?.text ?? ''}`}>
                                {data.faction}
                            </p>
                        </div>
                        <button
                            type="button"
                            onClick={() => setStep('faction')}
                            className="font-display text-xs uppercase tracking-wide text-text-medium hover:text-text-high"
                        >
                            ← Changer
                        </button>
                    </div>

                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">Pseudo</span>
                        <input
                            type="text"
                            autoComplete="username"
                            required
                            maxLength={80}
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                        {errors.name && <span className="text-danger text-xs">{errors.name}</span>}
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">Email</span>
                        <input
                            type="email"
                            autoComplete="email"
                            required
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                        {errors.email && <span className="text-danger text-xs">{errors.email}</span>}
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">Mot de passe</span>
                        <input
                            type="password"
                            autoComplete="new-password"
                            required
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                        {errors.password && <span className="text-danger text-xs">{errors.password}</span>}
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">Confirmer le mot de passe</span>
                        <input
                            type="password"
                            autoComplete="new-password"
                            required
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-display text-xs uppercase tracking-wide text-text-medium">
                            Code de parrainage <span className="text-text-low">(optionnel)</span>
                        </span>
                        <input
                            type="text"
                            value={data.referral_code}
                            onChange={(e) => setData('referral_code', e.target.value.toUpperCase())}
                            placeholder="XXX-XXXX-XXXX"
                            className="h-10 px-3 rounded-md bg-bg-elev1 border border-border-default text-text-high font-mono tracking-wider focus:outline-none focus:ring-2 focus:ring-shard-500"
                        />
                        {errors.referral_code && <span className="text-danger text-xs">{errors.referral_code}</span>}
                    </label>

                    <Button type="submit" loading={processing} fullWidth size="lg" variant="shard">
                        S'inscrire
                    </Button>

                    <div className="text-center text-sm pt-2">
                        <span className="text-text-medium">Déjà un compte ? </span>
                        <Link href="/login" className="text-shard-400 hover:text-shard-300">Connexion</Link>
                    </div>
                </form>
            )}
        </>
    );
}

Register.layout = (page: React.ReactNode) => <GuestLayout>{page}</GuestLayout>;
