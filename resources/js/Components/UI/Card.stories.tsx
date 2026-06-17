import type { Meta, StoryObj } from '@storybook/react-vite';
import Card from './Card';

const meta = {
    title: 'UI/Card',
    component: Card,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['default', 'elevated', 'shard'] },
        padding: { control: 'inline-radio', options: ['none', 'sm', 'md', 'lg'] },
        hoverable: { control: 'boolean' },
    },
    args: {
        children: (
            <div>
                <h3 className="font-display font-semibold uppercase tracking-wide text-text-high">Pack Apex Commandant</h3>
                <p className="font-body text-sm text-text-medium mt-2">
                    Pack événementiel : 1 ticket premium garanti + 50 shards.
                </p>
            </div>
        ),
    },
} satisfies Meta<typeof Card>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default:  Story = { args: { variant: 'default' } };
export const Elevated: Story = { args: { variant: 'elevated' } };
export const Shard:    Story = { args: { variant: 'shard' } };
export const Hoverable:Story = { args: { variant: 'default', hoverable: true } };

export const AllVariants: Story = {
    render: () => (
        <div className="grid sm:grid-cols-3 gap-4 max-w-4xl">
            <Card variant="default">
                <h3 className="font-display font-semibold uppercase tracking-wide">Default</h3>
                <p className="text-sm text-text-medium mt-2">Carte standard, fond elev1.</p>
            </Card>
            <Card variant="elevated">
                <h3 className="font-display font-semibold uppercase tracking-wide">Elevated</h3>
                <p className="text-sm text-text-medium mt-2">Fond elev2, ombre el2 pour pop visuel.</p>
            </Card>
            <Card variant="shard">
                <h3 className="font-display font-semibold uppercase tracking-wide">Shard</h3>
                <p className="text-sm text-text-medium mt-2">Bordure shard et glow pour highlight premium.</p>
            </Card>
        </div>
    ),
};
