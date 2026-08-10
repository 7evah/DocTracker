<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'discipline_id' => Discipline::factory(),
            'document_number' => strtoupper(fake()->unique()->bothify('??-####')),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'current_revision' => 'A',
            'status' => fake()->randomElement(DocumentStatus::cases()),
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => DocumentStatus::Approved]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => DocumentStatus::Draft]);
    }
}
