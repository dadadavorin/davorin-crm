import { useSyncExternalStore } from 'react';
import {
    type EntityView,
    getEntityView,
    subscribeEntityView,
} from '@/lib/entity-view';

/**
 * Reactive read of `entity`'s remembered view. `useSyncExternalStore` is what
 * makes this safe under the React Compiler — see the note in
 * `lib/entity-view.ts` on why a plain `getEntityView` call in a component
 * body isn't enough.
 */
export function useEntityView(entity: string): EntityView {
    return useSyncExternalStore(
        subscribeEntityView,
        () => getEntityView(entity),
        () => 'list',
    );
}
