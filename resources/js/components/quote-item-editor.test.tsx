import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it } from 'vite-plus/test';
import { QuoteItemEditor } from '@/components/quote-item-editor';
import type { QuoteItem } from '@/types';

afterEach(() => {
    cleanup();
});

describe('QuoteItemEditor', () => {
    it('shows an empty state with no items', () => {
        render(<QuoteItemEditor items={[]} errors={{}} />);

        expect(screen.getByText('No line items yet.')).toBeInTheDocument();
        expect(screen.getByText(/Subtotal preview: /)).toHaveTextContent(
            '€0.00',
        );
    });

    it('renders a row per existing item, named by array index', () => {
        const items: QuoteItem[] = [
            {
                id: 1,
                description: 'Consulting',
                quantity: 2,
                unit_price_minor: 10000,
                line_total_minor: 20000,
            },
        ];

        render(<QuoteItemEditor items={items} errors={{}} />);

        expect(screen.getByLabelText('Description')).toHaveValue('Consulting');
        expect(screen.getByLabelText('Description')).toHaveAttribute(
            'name',
            'items[0][description]',
        );
        expect(screen.getByLabelText('Quantity')).toHaveValue(2);
        expect(screen.getByLabelText('Unit price')).toHaveValue('100.00');
    });

    it('adding a row appends an editable line and updates the subtotal preview', () => {
        render(<QuoteItemEditor items={[]} errors={{}} />);

        fireEvent.click(screen.getByRole('button', { name: 'Add line item' }));

        fireEvent.change(screen.getByLabelText('Description'), {
            target: { value: 'Onboarding' },
        });
        fireEvent.change(screen.getByLabelText('Unit price'), {
            target: { value: '50.00' },
        });

        expect(screen.getByText(/Subtotal preview: /)).toHaveTextContent(
            '€50.00',
        );
    });

    it('removing a row drops it from the editor', () => {
        const items: QuoteItem[] = [
            {
                id: 1,
                description: 'Consulting',
                quantity: 1,
                unit_price_minor: 10000,
                line_total_minor: 10000,
            },
        ];

        render(<QuoteItemEditor items={items} errors={{}} />);

        fireEvent.click(screen.getByRole('button', { name: 'Remove' }));

        expect(screen.getByText('No line items yet.')).toBeInTheDocument();
    });

    it('marks a field invalid when a matching server error is present', () => {
        const items: QuoteItem[] = [
            {
                id: 1,
                description: '',
                quantity: 1,
                unit_price_minor: 0,
                line_total_minor: 0,
            },
        ];

        render(
            <QuoteItemEditor
                items={items}
                errors={{
                    'items.0.description': 'The description field is required.',
                }}
            />,
        );

        expect(screen.getByLabelText('Description')).toHaveAttribute(
            'aria-invalid',
            'true',
        );
    });
});
