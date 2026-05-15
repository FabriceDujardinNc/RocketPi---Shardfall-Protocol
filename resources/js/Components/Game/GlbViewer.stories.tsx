import type { Meta, StoryObj } from '@storybook/react-vite';
import GlbViewer from './GlbViewer';

const meta = {
    title: 'Game/GlbViewer',
    component: GlbViewer,
    parameters: { layout: 'centered' },
    tags: ['autodocs'],
    args: {
        src: '/storage/models/operators/vex/base.glb',
        alt: 'Mesh de base Vex',
        height: 360,
    },
} satisfies Meta<typeof GlbViewer>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Tall: Story = {
    args: { height: 500 },
};
