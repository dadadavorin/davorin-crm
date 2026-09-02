import { useEffect } from 'react';
import { type EntityView, setEntityView } from '@/lib/entity-view';

/**
 * Records that `entity`'s list/board view was just visited, so the sidebar
 * link can return here next time instead of defaulting to list.
 */
export function useRememberEntityView(entity: string, view: EntityView): void {
    useEffect(() => {
        setEntityView(entity, view);
    }, [entity, view]);
}
