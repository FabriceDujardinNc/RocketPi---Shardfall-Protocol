import type { Meta, StoryObj } from '@storybook/react-vite';
import FactionBadge from './FactionBadge';

const meta = {
    title: 'Game/FactionBadge',
    component: FactionBadge,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        faction: { control: 'inline-radio', options: ['orbit', 'ferro', 'veil'] },
        label:   { control: 'text' },
    },
} satisfies Meta<typeof FactionBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Orbit: Story = { args: { faction: 'orbit' } };
export const Ferro: Story = { args: { faction: 'ferro' } };
export const Veil:  Story = { args: { faction: 'veil' } };

export const AllFactions: Story = {
    render: () => (
        <div className="flex items-center gap-2">
            <FactionBadge faction="orbit" />
            <FactionBadge faction="ferro" />
            <FactionBadge faction="veil" />
        </div>
    ),
};

export const CustomLabel: Story = {
    args: { faction: 'orbit', label: 'Orbit Coalition' },
};
