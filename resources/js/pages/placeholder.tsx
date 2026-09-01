import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import type { BreadcrumbItem } from '@/types';

type Props = {
    title: string;
};

export default function Placeholder({ title }: Props) {
    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title={title}
                    description="This section is not built yet."
                />
            </div>
        </>
    );
}

Placeholder.layout = (props: Props) => {
    const breadcrumbs: BreadcrumbItem[] = [{ title: props.title, href: '#' }];

    return { breadcrumbs };
};
