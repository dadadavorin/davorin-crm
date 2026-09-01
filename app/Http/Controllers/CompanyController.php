<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Company\CreateCompany;
use App\Actions\Company\DeleteCompany;
use App\Actions\Company\UpdateCompany;
use App\Board\BoardBuilder;
use App\Board\BoardColumn;
use App\Enums\CompanyStatus;
use App\Http\Requests\Company\StoreCompanyRequest;
use App\Http\Requests\Company\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class CompanyController extends Controller
{
    /**
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = ['name', 'status', 'industry', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $search = trim((string) $request->string('search'));
        $sort = in_array($request->string('sort')->value(), self::SORTABLE_COLUMNS, true)
            ? $request->string('sort')->value()
            : 'name';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $companies = Company::query()
            ->with('owner:id,name')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('companies/index', [
            'companies' => $companies->through(fn (Company $company): array => $this->present($company)),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function board(BoardBuilder $builder): Response
    {
        $this->authorize('viewAny', Company::class);

        return Inertia::render('companies/board', [
            'columns' => array_map(fn (BoardColumn $column): array => $column->toArray(), $builder->build(Company::class)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Company::class);

        return Inertia::render('companies/create', [
            'statuses' => $this->statusOptions(),
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function store(StoreCompanyRequest $request, CreateCompany $action): RedirectResponse
    {
        $user = Auth::user() ?? abort(401);

        $company = $action->handle($request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company created.')]);

        return to_route('companies.show', $company);
    }

    public function show(Company $company): Response
    {
        $this->authorize('view', $company);

        $company->load('owner:id,name');

        $contacts = Contact::query()
            ->where('company_id', $company->id)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name', 'status']);

        $deals = Deal::query()
            ->where('company_id', $company->id)
            ->orderBy('title')
            ->get(['id', 'title', 'stage', 'value_minor']);

        return Inertia::render('companies/show', [
            'company' => $this->present($company),
            'contacts' => $contacts->map(fn (Contact $contact): array => [
                'id' => $contact->id,
                'name' => trim("{$contact->first_name} {$contact->last_name}"),
                'status' => $contact->status->value,
                'status_label' => $contact->status->label(),
            ])->all(),
            'deals' => $deals->map(fn (Deal $deal): array => [
                'id' => $deal->id,
                'title' => $deal->title,
                'stage' => $deal->stage->value,
                'stage_label' => $deal->stage->label(),
                'value_minor' => $deal->value_minor?->minorUnits,
            ])->all(),
        ]);
    }

    public function edit(Company $company): Response
    {
        $this->authorize('update', $company);

        $company->load('owner:id,name');

        return Inertia::render('companies/edit', [
            'company' => $this->present($company),
            'statuses' => $this->statusOptions(),
            'owners' => $this->ownerOptions(),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company, UpdateCompany $action): RedirectResponse
    {
        $action->handle($company, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company updated.')]);

        return to_route('companies.show', $company);
    }

    public function destroy(Company $company, DeleteCompany $action): RedirectResponse
    {
        $this->authorize('delete', $company);

        $action->handle($company);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company deleted.')]);

        return to_route('companies.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'status' => $company->status->value,
            'status_label' => $company->status->label(),
            'industry' => $company->industry,
            'website' => $company->website,
            'email' => $company->email?->value,
            'phone' => $company->phone,
            'address' => $company->address,
            'notes' => $company->notes,
            'owner' => $company->owner === null ? null : [
                'id' => $company->owner->id,
                'name' => $company->owner->name,
            ],
            'created_at' => $company->created_at?->toIso8601String(),
            'updated_at' => $company->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CompanyStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            CompanyStatus::cases(),
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
}
