import type { Meta, StoryObj } from '@storybook/react-vite';
import MissionCard from './MissionCard';

const meta = {
    title: 'Game/MissionCard',
    component: MissionCard,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    args: {
        title: 'Signal Shard',
        description: 'Effectue 10 tirages sur la bannière permanente.',
        progress: 6,
        total: 10,
        rewardLabel: '+50 ✦',
    },
} satisfies Meta<typeof MissionCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const InProgress: Story = {};
export const Completed:   Story = {
    args: { progress: 10, total: 10, completed: true, onClaim: () => alert('claim!') },
};
export const Claimed: Story = {
    args: { progress: 10, total: 10, completed: true, claimed: true },
};

export const Stack: Story = {
    render: () => (
        <div className="flex flex-col gap-3 max-w-lg">
            <MissionCard
                title="Signal Shard"
                description="Effectue 10 tirages sur la bannière permanente."
                progress={4}
                total={10}
                rewardLabel="+50 ✦"
            />
            <MissionCard
                title="Commandant actif"
                description="Joue 3 sessions cette semaine."
                progress={3}
                total={3}
                rewardLabel="+200 ✦ + 1 ticket"
                completed
                onClaim={() => {}}
            />
            <MissionCard
                title="Collectionneur"
                description="Possède 15 opérateurs uniques."
                progress={15}
                total={15}
                rewardLabel="+1 ticket prem"
                completed
                claimed
            />
        </div>
    ),
};
