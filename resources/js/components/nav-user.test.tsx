import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vite-plus/test';
import type { User } from '@/types';

const user: User = {
    id: 1,
    name: 'Ana Horvat',
    email: 'ana@example.com',
    role: 'member',
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
        router: { flushAll: vi.fn() },
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

import { NavUser } from '@/components/nav-user';
import { SidebarProvider } from '@/components/ui/sidebar';

describe('NavUser', () => {
    it('opens the user menu and shows account actions', async () => {
        const testUser = userEvent.setup();

        render(
            <SidebarProvider>
                <NavUser />
            </SidebarProvider>,
        );

        await testUser.click(screen.getByRole('button'));

        expect(screen.getByRole('menu')).toBeInTheDocument();
        expect(screen.getByText('ana@example.com')).toBeInTheDocument();
        expect(
            screen.getByRole('menuitem', { name: /settings/i }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('menuitem', { name: /log out/i }),
        ).toBeInTheDocument();
    });
});
