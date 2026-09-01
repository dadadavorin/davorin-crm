<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Deal\CreateDeal;
use App\Actions\Deal\DeleteDeal;
use App\Actions\Deal\ReopenDeal;
use App\Actions\Deal\UpdateDeal;
use App\Actions\Quote\CreateQuoteForDeal;
use App\Board\BoardBuilder;
use App\Board\BoardColumn;
use App\Enums\DealStage;
use App\Http\Requests\Deal\StoreDealRequest;
use App\Http\Requests\Deal\StoreQuoteForDealRequest;
use App\Http\Requests\Deal\UpdateDealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class DealController extends Controller
{
    /**
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = ['title', 'stage', 'value_minor', 'expected_close_date', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Deal::class);

        $search = trim((string) $request->string('search'));
        $sort = in_array($request->string('sort')->value(), self::SORTABLE_COLUMNS, true)
            ? $request->string('sort')->value()
            : 'created_at';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $deals = Deal::query()
            ->with(['company:id,name', 'primaryContact:id,first_name,last_name', 'owner:id,name'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('deals/index', [
            'deals' => $deals->through(fn (Deal $deal): array => $this->present($deal)),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function board(BoardBuilder $builder): Response
    {
        $this->authorize('viewAny', Deal::class);

        return Inertia::render('deals/board', [
            'columns' => array_map(fn (BoardColumn $column): array => $column->toArray(), $builder->build(Deal::class)),
            'quoteDefaults' => [
                'valid_until' => CreateQuoteForDeal::defaultValidUntil(),
                'tax_rate' => CreateQuoteForDeal::defaultTaxRate(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Deal::class);

        return Inertia::render('deals/create', [
            'stages' => $this->stageOptions(),
            'owners' => $this->ownerOptions(),
            'companies' => $this->companyOptions(),
            'contacts' => $this->contactOptions(),
        ]);
    }

    public function store(StoreDealRequest $request, CreateDeal $action): RedirectResponse
    {
        $user = Auth::user() ?? abort(401);

        $deal = $action->handle($request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal created.')]);

        return to_route('deals.show', $deal);
    }

    public function storeQuote(Deal $deal, StoreQuoteForDealRequest $request, CreateQuoteForDeal $action): RedirectResponse
    {
        $user = Auth::user() ?? abort(401);

        $quote = $action->handle($deal, $request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Quote created.')]);

        return to_route('quotes.edit', $quote);
    }

    public function show(Deal $deal): Response
    {
        $this->authorize('view', $deal);

        $deal->load(['company:id,name', 'primaryContact:id,first_name,last_name', 'owner:id,name']);

        $quotes = Quote::query()
            ->where('deal_id', $deal->id)
            ->orderByDesc('created_at')
            ->get(['id', 'number', 'status', 'total_minor']);

        return Inertia::render('deals/show', [
            'deal' => $this->present($deal),
            'quotes' => $quotes->map(fn (Quote $quote): array => [
                'id' => $quote->id,
                'number' => $quote->number,
                'status' => $quote->status->value,
                'status_label' => $quote->status->label(),
                'total_minor' => $quote->total_minor->minorUnits,
            ])->all(),
        ]);
    }

    public function edit(Deal $deal): Response
    {
        $this->authorize('update', $deal);

        $deal->load(['company:id,name', 'primaryContact:id,first_name,last_name', 'owner:id,name']);

        return Inertia::render('deals/edit', [
            'deal' => $this->present($deal),
            'stages' => $this->stageOptions(),
            'owners' => $this->ownerOptions(),
            'companies' => $this->companyOptions(),
            'contacts' => $this->contactOptions(),
        ]);
    }

    public function update(UpdateDealRequest $request, Deal $deal, UpdateDeal $action): RedirectResponse
    {
        $action->handle($deal, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal updated.')]);

        return to_route('deals.show', $deal);
    }

    public function destroy(Deal $deal, DeleteDeal $action): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $action->handle($deal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal deleted.')]);

        return to_route('deals.index');
    }

    public function reopen(Deal $deal, ReopenDeal $action): RedirectResponse
    {
        $this->authorize('update', $deal);

        $action->handle($deal);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Deal reopened.')]);

        return to_route('deals.show', $deal);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Deal $deal): array
    {
        return [
            'id' => $deal->id,
            'title' => $deal->title,
            'value_minor' => $deal->value_minor?->minorUnits,
            'stage' => $deal->stage->value,
            'stage_label' => $deal->stage->label(),
            'is_terminal' => $deal->stage->isTerminal(),
            'expected_close_date' => $deal->expected_close_date?->toDateString(),
            'company' => [
                'id' => $deal->company->id,
                'name' => $deal->company->name,
            ],
            'primary_contact' => $deal->primaryContact === null ? null : [
                'id' => $deal->primaryContact->id,
                'name' => trim("{$deal->primaryContact->first_name} {$deal->primaryContact->last_name}"),
            ],
            'owner' => $deal->owner === null ? null : [
                'id' => $deal->owner->id,
                'name' => $deal->owner->name,
            ],
            'created_at' => $deal->created_at?->toIso8601String(),
            'updated_at' => $deal->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function stageOptions(): array
    {
        return array_map(
            fn (DealStage $stage): array => ['value' => $stage->value, 'label' => $stage->label()],
            DealStage::cases(),
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
     * @return list<array{id: int, name: string}>
     */
    private function companyOptions(): array
    {
        return array_values(Company::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Company $company): array => ['id' => $company->id, 'name' => $company->name])
            ->all());
    }

    /**
     * @return list<array{id: int, name: string, company_name: string|null}>
     */
    private function contactOptions(): array
    {
        return array_values(Contact::query()
            ->with('company:id,name')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'company_id'])
            ->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => trim("{$contact->first_name} {$contact->last_name}"),
                'company_name' => $contact->company?->name,
            ])
            ->all());
    }
}
