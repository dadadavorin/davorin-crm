import { cleanup, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

type FormSlotProps = {
    processing: boolean;
    errors: Record<string, string>;
};

let mockProcessing = false;
let mockErrors: Record<string, string> = {};
let lastFormProps: { action: string; method: string } | null = null;

afterEach(() => {
    cleanup();
    mockProcessing = false;
    mockErrors = {};
    lastFormProps = null;
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
        }) => {
            lastFormProps = { action, method };

            return (
                <form data-action={action} data-method={method}>
                    {children({
                        processing: mockProcessing,
                        errors: mockErrors,
                    })}
                </form>
            );
        },
    };
});

import { CreateQuoteFromDealDialog } from '@/components/create-quote-from-deal-dialog';

const defaults = { valid_until: '2026-02-01', tax_rate: '0.2500' };

async function openDialog() {
    const user = userEvent.setup();
    render(<CreateQuoteFromDealDialog dealId={42} defaults={defaults} />);

    await user.click(screen.getByRole('button', { name: 'Create quote' }));

    return { user, dialog: screen.getByRole('dialog') };
}

describe('CreateQuoteFromDealDialog', () => {
    it('opens with the fields prefilled from the given defaults and posts to the deal-scoped route', async () => {
        const { dialog } = await openDialog();

        expect(within(dialog).getByLabelText('Valid until')).toHaveValue(
            '2026-02-01',
        );
        expect(within(dialog).getByLabelText('Tax rate')).toHaveValue('0.2500');
        expect(lastFormProps?.method).toBe('post');
        expect(lastFormProps?.action).toContain('/deals/42/quotes');
    });

    it('shows a field error message when the server rejects a value', async () => {
        mockErrors = {
            tax_rate:
                'The tax rate must be a non-negative decimal rate, e.g. "0.25".',
        };

        const { dialog } = await openDialog();

        expect(
            within(dialog).getByText(
                'The tax rate must be a non-negative decimal rate, e.g. "0.25".',
            ),
        ).toBeInTheDocument();
        expect(within(dialog).getByLabelText('Tax rate')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });

    it('disables the submit button while a request is in flight, so a double click cannot fire a second request', async () => {
        mockProcessing = true;

        const { dialog } = await openDialog();

        expect(
            within(dialog).getByRole('button', { name: 'Create quote' }),
        ).toBeDisabled();
    });
});
