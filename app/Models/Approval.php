<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_version_id',
        'approver_id',
        'step',
        'role',
        'status',
        'assigned_at',
        'deadline',
        'approved_at',
        'rejected_at',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'step' => 'integer',
            'assigned_at' => 'datetime',
            'deadline' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function documentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ApprovalStatus::openValues());
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
