<?php

declare(strict_types=1);

test('the health endpoint reports ok', function (): void {
    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertExactJson(['status' => 'ok']);
});
