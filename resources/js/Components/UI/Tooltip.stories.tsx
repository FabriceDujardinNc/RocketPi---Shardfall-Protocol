import type { Meta, StoryObj } from '@storybook/react-vite';
import Tooltip from './Tooltip';

const meta = {
    title: 'UI/Tooltip',
    component: Tooltip,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: { content: 'Tooltip', children: <button>Hover</button> },
} satisfies Meta<typeof Tooltip>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Top: Story = {
    render: () => (
        <Tooltip content="Pity légendaire à 80 tirages" side="top">
            <button className="px-4 py-2 rounded-md bg-shard-500/15 text-shard-400 font-display text-sm uppercase">
                Survole-moi (top)
            </button>
        </Tooltip>
    ),
};

export const Right: Story = {
    render: () => (
        <Tooltip content="Voir détails opérateur" side="right">
            <span className="inline-block px-3 py-2 rounded bg-bg-elev2 text-text-medium">Right</span>
        </Tooltip>
    ),
};

export const Bottom: Story = {
    render: () => (
        <Tooltip content="Action irréversible" side="bottom">
            <span className="inline-block px-3 py-2 rounded bg-danger/15 text-danger">Bottom</span>
        </Tooltip>
    ),
};

export const Left: Story = {
    render: () => (
        <Tooltip content="Tooltip à gauche" side="left">
            <span className="inline-block px-3 py-2 rounded bg-bg-elev2 text-text-medium">Left</span>
        </Tooltip>
    ),
};
