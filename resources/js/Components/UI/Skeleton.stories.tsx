import type { Meta, StoryObj } from '@storybook/react-vite';
import Skeleton from './Skeleton';

const meta = {
    title: 'UI/Skeleton',
    component: Skeleton,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
} satisfies Meta<typeof Skeleton>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Line: Story = {
    render: () => <Skeleton className="h-4 w-48" />,
};

export const Avatar: Story = {
    render: () => <Skeleton className="size-12 rounded-full" />,
};

export const Card: Story = {
    render: () => (
        <div className="rounded-lg bg-bg-elev1 border border-border-default p-4 flex flex-col gap-3 w-72">
            <Skeleton className="h-48 w-full" />
            <Skeleton className="h-4 w-3/4" />
            <Skeleton className="h-3 w-1/2" />
        </div>
    ),
};

export const ListRows: Story = {
    render: () => (
        <div className="flex flex-col gap-2 w-96">
            {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center gap-3 px-3 py-2 rounded-md bg-bg-elev1">
                    <Skeleton className="size-10 rounded-full" />
                    <Skeleton className="h-4 flex-1" />
                    <Skeleton className="h-4 w-12" />
                </div>
            ))}
        </div>
    ),
};
