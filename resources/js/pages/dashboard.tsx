import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

type Stats = {
    companies: number;
    contacts: number;
    deals: number;
    quotes: number;
};

type Props = {
    stats: Stats;
};

const tiles: { key: keyof Stats; label: string }[] = [
    { key: 'companies', label: 'Companies' },
    { key: 'contacts', label: 'Contacts' },
    { key: 'deals', label: 'Deals' },
    { key: 'quotes', label: 'Quotes' },
];

export default function Dashboard({ stats }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    {tiles.map((tile) => (
                        <div
                            key={tile.key}
                            className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-4"
                        >
                            <p className="text-muted-foreground text-sm">
                                {tile.label}
                            </p>
                            <p className="text-2xl font-semibold tracking-tight">
                                {stats[tile.key]}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
