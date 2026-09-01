<?php

declare(strict_types=1);

use App\Support\InertiaRoute;
use Illuminate\Support\Facades\Route;

InertiaRoute::get('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    InertiaRoute::get('dashboard', 'dashboard', [
        'stats' => [
            'companies' => 0,
            'contacts' => 0,
            'deals' => 0,
            'quotes' => 0,
        ],
    ])->name('dashboard');

    InertiaRoute::get('companies', 'placeholder', ['title' => 'Companies'])->name('companies.index');
    InertiaRoute::get('contacts', 'placeholder', ['title' => 'Contacts'])->name('contacts.index');
    InertiaRoute::get('deals', 'placeholder', ['title' => 'Deals'])->name('deals.index');
    InertiaRoute::get('quotes', 'placeholder', ['title' => 'Quotes'])->name('quotes.index');
});

require __DIR__.'/settings.php';
