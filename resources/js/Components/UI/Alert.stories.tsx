import type { Meta, StoryObj } from '@storybook/react-vite';
import Alert from './Alert';

const meta = {
    title: 'UI/Alert',
    component: Alert,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['info', 'success', 'warning', 'danger'] },
        title:   { control: 'text' },
    },
    args: { children: 'Le message d\'alerte à afficher au joueur.' },
} satisfies Meta<typeof Alert>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Info:    Story = { args: { variant: 'info', title: 'Information' } };
export const Success: Story = { args: { variant: 'success', title: 'Succès', children: 'Tirage légendaire validé.' } };
export const Warning: Story = { args: { variant: 'warning', title: 'Attention', children: 'Plus que 3 tirages avant le pity.' } };
export const Danger:  Story = { args: { variant: 'danger', title: 'Erreur', children: 'Solde insuffisant pour cette action.' } };

export const WithoutTitle: Story = {
    args: { variant: 'info', children: 'Maintenance prévue dimanche 02h-04h UTC.' },
};

export const AllVariants: Story = {
    render: () => (
        <div className="flex flex-col gap-3 max-w-xl">
            <Alert variant="info" title="Info">Maintenance ce dimanche.</Alert>
            <Alert variant="success" title="Succès">Récompense réclamée.</Alert>
            <Alert variant="warning" title="Attention">Saison hebdo s'achève dans 2h.</Alert>
            <Alert variant="danger" title="Erreur">Le serveur Redis ne répond pas.</Alert>
        </div>
    ),
};
