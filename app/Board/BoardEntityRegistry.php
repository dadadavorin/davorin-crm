<?php

declare(strict_types=1);

namespace App\Board;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The single place mapping a board move URL's `{entity}` segment to the
 * model class it moves. T8 adds its entry here and nowhere else — the move
 * route, the controller and `MoveCardAction` are already generic.
 */
final class BoardEntityRegistry
{
    /**
     * @var array<string, class-string<Model&HasBoardStatus>>
     */
    private const array MAP = [
        'companies' => Company::class,
        'contacts' => Contact::class,
        'deals' => Deal::class,
    ];

    /**
     * @return class-string<Model&HasBoardStatus>
     */
    public static function resolve(string $entity): string
    {
        return self::MAP[$entity] ?? throw new NotFoundHttpException("Unknown board entity \"{$entity}\".");
    }
}
