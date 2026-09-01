<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;

Route::get('v1/health', fn (): JsonResponse => response()->json(['status' => 'ok']))->name('health');
