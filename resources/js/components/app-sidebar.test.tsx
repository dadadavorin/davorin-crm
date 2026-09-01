import { render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vite-plus/test';
import type { User } from '@/types';

const user: User = {
    id: 1,
    name: 'Ana Horvat',
    email: 'ana@example.com',
    role: 'admin',
    email_verified_at: null,
    created_at: '2026-01-01T00:00:00Z',
    updated_at: '2026-01-01T00:00:00Z',
};

vi.mock('@inertiajs/react', async () => {
    const actual =
        await vi.importActual<typeof import('@inertiajs/react')>(
            '@inertiajs/react',
        );

    return {
        ...actual,
        usePage: () => ({
            props: { auth: { user }, sidebarOpen: true },
            url: '/dashboard',
            component: 'dashboard',
        }),
        Link: ({
            href,
            children,
            ...rest
        }: {
            href: unknown;
            children: React.ReactNode;
        }) => (
            <a href={typeof href === 'string' ? href : '#'} {...rest}>
                {children}
            </a>
        ),
    };
});

import { AppSidebar } from '@/components/app-sidebar';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';

describe('AppSidebar', () => {
    it('renders the main navigation entries', () => {
        render(
            <TooltipProvider>
                <SidebarProvider>
                    <AppSidebar />
                </SidebarProvider>
            </TooltipProvider>,
        );

        expect(
            screen.getByRole('link', { name: /dashboard/i }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /companies/i }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /contacts/i }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /deals/i }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /quotes/i }),
        ).toBeInTheDocument();
    });
});
