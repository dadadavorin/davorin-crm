<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Quote\CreateQuote;
use App\Actions\Quote\DeleteQuote;
use App\Actions\Quote\ReopenQuote;
use App\Actions\Quote\UpdateQuote;
use App\Board\BoardBuilder;
use App\Board\BoardColumn;
use App\Enums\QuoteStatus;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteRequest;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;

final class QuoteController extends Controller
{
    /**
     * The default VAT rate offered on a new quote — the demo data and the
     * assignment's Croatian context both use it. A user can change it per
     * quote up until it's sent; it is otherwise just a form default.
     */
    private const string DEFAULT_TAX_RATE = '0.2500';

    /**
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = ['number', 'status', 'total_minor', 'issue_date', 'valid_until', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Quote::class);

        $search = trim((string) $request->string('search'));
        $sort = in_array($request->string('sort')->value(), self::SORTABLE_COLUMNS, true)
            ? $request->string('sort')->value()
            : 'created_at';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $quotes = Quote::query()
            ->with(['deal:id,title', 'owner:id,name'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('quotes/index', [
            'quotes' => $quotes->through(fn (Quote $quote): array => $this->present($quote, withItems: false)),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function board(BoardBuilder $builder): Response
    {
        $this->authorize('viewAny', Quote::class);

        return Inertia::render('quotes/board', [
            'columns' => array_map(fn (BoardColumn $column): array => $column->toArray(), $builder->build(Quote::class)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Quote::class);

        return Inertia::render('quotes/create', [
            'deals' => $this->dealOptions(),
            'owners' => $this->ownerOptions(),
            'defaults' => [
                'issue_date' => Date::now()->toDateString(),
                'valid_until' => Date::now()->addDays(30)->toDateString(),
                'tax_rate' => self::DEFAULT_TAX_RATE,
            ],
        ]);
    }

    public function store(StoreQuoteRequest $request, CreateQuote $action): RedirectResponse
    {
        $user = Auth::user() ?? abort(401);

        $quote = $action->handle($request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote created.')]);

        return to_route('quotes.show', $quote);
    }

    public function show(Quote $quote): Response
    {
        $this->authorize('view', $quote);

        $quote->load(['deal:id,title', 'owner:id,name', 'items']);

        return Inertia::render('quotes/show', [
            'quote' => $this->present($quote, withItems: true),
        ]);
    }

    public function edit(Quote $quote): Response
    {
        $this->authorize('update', $quote);

        $quote->load(['deal:id,title', 'owner:id,name', 'items']);

        return Inertia::render('quotes/edit', [
            'quote' => $this->present($quote, withItems: true),
            'statuses' => $this->statusOptions(),
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function update(UpdateQuoteRequest $request, Quote $quote, UpdateQuote $action): RedirectResponse
    {
        $action->handle($quote, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote updated.')]);

        return to_route('quotes.show', $quote);
    }

    public function destroy(Quote $quote, DeleteQuote $action): RedirectResponse
    {
        $this->authorize('delete', $quote);

        $action->handle($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote deleted.')]);

        return to_route('quotes.index');
    }

    public function reopen(Quote $quote, ReopenQuote $action): RedirectResponse
    {
        $this->authorize('update', $quote);

        $action->handle($quote);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote reopened.')]);

        return to_route('quotes.show', $quote);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Quote $quote, bool $withItems): array
    {
        return [
            'id' => $quote->id,
            'number' => $quote->number,
            'status' => $quote->status->value,
            'status_label' => $quote->status->label(),
            'is_terminal' => $quote->status->isTerminal(),
            'is_draft' => $quote->status === QuoteStatus::Draft,
            'deal' => [
                'id' => $quote->deal->id,
                'title' => $quote->deal->title,
            ],
            'issue_date' => $quote->issue_date?->toDateString(),
            'valid_until' => $quote->valid_until?->toDateString(),
            'tax_rate' => $quote->tax_rate,
            'subtotal_minor' => $quote->subtotal_minor->minorUnits,
            'tax_minor' => $quote->tax_minor->minorUnits,
            'total_minor' => $quote->total_minor->minorUnits,
            'bill_to_company_name' => $quote->bill_to_company_name,
            'bill_to_address' => $quote->bill_to_address,
            'bill_to_contact_name' => $quote->bill_to_contact_name,
            'bill_to_contact_email' => $quote->bill_to_contact_email,
            'notes' => $quote->notes,
            'terms' => $quote->terms,
            'owner' => $quote->owner === null ? null : [
                'id' => $quote->owner->id,
                'name' => $quote->owner->name,
            ],
            'items' => $withItems ? $quote->items->map(fn (QuoteItem $item): array => [
                'id' => $item->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price_minor' => $item->unit_price_minor->minorUnits,
                'line_total_minor' => $item->line_total_minor->minorUnits,
            ])->all() : [],
            'created_at' => $quote->created_at?->toIso8601String(),
            'updated_at' => $quote->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (QuoteStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            QuoteStatus::cases(),
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function ownerOptions(): array
    {
        return array_values(User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])
            ->all());
    }

    /**
     * @return list<array{id: int, title: string, company_name: string}>
     */
    private function dealOptions(): array
    {
        return array_values(Deal::query()
            ->with('company:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'company_id'])
            ->map(fn (Deal $deal): array => [
                'id' => $deal->id,
                'title' => $deal->title,
                'company_name' => $deal->company->name,
            ])
            ->all());
    }
}
