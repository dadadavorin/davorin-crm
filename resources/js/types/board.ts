export type BoardCard = {
    id: number;
    position: string;
};

export type BoardColumn<TCard extends BoardCard = BoardCard> = {
    status: string;
    label: string;
    cards: TCard[];
    total: number;
    has_more: boolean;
};
