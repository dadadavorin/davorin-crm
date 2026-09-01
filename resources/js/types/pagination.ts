export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/**
 * The shape of a Laravel `LengthAwarePaginator` once it crosses an Inertia
 * prop boundary (`Model::paginate()->toArray()`). Offset pagination is a
 * deliberate application-wide choice — ADR-0003.
 */
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
};
