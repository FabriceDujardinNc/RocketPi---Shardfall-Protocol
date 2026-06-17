import type { Meta, StoryObj } from '@storybook/react-vite';
import { ToastItem } from './Toast';

const meta = {
    title: 'UI/Toast',
    component: ToastItem,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['info', 'success', 'warning', 'danger'] },
        message: { control: 'text' },
    },
    args: {
        message: 'Tirage légendaire validé.',
        variant: 'success',
        onClose: () => {},
    },
} satisfies Meta<typeof ToastItem>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Success: Story = { args: { variant: 'success', message: 'Récompense réclamée.' } };
export const Info:    Story = { args: { variant: 'info',    message: 'Mise à jour disponible.' } };
export const Warning: Story = { args: { variant: 'warning', message: 'Saison se termine dans 2h.' } };
export const Danger:  Story = { args: { variant: 'danger',  message: 'Connexion perdue avec le serveur.' } };

export const AllVariants: Story = {
    render: () => (
        <div className="flex flex-col gap-2">
            <ToastItem variant="info"    message="Mise à jour disponible." onClose={() => {}} />
            <ToastItem variant="success" message="Mission réclamée."        onClose={() => {}} />
            <ToastItem variant="warning" message="Bientôt l'expiration."    onClose={() => {}} />
            <ToastItem variant="danger"  message="Erreur réseau."           onClose={() => {}} />
        </div>
    ),
};
