<?php

declare(strict_types=1);

namespace App\Board;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The single place mapping a board move URL's `{entity}` segment to the
 * model class it moves. T6, T7 and T8 add their entry here and nowhere
 * else — the move route, the controller and `MoveCardAction` are already
 * generic.
 */
final class BoardEntityRegistry
{
    /**
     * @var array<string, class-string<Model&HasBoardStatus>>
     */
    private const array MAP = [
        'companies' => Company::class,
    ];

    /**
     * @return class-string<Model&HasBoardStatus>
     */
    public static function resolve(string $entity): string
    {
        return self::MAP[$entity] ?? throw new NotFoundHttpException("Unknown board entity \"{$entity}\".");
    }
}
