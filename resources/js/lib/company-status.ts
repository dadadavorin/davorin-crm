import type { badgeVariants } from '@/components/ui/badge';
import type { CompanyStatusValue } from '@/types';
import type { VariantProps } from 'class-variance-authority';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const VARIANTS: Record<CompanyStatusValue, BadgeVariant> = {
    lead: 'outline',
    prospect: 'secondary',
    customer: 'default',
    inactive: 'destructive',
};

export function companyStatusVariant(status: CompanyStatusValue): BadgeVariant {
    return VARIANTS[status];
}
