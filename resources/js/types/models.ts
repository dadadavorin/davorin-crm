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

export type ContactOption = {
    id: number;
    name: string;
    company_name: string | null;
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

export type DealStageValue =
    | 'new'
    | 'qualified'
    | 'proposal'
    | 'negotiation'
    | 'won'
    | 'lost';

export type PrimaryContactOption = {
    id: number;
    name: string;
};

export type Deal = {
    id: number;
    title: string;
    value_minor: number | null;
    stage: DealStageValue;
    stage_label: string;
    is_terminal: boolean;
    expected_close_date: string | null;
    company: CompanyOption;
    primary_contact: PrimaryContactOption | null;
    owner: OwnerOption | null;
    created_at: string | null;
    updated_at: string | null;
};

export type DealBoardCard = {
    id: number;
    title: string;
    value_minor: number | null;
    expected_close_date: string | null;
    company: CompanyOption;
    primary_contact: PrimaryContactOption | null;
    owner: OwnerOption | null;
    position: string;
};

export type CompanyDeal = {
    id: number;
    title: string;
    stage: DealStageValue;
    stage_label: string;
    value_minor: number | null;
};

export type QuoteStatusValue =
    | 'draft'
    | 'sent'
    | 'accepted'
    | 'rejected'
    | 'expired';

export type DealOption = {
    id: number;
    title: string;
    company_name: string;
};

export type DealOnQuote = {
    id: number;
    title: string;
};

export type QuoteItem = {
    id: number;
    description: string;
    quantity: number;
    unit_price_minor: number;
    line_total_minor: number;
};

export type Quote = {
    id: number;
    number: string;
    status: QuoteStatusValue;
    status_label: string;
    is_terminal: boolean;
    is_draft: boolean;
    deal: DealOnQuote;
    issue_date: string | null;
    valid_until: string | null;
    tax_rate: string;
    subtotal_minor: number;
    tax_minor: number;
    total_minor: number;
    bill_to_company_name: string;
    bill_to_address: string | null;
    bill_to_contact_name: string | null;
    bill_to_contact_email: string | null;
    notes: string | null;
    terms: string | null;
    owner: OwnerOption | null;
    items: QuoteItem[];
    created_at: string | null;
    updated_at: string | null;
};

export type QuoteBoardCard = {
    id: number;
    number: string;
    total_minor: number;
    valid_until: string | null;
    deal: DealOnQuote;
    owner: OwnerOption | null;
    position: string;
};

export type DealQuote = {
    id: number;
    number: string;
    status: QuoteStatusValue;
    status_label: string;
    total_minor: number;
};

export type QuoteDefaults = {
    valid_until: string;
    tax_rate: string;
};
