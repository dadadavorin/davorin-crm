import type { badgeVariants } from '@/components/ui/badge';
import type { DealStageValue } from '@/types';
import type { VariantProps } from 'class-variance-authority';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

const VARIANTS: Record<DealStageValue, BadgeVariant> = {
    new: 'outline',
    qualified: 'secondary',
    proposal: 'secondary',
    negotiation: 'default',
    won: 'default',
    lost: 'destructive',
};

export function dealStageVariant(stage: DealStageValue): BadgeVariant {
    return VARIANTS[stage];
}
