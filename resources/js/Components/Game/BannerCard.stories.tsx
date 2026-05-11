import type { Meta, StoryObj } from '@storybook/react-vite';
import BannerCard from './BannerCard';

const meta = {
    title: 'Game/BannerCard',
    component: BannerCard,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    args: {
        name: 'Apex — Vex Rate-Up',
        description: 'Tirage à taux augmenté sur Vex (legendary).',
        endsAt: '2026-06-30 23:59 UTC',
    },
} satisfies Meta<typeof BannerCard>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default:  Story = {};
export const Featured: Story = { args: { featured: true } };
export const NoImage:   Story = { args: { imageUrl: undefined, featured: true } };
export const NoEnd:    Story = { args: { endsAt: null } };

export const Grid: Story = {
    render: () => (
        <div className="grid sm:grid-cols-2 gap-4 max-w-3xl">
            <BannerCard name="Bannière permanente" description="Roster complet." />
            <BannerCard name="Apex — Vex Rate-Up" description="Boost x2 sur Vex" featured endsAt="2026-06-30" />
        </div>
    ),
};
