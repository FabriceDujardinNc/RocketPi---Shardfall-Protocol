import type { Meta, StoryObj } from '@storybook/react-vite';
import Avatar from './Avatar';

const meta = {
    title: 'UI/Avatar',
    component: Avatar,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        size: { control: 'inline-radio', options: ['sm', 'md', 'lg', 'xl'] },
        ring: { control: 'inline-radio', options: ['shard', 'gold'] },
        fallback: { control: 'text' },
        src: { control: 'text' },
    },
    args: { alt: 'Avatar joueur' },
} satisfies Meta<typeof Avatar>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Fallback:   Story = { args: { fallback: 'Vex' } };
export const RingShard:  Story = { args: { fallback: 'OP', ring: 'shard', size: 'lg' } };
export const RingGold:   Story = { args: { fallback: 'AP', ring: 'gold', size: 'lg' } };

export const AllSizes: Story = {
    render: () => (
        <div className="flex items-end gap-3">
            <Avatar size="sm" fallback="SM" />
            <Avatar size="md" fallback="MD" />
            <Avatar size="lg" fallback="LG" />
            <Avatar size="xl" fallback="XL" />
        </div>
    ),
};
