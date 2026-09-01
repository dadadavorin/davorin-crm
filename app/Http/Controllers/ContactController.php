<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Contact\CreateContact;
use App\Actions\Contact\DeleteContact;
use App\Actions\Contact\UpdateContact;
use App\Board\BoardBuilder;
use App\Board\BoardColumn;
use App\Enums\ContactStatus;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    /**
     * @var list<string>
     */
    private const array SORTABLE_COLUMNS = ['first_name', 'last_name', 'status', 'job_title', 'created_at'];

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Contact::class);

        $search = trim((string) $request->string('search'));
        $sort = in_array($request->string('sort')->value(), self::SORTABLE_COLUMNS, true)
            ? $request->string('sort')->value()
            : 'last_name';
        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';

        $contacts = Contact::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy($sort, $direction)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('contacts/index', [
            'contacts' => $contacts->through(fn (Contact $contact): array => $this->present($contact)),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    public function board(BoardBuilder $builder): Response
    {
        $this->authorize('viewAny', Contact::class);

        return Inertia::render('contacts/board', [
            'columns' => array_map(fn (BoardColumn $column): array => $column->toArray(), $builder->build(Contact::class)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Contact::class);

        return Inertia::render('contacts/create', [
            'statuses' => $this->statusOptions(),
            'owners' => $this->ownerOptions(),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function store(StoreContactRequest $request, CreateContact $action): RedirectResponse
    {
        $user = Auth::user() ?? abort(401);

        $contact = $action->handle($request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact created.')]);

        return to_route('contacts.show', $contact);
    }

    public function show(Contact $contact): Response
    {
        $this->authorize('view', $contact);

        $contact->load(['company:id,name', 'owner:id,name']);

        $deals = Deal::query()
            ->where('primary_contact_id', $contact->id)
            ->orderBy('title')
            ->get(['id', 'title', 'stage', 'value_minor']);

        return Inertia::render('contacts/show', [
            'contact' => $this->present($contact),
            'deals' => $deals->map(fn (Deal $deal): array => [
                'id' => $deal->id,
                'title' => $deal->title,
                'stage' => $deal->stage->value,
                'stage_label' => $deal->stage->label(),
                'value_minor' => $deal->value_minor?->minorUnits,
            ])->all(),
        ]);
    }

    public function edit(Contact $contact): Response
    {
        $this->authorize('update', $contact);

        $contact->load(['company:id,name', 'owner:id,name']);

        return Inertia::render('contacts/edit', [
            'contact' => $this->present($contact),
            'statuses' => $this->statusOptions(),
            'owners' => $this->ownerOptions(),
            'companies' => $this->companyOptions(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact, UpdateContact $action): RedirectResponse
    {
        $action->handle($contact, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact updated.')]);

        return to_route('contacts.show', $contact);
    }

    public function destroy(Contact $contact, DeleteContact $action): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $action->handle($contact);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Contact deleted.')]);

        return to_route('contacts.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'email' => $contact->email?->value,
            'phone' => $contact->phone,
            'job_title' => $contact->job_title,
            'status' => $contact->status->value,
            'status_label' => $contact->status->label(),
            'company' => $contact->company === null ? null : [
                'id' => $contact->company->id,
                'name' => $contact->company->name,
            ],
            'owner' => $contact->owner === null ? null : [
                'id' => $contact->owner->id,
                'name' => $contact->owner->name,
            ],
            'created_at' => $contact->created_at?->toIso8601String(),
            'updated_at' => $contact->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (ContactStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            ContactStatus::cases(),
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
}
