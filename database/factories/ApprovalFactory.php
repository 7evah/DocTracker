<?php

namespace Database\Factories;

use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'document_version_id' => DocumentVersion::factory(),
            'approver_id' => User::factory(),
            'step' => 1,
            'role' => 'approver',
            'status' => ApprovalStatus::Pending,
            'assigned_at' => now(),
            'deadline' => now()->addDays(3),
        ];
    }

    /** The step currently awaiting a signature. */
    public function active(): static
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::InProgress,
            'assigned_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => ApprovalStatus::InProgress,
            'deadline' => now()->subDays(3),
        ]);
    }
}
