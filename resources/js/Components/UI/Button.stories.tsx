import type { Meta, StoryObj } from '@storybook/react-vite';
import { Rocket } from 'lucide-react';
import Button from './Button';

const meta = {
    title: 'UI/Button',
    component: Button,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        variant:   { control: 'inline-radio', options: ['primary', 'secondary', 'ghost', 'danger', 'shard'] },
        size:      { control: 'inline-radio', options: ['sm', 'md', 'lg'] },
        fullWidth: { control: 'boolean' },
        loading:   { control: 'boolean' },
        disabled:  { control: 'boolean' },
    },
    args: { children: 'Tirer x10' },
} satisfies Meta<typeof Button>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Primary:   Story = { args: { variant: 'primary' } };
export const Secondary: Story = { args: { variant: 'secondary' } };
export const Ghost:     Story = { args: { variant: 'ghost' } };
export const Danger:    Story = { args: { variant: 'danger', children: 'Bannir' } };
export const Shard:     Story = { args: { variant: 'shard', children: 'Recruter' } };

export const WithIcon: Story = {
    args: { variant: 'primary', icon: <Rocket size={14} />, children: 'Lancer la mission' },
};

export const Loading: Story = { args: { loading: true } };

export const AllSizes: Story = {
    render: (args) => (
        <div className="flex items-center gap-3">
            <Button {...args} size="sm">Small</Button>
            <Button {...args} size="md">Medium</Button>
            <Button {...args} size="lg">Large</Button>
        </div>
    ),
};
