import type { Meta, StoryObj } from '@storybook/react-vite';
import PityCounter from './PityCounter';

const meta = {
    title: 'Game/PityCounter',
    component: PityCounter,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    argTypes: {
        rarity:    { control: 'inline-radio', options: ['legendary', 'epic'] },
        current:   { control: 'number' },
        threshold: { control: 'number' },
        softPity:  { control: 'number' },
    },
    args: { current: 35, threshold: 80, rarity: 'legendary', softPity: 60 },
} satisfies Meta<typeof PityCounter>;

export default meta;
type Story = StoryObj<typeof meta>;

export const LegendaryNormal:  Story = { args: { current: 35, threshold: 80, rarity: 'legendary', softPity: 60 } };
export const LegendarySoftPity: Story = { args: { current: 70, threshold: 80, rarity: 'legendary', softPity: 60 } };
export const LegendaryReady:    Story = { args: { current: 79, threshold: 80, rarity: 'legendary', softPity: 60 } };
export const Epic:               Story = { args: { current: 7,  threshold: 10, rarity: 'epic' } };

export const Both: Story = {
    render: () => (
        <div className="flex flex-col gap-4 max-w-md">
            <PityCounter current={35} threshold={80} rarity="legendary" softPity={60} />
            <PityCounter current={72} threshold={80} rarity="legendary" softPity={60} />
            <PityCounter current={4}  threshold={10} rarity="epic" />
        </div>
    ),
};
