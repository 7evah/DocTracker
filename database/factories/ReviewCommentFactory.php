<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewComment>
 */
class ReviewCommentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'comment' => fake()->sentence(12),
            'page' => fake()->optional()->numberBetween(1, 12),
            'resolved' => false,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'resolved' => true,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
