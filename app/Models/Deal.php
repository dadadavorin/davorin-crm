<?php

declare(strict_types=1);

namespace App\Models;

use App\Board\HasBoardStatus;
use App\Enums\DealStage;
use App\Exceptions\IllegalStatusTransitionException;
use App\Support\Money;
use App\Support\MoneyCast;
use Database\Factories\DealFactory;
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
 * @property string $title
 * @property Money|null $value_minor
 * @property DealStage $stage
 * @property string $position
 * @property Carbon|null $expected_close_date
 * @property int $company_id
 * @property int|null $primary_contact_id
 * @property int|null $owner_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company $company
 */
#[Fillable(['title', 'value_minor', 'stage', 'position', 'expected_close_date', 'company_id', 'primary_contact_id', 'owner_id'])]
class Deal extends Model implements HasBoardStatus
{
    /** @use HasFactory<DealFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'value_minor' => MoneyCast::class,
            'expected_close_date' => 'date',
            'position' => 'decimal:10',
        ];
    }

    /**
     * `Won` and `Lost` are terminal (T7): once a deal reaches either, only a
     * write that lands it on `Negotiation` is accepted — the one legal
     * reopen target (`ReopenDeal`). Every other write path, including a
     * direct `save()`, goes through this same event, so none of them can
     * bypass it the way a Form Request check alone could be skipped by
     * calling the model directly.
     */
    protected static function booted(): void
    {
        static::saving(function (Deal $deal): void {
            if (! $deal->exists || ! $deal->isDirty('stage')) {
                return;
            }

            $raw = $deal->getRawOriginal('stage');

            if (! is_string($raw)) {
                throw new LogicException('Attribute "stage" is not a string.');
            }

            $original = DealStage::from($raw);
            $target = $deal->stage;

            if ($original->isTerminal() && $target !== DealStage::Negotiation) {
                throw new IllegalStatusTransitionException($original, $target);
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<Contact, $this>
     */
    public function primaryContact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function boardStatusEnum(): string
    {
        return DealStage::class;
    }

    public static function boardStatusColumn(): string
    {
        return 'stage';
    }

    /**
     * @return list<string>
     */
    public static function boardCardRelations(): array
    {
        return ['company:id,name', 'primaryContact:id,first_name,last_name', 'owner:id,name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBoardCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'value_minor' => $this->value_minor?->minorUnits,
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],
            'primary_contact' => $this->primaryContact === null ? null : [
                'id' => $this->primaryContact->id,
                'name' => trim("{$this->primaryContact->first_name} {$this->primaryContact->last_name}"),
            ],
            'owner' => $this->owner === null ? null : [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
            'position' => $this->position,
        ];
    }

    /**
     * Live (non-deleted) record counts that block this deal's deletion,
     * keyed by plural relation label. `quotes.deal_id` is a required FK
     * (T8), the same asymmetry as `Company::dependentCounts()` — a nullable
     * FK nulls out on delete instead (see `DeleteContact`).
     *
     * @return array<string, int>
     */
    public function dependentCounts(): array
    {
        return array_filter([
            'quotes' => $this->quotes()->count(),
        ], fn (int $count): bool => $count > 0);
    }

    /**
     * Case-insensitive search on `title`, escaping the `ILIKE`
     * metacharacters `\`, `%` and `_` with an explicit `ESCAPE` clause so a
     * literal percent or underscore in the search box can't turn into a
     * wildcard.
     *
     * @param  Builder<Deal>  $query
     * @return Builder<Deal>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->whereRaw('title ILIKE ? ESCAPE ?', ["%{$escaped}%", '\\']);
    }
}
