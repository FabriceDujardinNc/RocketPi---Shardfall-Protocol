import type { Meta, StoryObj } from '@storybook/react-vite';
import { Table, Thead, Tbody, Tr, Th, Td } from './Table';

const meta = {
    title: 'UI/Table',
    component: Table,
    parameters: { layout: 'padded' },
    tags: ['autodocs'],
} satisfies Meta<typeof Table>;

export default meta;
type Story = StoryObj<typeof meta>;

const ROWS = [
    { name: 'Vex',   codename: 'VX-01', faction: 'ORBIT', rarity: 'legendary' },
    { name: 'Halo',  codename: 'HL-02', faction: 'ORBIT', rarity: 'epic' },
    { name: 'Forge', codename: 'FR-04', faction: 'FERRO', rarity: 'rare' },
    { name: 'Echo',  codename: 'EC-05', faction: 'VEIL',  rarity: 'common' },
];

export const Default: Story = {
    render: () => (
        <Table>
            <Thead>
                <Tr>
                    <Th>Nom</Th>
                    <Th>Codename</Th>
                    <Th>Faction</Th>
                    <Th>Rareté</Th>
                </Tr>
            </Thead>
            <Tbody>
                {ROWS.map(r => (
                    <Tr key={r.codename}>
                        <Td>{r.name}</Td>
                        <Td className="font-mono text-text-medium">{r.codename}</Td>
                        <Td>{r.faction}</Td>
                        <Td>{r.rarity}</Td>
                    </Tr>
                ))}
            </Tbody>
        </Table>
    ),
};

export const Empty: Story = {
    render: () => (
        <Table>
            <Thead>
                <Tr>
                    <Th>Nom</Th>
                    <Th>Score</Th>
                </Tr>
            </Thead>
            <Tbody>
                <Tr>
                    <Td colSpan={2} className="text-center py-8 text-text-low">Aucun résultat</Td>
                </Tr>
            </Tbody>
        </Table>
    ),
};
