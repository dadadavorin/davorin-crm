import { Link } from '@inertiajs/react';
import {
    Building2,
    Handshake,
    LayoutGrid,
    ReceiptText,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useEntityView } from '@/hooks/use-entity-view';
import { dashboard } from '@/routes';
import {
    board as companiesBoard,
    index as companiesIndex,
} from '@/routes/companies';
import {
    board as contactsBoard,
    index as contactsIndex,
} from '@/routes/contacts';
import { board as dealsBoard, index as dealsIndex } from '@/routes/deals';
import { board as quotesBoard, index as quotesIndex } from '@/routes/quotes';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const companiesView = useEntityView('companies');
    const contactsView = useEntityView('contacts');
    const dealsView = useEntityView('deals');
    const quotesView = useEntityView('quotes');

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Companies',
            href:
                companiesView === 'board' ? companiesBoard() : companiesIndex(),
            icon: Building2,
        },
        {
            title: 'Contacts',
            href: contactsView === 'board' ? contactsBoard() : contactsIndex(),
            icon: Users,
        },
        {
            title: 'Deals',
            href: dealsView === 'board' ? dealsBoard() : dealsIndex(),
            icon: Handshake,
        },
        {
            title: 'Quotes',
            href: quotesView === 'board' ? quotesBoard() : quotesIndex(),
            icon: ReceiptText,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
