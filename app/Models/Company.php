<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Support\EmailAddress;
use App\Support\EmailAddressCast;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property int|null $owner_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'status', 'industry', 'website', 'email', 'phone', 'address', 'notes', 'owner_id'])]
class Company extends Model
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
     * Live (non-deleted) record counts that block this company's deletion,
     * keyed by plural relation label. Empty until a later task gives the
     * company dependents — contacts (T6) and deals (T7) add entries here,
     * not new call sites in `DeleteCompany`.
     *
     * @return array<string, int>
     */
    public function dependentCounts(): array
    {
        return [];
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
