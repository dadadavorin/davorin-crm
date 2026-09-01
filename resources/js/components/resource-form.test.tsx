import { cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

type FormSlotProps = {
    processing: boolean;
    errors: Record<string, string>;
};

let mockErrors: Record<string, string> = {};

afterEach(() => {
    cleanup();
});

vi.mock('@inertiajs/react', async () => {
    const actual =
        await vi.importActual<typeof import('@inertiajs/react')>(
            '@inertiajs/react',
        );

    return {
        ...actual,
        Form: ({
            action,
            method,
            children,
        }: {
            action: string;
            method: string;
            children: (props: FormSlotProps) => ReactNode;
        }) => (
            <form data-action={action} data-method={method}>
                {children({ processing: false, errors: mockErrors })}
            </form>
        ),
        Link: ({
            href,
            children,
        }: {
            href: unknown;
            children: React.ReactNode;
        }) => <a href={typeof href === 'string' ? href : '#'}>{children}</a>,
    };
});

import {
    ResourceForm,
    type ResourceFormField,
} from '@/components/resource-form';

const fields: ResourceFormField[] = [
    { type: 'text', name: 'name', label: 'Name', required: true },
    { type: 'textarea', name: 'notes', label: 'Notes' },
    {
        type: 'select',
        name: 'status',
        label: 'Status',
        options: [
            { value: 'lead', label: 'Lead' },
            { value: 'prospect', label: 'Prospect' },
        ],
    },
];

describe('ResourceForm', () => {
    it('renders a labeled field for every entry with no errors (empty state)', () => {
        mockErrors = {};

        render(
            <ResourceForm
                form={{ action: '/companies', method: 'post' }}
                fields={fields}
                submitLabel="Create company"
                cancelHref="/companies"
            />,
        );

        expect(screen.getByLabelText('Name')).toBeInTheDocument();
        expect(screen.getByLabelText('Notes')).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Create company' }),
        ).toBeInTheDocument();
        expect(screen.queryByText(/is required/i)).not.toBeInTheDocument();
    });

    it('shows a field error message when the server rejects a value (error state)', () => {
        mockErrors = { name: 'The name field is required.' };

        render(
            <ResourceForm
                form={{ action: '/companies', method: 'post' }}
                fields={fields}
                submitLabel="Create company"
                cancelHref="/companies"
            />,
        );

        expect(
            screen.getByText('The name field is required.'),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Name')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('submits to the given action and method', () => {
        mockErrors = {};

        const { container } = render(
            <ResourceForm
                form={{ action: '/companies/5', method: 'post' }}
                fields={fields}
                submitLabel="Save changes"
                cancelHref="/companies/5"
            />,
        );
        const form = container.querySelector('form');

        expect(form).toHaveAttribute('data-action', '/companies/5');
        expect(form).toHaveAttribute('data-method', 'post');
    });
});
