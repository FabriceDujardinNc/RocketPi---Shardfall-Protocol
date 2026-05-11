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
        faction: 'ORBIT',
        level: 12,
    },
} satisfies Meta<typeof OperatorCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Legendary: Story = { args: { rarity: 'legendary', faction: 'ORBIT', name: 'Vex',   role: 'Sniper longue portée' } };
export const Epic:      Story = { args: { rarity: 'epic',      faction: 'VEIL',  name: 'Wraith', role: 'Infiltrateur invisible' } };
export const Rare:      Story = { args: { rarity: 'rare',      faction: 'ORBIT', name: 'Drift',  role: 'Éclaireur rapide' } };
export const Common:    Story = { args: { rarity: 'common',    faction: 'FERRO', name: 'Iron',   role: 'Assaut polyvalent' } };

export const RosterGrid: Story = {
    render: () => (
        <div className="grid grid-cols-4 gap-4 max-w-3xl">
            <OperatorCard name="Vex"    role="Sniper"    rarity="legendary" faction="ORBIT" level={8} />
            <OperatorCard name="Crag"   role="Tank"      rarity="legendary" faction="FERRO" level={5} />
            <OperatorCard name="Wraith" role="Infiltr."  rarity="epic"      faction="VEIL"  level={3} />
            <OperatorCard name="Iron"   role="Assaut"    rarity="common"    faction="FERRO" level={1} />
        </div>
    ),
};
