import type { Meta, StoryObj } from '@storybook/react-vite';
import Select from './Select';

const meta = {
    title: 'UI/Select',
    component: Select,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        invalid:  { control: 'boolean' },
        disabled: { control: 'boolean' },
    },
} satisfies Meta<typeof Select>;

export default meta;
type Story = StoryObj<typeof meta>;

const opts = (
    <>
        <option value="">— Choisir une faction —</option>
        <option value="ORBIT">ORBIT</option>
        <option value="FERRO">FERRO</option>
        <option value="VEIL">VEIL</option>
    </>
);

export const Default: Story = {
    render: (args) => <div className="w-72"><Select {...args}>{opts}</Select></div>,
};
export const Invalid:  Story = { render: (args) => <div className="w-72"><Select {...args} invalid defaultValue="">{opts}</Select></div> };
export const Disabled: Story = { render: (args) => <div className="w-72"><Select {...args} disabled defaultValue="ORBIT">{opts}</Select></div> };
