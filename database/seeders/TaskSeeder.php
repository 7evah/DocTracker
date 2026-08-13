<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Models\Document;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Follow-up actions of the kind a real review produces (§27, §35).
     *
     * Several are deliberately overdue and several complete, so the counters,
     * the overdue styling and the completed state all have data behind them.
     */
    public function run(): void
    {
        $engineers = User::role(UserRole::Engineer->value)->get();
        $manager = User::role(UserRole::ProjectManager->value)->first();

        if ($engineers->isEmpty() || ! $manager) {
            return;
        }

        // Attach to documents that actually drew review remarks.
        $documents = Document::with('project')->get();

        if ($documents->isEmpty()) {
            return;
        }

        $templates = [
            ['Corriger les cotes de la vue en plan', 'Reprendre les cotes signalées en revue et réémettre la révision.', Priority::High, TaskStatus::Open, 4],
            ['Compléter la nomenclature des supports', 'Ajouter les références matériaux manquantes au tableau.', Priority::Medium, TaskStatus::InProgress, 9],
            ['Mettre à jour le cartouche', 'Indiquer l’indice de révision et la date d’émission.', Priority::Low, TaskStatus::Completed, -6],
            ['Justifier les hypothèses de calcul', 'Joindre la note de calcul et les références normatives.', Priority::Critical, TaskStatus::Open, -3],
            ['Vérifier la cohérence avec le P&ID', 'Contrôler les repères d’équipements entre les deux documents.', Priority::Medium, TaskStatus::Open, 12],
            ['Organiser la réunion de levée de réserves', 'Convier le chef de projet et les vérificateurs concernés.', Priority::Medium, TaskStatus::Open, -1],
            ['Transmettre le document au client', 'Après approbation finale, diffuser via la GED client.', Priority::Low, TaskStatus::Open, 20],
            ['Archiver les révisions obsolètes', 'Marquer les révisions A et B comme superseded.', Priority::Low, TaskStatus::Completed, -14],
        ];

        foreach ($templates as $index => [$title, $description, $priority, $status, $dueInDays]) {
            $document = $documents[$index % $documents->count()];
            $assignee = $engineers[$index % $engineers->count()];

            Task::updateOrCreate(
                [
                    'project_id' => $document->project_id,
                    'title' => $title,
                ],
                [
                    'document_id' => $document->id,
                    'assigned_to' => $assignee->id,
                    'created_by' => $manager->id,
                    'description' => $description,
                    'priority' => $priority,
                    'status' => $status,
                    'due_date' => now()->addDays($dueInDays)->toDateString(),
                    'completed_at' => $status === TaskStatus::Completed ? now()->subDays(2) : null,
                ],
            );
        }
    }
}
