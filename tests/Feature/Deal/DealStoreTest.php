<?php

declare(strict_types=1);

namespace Tests\Feature\Deal;

use App\Enums\DealStage;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Company $company): array
    {
        return [
            'title' => 'New CRM rollout',
            'value' => '15000.50',
            'stage' => DealStage::Qualified->value,
            'expected_close_date' => '2026-12-01',
            'company_id' => $company->id,
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $company = Company::factory()->create();

        $this->post(route('deals.store'), $this->validPayload($company))
            ->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_create_a_deal(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $response = $this->actingAs($user)->post(route('deals.store'), $this->validPayload($company));

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();
        $response->assertRedirect(route('deals.show', $deal));

        $this->assertSame(DealStage::Qualified, $deal->stage);
        $this->assertSame($company->id, $deal->company_id);
    }

    public function test_money_round_trips_through_create(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('deals.store'), $this->validPayload($company));

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();

        $this->assertSame(1_500_050, $deal->value_minor?->minorUnits);
    }

    public function test_a_deal_can_be_created_without_a_value(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload($company);
        unset($payload['value']);

        $this->actingAs($user)->post(route('deals.store'), $payload);

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();
        $this->assertNull($deal->value_minor);
    }

    public function test_the_creator_becomes_the_owner_when_none_is_given(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)->post(route('deals.store'), $this->validPayload($company));

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();
        $this->assertSame($user->id, $deal->owner_id);
    }

    public function test_omitting_stage_defaults_to_new(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload($company);
        unset($payload['stage']);

        $this->actingAs($user)->post(route('deals.store'), $payload);

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();
        $this->assertSame(DealStage::New, $deal->stage);
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload($company);
        unset($payload['title']);

        $this->actingAs($user)
            ->post(route('deals.store'), $payload)
            ->assertSessionHasErrors('title');
    }

    public function test_a_deal_requires_a_company(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $payload = $this->validPayload($company);
        unset($payload['company_id']);

        $this->actingAs($user)
            ->post(route('deals.store'), $payload)
            ->assertSessionHasErrors('company_id');

        $this->assertDatabaseMissing('deals', ['title' => 'New CRM rollout']);
    }

    public function test_an_unknown_company_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->post(route('deals.store'), [...$this->validPayload($company), 'company_id' => 999_999])
            ->assertSessionHasErrors('company_id');
    }

    public function test_a_malformed_value_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->post(route('deals.store'), [...$this->validPayload($company), 'value' => '12.345'])
            ->assertSessionHasErrors('value');
    }

    public function test_an_invalid_stage_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($user)
            ->post(route('deals.store'), [...$this->validPayload($company), 'stage' => 'not-a-stage'])
            ->assertSessionHasErrors('stage');
    }

    public function test_a_contact_belonging_to_the_deals_company_is_accepted(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->post(route('deals.store'), [
            ...$this->validPayload($company),
            'primary_contact_id' => $contact->id,
        ]);

        $deal = Deal::query()->where('title', 'New CRM rollout')->firstOrFail();
        $this->assertSame($contact->id, $deal->primary_contact_id);
    }

    public function test_a_contact_from_a_different_company_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($user)
            ->post(route('deals.store'), [
                ...$this->validPayload($company),
                'primary_contact_id' => $contact->id,
            ])
            ->assertSessionHasErrors('primary_contact_id');

        $this->assertDatabaseMissing('deals', ['title' => 'New CRM rollout']);
    }

    public function test_a_contact_with_no_company_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();
        $contact = Contact::factory()->withoutCompany()->create();

        $this->actingAs($user)
            ->post(route('deals.store'), [
                ...$this->validPayload($company),
                'primary_contact_id' => $contact->id,
            ])
            ->assertSessionHasErrors('primary_contact_id');
    }
}
