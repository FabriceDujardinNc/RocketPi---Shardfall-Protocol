import type { Meta, StoryObj } from '@storybook/react-vite';
import Pagination from './Pagination';

const meta = {
    title: 'UI/Pagination',
    component: Pagination,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
} satisfies Meta<typeof Pagination>;

export default meta;
type Story = StoryObj<typeof meta>;

const sampleLinks = [
    { url: null,                 label: '&laquo; Précédent', active: false },
    { url: '/items?page=1',      label: '1',                 active: false },
    { url: '/items?page=2',      label: '2',                 active: true  },
    { url: '/items?page=3',      label: '3',                 active: false },
    { url: '/items?page=4',      label: '4',                 active: false },
    { url: '/items?page=5',      label: '5',                 active: false },
    { url: '/items?page=3',      label: 'Suivant &raquo;',   active: false },
];

export const Default:  Story = { args: { links: sampleLinks } };

export const FirstPage: Story = {
    args: {
        links: [
            { url: null,            label: '&laquo;', active: false },
            { url: '/items?page=1', label: '1',       active: true },
            { url: '/items?page=2', label: '2',       active: false },
            { url: '/items?page=2', label: '&raquo;', active: false },
        ],
    },
};

export const SinglePage: Story = {
    // Pagination retourne null si links.length <= 3 — on montre que c'est vide
    args: {
        links: [
            { url: null, label: '&laquo;', active: false },
            { url: '/p', label: '1',       active: true  },
            { url: null, label: '&raquo;', active: false },
        ],
    },
};
