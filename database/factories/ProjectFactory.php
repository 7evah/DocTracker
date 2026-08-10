<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-2 years', '-2 months');

        return [
            'project_code' => strtoupper(fake()->unique()->bothify('???-##-####')),
            'name' => fake()->sentence(3),
            'client' => fake()->company(),
            'location' => fake()->city(),
            'description' => fake()->paragraph(),
            'manager_id' => User::factory(),
            'status' => fake()->randomElement(ProjectStatus::cases()),
            'start_date' => $start,
            'end_date' => fake()->dateTimeBetween($start, '+1 year'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Active]);
    }

    /** End date already passed while the project is still open. */
    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => ProjectStatus::Active,
            'end_date' => now()->subWeeks(2),
        ]);
    }
}
