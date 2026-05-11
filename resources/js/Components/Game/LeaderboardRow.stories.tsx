import type { Meta, StoryObj } from '@storybook/react-vite';
import LeaderboardRow from './LeaderboardRow';

const meta = {
    title: 'Game/LeaderboardRow',
    component: LeaderboardRow,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    args: { rank: 5, name: 'Apex Commander', score: 12450 },
} satisfies Meta<typeof LeaderboardRow>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default:     Story = {};
export const First:        Story = { args: { rank: 1, name: 'Vex', score: 99850 } };
export const CurrentUser:  Story = { args: { rank: 47, name: 'Toi', score: 2310, isCurrentUser: true } };

export const Top5: Story = {
    render: () => (
        <div className="flex flex-col gap-2 max-w-2xl">
            <LeaderboardRow rank={1} name="Vex"             score={99850} />
            <LeaderboardRow rank={2} name="ApexHunter"      score={87200} />
            <LeaderboardRow rank={3} name="OrbitMancer"     score={76450} />
            <LeaderboardRow rank={4} name="ShardCollector"  score={68210} />
            <LeaderboardRow rank={5} name="Toi"             score={64875} isCurrentUser />
        </div>
    ),
};
