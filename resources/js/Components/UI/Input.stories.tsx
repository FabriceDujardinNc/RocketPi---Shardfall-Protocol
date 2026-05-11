import type { Meta, StoryObj } from '@storybook/react-vite';
import Input from './Input';

const meta = {
    title: 'UI/Input',
    component: Input,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        size:     { control: 'inline-radio', options: ['sm', 'md', 'lg'] },
        invalid:  { control: 'boolean' },
        disabled: { control: 'boolean' },
        placeholder: { control: 'text' },
    },
    args: { placeholder: 'pseudo@rocketpi.pro' },
} satisfies Meta<typeof Input>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default:   Story = { args: { size: 'md' } };
export const Small:     Story = { args: { size: 'sm' } };
export const Large:     Story = { args: { size: 'lg' } };
export const Invalid:   Story = { args: { invalid: true, defaultValue: 'invalid@', placeholder: 'pseudo@rocketpi.pro' } };
export const Disabled:  Story = { args: { disabled: true, defaultValue: 'disabled@rocketpi.pro' } };

export const AllSizes: Story = {
    render: () => (
        <div className="flex flex-col gap-3 w-72">
            <Input size="sm" placeholder="Small" />
            <Input size="md" placeholder="Medium" />
            <Input size="lg" placeholder="Large" />
            <Input invalid defaultValue="invalid" placeholder="Invalid" />
            <Input disabled defaultValue="disabled" />
        </div>
    ),
};
