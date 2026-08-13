<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'document_id' => null,
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(Priority::cases()),
            'status' => TaskStatus::Open,
            'due_date' => fake()->dateTimeBetween('now', '+3 weeks'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /** Past due while still open — completed tasks are never overdue. */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Open,
            'due_date' => now()->subDays(5),
            'completed_at' => null,
        ]);
    }
}
