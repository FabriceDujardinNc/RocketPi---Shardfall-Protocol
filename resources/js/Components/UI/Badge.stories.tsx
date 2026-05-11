import type { Meta, StoryObj } from '@storybook/react-vite';
import Badge from './Badge';

const meta = {
    title: 'UI/Badge',
    component: Badge,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['default', 'shard', 'success', 'warning', 'danger', 'info'] },
    },
    args: { children: 'Nouveau' },
} satisfies Meta<typeof Badge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = { args: { variant: 'default' } };
export const Shard:   Story = { args: { variant: 'shard', children: 'Premium' } };
export const Success: Story = { args: { variant: 'success', children: 'Réclamé' } };
export const Warning: Story = { args: { variant: 'warning', children: 'Bientôt expiré' } };
export const Danger:  Story = { args: { variant: 'danger', children: 'Banni' } };
export const Info:    Story = { args: { variant: 'info', children: 'Bêta' } };

export const AllVariants: Story = {
    render: () => (
        <div className="flex items-center gap-2 flex-wrap">
            <Badge variant="default">Default</Badge>
            <Badge variant="shard">Shard</Badge>
            <Badge variant="success">Success</Badge>
            <Badge variant="warning">Warning</Badge>
            <Badge variant="danger">Danger</Badge>
            <Badge variant="info">Info</Badge>
        </div>
    ),
};
