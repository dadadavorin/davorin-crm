<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuoteStatus;
use App\Exceptions\QuoteNotEditableException;
use App\Support\Money;
use App\Support\MoneyCast;
use Database\Factories\QuoteItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A snapshot: `description` and `unit_price_minor` are frozen at write time
 * and never joined from a product catalogue or anywhere else (T8) — a
 * historical quote's line items can never change because something
 * upstream did.
 *
 * @property int $id
 * @property int $quote_id
 * @property int $sort_order
 * @property string $description
 * @property int $quantity
 * @property Money $unit_price_minor
 * @property Money $line_total_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Quote $quote
 */
#[Fillable(['quote_id', 'sort_order', 'description', 'quantity', 'unit_price_minor', 'line_total_minor'])]
class QuoteItem extends Model
{
    /** @use HasFactory<QuoteItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_minor' => MoneyCast::class,
            'line_total_minor' => MoneyCast::class,
        ];
    }

    /**
     * Mirrors `Quote::booted()`'s freeze guard from the item side: once the
     * parent quote has left `Draft`, no item write — create, update or
     * delete, including a direct `save()`/`delete()` that bypasses
     * `UpdateQuote` entirely — is accepted.
     *
     * Reads the quote's status with a fresh query rather than through the
     * `quote()` relation, which may already be cached with a status from
     * before the quote itself was transitioned elsewhere in the same
     * request — a stale cache here would be exactly the bypass this guard
     * exists to close.
     */
    protected static function booted(): void
    {
        $guard = function (QuoteItem $item): void {
            $status = Quote::query()->whereKey($item->quote_id)->value('status');
            $statusValue = $status instanceof QuoteStatus ? $status->value : $status;

            if ($statusValue !== QuoteStatus::Draft->value) {
                throw new QuoteNotEditableException($item->quote_id);
            }
        };

        static::saving($guard);
        static::deleting($guard);
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
