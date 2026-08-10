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
     * A project holding documents must not be removed — those documents carry
     * review and approval history (§34). Change its status to Completed or
     * Cancelled instead.
     *
     * This is an integrity rule rather than a permission, so it holds for
     * every role including administrators.
     */
    public function canBeDeleted(): bool
    {
        return $this->documents()->doesntExist();
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
