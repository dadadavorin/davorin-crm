export type CompanyStatusValue = 'lead' | 'prospect' | 'customer' | 'inactive';

export type OwnerOption = {
    id: number;
    name: string;
};

export type Company = {
    id: number;
    name: string;
    status: CompanyStatusValue;
    status_label: string;
    industry: string | null;
    website: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    notes: string | null;
    owner: OwnerOption | null;
    created_at: string | null;
    updated_at: string | null;
};

export type StatusOption = {
    value: string;
    label: string;
};

export type CompanyBoardCard = {
    id: number;
    name: string;
    industry: string | null;
    owner: OwnerOption | null;
    position: string;
};
