import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import Button from './Button';
import Drawer from './Drawer';

const meta = {
    title: 'UI/Drawer',
    component: Drawer,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        open: false,
        onOpenChange: () => {},
    },
} satisfies Meta<typeof Drawer>;

export default meta;
type Story = StoryObj<typeof meta>;

function DrawerDemo({ side, title }: { side?: 'left' | 'right'; title?: string }) {
    const [open, setOpen] = useState(false);
    return (
        <>
            <Button onClick={() => setOpen(true)} variant="ghost">Ouvrir le drawer</Button>
            <Drawer open={open} onOpenChange={setOpen} side={side} title={title}>
                <div className="flex flex-col gap-3">
                    <p className="font-body text-sm text-text-medium">
                        Le drawer slide depuis le côté. Idéal pour menus, filtres avancés, panneau de paramètres.
                    </p>
                    <Button onClick={() => setOpen(false)} variant="primary" fullWidth>Fermer</Button>
                </div>
            </Drawer>
        </>
    );
}

export const Right: Story = { render: () => <DrawerDemo side="right" title="Filtres" /> };
export const Left:  Story = { render: () => <DrawerDemo side="left" title="Menu" /> };
