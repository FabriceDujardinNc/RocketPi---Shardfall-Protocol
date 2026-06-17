import type { Meta, StoryObj } from '@storybook/react-vite';
import Radio from './Radio';

const meta = {
    title: 'UI/Radio',
    component: Radio,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        label: { control: 'text' },
        disabled: { control: 'boolean' },
    },
    args: { label: 'ORBIT', name: 'faction' },
} satisfies Meta<typeof Radio>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default:        Story = {};
export const Checked:         Story = { args: { defaultChecked: true } };
export const Disabled:        Story = { args: { disabled: true } };
export const Group: Story = {
    render: () => (
        <div className="flex flex-col gap-2">
            <Radio name="faction-group" value="ORBIT" label="ORBIT" defaultChecked />
            <Radio name="faction-group" value="FERRO" label="FERRO" />
            <Radio name="faction-group" value="VEIL"  label="VEIL" />
        </div>
    ),
};
