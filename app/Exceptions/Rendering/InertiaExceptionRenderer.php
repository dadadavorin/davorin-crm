<?php

declare(strict_types=1);

namespace App\Exceptions\Rendering;

use App\Exceptions\DomainException;
use App\Exceptions\ExceptionMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Renders a domain exception the way an Inertia request expects: a
 * redirect back to the previous page with the error flashed to the
 * session, never a status code with a JSON body. See ADR-0006.
 */
final class InertiaExceptionRenderer
{
    public function render(DomainException $e, Request $request): RedirectResponse
    {
        $mapping = ExceptionMap::resolve($e);

        return redirect()->back()->withErrors([$mapping->messageKey => $e->getMessage()]);
    }
}
