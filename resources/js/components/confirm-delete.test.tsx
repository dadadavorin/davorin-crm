import { act, cleanup, render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it, vi } from 'vite-plus/test';

type DeleteOptions = {
    preserveScroll?: boolean;
    onSuccess?: () => void;
    onError?: (errors: Record<string, string>) => void;
    onFinish?: () => void;
};

const { deleteFn, toastError } = vi.hoisted(() => ({
    deleteFn: vi.fn(),
    toastError: vi.fn(),
}));

vi.mock('@inertiajs/react', async () => {
    const actual =
        await vi.importActual<typeof import('@inertiajs/react')>(
            '@inertiajs/react',
        );

    return {
        ...actual,
        router: { delete: deleteFn },
    };
});

vi.mock('sonner', () => ({
    toast: { error: toastError },
}));

import { ConfirmDelete } from '@/components/confirm-delete';

afterEach(() => {
    cleanup();
    deleteFn.mockClear();
    toastError.mockClear();
});

async function openAndConfirm() {
    const user = userEvent.setup();
    render(
        <ConfirmDelete
            href="/companies/1"
            title="Delete this company?"
            description="This soft-deletes Acme."
        />,
    );

    await user.click(screen.getByRole('button', { name: 'Delete' }));

    const dialog = screen.getByRole('dialog');
    await user.click(within(dialog).getByRole('button', { name: 'Delete' }));

    return deleteFn.mock.calls[0][1] as DeleteOptions;
}

describe('ConfirmDelete', () => {
    it('surfaces the server refusal as a toast instead of failing silently', async () => {
        const options = await openAndConfirm();

        options.onError?.({
            record_has_dependents:
                'Cannot delete this company: 2 live deals depend on it.',
        });

        expect(toastError).toHaveBeenCalledWith(
            'Cannot delete this company: 2 live deals depend on it.',
        );
    });

    it('closes the dialog and stays quiet on success', async () => {
        const options = await openAndConfirm();

        act(() => {
            options.onSuccess?.();
        });

        expect(toastError).not.toHaveBeenCalled();
        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
    });
});
