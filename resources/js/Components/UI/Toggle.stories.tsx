import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import Toggle from './Toggle';

const meta = {
    title: 'UI/Toggle',
    component: Toggle,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        checked: false,
        onChange: () => {},
        label: 'Notifications email',
    },
} satisfies Meta<typeof Toggle>;

export default meta;
type Story = StoryObj<typeof meta>;

function ControlledStory({ label, disabled, defaultChecked }: { label?: string; disabled?: boolean; defaultChecked?: boolean }) {
    const [checked, setChecked] = useState<boolean>(defaultChecked ?? false);
    return <Toggle checked={checked} onChange={setChecked} label={label} disabled={disabled} />;
}

export const Off:      Story = { render: () => <ControlledStory label="Notifications email" /> };
export const On:       Story = { render: () => <ControlledStory label="Notifications email" defaultChecked /> };
export const Disabled: Story = { render: () => <ControlledStory label="Notifications email" disabled defaultChecked /> };
export const NoLabel:  Story = { render: () => <ControlledStory /> };
