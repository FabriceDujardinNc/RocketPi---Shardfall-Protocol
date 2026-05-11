import type { Meta, StoryObj } from '@storybook/react-vite';
import RankBadge from './RankBadge';

const meta = {
    title: 'Game/RankBadge',
    component: RankBadge,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        rank: { control: { type: 'number', min: 1 } },
    },
    args: { rank: 1 },
} satisfies Meta<typeof RankBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const First:  Story = { args: { rank: 1 } };
export const Second: Story = { args: { rank: 2 } };
export const Third:  Story = { args: { rank: 3 } };
export const Tenth:  Story = { args: { rank: 10 } };
export const HighRank: Story = { args: { rank: 4287 } };

export const Podium: Story = {
    render: () => (
        <div className="flex items-center gap-2">
            <RankBadge rank={1} />
            <RankBadge rank={2} />
            <RankBadge rank={3} />
            <RankBadge rank={4} />
            <RankBadge rank={100} />
        </div>
    ),
};
