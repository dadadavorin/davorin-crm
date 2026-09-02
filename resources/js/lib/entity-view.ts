const STORAGE_PREFIX = 'crm:entity-view:';

export type EntityView = 'list' | 'board';

type Listener = () => void;

/**
 * Which of an entity's two views (list or board) was last visited, so the
 * sidebar can return the user to it instead of always defaulting to list.
 * Kept in localStorage rather than server-side state — it's a per-browser
 * display preference, not data.
 *
 * Reads go through an in-memory cache with a subscriber list (consumed via
 * `useSyncExternalStore` in `useEntityView`) rather than reading
 * `localStorage` directly at render time. The sidebar renders on every page
 * — including single-page navigations that never touch `localStorage`
 * themselves — and the React Compiler memoizes a component with no changed
 * props or hooked state, so a plain `localStorage.getItem` call in its body
 * would silently return a stale value after the first render.
 */
const cache = new Map<string, EntityView>();
const listeners = new Set<Listener>();

function readFromStorage(entity: string): EntityView {
    if (typeof window === 'undefined') {
        return 'list';
    }

    try {
        return window.localStorage.getItem(`${STORAGE_PREFIX}${entity}`) ===
            'board'
            ? 'board'
            : 'list';
    } catch {
        return 'list';
    }
}

export function getEntityView(entity: string): EntityView {
    let view = cache.get(entity);

    if (view === undefined) {
        view = readFromStorage(entity);
        cache.set(entity, view);
    }

    return view;
}

export function setEntityView(entity: string, view: EntityView): void {
    if (cache.get(entity) === view) {
        return;
    }

    cache.set(entity, view);

    if (typeof window !== 'undefined') {
        try {
            window.localStorage.setItem(`${STORAGE_PREFIX}${entity}`, view);
        } catch {
            // Storage may be unavailable (private browsing); the preference
            // is a convenience, not worth failing the page over.
        }
    }

    listeners.forEach((listener) => listener());
}

export function subscribeEntityView(listener: Listener): () => void {
    listeners.add(listener);

    return () => {
        listeners.delete(listener);
    };
}
