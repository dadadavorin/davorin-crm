<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Tests\TestCase;

class DisabledAuthRoutesTest extends TestCase
{
    public function test_registration_routes_are_not_available(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_password_reset_routes_are_not_available(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password')->assertNotFound();
        $this->get('/reset-password/some-token')->assertNotFound();
        $this->post('/reset-password')->assertNotFound();
    }
}
