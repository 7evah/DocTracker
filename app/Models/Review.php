<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_version_id',
        'reviewer_id',
        'assigned_by',
        'status',
        'priority',
        'carry_forward',
        'assigned_at',
        'deadline',
        'reviewed_at',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReviewStatus::class,
            'priority' => Priority::class,
            'carry_forward' => 'boolean',
            'assigned_at' => 'datetime',
            'deadline' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ReviewStatus::openValues());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('deadline')
            ->where('deadline', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->status->isOpen()
            && $this->deadline !== null
            && $this->deadline->isPast();
    }
}
