<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStep::class, 'workflow_id')->orderBy('step_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * The workflow to apply for a project: its own default if it has one,
     * otherwise the global default (project_id null).
     */
    public static function resolveFor(?Project $project): ?self
    {
        return static::query()
            ->active()
            ->where('is_default', true)
            ->when(
                $project,
                fn (Builder $q) => $q->where(fn (Builder $q) => $q->where('project_id', $project->id)->orWhereNull('project_id')),
                fn (Builder $q) => $q->whereNull('project_id'),
            )
            // Project-specific wins over global.
            ->orderByRaw('project_id is null')
            ->with('steps')
            ->first();
    }
}
