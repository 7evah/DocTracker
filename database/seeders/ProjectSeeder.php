<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Realistic Moroccan industrial engineering projects (§35).
     * Deliberately not "Project 1" — these read as real to a JESA engineer.
     */
    public function run(): void
    {
        $manager = User::role(UserRole::ProjectManager->value)->first()
            ?? User::role(UserRole::Administrator->value)->first();

        $projects = [
            [
                'project_code' => 'OCP-GA-2026',
                'name' => 'OCP Green Ammonia Project',
                'client' => 'OCP Group',
                'location' => 'Jorf Lasfar, El Jadida',
                'description' => "Unité de production d'ammoniac vert alimentée par énergies renouvelables. Périmètre : études de base, ingénierie de détail et suivi documentaire multidisciplinaire.",
                'status' => ProjectStatus::Active,
                'start_date' => '2026-01-15',
                'end_date' => '2027-06-30',
            ],
            [
                'project_code' => 'OCP-PPE-2025',
                'name' => 'Phosphate Processing Expansion',
                'client' => 'OCP Group',
                'location' => 'Khouribga',
                'description' => 'Extension de la capacité de traitement du phosphate : nouvelles lignes de broyage, convoyeurs et utilités associées.',
                'status' => ProjectStatus::Active,
                'start_date' => '2025-09-01',
                'end_date' => '2026-12-15',
            ],
            [
                'project_code' => 'ONEE-IWT-2026',
                'name' => 'Industrial Water Treatment Project',
                'client' => 'ONEE — Branche Eau',
                'location' => 'Mohammedia',
                'description' => "Station de traitement des eaux industrielles et réseau de distribution associé, incluant les ouvrages de génie civil et l'instrumentation.",
                'status' => ProjectStatus::Planning,
                'start_date' => '2026-04-01',
                'end_date' => '2027-10-31',
            ],
            [
                'project_code' => 'JES-SUB-2025',
                'name' => 'Poste de transformation 225/60 kV',
                'client' => 'ONEE — Branche Électricité',
                'location' => 'Tanger Med',
                'description' => "Conception et suivi de réalisation d'un poste de transformation haute tension desservant la zone industrielle portuaire.",
                'status' => ProjectStatus::OnHold,
                'start_date' => '2025-03-10',
                'end_date' => '2026-05-30',
            ],
            [
                'project_code' => 'MAS-DES-2024',
                'name' => 'Station de dessalement Agadir — Phase 2',
                'client' => 'Ministère de l’Équipement et de l’Eau',
                'location' => 'Chtouka Aït Baha, Agadir',
                'description' => "Extension de la capacité de dessalement par osmose inverse et raccordement au réseau d'irrigation.",
                'status' => ProjectStatus::Completed,
                'start_date' => '2024-02-01',
                'end_date' => '2025-11-20',
            ],
        ];

        foreach ($projects as $data) {
            Project::updateOrCreate(
                ['project_code' => $data['project_code']],
                $data + ['manager_id' => $manager?->id],
            );
        }

        $this->seedDefaultWorkflow();
    }

    /**
     * A single global approval circuit matching the §8 example, used as the
     * default for every project until a project defines its own.
     */
    private function seedDefaultWorkflow(): void
    {
        $workflow = ApprovalWorkflow::updateOrCreate(
            ['project_id' => null, 'name' => 'Circuit standard JESA'],
            [
                'description' => 'Vérification technique, validation projet, puis approbation finale.',
                'is_active' => true,
                'is_default' => true,
            ],
        );

        $steps = [
            ['step_order' => 1, 'role' => UserRole::Reviewer->value, 'label' => 'Vérification technique', 'turnaround_days' => 5],
            ['step_order' => 2, 'role' => UserRole::ProjectManager->value, 'label' => 'Validation chef de projet', 'turnaround_days' => 3],
            ['step_order' => 3, 'role' => UserRole::Approver->value, 'label' => 'Approbation finale', 'turnaround_days' => 3],
        ];

        foreach ($steps as $step) {
            ApprovalWorkflowStep::updateOrCreate(
                ['workflow_id' => $workflow->id, 'step_order' => $step['step_order']],
                $step + ['required' => true],
            );
        }
    }
}
