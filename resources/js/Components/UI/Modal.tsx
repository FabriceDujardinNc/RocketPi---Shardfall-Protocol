import * as Dialog from '@radix-ui/react-dialog';
import { X } from 'lucide-react';
import { type PropsWithChildren, type ReactNode } from 'react';

interface Props extends PropsWithChildren {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title?: string;
    description?: string;
    footer?: ReactNode;
}

export default function Modal({ open, onOpenChange, title, description, footer, children }: Props) {
    return (
        <Dialog.Root open={open} onOpenChange={onOpenChange}>
            <Dialog.Portal>
                <Dialog.Overlay className="fixed inset-0 z-overlay bg-bg-base/80 backdrop-blur-sm data-[state=open]:animate-in data-[state=open]:fade-in" />
                <Dialog.Content className="fixed left-1/2 top-1/2 z-modal w-full max-w-lg -translate-x-1/2 -translate-y-1/2 rounded-lg bg-bg-elev1 border border-border-default p-6 shadow-el3 focus:outline-none">
                    {title && (
                        <Dialog.Title className="font-display font-bold text-lg uppercase tracking-wide text-text-high">
                            {title}
                        </Dialog.Title>
                    )}
                    {description && (
                        <Dialog.Description className="font-body text-sm text-text-medium mt-1">
                            {description}
                        </Dialog.Description>
                    )}
                    <div className="mt-4">{children}</div>
                    {footer && <div className="mt-6 flex justify-end gap-2">{footer}</div>}
                    <Dialog.Close
                        className="absolute top-3 right-3 text-text-medium hover:text-text-high focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-shard-500 rounded-md"
                        aria-label="Fermer"
                    >
                        <X size={18} />
                    </Dialog.Close>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
}
