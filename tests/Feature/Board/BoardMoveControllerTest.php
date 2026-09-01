<?php

declare(strict_types=1);

namespace Tests\Feature\Board;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * `POST /api/v1/boards/{entity}/{id}/move` — the plain JSON route (ADR-0006).
 * Every assertion here is against a real status code and a real JSON body,
 * never a redirect, which is the entire reason this route isn't Inertia.
 */
class BoardMoveControllerTest extends TestCase
{
    use RefreshDatabase;

    private function moveUrl(Company $company): string
    {
        return "/api/v1/boards/companies/{$company->id}/move";
    }

    public function test_guests_receive_a_json_401_not_a_redirect(): void
    {
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->postJson($this->moveUrl($company), ['status' => CompanyStatus::Prospect->value])
            ->assertUnauthorized();
    }

    public function test_a_valid_move_returns_204_and_persists(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->postJson($this->moveUrl($company), ['status' => CompanyStatus::Prospect->value])
            ->assertNoContent();

        $this->assertSame(CompanyStatus::Prospect, $company->fresh()->status);
    }

    public function test_an_illegal_transition_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($company), ['status' => CompanyStatus::Customer->value]);

        $response->assertStatus(422);
        expect($response->headers->get('content-type'))->toContain('application/problem+json');
        $response->assertJson(['title' => 'illegal_status_transition']);
    }

    public function test_an_unknown_status_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $response = $this->actingAs($user)
            ->postJson($this->moveUrl($company), ['status' => 'not-a-real-status']);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'unknown_board_status']);
    }

    public function test_missing_status_fails_shape_validation(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->postJson($this->moveUrl($company), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_a_neighbour_outside_the_target_column_returns_a_problem_json_422(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $strangerColumnCard = Company::factory()->create(['status' => CompanyStatus::Customer]);

        $response = $this->actingAs($user)->postJson($this->moveUrl($company), [
            'status' => CompanyStatus::Prospect->value,
            'before_id' => $strangerColumnCard->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['title' => 'invalid_board_neighbour']);
    }

    public function test_a_soft_deleted_company_cannot_be_moved(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);
        $company->delete();

        $this->actingAs($user)
            ->postJson($this->moveUrl($company), ['status' => CompanyStatus::Prospect->value])
            ->assertNotFound();
    }

    public function test_a_user_without_the_update_ability_receives_403(): void
    {
        // CompanyPolicy::update() is unconditionally true today (reads and
        // writes are never owner-scoped) — there is no real user for whom
        // this board move is currently forbidden. Swapping in a
        // deny-everything policy for this one test exercises the
        // authorization branch the controller really has, the same way
        // Tests\Fixtures\CompanyWithDependents exercises DeleteCompany's
        // dependent branch before any entity actually depends on a company.
        $denyAll = new class
        {
            public function update(User $user, Company $company): bool
            {
                return false;
            }
        };

        Gate::policy(Company::class, $denyAll::class);

        $user = User::factory()->create();
        $company = Company::factory()->create(['status' => CompanyStatus::Lead]);

        $this->actingAs($user)
            ->postJson($this->moveUrl($company), ['status' => CompanyStatus::Prospect->value])
            ->assertForbidden();
    }

    public function test_an_unknown_board_entity_is_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/boards/not-a-real-entity/1/move', ['status' => 'lead'])
            ->assertNotFound();
    }
}
