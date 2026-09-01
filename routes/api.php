<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BoardMoveController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('v1/health', fn (): JsonResponse => response()->json(['status' => 'ok']))->name('health');

// The one deliberate JSON API surface (ADR-0006): session-authenticated via
// the 'web' middleware group, same as the rest of the app, but never
// Inertia — a domain rejection here must come back as a real 422 body a
// `fetch` call can branch on, not a 302 with flashed session errors.
Route::middleware(['web', 'auth'])
    ->post('v1/boards/{entity}/{id}/move', BoardMoveController::class)
    ->whereNumber('id')
    ->name('api.boards.move');
