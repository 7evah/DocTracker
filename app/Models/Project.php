<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'project_code',
        'name',
        'client',
        'location',
        'description',
        'manager_id',
        'status',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['project_code', 'name', 'client', 'status', 'manager_id', 'start_date', 'end_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('project');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function approvalWorkflows(): HasMany
    {
        return $this->hasMany(ApprovalWorkflow::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ProjectStatus::openValues());
    }

    /** Searches the fields a user would actually type (§18). */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($term)).'%';

        return $query->where(function (Builder $query) use ($term) {
            $query->where('project_code', 'like', $term)
                ->orWhere('name', 'like', $term)
                ->orWhere('client', 'like', $term)
                ->orWhere('location', 'like', $term);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Derived values
    |--------------------------------------------------------------------------
    */

    /**
     * Share of documents that have reached Approved, 0–100.
     *
     * Reads the counts loaded by withCount() so a project list does not fire
     * a query per row (§40). Returns null when nothing has been loaded, which
     * the view renders as "—" rather than a misleading 0%.
     */
    public function documentProgress(): ?int
    {
        $total = $this->documents_count ?? null;

        if ($total === null) {
            return null;
        }

        if ($total === 0) {
            return 0;
        }

        return (int) round((($this->approved_documents_count ?? 0) / $total) * 100);
    }

    /**
     * Deleting a project takes its documents with it.
     *
     * A project holding documents used to be undeletable outright, which made
     * any project that had ever received a single upload permanent — there
     * was no way back for one created by mistake, for any role. Cascading is
     * safe here because both models are soft-deleted: nothing is destroyed,
     * the revisions, reviews and approvals hanging off those documents stay
     * in the database, and the stored files are untouched (§34).
     *
     * Done as a model event rather than in the component so it holds however
     * the project is deleted — UI, console, or a future bulk action.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $project): void {
            $project->documents()->get()->each(
                fn (Document $document) => $project->isForceDeleting()
                    ? $document->forceDelete()
                    : $document->delete(),
            );
        });
    }

    /** Documents that would be removed along with this project. */
    public function documentsAtRisk(): int
    {
        return $this->documents_count ?? $this->documents()->count();
    }

    /** True once the end date has passed while work is still open. */
    public function isOverdue(): bool
    {
        return $this->end_date !== null
            && $this->status->isOpen()
            && $this->end_date->isPast();
    }

    /**
     * Eager-load the aggregate columns every project listing needs.
     * Kept here so index, dashboard and reports stay consistent.
     */
    public function scopeWithListingCounts(Builder $query): Builder
    {
        return $query
            ->withCount([
                'documents',
                'documents as approved_documents_count' => fn (Builder $q) => $q->where('status', DocumentStatus::Approved),
                'documents as pending_documents_count' => fn (Builder $q) => $q->whereIn('status', [
                    DocumentStatus::Draft->value,
                    DocumentStatus::UnderReview->value,
                    DocumentStatus::NeedsRevision->value,
                ]),
                'tasks as open_tasks_count' => fn (Builder $q) => $q->whereIn('status', TaskStatus::openValues()),
            ]);
    }
}
