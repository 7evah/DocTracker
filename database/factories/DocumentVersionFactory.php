<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $revision = 'A';

        return [
            'document_id' => Document::factory(),
            'revision' => $revision,
            // Mirrors the real layout; tests that need a readable file use
            // the DocumentStorage service instead of this default.
            'file_path' => 'documents/1/1/'.$revision.'/'.Str::random(40).'.pdf',
            'original_filename' => fake()->slug(3).'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(50_000, 5_000_000),
            'version_notes' => fake()->optional()->sentence(),
            'uploaded_by' => User::factory(),
        ];
    }

    public function revision(string $revision): static
    {
        return $this->state(fn () => ['revision' => $revision]);
    }
}
