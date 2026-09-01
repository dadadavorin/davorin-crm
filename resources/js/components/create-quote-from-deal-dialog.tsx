import { Form } from '@inertiajs/react';
import { useState } from 'react';
import DealController from '@/actions/App/Http/Controllers/DealController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { QuoteDefaults } from '@/types';

type CreateQuoteFromDealDialogProps = {
    dealId: number;
    defaults: QuoteDefaults;
};

/**
 * The deal-board shortcut (T9): creates a linked draft quote without
 * leaving the board. Submits through Inertia's own `<Form>`, the same as
 * every other create in this app, so the redirect to the new quote's edit
 * page happens as an ordinary navigation and the submit button disabling
 * while `processing` is what stops a double click from firing twice.
 */
export function CreateQuoteFromDealDialog({
    dealId,
    defaults,
}: CreateQuoteFromDealDialogProps) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button type="button" variant="secondary" size="sm">
                    Create quote
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Create a quote for this deal</DialogTitle>
                <DialogDescription>
                    A draft quote is created with this deal&apos;s company and
                    contact snapshotted onto it. Line items are added on the
                    next screen.
                </DialogDescription>

                <Form
                    action={DealController.storeQuote(dealId).url}
                    method="post"
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => {
                        const fieldErrors = errors as Record<string, string>;

                        return (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="valid_until">
                                        Valid until
                                    </Label>
                                    <Input
                                        id="valid_until"
                                        name="valid_until"
                                        type="date"
                                        defaultValue={defaults.valid_until}
                                        aria-invalid={Boolean(
                                            fieldErrors.valid_until,
                                        )}
                                    />
                                    <InputError
                                        message={fieldErrors.valid_until}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tax_rate">Tax rate</Label>
                                    <Input
                                        id="tax_rate"
                                        name="tax_rate"
                                        defaultValue={defaults.tax_rate}
                                        aria-invalid={Boolean(
                                            fieldErrors.tax_rate,
                                        )}
                                    />
                                    <InputError
                                        message={fieldErrors.tax_rate}
                                    />
                                </div>

                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button
                                            type="button"
                                            variant="secondary"
                                        >
                                            Cancel
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        Create quote
                                    </Button>
                                </DialogFooter>
                            </>
                        );
                    }}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
