import type { Meta, StoryObj } from '@storybook/react-vite';
import CurrencyDisplay from './CurrencyDisplay';

const meta = {
    title: 'Game/CurrencyDisplay',
    component: CurrencyDisplay,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        currency: { control: 'inline-radio', options: ['premium', 'soft', 'fragments'] },
        amount:   { control: 'number' },
        label:    { control: 'text' },
    },
    args: { currency: 'premium', amount: 1240 },
} satisfies Meta<typeof CurrencyDisplay>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Premium:   Story = { args: { currency: 'premium', amount: 12400 } };
export const Soft:      Story = { args: { currency: 'soft', amount: 99850 } };
export const Fragments: Story = { args: { currency: 'fragments', amount: 47 } };

export const Header: Story = {
    render: () => (
        <div className="flex items-center gap-4 px-4 py-2 rounded-md bg-bg-elev1 border border-border-default">
            <CurrencyDisplay currency="premium" amount={2400} />
            <CurrencyDisplay currency="soft" amount={150000} />
            <CurrencyDisplay currency="fragments" amount={32} />
        </div>
    ),
};
