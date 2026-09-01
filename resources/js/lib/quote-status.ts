import type { badgeVariants } from '@/components/ui/badge';
import type { QuoteStatusValue } from '@/types';
import type { VariantProps } from 'class-variance-authority';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const VARIANTS: Record<QuoteStatusValue, BadgeVariant> = {
    draft: 'outline',
    sent: 'secondary',
    accepted: 'default',
    rejected: 'destructive',
    expired: 'destructive',
};

export function quoteStatusVariant(status: QuoteStatusValue): BadgeVariant {
    return VARIANTS[status];
}
