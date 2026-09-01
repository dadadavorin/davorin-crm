import type { badgeVariants } from '@/components/ui/badge';
import type { ContactStatusValue } from '@/types';
import type { VariantProps } from 'class-variance-authority';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const VARIANTS: Record<ContactStatusValue, BadgeVariant> = {
    new: 'outline',
    active: 'default',
    inactive: 'destructive',
};

export function contactStatusVariant(status: ContactStatusValue): BadgeVariant {
    return VARIANTS[status];
}
