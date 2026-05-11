import type { Meta, StoryObj } from '@storybook/react-vite';
import OperatorCard from './OperatorCard';

const meta = {
    title: 'Game/OperatorCard',
    component: OperatorCard,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        name: 'Vex',
        role: 'Sniper longue portée',
        rarity: 'legendary',
        faction: 'orbit',
        level: 12,
    },
} satisfies Meta<typeof OperatorCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Legendary: Story = { args: { rarity: 'legendary', faction: 'orbit', name: 'Vex',   role: 'Sniper longue portée' } };
export const Epic:      Story = { args: { rarity: 'epic',      faction: 'veil',  name: 'Wraith', role: 'Infiltrateur invisible' } };
export const Rare:      Story = { args: { rarity: 'rare',      faction: 'orbit', name: 'Drift',  role: 'Éclaireur rapide' } };
export const Common:    Story = { args: { rarity: 'common',    faction: 'ferro', name: 'Iron',   role: 'Assaut polyvalent' } };

export const RosterGrid: Story = {
    render: () => (
        <div className="grid grid-cols-4 gap-4 max-w-3xl">
            <OperatorCard name="Vex"    role="Sniper"    rarity="legendary" faction="orbit" level={8} />
            <OperatorCard name="Crag"   role="Tank"      rarity="legendary" faction="ferro" level={5} />
            <OperatorCard name="Wraith" role="Infiltr."  rarity="epic"      faction="veil"  level={3} />
            <OperatorCard name="Iron"   role="Assaut"    rarity="common"    faction="ferro" level={1} />
        </div>
    ),
};
