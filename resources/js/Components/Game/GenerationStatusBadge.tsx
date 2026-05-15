import Badge from '@ui/Badge';
import { Clock, Loader2, Sparkles, CheckCircle2, AlertTriangle } from 'lucide-react';
import { type ComponentProps } from 'react';

export type GenerationStatus = 'pending' | 'queued' | 'generating' | 'ready' | 'failed';

type BadgeVariant = NonNullable<ComponentProps<typeof Badge>['variant']>;

const config: Record<GenerationStatus, { variant: BadgeVariant; label: string; Icon: typeof Clock; spin?: boolean }> = {
    pending:    { variant: 'default', label: 'En attente',  Icon: Clock },
    queued:     { variant: 'info',    label: 'En file',     Icon: Sparkles },
    generating: { variant: 'warning', label: 'Génération',  Icon: Loader2, spin: true },
    ready:      { variant: 'success', label: 'Prêt',        Icon: CheckCircle2 },
    failed:     { variant: 'danger',  label: 'Échec',       Icon: AlertTriangle },
};

export default function GenerationStatusBadge({ status }: { status: GenerationStatus }) {
    const { variant, label, Icon, spin } = config[status];
    return (
        <Badge variant={variant}>
            <Icon className={`h-3 w-3 ${spin ? 'animate-spin' : ''}`} aria-hidden />
            {label}
        </Badge>
    );
}
