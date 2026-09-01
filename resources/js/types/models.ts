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

export type CompanyOption = {
    id: number;
    name: string;
};

export type CompanyContact = {
    id: number;
    name: string;
    status: ContactStatusValue;
    status_label: string;
};

export type ContactStatusValue = 'new' | 'active' | 'inactive';

export type Contact = {
    id: number;
    first_name: string;
    last_name: string;
    email: string | null;
    phone: string | null;
    job_title: string | null;
    status: ContactStatusValue;
    status_label: string;
    company: CompanyOption | null;
    owner: OwnerOption | null;
    created_at: string | null;
    updated_at: string | null;
};

export type ContactBoardCard = {
    id: number;
    first_name: string;
    last_name: string;
    job_title: string | null;
    company: CompanyOption | null;
    owner: OwnerOption | null;
    position: string;
};
