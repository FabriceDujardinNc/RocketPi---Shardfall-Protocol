import type { Meta, StoryObj } from '@storybook/react-vite';
import Progress from './Progress';

const meta = {
    title: 'UI/Progress',
    component: Progress,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    argTypes: {
        value:   { control: { type: 'range', min: 0, max: 100, step: 5 } },
        max:     { control: 'number' },
        label:   { control: 'text' },
        variant: { control: 'inline-radio', options: ['shard', 'success', 'warning', 'danger'] },
    },
    args: { value: 65, max: 100, label: 'XP Battle Pass', variant: 'shard' },
} satisfies Meta<typeof Progress>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Shard:   Story = { args: { variant: 'shard' } };
export const Success: Story = { args: { variant: 'success', value: 100, label: 'Mission complète' } };
export const Warning: Story = { args: { variant: 'warning', value: 30, label: 'Stock faible' } };
export const Danger:  Story = { args: { variant: 'danger', value: 10, label: 'HP critique' } };
export const NoLabel: Story = { args: { label: undefined } };

export const AllVariants: Story = {
    render: () => (
        <div className="flex flex-col gap-4 max-w-md">
            <Progress label="Shard" value={70} variant="shard" />
            <Progress label="Success" value={100} variant="success" />
            <Progress label="Warning" value={45} variant="warning" />
            <Progress label="Danger" value={15} variant="danger" />
        </div>
    ),
};
