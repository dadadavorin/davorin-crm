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

class DealUpdateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Company $company, DealStage $stage = DealStage::Qualified): array
    {
        return [
            'title' => 'Renamed deal',
            'value' => '2500.00',
            'stage' => $stage->value,
            'company_id' => $company->id,
        ];
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $deal = Deal::factory()->create();

        $this->put(route('deals.update', $deal), $this->validPayload($deal->company))
            ->assertRedirect(route('login'));
    }

    public function test_any_authenticated_user_can_update_a_deal_they_do_not_own(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create(['owner_id' => $owner->id]);

        $this->actingAs($stranger)
            ->put(route('deals.update', $deal), $this->validPayload($deal->company))
            ->assertRedirect(route('deals.show', $deal));

        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'title' => 'Renamed deal']);
    }

    public function test_money_round_trips_through_update(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();

        $this->actingAs($user)->put(route('deals.update', $deal), $this->validPayload($deal->company));

        $this->assertSame(250_000, $deal->fresh()->value_minor?->minorUnits);
    }

    public function test_title_is_required(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $payload = $this->validPayload($deal->company);
        unset($payload['title']);

        $this->actingAs($user)
            ->put(route('deals.update', $deal), $payload)
            ->assertSessionHasErrors('title');
    }

    public function test_stage_is_required(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $payload = $this->validPayload($deal->company);
        unset($payload['stage']);

        $this->actingAs($user)
            ->put(route('deals.update', $deal), $payload)
            ->assertSessionHasErrors('stage');
    }

    public function test_a_deal_can_be_moved_to_a_different_company(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $newCompany = Company::factory()->create();

        $this->actingAs($user)->put(route('deals.update', $deal), $this->validPayload($newCompany));

        $this->assertSame($newCompany->id, $deal->fresh()->company_id);
    }

    public function test_a_soft_deleted_deal_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();
        $deal->delete();

        $this->actingAs($user)
            ->put(route('deals.update', $deal), $this->validPayload($deal->company))
            ->assertNotFound();
    }

    public function test_a_contact_from_a_different_company_is_rejected_on_update(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();
        $otherCompany = Company::factory()->create();
        $contact = Contact::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($user)
            ->put(route('deals.update', $deal), [
                ...$this->validPayload($deal->company),
                'primary_contact_id' => $contact->id,
            ])
            ->assertSessionHasErrors('primary_contact_id');
    }

    public function test_a_non_terminal_stage_transition_via_edit_is_accepted(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::New)->create();

        $this->actingAs($user)->put(
            route('deals.update', $deal),
            $this->validPayload($deal->company, DealStage::Qualified),
        );

        $this->assertSame(DealStage::Qualified, $deal->fresh()->stage);
    }

    public function test_moving_a_won_deal_to_a_non_negotiation_stage_is_rejected(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->stage(DealStage::Won)->create();

        $this->actingAs($user)
            ->put(route('deals.update', $deal), $this->validPayload($deal->company, DealStage::New))
            ->assertRedirect()
            ->assertSessionHasErrors('illegal_status_transition');

        $this->assertSame(DealStage::Won, $deal->fresh()->stage);
    }
}
