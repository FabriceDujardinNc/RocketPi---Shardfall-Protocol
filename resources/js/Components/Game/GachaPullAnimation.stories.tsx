import type { Meta, StoryObj } from '@storybook/react-vite';
import GachaPullAnimation from './GachaPullAnimation';

const meta = {
    title: 'Game/GachaPullAnimation',
    component: GachaPullAnimation,
    parameters: { layout: 'fullscreen' },
    tags: ['autodocs'],
} satisfies Meta<typeof GachaPullAnimation>;

export default meta;
type Story = StoryObj<typeof meta>;

const SINGLE = [
    { operatorId: 1, name: 'Vex', rarity: 'legendary' as const, isNew: true },
];

const MULTI = [
    { operatorId: 1, name: 'Vex',   rarity: 'legendary' as const, isNew: true },
    { operatorId: 2, name: 'Halo',  rarity: 'epic'      as const, isNew: false },
    { operatorId: 3, name: 'Drift', rarity: 'rare'      as const, isNew: true },
    { operatorId: 4, name: 'Echo',  rarity: 'common'    as const, isNew: false },
    { operatorId: 5, name: 'Forge', rarity: 'rare'      as const, isNew: true },
    { operatorId: 6, name: 'Spark', rarity: 'common'    as const, isNew: false },
    { operatorId: 7, name: 'Vault', rarity: 'epic'      as const, isNew: true },
    { operatorId: 8, name: 'Ghost', rarity: 'rare'      as const, isNew: false },
    { operatorId: 9, name: 'Stark', rarity: 'common'    as const, isNew: false },
    { operatorId: 10, name: 'Sentry', rarity: 'rare'    as const, isNew: true },
];

export const SinglePull: Story = { args: { results: SINGLE } };
export const MultiPull:  Story = { args: { results: MULTI } };
