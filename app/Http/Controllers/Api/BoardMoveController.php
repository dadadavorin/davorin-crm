<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Board\BoardEntityRegistry;
use App\Board\MoveCardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Board\MoveCardRequest;
use Illuminate\Http\Response;

/**
 * `POST /api/v1/boards/{entity}/{id}/move` — the one deliberate JSON API
 * surface named in ADR-0006. A plain route, not Inertia: a drag-and-drop
 * reorder needs a real status code to branch its optimistic-update revert
 * on, and Inertia would turn a rejection into a 302 with flashed errors
 * instead. Hit with `fetch`/axios from `<KanbanBoard>`, never `router.post`.
 */
final class BoardMoveController extends Controller
{
    public function __invoke(string $entity, int $id, MoveCardRequest $request, MoveCardAction $action): Response
    {
        $modelClass = BoardEntityRegistry::resolve($entity);

        $card = $modelClass::query()->findOrFail($id);

        $this->authorize('update', $card);

        $action->handle(
            $modelClass,
            $id,
            $request->string('status')->value(),
            $request->filled('before_id') ? $request->integer('before_id') : null,
            $request->filled('after_id') ? $request->integer('after_id') : null,
        );

        return response()->noContent();
    }
}
