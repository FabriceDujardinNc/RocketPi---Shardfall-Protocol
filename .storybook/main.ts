import type { StorybookConfig } from '@storybook/react-vite';

const config: StorybookConfig = {
    stories: [
        '../resources/js/Components/**/*.stories.@(ts|tsx)',
    ],
    framework: {
        name: '@storybook/react-vite',
        options: {},
    },
    typescript: {
        check: false,
        reactDocgen: 'react-docgen-typescript',
    },
};

export default config;
