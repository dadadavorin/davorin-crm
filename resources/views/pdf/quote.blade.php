<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $quote->number }}</title>
<style>
    @page {
        margin: 32px 40px;
    }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10pt;
        color: #1a1a1a;
    }

    h1 {
        font-size: 18pt;
        margin: 0 0 4px 0;
    }

    .muted {
        color: #666666;
    }

    .header {
        margin-bottom: 24px;
    }

    .header .brand {
        float: left;
        width: 50%;
    }

    .header .meta {
        float: right;
        width: 50%;
        text-align: right;
    }

    .header .meta table {
        width: 100%;
        border-collapse: collapse;
    }

    .header .meta td {
        padding: 1px 0;
    }

    .header .meta td.label {
        text-align: right;
        padding-right: 8px;
        color: #666666;
    }

    .header .meta td.value {
        text-align: right;
        font-weight: bold;
    }

    .clearfix {
        clear: both;
    }

    .bill-to {
        margin-bottom: 20px;
        padding-top: 8px;
        border-top: 1px solid #cccccc;
    }

    .bill-to .label {
        color: #666666;
        text-transform: uppercase;
        font-size: 8pt;
        margin-bottom: 4px;
    }

    .bill-to .name {
        font-weight: bold;
        font-size: 11pt;
    }

    table.items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
    }

    table.items thead {
        display: table-header-group;
    }

    table.items th {
        text-align: left;
        border-bottom: 1px solid #1a1a1a;
        padding: 6px 4px;
        font-size: 8pt;
        text-transform: uppercase;
        color: #666666;
    }

    table.items td {
        padding: 6px 4px;
        border-bottom: 1px solid #eeeeee;
        vertical-align: top;
    }

    table.items th.numeric,
    table.items td.numeric {
        text-align: right;
    }

    table.items tr {
        page-break-inside: avoid;
    }

    table.totals {
        width: 45%;
        margin-left: 55%;
        border-collapse: collapse;
    }

    table.totals td {
        padding: 4px;
    }

    table.totals td.label {
        color: #666666;
    }

    table.totals td.value {
        text-align: right;
    }

    table.totals tr.total td {
        border-top: 1px solid #1a1a1a;
        font-weight: bold;
        font-size: 12pt;
        padding-top: 8px;
    }

    .notes {
        margin-top: 24px;
    }

    .notes .block {
        margin-bottom: 16px;
    }

    .notes .label {
        color: #666666;
        text-transform: uppercase;
        font-size: 8pt;
        margin-bottom: 4px;
    }
</style>
</head>
<body>

<div class="header">
    <div class="brand">
        <h1>Davorin CRM</h1>
        <div class="muted">Quote</div>
    </div>
    <div class="meta">
        <table>
            <tr>
                <td class="label">Number</td>
                <td class="value">{{ $quote->number }}</td>
            </tr>
            <tr>
                <td class="label">Issue date</td>
                <td class="value">{{ $quote->issue_date?->toDateString() }}</td>
            </tr>
            <tr>
                <td class="label">Valid until</td>
                <td class="value">{{ $quote->valid_until?->toDateString() }}</td>
            </tr>
        </table>
    </div>
    <div class="clearfix"></div>
</div>

<div class="bill-to">
    <div class="label">Bill to</div>
    <div class="name">{{ $quote->bill_to_company_name }}</div>
    @if ($quote->bill_to_address !== null)
        <div>{{ $quote->bill_to_address }}</div>
    @endif
    @if ($quote->bill_to_contact_name !== null)
        <div>{{ $quote->bill_to_contact_name }}</div>
    @endif
    @if ($quote->bill_to_contact_email !== null)
        <div>{{ $quote->bill_to_contact_email }}</div>
    @endif
</div>

<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            <th class="numeric">Qty</th>
            <th class="numeric">Unit price</th>
            <th class="numeric">Line total</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($quote->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="numeric">{{ $item->quantity }}</td>
                <td class="numeric">&euro;{{ $item->unit_price_minor->toDecimalString() }}</td>
                <td class="numeric">&euro;{{ $item->line_total_minor->toDecimalString() }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="muted">No line items.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="label">Subtotal</td>
        <td class="value">&euro;{{ $quote->subtotal_minor->toDecimalString() }}</td>
    </tr>
    <tr>
        <td class="label">Tax ({{ $quote->tax_rate }})</td>
        <td class="value">&euro;{{ $quote->tax_minor->toDecimalString() }}</td>
    </tr>
    <tr class="total">
        <td class="label">Total</td>
        <td class="value">&euro;{{ $quote->total_minor->toDecimalString() }}</td>
    </tr>
</table>

<div class="clearfix"></div>

@if ($quote->notes !== null || $quote->terms !== null)
    <div class="notes">
        @if ($quote->notes !== null)
            <div class="block">
                <div class="label">Notes</div>
                <div>{{ $quote->notes }}</div>
            </div>
        @endif
        @if ($quote->terms !== null)
            <div class="block">
                <div class="label">Terms</div>
                <div>{{ $quote->terms }}</div>
            </div>
        @endif
    </div>
@endif

</body>
</html>
