import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatMoney, moneyToInputValue } from '@/lib/money';
import type { QuoteItem } from '@/types';

type QuoteItemDraft = {
    key: string;
    description: string;
    quantity: string;
    unitPrice: string;
};

let draftKeySequence = 0;

function draftFromItem(item?: QuoteItem): QuoteItemDraft {
    draftKeySequence += 1;

    return {
        key: `item-${draftKeySequence}`,
        description: item?.description ?? '',
        quantity: item ? String(item.quantity) : '1',
        unitPrice: item
            ? (moneyToInputValue(item.unit_price_minor) ?? '0.00')
            : '0.00',
    };
}

/**
 * Line items are collected as native `items[N][field]` inputs so Inertia's
 * `<Form>` (a plain `<form>` under the hood) picks them up the same way it
 * does every other field — no separate submission path. State here is only
 * what the editor needs for add/remove and the client-side subtotal
 * preview; the server, via `RecalculateQuoteTotals`, is the only
 * authoritative total.
 */
type QuoteItemEditorProps = {
    items: QuoteItem[];
    errors: Record<string, string>;
};

export function QuoteItemEditor({ items, errors }: QuoteItemEditorProps) {
    const [drafts, setDrafts] = useState<QuoteItemDraft[]>(() =>
        items.map((item) => draftFromItem(item)),
    );

    const updateDraft = (key: string, patch: Partial<QuoteItemDraft>) => {
        setDrafts((previous) =>
            previous.map((draft) =>
                draft.key === key ? { ...draft, ...patch } : draft,
            ),
        );
    };

    const addRow = () =>
        setDrafts((previous) => [...previous, draftFromItem()]);
    const removeRow = (key: string) =>
        setDrafts((previous) => previous.filter((draft) => draft.key !== key));

    const lineTotalMinor = (draft: QuoteItemDraft): number | null => {
        const quantity = Number.parseInt(draft.quantity, 10);
        const unitPrice = Number.parseFloat(draft.unitPrice);

        if (!Number.isFinite(quantity) || !Number.isFinite(unitPrice)) {
            return null;
        }

        return Math.round(unitPrice * 100) * quantity;
    };

    const subtotalPreviewMinor = drafts.reduce(
        (total, draft) => total + (lineTotalMinor(draft) ?? 0),
        0,
    );

    return (
        <div className="space-y-3">
            <div className="border-sidebar-border/70 dark:border-sidebar-border overflow-x-auto rounded-xl border">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-sidebar-border/70 dark:border-sidebar-border border-b">
                            <th className="text-muted-foreground px-3 py-2 text-left font-medium">
                                Description
                            </th>
                            <th className="text-muted-foreground w-24 px-3 py-2 text-left font-medium">
                                Qty
                            </th>
                            <th className="text-muted-foreground w-32 px-3 py-2 text-left font-medium">
                                Unit price
                            </th>
                            <th className="text-muted-foreground w-32 px-3 py-2 text-left font-medium">
                                Line total
                            </th>
                            <th className="w-10" />
                        </tr>
                    </thead>
                    <tbody>
                        {drafts.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={5}
                                    className="text-muted-foreground px-3 py-6 text-center"
                                >
                                    No line items yet.
                                </td>
                            </tr>
                        ) : (
                            drafts.map((draft, index) => {
                                const total = lineTotalMinor(draft);

                                return (
                                    <tr
                                        key={draft.key}
                                        className="border-sidebar-border/70 dark:border-sidebar-border border-b last:border-0"
                                    >
                                        <td className="px-3 py-2">
                                            <Input
                                                name={`items[${index}][description]`}
                                                value={draft.description}
                                                onChange={(event) =>
                                                    updateDraft(draft.key, {
                                                        description:
                                                            event.target.value,
                                                    })
                                                }
                                                aria-label="Description"
                                                aria-invalid={Boolean(
                                                    errors[
                                                        `items.${index}.description`
                                                    ],
                                                )}
                                                required
                                            />
                                        </td>
                                        <td className="px-3 py-2">
                                            <Input
                                                type="number"
                                                min={1}
                                                step={1}
                                                name={`items[${index}][quantity]`}
                                                value={draft.quantity}
                                                onChange={(event) =>
                                                    updateDraft(draft.key, {
                                                        quantity:
                                                            event.target.value,
                                                    })
                                                }
                                                aria-label="Quantity"
                                                aria-invalid={Boolean(
                                                    errors[
                                                        `items.${index}.quantity`
                                                    ],
                                                )}
                                                required
                                            />
                                        </td>
                                        <td className="px-3 py-2">
                                            <Input
                                                name={`items[${index}][unit_price]`}
                                                value={draft.unitPrice}
                                                onChange={(event) =>
                                                    updateDraft(draft.key, {
                                                        unitPrice:
                                                            event.target.value,
                                                    })
                                                }
                                                aria-label="Unit price"
                                                aria-invalid={Boolean(
                                                    errors[
                                                        `items.${index}.unit_price`
                                                    ],
                                                )}
                                                placeholder="0.00"
                                                required
                                            />
                                        </td>
                                        <td className="text-muted-foreground px-3 py-2">
                                            {total === null
                                                ? '—'
                                                : formatMoney(total)}
                                        </td>
                                        <td className="px-3 py-2">
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    removeRow(draft.key)
                                                }
                                            >
                                                Remove
                                            </Button>
                                        </td>
                                    </tr>
                                );
                            })
                        )}
                    </tbody>
                </table>
            </div>

            <div className="flex items-center justify-between">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={addRow}
                >
                    Add line item
                </Button>
                <p className="text-muted-foreground text-sm">
                    Subtotal preview: {formatMoney(subtotalPreviewMinor)}
                </p>
            </div>
        </div>
    );
}
