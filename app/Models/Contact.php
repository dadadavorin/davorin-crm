<?php

declare(strict_types=1);

namespace App\Models;

use App\Board\HasBoardStatus;
use App\Enums\ContactStatus;
use App\Support\EmailAddress;
use App\Support\EmailAddressCast;
use Database\Factories\ContactFactory;
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
 * @property string $first_name
 * @property string $last_name
 * @property EmailAddress|null $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property ContactStatus $status
 * @property string $position
 * @property int|null $company_id
 * @property int|null $owner_id
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'job_title', 'status', 'position', 'company_id', 'owner_id'])]
class Contact extends Model implements HasBoardStatus
{
    /** @use HasFactory<ContactFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContactStatus::class,
            'email' => EmailAddressCast::class,
            'position' => 'decimal:10',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Deals this contact is the primary contact on. Deleting a contact
     * nulls `primary_contact_id` here rather than being refused
     * (`DeleteContact`) — the column is nullable by design.
     *
     * @return HasMany<Deal, $this>
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'primary_contact_id');
    }

    public static function boardStatusEnum(): string
    {
        return ContactStatus::class;
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
        return ['company:id,name', 'owner:id,name'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toBoardCard(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'job_title' => $this->job_title,
            'company' => $this->company === null ? null : [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ],
            'owner' => $this->owner === null ? null : [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
            ],
            'position' => $this->position,
        ];
    }

    /**
     * Case-insensitive search across the full name, escaping the `ILIKE`
     * metacharacters `\`, `%` and `_` with an explicit `ESCAPE` clause so a
     * literal percent or underscore in the search box can't turn into a
     * wildcard.
     *
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);

        return $query->whereRaw("(first_name || ' ' || last_name) ILIKE ? ESCAPE ?", ["%{$escaped}%", '\\']);
    }
}
