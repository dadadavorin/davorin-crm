import { Badge, type badgeVariants } from '@/components/ui/badge';
import type { VariantProps } from 'class-variance-authority';

type BadgeVariant = NonNullable<VariantProps<typeof badgeVariants>['variant']>;

type StatusBadgeProps = {
    label: string;
    variant?: BadgeVariant;
};

/**
 * A status label rendered as a badge. Shared across every entity's board and
 * detail screens; each entity maps its own enum to a `BadgeVariant` and
 * passes the result in, so the badge itself carries no domain knowledge.
 */
export function StatusBadge({
    label,
    variant = 'secondary',
}: StatusBadgeProps) {
    return <Badge variant={variant}>{label}</Badge>;
}
