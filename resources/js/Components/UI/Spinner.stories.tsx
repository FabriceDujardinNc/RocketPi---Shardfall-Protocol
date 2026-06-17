import type { Meta, StoryObj } from '@storybook/react-vite';
import Spinner from './Spinner';

const meta = {
    title: 'UI/Spinner',
    component: Spinner,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        size: { control: { type: 'range', min: 12, max: 48, step: 2 } },
    },
    args: { size: 16 },
} satisfies Meta<typeof Spinner>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Small:  Story = { args: { size: 14 } };
export const Medium: Story = { args: { size: 24 } };
export const Large:  Story = { args: { size: 40 } };

export const AllSizes: Story = {
    render: () => (
        <div className="flex items-center gap-6">
            <Spinner size={14} />
            <Spinner size={20} />
            <Spinner size={28} />
            <Spinner size={40} />
        </div>
    ),
};
