<?php

declare(strict_types=1);

namespace App\Models;

use App\Board\HasBoardStatus;
use App\Enums\CompanyStatus;
use App\Support\EmailAddress;
use App\Support\EmailAddressCast;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property CompanyStatus $status
 * @property string|null $industry
 * @property string|null $website
 * @property EmailAddress|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $notes
 * @property string $position
 * @property int|null $owner_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'status', 'industry', 'website', 'email', 'phone', 'address', 'notes', 'owner_id', 'position'])]
class Company extends Model implements HasBoardStatus
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'email' => EmailAddressCast::class,
            'position' => 'decimal:10',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Contact, $this>
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    /**
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public static function boardStatusEnum(): string
    {
        return CompanyStatus::class;
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
        return ['owner:id,name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBoardCard(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'industry' => $this->industry,
            'owner' => $this->owner === null ? null : [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
            'position' => $this->position,
        ];
    }

    /**
     * Live (non-deleted) record counts that block this company's deletion,
     * keyed by plural relation label.
     *
     * @return array<string, int>
     */
    public function dependentCounts(): array
    {
        return array_filter([
            'contacts' => $this->contacts()->count(),
            'deals' => $this->deals()->count(),
        ], fn (int $count): bool => $count > 0);
    }

    /**
     * Case-insensitive prefix/substring search on `name`, escaping the
     * `ILIKE` metacharacters `\`, `%` and `_` with an explicit `ESCAPE`
     * clause so a literal percent or underscore in the search box can't
     * turn into a wildcard.
     *
     * @param  Builder<Company>  $query
     * @return Builder<Company>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->whereRaw('name ILIKE ? ESCAPE ?', ["%{$escaped}%", '\\']);
    }
}
