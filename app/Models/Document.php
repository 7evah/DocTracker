<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Document extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'project_id',
        'discipline_id',
        'document_number',
        'title',
        'description',
        'current_revision',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DocumentStatus::class,
        ];
    }

    /**
     * Only metadata edits are auto-logged. Lifecycle events (created,
     * revision uploaded, submitted, archived, downloaded) are logged
     * explicitly by DocumentService with richer descriptions, so recording
     * them here too would double every entry in the audit trail (§34).
     *
     * @var list<string>
     */
    protected static $recordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['document_number', 'title', 'status', 'current_revision', 'discipline_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('document');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    /** Newest revision by id — revisions are append-only, so id order is chronological. */
    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->latestOfMany();
    }

    public function reviews(): HasManyThrough
    {
        return $this->hasManyThrough(Review::class, DocumentVersion::class);
    }

    public function approvals(): HasManyThrough
    {
        return $this->hasManyThrough(Approval::class, DocumentVersion::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->where('document_number', 'like', $term)
                ->orWhere('title', 'like', $term);
        });
    }

    /**
     * Next revision label in the A, B, … Z, AA sequence used on drawings.
     * Derived from existing revisions so gaps never produce a collision.
     */
    public function nextRevisionLabel(): string
    {
        $current = $this->current_revision;

        if (blank($current)) {
            return 'A';
        }

        return strtoupper(++$current);
    }

    /*
    |--------------------------------------------------------------------------
    | Integrity rules
    |--------------------------------------------------------------------------
    |
    | These sit on the model rather than the policy because administrators
    | bypass every policy through Gate::before — a rule that must hold for
    | everyone cannot live somewhere admins skip (§39). Same reasoning as
    | Project::canBeDeleted().
    */

    /**
     * Whether the document may be sent into review right now.
     *
     * Draft only, and deliberately not "révision requise": uploading a new
     * revision already returns the document to Draft (see
     * DocumentService::addRevision), so allowing a submit straight from
     * "révision requise" would let the author bounce the very file the
     * reviewer just rejected back at them without changing anything.
     */
    public function canBeSubmittedForReview(): bool
    {
        return $this->status === DocumentStatus::Draft;
    }

    /**
     * Whether the metadata (title, description, discipline…) may still be
     * edited.
     *
     * Not once approved or archived: the approval was granted for this
     * content, and editing it afterwards would silently change what was
     * signed off. Both states have a documented way back — upload a new
     * revision, or unarchive — so this closes no door permanently.
     */
    public function acceptsMetadataEdit(): bool
    {
        return ! $this->status->isTerminal();
    }
}
