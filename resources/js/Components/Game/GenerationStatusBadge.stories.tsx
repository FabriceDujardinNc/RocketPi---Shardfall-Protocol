import type { Meta, StoryObj } from '@storybook/react-vite';
import GenerationStatusBadge from './GenerationStatusBadge';

const meta = {
    title: 'Game/GenerationStatusBadge',
    component: GenerationStatusBadge,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        status: { control: 'inline-radio', options: ['pending', 'queued', 'generating', 'ready', 'failed'] },
    },
} satisfies Meta<typeof GenerationStatusBadge>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Pending:    Story = { args: { status: 'pending' } };
export const Queued:     Story = { args: { status: 'queued' } };
export const Generating: Story = { args: { status: 'generating' } };
export const Ready:      Story = { args: { status: 'ready' } };
export const Failed:     Story = { args: { status: 'failed' } };

export const AllVariants: Story = {
    render: () => (
        <div className="flex items-center gap-3 flex-wrap">
            <GenerationStatusBadge status="pending" />
            <GenerationStatusBadge status="queued" />
            <GenerationStatusBadge status="generating" />
            <GenerationStatusBadge status="ready" />
            <GenerationStatusBadge status="failed" />
        </div>
    ),
};
