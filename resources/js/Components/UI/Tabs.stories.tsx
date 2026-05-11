import type { Meta, StoryObj } from '@storybook/react-vite';
import { Root, List, Trigger, Content } from './Tabs';

const meta = {
    title: 'UI/Tabs',
    component: Root,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
} satisfies Meta<typeof Root>;

export default meta;
type Story = StoryObj<typeof meta>;

export const Default: Story = {
    render: () => (
        <Root defaultValue="overview">
            <List>
                <Trigger value="overview">Vue d'ensemble</Trigger>
                <Trigger value="missions">Missions</Trigger>
                <Trigger value="rewards">Récompenses</Trigger>
            </List>
            <Content value="overview">
                <p className="font-body text-sm text-text-medium">Statistiques globales de la saison en cours.</p>
            </Content>
            <Content value="missions">
                <p className="font-body text-sm text-text-medium">3 missions actives, 2 réclamées.</p>
            </Content>
            <Content value="rewards">
                <p className="font-body text-sm text-text-medium">Paliers de récompenses débloqués.</p>
            </Content>
        </Root>
    ),
};

export const Many: Story = {
    render: () => (
        <Root defaultValue="t1">
            <List>
                <Trigger value="t1">Skin</Trigger>
                <Trigger value="t2">Titre</Trigger>
                <Trigger value="t3">Voiceline</Trigger>
                <Trigger value="t4">Bannière</Trigger>
                <Trigger value="t5">Bordure</Trigger>
            </List>
            <Content value="t1"><span className="text-text-medium">Skins</span></Content>
            <Content value="t2"><span className="text-text-medium">Titres</span></Content>
            <Content value="t3"><span className="text-text-medium">Voicelines</span></Content>
            <Content value="t4"><span className="text-text-medium">Bannières</span></Content>
            <Content value="t5"><span className="text-text-medium">Bordures</span></Content>
        </Root>
    ),
};
