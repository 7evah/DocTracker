<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Models\DocumentVersion;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $assignedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'document_version_id' => DocumentVersion::factory(),
            'reviewer_id' => User::factory(),
            'assigned_by' => User::factory(),
            'status' => ReviewStatus::Pending,
            'priority' => fake()->randomElement(Priority::cases()),
            'assigned_at' => $assignedAt,
            'deadline' => fake()->dateTimeBetween($assignedAt, '+10 days'),
            'reviewed_at' => null,
            'summary' => null,
        ];
    }

    public function completed(ReviewStatus $status = ReviewStatus::Approved): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'reviewed_at' => now(),
            'summary' => 'Vérification effectuée.',
        ]);
    }

    /** Deadline already passed while the review is still open. */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => ReviewStatus::Pending,
            'deadline' => now()->subDays(4),
            'reviewed_at' => null,
        ]);
    }
}
