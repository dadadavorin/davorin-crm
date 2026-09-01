<?php

declare(strict_types=1);

use App\Support\InertiaRoute;
use Illuminate\Support\Facades\Route;

InertiaRoute::get('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    InertiaRoute::get('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
