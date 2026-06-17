import type { Preview } from '@storybook/react-vite';
import '../resources/css/app.css';

const preview: Preview = {
    parameters: {
        backgrounds: {
            default: 'dark',
            values: [
                { name: 'dark',  value: '#0c0f14' },
                { name: 'elev1', value: '#13171f' },
                { name: 'light', value: '#f4f4f5' },
            ],
        },
        controls: {
            matchers: {
                color: /(background|color)$/i,
                date: /Date$/i,
            },
        },
    },
    decorators: [
        (Story) => (
            <div className="font-body text-text-high p-6">
                <Story />
            </div>
        ),
    ],
};

export default preview;
