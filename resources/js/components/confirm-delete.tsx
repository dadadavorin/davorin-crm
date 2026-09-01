import { router } from '@inertiajs/react';
import { useState } from 'react';
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

type ConfirmDeleteProps = {
    href: string;
    title: string;
    description: string;
    trigger?: React.ReactNode;
};

/**
 * A destructive-action confirmation dialog: every entity's delete button
 * opens this instead of firing immediately. Submits via Inertia's own
 * `router.delete`, never a raw `fetch` call, and surfaces the server's
 * refusal (e.g. `RecordHasDependentsException`) as a flashed error instead
 * of navigating away.
 */
export function ConfirmDelete({
    href,
    title,
    description,
    trigger,
}: ConfirmDeleteProps) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const handleDelete = () => {
        setProcessing(true);
        router.delete(href, {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button variant="destructive" size="sm">
                        Delete
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>

                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>

                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={handleDelete}
                    >
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
