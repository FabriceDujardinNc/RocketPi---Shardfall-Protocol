import type { Meta, StoryObj } from '@storybook/react-vite';
import RarityBadge from './RarityBadge';

const meta = {
    title: 'Game/RarityBadge',
    component: RarityBadge,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        rarity: { control: 'inline-radio', options: ['common', 'rare', 'epic', 'legendary'] },
        label:  { control: 'text' },
    },
} satisfies Meta<typeof RarityBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Common:    Story = { args: { rarity: 'common' } };
export const Rare:      Story = { args: { rarity: 'rare' } };
export const Epic:      Story = { args: { rarity: 'epic' } };
export const Legendary: Story = { args: { rarity: 'legendary' } };

export const CustomLabel: Story = {
    args: { rarity: 'legendary', label: '5★' },
};

export const AllRarities: Story = {
    render: () => (
        <div className="flex items-center gap-2">
            <RarityBadge rarity="common" />
            <RarityBadge rarity="rare" />
            <RarityBadge rarity="epic" />
            <RarityBadge rarity="legendary" />
        </div>
    ),
};
