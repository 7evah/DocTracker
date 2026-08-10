<?php

namespace Database\Seeders;

use App\Enums\DocumentStatus;
use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Review;
use App\Models\ReviewComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Reviews consistent with each document's status (§35).
     *
     * The verdicts are derived from the document status rather than picked at
     * random, so the demo data obeys the same rollup rule the application
     * enforces — an "Approved" document never shows a pending review.
     */
    public function run(): void
    {
        $reviewers = User::role(UserRole::Reviewer->value)->get();
        $assigner = User::role(UserRole::ProjectManager->value)->first()
            ?? User::role(UserRole::Administrator->value)->first();

        if ($reviewers->isEmpty()) {
            return;
        }

        $documents = Document::with('versions')->get();

        foreach ($documents as $index => $document) {
            $version = $document->versions->sortByDesc('id')->first();

            if (! $version || $document->status === DocumentStatus::Draft) {
                continue;
            }

            $verdict = match ($document->status) {
                DocumentStatus::Approved => ReviewStatus::Approved,
                DocumentStatus::NeedsRevision => ReviewStatus::RevisionRequested,
                DocumentStatus::Rejected => ReviewStatus::Rejected,
                default => ReviewStatus::Pending,
            };

            $reviewer = $reviewers[$index % $reviewers->count()];
            $priority = collect(Priority::cases())[$index % 4];

            // Overdue examples so the dashboard's overdue counter is non-zero.
            $overdue = $verdict === ReviewStatus::Pending && $index % 3 === 0;

            $assignedAt = $version->created_at?->copy()->addDay() ?? now()->subDays(10);

            $review = Review::updateOrCreate(
                [
                    'document_version_id' => $version->id,
                    'reviewer_id' => $reviewer->id,
                ],
                [
                    'assigned_by' => $assigner?->id,
                    'status' => $verdict,
                    'priority' => $priority,
                    'assigned_at' => $assignedAt,
                    'deadline' => $overdue
                        ? now()->subDays(3)
                        : $assignedAt->copy()->addDays($priority->defaultTurnaroundDays()),
                    'reviewed_at' => $verdict->isOpen() ? null : $assignedAt->copy()->addDays(2),
                    'summary' => $this->summaryFor($verdict),
                ],
            );

            $this->seedComments($review, $reviewer, $document, $verdict);
        }
    }

    private function summaryFor(ReviewStatus $verdict): ?string
    {
        return match ($verdict) {
            ReviewStatus::Approved => 'Document conforme aux exigences techniques du projet. Aucune remarque bloquante.',
            ReviewStatus::RevisionRequested => 'Plusieurs cotes manquantes et incohérence sur la nomenclature. Merci de reprendre et de réémettre.',
            ReviewStatus::Rejected => 'Document non conforme au cahier des charges. Reprise complète nécessaire.',
            default => null,
        };
    }

    private function seedComments(Review $review, User $reviewer, Document $document, ReviewStatus $verdict): void
    {
        if ($review->comments()->exists()) {
            return;
        }

        $remarks = match ($verdict) {
            ReviewStatus::RevisionRequested => [
                ['Les cotes de la vue en plan ne correspondent pas à la nomenclature en page 3.', 4, false],
                ['Merci de préciser la spécification matériau pour les supports.', 2, false],
                ['Le cartouche ne mentionne pas l’indice de révision.', 1, true],
            ],
            ReviewStatus::Rejected => [
                ['Le document ne référence pas la bonne version du cahier des charges.', 1, false],
                ['Les hypothèses de calcul ne sont pas justifiées.', 5, false],
            ],
            ReviewStatus::Approved => [
                ['Vérification effectuée, document conforme.', null, true],
            ],
            default => [
                ['Revue en cours, première lecture effectuée.', null, false],
            ],
        };

        foreach ($remarks as [$body, $page, $resolved]) {
            $comment = ReviewComment::create([
                'review_id' => $review->id,
                'user_id' => $reviewer->id,
                'comment' => $body,
                'page' => $page,
                'resolved' => $resolved,
                'resolved_by' => $resolved ? $document->created_by : null,
                'resolved_at' => $resolved ? now()->subDays(2) : null,
            ]);

            // One threaded reply so the discussion UI has something to show.
            if (! $resolved && $document->created_by && $page === 4) {
                ReviewComment::create([
                    'review_id' => $review->id,
                    'user_id' => $document->created_by,
                    'parent_id' => $comment->id,
                    'comment' => 'Bien noté, correction prise en compte dans la prochaine révision.',
                ]);
            }
        }
    }
}
