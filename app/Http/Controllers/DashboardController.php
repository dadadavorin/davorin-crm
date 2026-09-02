<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Quote;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('dashboard', [
            'stats' => [
                'companies' => Company::query()->count(),
                'contacts' => Contact::query()->count(),
                'deals' => Deal::query()->count(),
                'quotes' => Quote::query()->count(),
            ],
        ]);
    }
}
