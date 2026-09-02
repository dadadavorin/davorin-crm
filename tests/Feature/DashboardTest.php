<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_dashboard_reports_real_record_counts()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $company = Company::factory()->create();
        Company::factory()->create();
        Contact::factory()->count(3)->for($company)->create();
        $deal = Deal::factory()->for($company)->create();
        Quote::factory()->count(4)->for($deal)->create();

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('dashboard')
            ->where('stats.companies', 2)
            ->where('stats.contacts', 3)
            ->where('stats.deals', 1)
            ->where('stats.quotes', 4)
        );
    }
}
