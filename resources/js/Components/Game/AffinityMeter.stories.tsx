import type { Meta, StoryObj } from '@storybook/react-vite';
import AffinityMeter from './AffinityMeter';

const meta = {
    title: 'Game/AffinityMeter',
    component: AffinityMeter,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    argTypes: {
        level:       { control: { type: 'range', min: 0, max: 10, step: 1 } },
        xp:          { control: 'number' },
        nextLevelXp: { control: 'number' },
    },
    args: { level: 3, xp: 220, nextLevelXp: 400 },
} satisfies Meta<typeof AffinityMeter>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};
export const Empty:    Story = { args: { level: 0, xp: 0,   nextLevelXp: 100 } };
export const MidLevel: Story = { args: { level: 5, xp: 250, nextLevelXp: 600 } };
export const NearLevelUp: Story = { args: { level: 7, xp: 750, nextLevelXp: 800 } };
export const Maxed: Story = { args: { level: 10, xp: 0, nextLevelXp: 1000 } };

export const Range: Story = {
    render: () => (
        <div className="flex flex-col gap-4 max-w-md">
            <AffinityMeter level={0} xp={0} nextLevelXp={100} />
            <AffinityMeter level={3} xp={150} nextLevelXp={400} />
            <AffinityMeter level={7} xp={650} nextLevelXp={800} />
            <AffinityMeter level={10} xp={0} nextLevelXp={1000} />
        </div>
    ),
};
