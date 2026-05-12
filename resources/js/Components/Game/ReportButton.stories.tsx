import type { Meta, StoryObj } from '@storybook/react-vite';
import ReportButton from './ReportButton';

const meta = {
    title: 'Game/ReportButton',
    component: ReportButton,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    argTypes: {
        variant: { control: 'inline-radio', options: ['button', 'icon'] },
    },
    args: {
        reportedId: 42,
        reportedName: 'Vex',
        variant: 'button',
    },
} satisfies Meta<typeof ReportButton>;

export default meta;
type Story = StoryObj<typeof meta>;

export const ButtonVariant: Story = { args: { variant: 'button' } };
export const IconVariant:   Story = { args: { variant: 'icon' } };

export const WithMatchSession: Story = {
    args: { variant: 'button', matchSessionId: 123 },
};
