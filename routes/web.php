<?php

declare(strict_types=1);

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\QuoteController;
use App\Support\InertiaRoute;
use Illuminate\Support\Facades\Route;

InertiaRoute::get('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('companies/board', [CompanyController::class, 'board'])->name('companies.board');
    Route::resource('companies', CompanyController::class);

    Route::get('contacts/board', [ContactController::class, 'board'])->name('contacts.board');
    Route::resource('contacts', ContactController::class);

    Route::get('deals/board', [DealController::class, 'board'])->name('deals.board');
    Route::post('deals/{deal}/reopen', [DealController::class, 'reopen'])->name('deals.reopen');
    Route::post('deals/{deal}/quotes', [DealController::class, 'storeQuote'])->name('deals.quotes.store');
    Route::resource('deals', DealController::class);

    Route::get('quotes/board', [QuoteController::class, 'board'])->name('quotes.board');
    Route::get('quotes/{quote}/pdf', [QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::post('quotes/{quote}/reopen', [QuoteController::class, 'reopen'])->name('quotes.reopen');
    Route::resource('quotes', QuoteController::class);
});

require __DIR__.'/settings.php';
