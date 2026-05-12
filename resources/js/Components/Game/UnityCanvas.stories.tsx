import type { Meta, StoryObj } from '@storybook/react-vite';
import UnityCanvas from './UnityCanvas';

const meta = {
    title: 'Game/UnityCanvas',
    component: UnityCanvas,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
    args: {
        apiToken: 'storybook-fake-token',
        apiBaseUrl: 'https://rocketpi.local',
        userId: 42,
        locale: 'fr',
        // /unity n'existe pas en Storybook → le composant tombe sur l'état "missing"
        // ce qui est exactement ce qu'on veut montrer ici (placeholder propre).
        buildPath: '/unity-storybook-noop',
    },
} satisfies Meta<typeof UnityCanvas>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Missing: Story = {};

export const Square: Story = {
    args: { ratio: 'square' },
};

export const Tall: Story = {
    args: { ratio: 'tall' },
};
