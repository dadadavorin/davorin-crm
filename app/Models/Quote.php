<?php

declare(strict_types=1);

namespace App\Models;

use App\Board\HasBoardStatus;
use App\Enums\QuoteStatus;
use App\Exceptions\IllegalStatusTransitionException;
use App\Exceptions\QuoteNotEditableException;
use App\Support\Money;
use App\Support\MoneyCast;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property string $number
 * @property QuoteStatus $status
 * @property int $deal_id
 * @property Carbon|null $issue_date
 * @property Carbon|null $valid_until
 * @property string $tax_rate
 * @property Money $subtotal_minor
 * @property Money $tax_minor
 * @property Money $total_minor
 * @property string $bill_to_company_name
 * @property string|null $bill_to_address
 * @property string|null $bill_to_contact_name
 * @property string|null $bill_to_contact_email
 * @property string|null $notes
 * @property string|null $terms
 * @property string $position
 * @property int|null $owner_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Deal $deal
 */
#[Fillable([
    'number', 'status', 'deal_id', 'issue_date', 'valid_until', 'tax_rate',
    'subtotal_minor', 'tax_minor', 'total_minor',
    'bill_to_company_name', 'bill_to_address', 'bill_to_contact_name', 'bill_to_contact_email',
    'notes', 'terms', 'position', 'owner_id',
])]
class Quote extends Model implements HasBoardStatus
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The line items, the money fields they sum to, and the customer block
     * are all frozen once the quote leaves `Draft` (T8) — see `booted()`.
     *
     * @var list<string>
     */
    private const array FROZEN_FIELDS = [
        'tax_rate', 'subtotal_minor', 'tax_minor', 'total_minor',
        'bill_to_company_name', 'bill_to_address', 'bill_to_contact_name', 'bill_to_contact_email',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'tax_rate' => 'decimal:4',
            'subtotal_minor' => MoneyCast::class,
            'tax_minor' => MoneyCast::class,
            'total_minor' => MoneyCast::class,
            'position' => 'decimal:10',
        ];
    }

    /**
     * Two rules, mirroring `Deal::booted()` (T7) for the first and adding a
     * second the deal guard has no equivalent of:
     *
     *   1. A terminal status (`Accepted`/`Rejected`/`Expired`) only accepts a
     *      write that lands back on `Sent` — the one legal reopen target
     *      (`ReopenQuote`). `Sent` itself can never revert to `Draft`, since
     *      nothing else here would stop it.
     *   2. From `Sent` onward, the line items, money fields and customer
     *      block are frozen (`FROZEN_FIELDS`) — `QuoteNotEditableException`
     *      rejects any write that touches one, including a direct `save()`.
     *
     * Both run on every save, not just ones routed through a Form Request or
     * `MoveCardAction`, so neither can be bypassed by calling the model
     * directly.
     */
    protected static function booted(): void
    {
        static::saving(function (Quote $quote): void {
            if (! $quote->exists) {
                return;
            }

            $raw = $quote->getRawOriginal('status');

            if (! is_string($raw)) {
                throw new LogicException('Attribute "status" is not a string.');
            }

            $original = QuoteStatus::from($raw);

            if ($quote->isDirty('status')) {
                $target = $quote->status;

                if ($original->isTerminal() && $target !== QuoteStatus::Sent) {
                    throw new IllegalStatusTransitionException($original, $target);
                }

                if ($original === QuoteStatus::Sent && $target === QuoteStatus::Draft) {
                    throw new IllegalStatusTransitionException($original, $target);
                }
            }

            if ($original !== QuoteStatus::Draft) {
                foreach (self::FROZEN_FIELDS as $field) {
                    if ($quote->isDirty($field)) {
                        throw new QuoteNotEditableException($quote->id);
                    }
                }
            }
        });
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<QuoteItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    public static function boardStatusEnum(): string
    {
        return QuoteStatus::class;
    }

    public static function boardStatusColumn(): string
    {
        return 'status';
    }

    /**
     * @return list<string>
     */
    public static function boardCardRelations(): array
    {
        return ['deal:id,title', 'owner:id,name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBoardCard(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'total_minor' => $this->total_minor->minorUnits,
            'valid_until' => $this->valid_until?->toDateString(),
            'deal' => [
                'id' => $this->deal->id,
                'title' => $this->deal->title,
            ],
            'owner' => $this->owner === null ? null : [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
            'position' => $this->position,
        ];
    }

    /**
     * Case-insensitive search on `number`, escaping the `ILIKE`
     * metacharacters `\`, `%` and `_` with an explicit `ESCAPE` clause so a
     * literal percent or underscore in the search box can't turn into a
     * wildcard.
     *
     * @param  Builder<Quote>  $query
     * @return Builder<Quote>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->whereRaw('number ILIKE ? ESCAPE ?', ["%{$escaped}%", '\\']);
    }
}
