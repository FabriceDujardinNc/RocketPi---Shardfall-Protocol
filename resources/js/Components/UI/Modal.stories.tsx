import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import Button from './Button';
import Modal from './Modal';

const meta = {
    title: 'UI/Modal',
    component: Modal,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        open: false,
        onOpenChange: () => {},
    },
} satisfies Meta<typeof Modal>;

export default meta;
type Story = StoryObj<typeof meta>;

function ModalDemo({ title, description, withFooter }: { title?: string; description?: string; withFooter?: boolean }) {
    const [open, setOpen] = useState(false);
    return (
        <>
            <Button onClick={() => setOpen(true)} variant="primary">Ouvrir la modale</Button>
            <Modal
                open={open}
                onOpenChange={setOpen}
                title={title}
                description={description}
                footer={
                    withFooter ? (
                        <>
                            <Button variant="ghost" onClick={() => setOpen(false)}>Annuler</Button>
                            <Button variant="danger" onClick={() => setOpen(false)}>Confirmer</Button>
                        </>
                    ) : undefined
                }
            >
                <p className="font-body text-sm text-text-medium">
                    Le contenu de la modale s'affiche ici. Idéal pour confirmations, formulaires courts, détails.
                </p>
            </Modal>
        </>
    );
}

export const Simple:  Story = { render: () => <ModalDemo title="Confirmer le tirage" description="10 ✦ seront débités de ton solde." /> };
export const WithFooter: Story = { render: () => <ModalDemo title="Bannir le joueur" description="Cette action est réversible." withFooter /> };
export const NoTitle: Story = { render: () => <ModalDemo /> };
