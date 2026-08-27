<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var list<string>
     *
     * `status` is deliberately excluded — it is an administrative field and
     * must never be settable from a profile form (§39, mass assignment).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'department',
        'phone',
        'avatar_path',
        'job_title',
        'locale',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'temporary_password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'password' => 'hashed',
            'temporary_password' => 'hashed',
            'temporary_password_expires_at' => 'datetime',
            'must_change_password' => 'boolean',
            'status' => UserStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Temporary passwords (§4)
    |--------------------------------------------------------------------------
    |
    | The forgot-password flow mails a one-off password instead of a reset
    | link. It is kept beside the real password, never in place of it, so
    | requesting a reset for somebody else's address cannot lock them out of
    | an account they can still sign into.
    */

    /** How long a mailed password stays usable. */
    public const TEMPORARY_PASSWORD_TTL_MINUTES = 60;

    /**
     * Issue a fresh temporary password and return the plain text.
     *
     * The plain text is returned rather than stored: it exists only long
     * enough to be put in the e-mail, and the column holds the hash.
     */
    public function issueTemporaryPassword(): string
    {
        // Unambiguous alphabet: no O/0 or I/l/1, because this is a string
        // somebody reads out of an e-mail and types by hand.
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $plain = '';

        for ($i = 0; $i < 12; $i++) {
            $plain .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $this->forceFill([
            'temporary_password' => $plain,
            'temporary_password_expires_at' => now()->addMinutes(self::TEMPORARY_PASSWORD_TTL_MINUTES),
        ])->save();

        return $plain;
    }

    /** True when $plain is this user's live, unexpired temporary password. */
    public function matchesTemporaryPassword(string $plain): bool
    {
        if (blank($this->temporary_password) || $this->temporary_password_expires_at === null) {
            return false;
        }

        if ($this->temporary_password_expires_at->isPast()) {
            return false;
        }

        return Hash::check($plain, $this->temporary_password);
    }

    /** Drop the temporary password, whether it was used or replaced. */
    public function clearTemporaryPassword(): void
    {
        $this->forceFill([
            'temporary_password' => null,
            'temporary_password_expires_at' => null,
        ])->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function managedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function createdDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function uploadedVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'uploaded_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class, 'approver_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', UserStatus::Active);
    }

    /** Free-text search across the fields shown in the admin user list (§29). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('department', 'like', $term)
                ->orWhere('job_title', 'like', $term);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Presentation
    |--------------------------------------------------------------------------
    */

    /**
     * Two-letter monogram used by the avatar component when no image is set.
     * Handles single-word names and accented characters (Aït, Benaïssa…).
     */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = Str::upper(Str::substr($words[0], 0, 1));

        if (count($words) === 1) {
            return $first;
        }

        return $first.Str::upper(Str::substr(end($words), 0, 1));
    }

    /** Public URL for the avatar, or null to fall back to initials. */
    public function avatarUrl(): ?string
    {
        if (blank($this->avatar_path)) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    /*
    |--------------------------------------------------------------------------
    | Administration guards (§29)
    |--------------------------------------------------------------------------
    |
    | These are integrity rules, not permissions: administrators bypass every
    | policy via Gate::before, so putting them in UserPolicy would make them
    | dead code — the same mistake caught earlier on ProjectPolicy.
    */

    public function isAdministrator(): bool
    {
        return $this->hasRole(UserRole::Administrator->value);
    }

    /**
     * True when deactivating or demoting this account would leave the
     * installation with nobody able to administer it.
     */
    public function isLastActiveAdministrator(): bool
    {
        if (! $this->isAdministrator() || ! $this->status->canLogin()) {
            return false;
        }

        return static::query()
            ->active()
            ->whereKeyNot($this->id)
            ->whereHas('roles', fn ($query) => $query->where('name', UserRole::Administrator->value))
            ->doesntExist();
    }

    /** Nobody may lock themselves out, and the last admin may not be disabled. */
    public function canHaveStatusChangedBy(self $actor): bool
    {
        return $this->isNot($actor) && ! $this->isLastActiveAdministrator();
    }

    /**
     * Deleting is refused when the account owns document history — the audit
     * trail must survive the person (§34). Deactivate instead.
     */
    public function canBeDeleted(): bool
    {
        if ($this->isLastActiveAdministrator()) {
            return false;
        }

        return $this->createdDocuments()->doesntExist()
            && $this->uploadedVersions()->doesntExist()
            && $this->reviews()->doesntExist()
            && $this->approvals()->doesntExist();
    }

    public function canBeDeletedBy(self $actor): bool
    {
        return $this->isNot($actor) && $this->canBeDeleted();
    }

    /**
     * Highest-ranking role name, used for the "role" line in the UI.
     *
     * `auth()->user()` reliably has `roles` loaded already, because our
     * Gate::before hook calls hasRole() on nearly every request and Spatie
     * loads that relation via loadMissing() internally. A User pulled in
     * through someone else's relation (a comment's author, a review's
     * reviewer) usually has not, so loadMissing() here is a real safety net,
     * not decoration — without it this throws under strict lazy-loading.
     * Callers that render this in a loop should still eager-load `roles`
     * themselves so this becomes a no-op rather than N extra queries (§40).
     */
    public function primaryRole(): ?string
    {
        return $this->loadMissing('roles')->roles->first()?->name;
    }
}
