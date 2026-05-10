import { AlertCircle, CheckCircle2, Info, AlertTriangle } from 'lucide-react';
import { type PropsWithChildren } from 'react';

interface Props extends PropsWithChildren {
    variant?: 'info' | 'success' | 'warning' | 'danger';
    title?: string;
}

const ICON = {
    info: Info,
    success: CheckCircle2,
    warning: AlertTriangle,
    danger: AlertCircle,
};

const STYLE = {
    info:    'bg-info/10 border-info/30 text-info',
    success: 'bg-success/10 border-success/30 text-success',
    warning: 'bg-warning/10 border-warning/30 text-warning',
    danger:  'bg-danger/10 border-danger/30 text-danger',
};

export default function Alert({ variant = 'info', title, children }: Props) {
    const Icon = ICON[variant];
    return (
        <div className={`flex gap-3 p-4 rounded-md border ${STYLE[variant]}`}>
            <Icon size={18} className="flex-shrink-0 mt-0.5" />
            <div className="flex-1 text-sm">
                {title && <p className="font-display uppercase tracking-wide text-xs font-semibold mb-1">{title}</p>}
                <div className="text-text-high">{children}</div>
            </div>
        </div>
    );
}
