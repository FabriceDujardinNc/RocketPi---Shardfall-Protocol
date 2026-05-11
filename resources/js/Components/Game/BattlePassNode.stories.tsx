import type { Meta, StoryObj } from '@storybook/react-vite';
import BattlePassNode from './BattlePassNode';

const meta = {
    title: 'Game/BattlePassNode',
    component: BattlePassNode,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        tier: 5,
        label: '250 XP',
        free: true,
    },
} satisfies Meta<typeof BattlePassNode>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Locked:    Story = { args: { unlocked: false, claimed: false } };
export const Unlocked:  Story = { args: { unlocked: true,  claimed: false } };
export const Claimed:   Story = { args: { unlocked: true,  claimed: true  } };
export const Premium:   Story = { args: { unlocked: true,  premium: true, free: false, label: '⭐ Milestone' } };

export const Roadmap: Story = {
    render: () => (
        <div className="flex gap-2">
            <BattlePassNode tier={1} label="100 XP" unlocked claimed free />
            <BattlePassNode tier={2} label="250 XP" unlocked free />
            <BattlePassNode tier={3} label="500 XP" free />
            <BattlePassNode tier={4} label="800 XP" free />
            <BattlePassNode tier={5} label="⭐ Milestone" premium />
        </div>
    ),
};
