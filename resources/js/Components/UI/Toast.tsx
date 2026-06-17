// Toast — stub minimal pour Phase 1.
// À remplacer par sonner ou un store global en Phase 2.

import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-react';

type ToastVariant = 'success' | 'danger' | 'warning' | 'info';

const ICON = {
    success: CheckCircle2,
    danger:  AlertCircle,
    warning: AlertTriangle,
    info:    Info,
};

const STYLE = {
    success: 'bg-success/15 border-success/40 text-success',
    danger:  'bg-danger/15 border-danger/40 text-danger',
    warning: 'bg-warning/15 border-warning/40 text-warning',
    info:    'bg-info/15 border-info/40 text-info',
};

// Stub temporaire — phase 2 : remplacer par un vrai store (Zustand ou Context).
export const useToast = (): { push: (msg: string, v?: ToastVariant) => void } => ({
    push: (msg) => console.log('[toast]', msg),
});

export function ToastContainer() {
    // Phase 2 — afficher les toasts depuis le store.
    return (
        <div
            className="fixed bottom-4 right-4 z-toast flex flex-col gap-2 pointer-events-none"
            aria-live="polite"
        />
    );
}

interface ToastItemProps {
    message: string;
    variant?: ToastVariant;
    onClose: () => void;
}

export function ToastItem({ message, variant = 'info', onClose }: ToastItemProps) {
    const Icon = ICON[variant];
    return (
        <div
            className={
                `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-md border ${STYLE[variant]} shadow-el2 backdrop-blur`
            }
        >
            <Icon size={16} />
            <span className="font-body text-sm text-text-high">{message}</span>
            <button
                type="button"
                onClick={onClose}
                aria-label="Fermer"
                className="text-text-medium hover:text-text-high"
            >
                <X size={14} />
            </button>
        </div>
    );
}
