<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'role',
        'label',
        'required',
        'turnaround_days',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'required' => 'boolean',
            'turnaround_days' => 'integer',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    /** Falls back to the role's translated name when no label is set. */
    public function displayLabel(): string
    {
        return $this->label ?: __("enums.role.{$this->role}");
    }
}
