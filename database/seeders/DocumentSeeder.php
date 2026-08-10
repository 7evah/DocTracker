<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentSeeder extends Seeder
{
    /**
     * Realistic technical documents with genuine revision chains (§35).
     *
     * Writes an actual (small) PDF for every revision, so downloads, size
     * formatting and the "file missing" warning can all be demonstrated
     * rather than mocked.
     */
    public function run(): void
    {
        $engineers = User::role(UserRole::Engineer->value)->get();

        if ($engineers->isEmpty()) {
            $engineers = User::role(UserRole::Administrator->value)->get();
        }

        $documents = [
            // [project code, discipline, number, title, revisions, status]
            ['OCP-GA-2026', 'PI', 'PI-1023', 'Plan d’implantation tuyauterie — Unité 100', ['A', 'B', 'C'], DocumentStatus::Approved],
            ['OCP-GA-2026', 'PR', 'PR-3011', 'Schéma de procédé — Synthèse ammoniac', ['A', 'B'], DocumentStatus::UnderReview],
            ['OCP-GA-2026', 'EL', 'EL-2250', 'Schéma de cheminement des câbles — Poste HT', ['A'], DocumentStatus::Draft],
            ['OCP-GA-2026', 'IN', 'IN-4102', 'Liste des boucles de régulation', ['A', 'B'], DocumentStatus::NeedsRevision],
            ['OCP-GA-2026', 'HS', 'HS-0450', 'Étude de dangers — Stockage ammoniac', ['A'], DocumentStatus::UnderReview],

            ['OCP-PPE-2025', 'CV', 'CV-0102', 'Plan de fondation — Bâtiment broyage', ['A', 'B'], DocumentStatus::Approved],
            ['OCP-PPE-2025', 'ST', 'ST-0788', 'Note de calcul charpente métallique', ['A'], DocumentStatus::UnderReview],
            ['OCP-PPE-2025', 'ME', 'ME-1560', 'Spécification technique — Convoyeurs à bande', ['A', 'B'], DocumentStatus::NeedsRevision],
            ['OCP-PPE-2025', 'EL', 'EL-2310', 'Bilan de puissance électrique', ['A'], DocumentStatus::Draft],

            ['ONEE-IWT-2026', 'PR', 'PR-3055', 'Bilan matière — Station de traitement', ['A'], DocumentStatus::Draft],
            ['ONEE-IWT-2026', 'CV', 'CV-0210', 'Plan de génie civil — Bassins de décantation', ['A'], DocumentStatus::Draft],
            ['ONEE-IWT-2026', 'IN', 'IN-4210', 'Architecture du système de contrôle-commande', ['A'], DocumentStatus::Draft],

            ['JES-SUB-2025', 'EL', 'EL-2410', 'Schéma unifilaire général 225/60 kV', ['A', 'B'], DocumentStatus::Approved],
            ['JES-SUB-2025', 'CV', 'CV-0330', 'Plan de masse du poste', ['A'], DocumentStatus::Archived],

            ['MAS-DES-2024', 'ME', 'ME-1780', 'Spécification pompes haute pression', ['A', 'B', 'C'], DocumentStatus::Approved],
            ['MAS-DES-2024', 'PR', 'PR-3120', 'Schéma P&ID — Osmose inverse', ['A', 'B'], DocumentStatus::Approved],
        ];

        $disk = Storage::disk(config('documents.disk'));

        foreach ($documents as $index => [$projectCode, $disciplineCode, $number, $title, $revisions, $status]) {
            $project = Project::where('project_code', $projectCode)->first();
            $discipline = Discipline::where('code', $disciplineCode)->first();

            if (! $project || ! $discipline) {
                continue;
            }

            $author = $engineers[$index % max($engineers->count(), 1)] ?? null;

            $document = Document::updateOrCreate(
                ['project_id' => $project->id, 'document_number' => $number],
                [
                    'discipline_id' => $discipline->id,
                    'title' => $title,
                    'description' => "Document technique établi dans le cadre du projet {$project->name}.",
                    'current_revision' => end($revisions),
                    'status' => $status,
                    'created_by' => $author?->id,
                ],
            );

            $this->seedRevisions($document, $revisions, $author, $disk);
        }
    }

    /**
     * @param  list<string>  $revisions
     */
    private function seedRevisions(Document $document, array $revisions, ?User $author, $disk): void
    {
        foreach ($revisions as $position => $revision) {
            if ($document->versions()->where('revision', $revision)->exists()) {
                continue;
            }

            $directory = implode('/', [
                trim(config('documents.root'), '/'),
                $document->project_id,
                $document->id,
                $revision,
            ]);

            $filename = Str::random(40).'.pdf';
            $contents = $this->placeholderPdf($document, $revision);

            $disk->put("{$directory}/{$filename}", $contents);

            $occurredAt = now()->subDays((count($revisions) - $position) * 12);

            $document->versions()->create([
                'revision' => $revision,
                'file_path' => "{$directory}/{$filename}",
                'original_filename' => Str::of($document->document_number)->lower()->slug().'-rev-'.Str::lower($revision).'.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => strlen($contents),
                'version_notes' => $position === 0
                    ? 'Première émission pour revue interne.'
                    : 'Prise en compte des commentaires de la révision précédente.',
                'uploaded_by' => $author?->id,
                // Space revisions out so the history reads as a real timeline.
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);

            $this->logHistory($document, $revision, $author, $occurredAt, first: $position === 0);
        }
    }

    /**
     * Seeders run with model events disabled, so the audit trail is written
     * explicitly here. Without it the Activity tab would be empty in the demo
     * and §34 would be impossible to show.
     */
    private function logHistory(Document $document, string $revision, ?User $author, $occurredAt, bool $first): void
    {
        $entry = activity('document')
            ->performedOn($document)
            ->causedBy($author)
            ->event($first ? 'created' : 'revision_uploaded')
            ->withProperties(['revision' => $revision])
            ->log($first ? 'document.created' : 'document.revision_uploaded');

        // log() stamps "now"; backdate so the timeline matches the revisions.
        $entry?->forceFill(['created_at' => $occurredAt, 'updated_at' => $occurredAt])->save();

        if (! $first || count($document->versions) > 1) {
            $submitted = activity('document')
                ->performedOn($document)
                ->causedBy($author)
                ->event('submitted')
                ->withProperties(['revision' => $revision])
                ->log('document.submitted');

            $submitted?->forceFill([
                'created_at' => $occurredAt->copy()->addDay(),
                'updated_at' => $occurredAt->copy()->addDay(),
            ])->save();
        }
    }

    /**
     * A minimal but genuinely valid single-page PDF, so seeded downloads open
     * in a viewer instead of erroring.
     */
    private function placeholderPdf(Document $document, string $revision): string
    {
        $text = "{$document->document_number} - Revision {$revision}";
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
            .'/Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>';

        $stream = "BT /F1 18 Tf 72 750 Td ({$escaped}) Tj ET";
        $objects[4] = '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream";
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $count = count($objects) + 1;

        $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n";

        foreach ($objects as $number => $body) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$number]);
        }

        $pdf .= "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xrefPosition}\n%%EOF\n";

        return $pdf;
    }
}
