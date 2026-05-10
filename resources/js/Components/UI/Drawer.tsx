import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { type PropsWithChildren } from 'react';

interface Props extends PropsWithChildren {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    side?: 'right' | 'left';
    title?: string;
}

export default function Drawer({ open, onOpenChange, side = 'right', title, children }: Props) {
    const sideClass = side === 'right' ? 'right-0' : 'left-0';
    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-overlay bg-bg-base/80 backdrop-blur-sm" />
                <Dialog.Content
                    className={`fixed top-0 ${sideClass} z-modal h-full w-full max-w-md bg-bg-elev1 border-l border-border-default p-6 shadow-el3 focus:outline-none`}
                >
                    {title && (
                        <Dialog.Title className="font-display font-bold text-lg uppercase tracking-wide text-text-high mb-4">
                            {title}
                        </Dialog.Title>
                    )}
                    <Dialog.Close
                        className="absolute top-4 right-4 text-text-medium hover:text-text-high"
                        aria-label="Fermer"
                    >
                        <X size={18} />
                    </Dialog.Close>
                    <div>{children}</div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
