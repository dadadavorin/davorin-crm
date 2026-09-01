<?php

declare(strict_types=1);

use App\Exceptions\RecordHasDependentsException;
use Illuminate\Support\Facades\Route;

function throwsRecordHasDependents(): void
{
    Route::get('/__test/domain-exception', function (): never {
        throw new RecordHasDependentsException('company', ['contacts' => 3]);
    })->middleware('web');
}

test('the Inertia renderer redirects back with flashed errors, not a JSON body', function (): void {
    throwsRecordHasDependents();

    $response = $this->from('/previous')->get('/__test/domain-exception');

    $response->assertRedirect('/previous');
    $response->assertSessionHasErrors(['record_has_dependents']);
    expect($response->headers->get('content-type'))->not->toContain('application/problem+json');
});

test('the JSON renderer returns a problem+json 422 body, not a redirect', function (): void {
    throwsRecordHasDependents();

    $response = $this->getJson('/__test/domain-exception');

    $response->assertStatus(422);
    expect($response->headers->get('content-type'))->toContain('application/problem+json');
    $response->assertJson([
        'title' => 'record_has_dependents',
        'status' => 422,
    ]);
    $response->assertJsonPath('detail', 'Cannot delete this company: 3 live contacts depend on it.');
});
