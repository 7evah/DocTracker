<?php

namespace Tests\Feature\Tasks;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Tasks\Form as TaskForm;
use App\Livewire\Tasks\Index as TaskIndex;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Services\DashboardStatsService;
use App\Services\TaskService;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TaskModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
    }

    private function userWithRole(UserRole $role, string $name = 'Utilisateur'): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    /*
    |--------------------------------------------------------------------------
    | Creation (§27)
    |--------------------------------------------------------------------------
    */

    public function test_a_task_can_be_created_and_notifies_the_assignee(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(TaskForm::class)
            ->set('title', 'Corriger les cotes de la vue en plan')
            ->set('project_id', (string) $project->id)
            ->set('assigned_to', (string) $engineer->id)
            ->set('priority', Priority::High->value)
            ->set('due_date', now()->addWeek()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::first();

        $this->assertNotNull($task);
        $this->assertSame('Corriger les cotes de la vue en plan', $task->title);
        $this->assertSame($manager->id, $task->created_by);
        $this->assertSame($engineer->id, $task->assigned_to);
        $this->assertSame(TaskStatus::Open, $task->status);

        Notification::assertSentTo($engineer, TaskAssigned::class);
    }

    /** Assigning something to yourself needs no notification. */
    public function test_self_assignment_does_not_notify(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $project = Project::factory()->create();

        Livewire::actingAs($manager)
            ->test(TaskForm::class)
            ->set('title', 'Ma propre tâche')
            ->set('project_id', (string) $project->id)
            ->set('assigned_to', (string) $manager->id)
            ->call('save')
            ->assertHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_title_and_project_are_required(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(TaskForm::class)
            ->set('title', '')
            ->set('project_id', '')
            ->call('save')
            ->assertHasErrors(['title' => 'required', 'project_id' => 'required']);
    }

    /**
     * A task may only point at a document inside its own project, or the
     * project page would list documents that do not belong to it.
     */
    public function test_a_document_from_another_project_is_rejected(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $documentOfB = Document::factory()->create([
            'project_id' => $projectB->id,
            'discipline_id' => Discipline::first()->id,
        ]);

        Livewire::actingAs($manager)
            ->test(TaskForm::class)
            ->set('title', 'Tâche incohérente')
            ->set('project_id', (string) $projectA->id)
            ->set('document_id', (string) $documentOfB->id)
            ->call('save')
            ->assertHasErrors('document_id');
    }

    public function test_changing_the_project_clears_the_selected_document(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();

        $documentOfA = Document::factory()->create([
            'project_id' => $projectA->id,
            'discipline_id' => Discipline::first()->id,
        ]);

        Livewire::actingAs($manager)
            ->test(TaskForm::class)
            ->set('project_id', (string) $projectA->id)
            ->set('document_id', (string) $documentOfA->id)
            ->set('project_id', (string) $projectB->id)
            ->assertSet('document_id', '');
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function test_completing_a_task_stamps_the_time(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $task = Task::factory()->create(['assigned_to' => $engineer->id, 'created_by' => $engineer->id]);

        app(TaskService::class)->complete($task, $engineer);

        $task->refresh();

        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    /** Reopening must clear the completion stamp so history stays honest. */
    public function test_reopening_clears_the_completion_stamp(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $task = Task::factory()->completed()->create([
            'assigned_to' => $engineer->id,
            'created_by' => $engineer->id,
        ]);

        app(TaskService::class)->reopen($task, $engineer);

        $task->refresh();

        $this->assertSame(TaskStatus::Open, $task->status);
        $this->assertNull($task->completed_at);
    }

    public function test_completing_an_already_completed_task_is_a_no_op(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $task = Task::factory()->completed()->create([
            'assigned_to' => $engineer->id,
            'created_by' => $engineer->id,
        ]);

        $stampedAt = $task->completed_at;

        app(TaskService::class)->complete($task, $engineer);

        $this->assertEquals($stampedAt, $task->fresh()->completed_at);
    }

    public function test_reassigning_notifies_the_new_assignee_only(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $first = $this->userWithRole(UserRole::Engineer, 'Premier');
        $second = $this->userWithRole(UserRole::Engineer, 'Second');

        $task = Task::factory()->create([
            'assigned_to' => $first->id,
            'created_by' => $manager->id,
        ]);

        app(TaskService::class)->update($task, ['assigned_to' => $second->id], $manager);

        Notification::assertSentTo($second, TaskAssigned::class);
        Notification::assertNotSentTo($first, TaskAssigned::class);
    }

    /** Editing without touching the assignee must not re-notify them. */
    public function test_editing_without_reassigning_does_not_notify(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $engineer = $this->userWithRole(UserRole::Engineer);

        $task = Task::factory()->create([
            'assigned_to' => $engineer->id,
            'created_by' => $manager->id,
        ]);

        app(TaskService::class)->update($task, ['title' => 'Intitulé corrigé'], $manager);

        Notification::assertNothingSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Overdue detection (§27)
    |--------------------------------------------------------------------------
    */

    public function test_overdue_only_covers_open_tasks_with_a_past_due_date(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        Task::factory()->overdue()->create(['assigned_to' => $engineer->id]);
        Task::factory()->create(['assigned_to' => $engineer->id, 'due_date' => now()->addWeek()]);
        Task::factory()->completed()->create(['assigned_to' => $engineer->id, 'due_date' => now()->subMonth()]);
        Task::factory()->create(['assigned_to' => $engineer->id, 'due_date' => null]);

        // Only the genuinely late, still-open one.
        $this->assertSame(1, Task::query()->overdue()->count());
    }

    public function test_a_task_without_a_due_date_is_never_overdue(): void
    {
        $task = Task::factory()->create(['due_date' => null]);

        $this->assertFalse($task->isOverdue());
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization (§13)
    |--------------------------------------------------------------------------
    */

    public function test_an_unrelated_user_cannot_edit_someone_elses_task(): void
    {
        $owner = $this->userWithRole(UserRole::ProjectManager, 'Propriétaire');
        $assignee = $this->userWithRole(UserRole::Engineer, 'Affecté');
        $stranger = $this->userWithRole(UserRole::Engineer, 'Tiers');

        $task = Task::factory()->create([
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->assertTrue($owner->can('update', $task));
        $this->assertTrue($assignee->can('update', $task));
        $this->assertFalse($stranger->can('update', $task));
    }

    public function test_a_viewer_cannot_create_tasks(): void
    {
        $this->assertFalse($this->userWithRole(UserRole::Viewer)->can('create', Task::class));
    }

    public function test_only_the_raiser_can_delete(): void
    {
        $owner = $this->userWithRole(UserRole::ProjectManager);
        $assignee = $this->userWithRole(UserRole::Engineer);

        $task = Task::factory()->create([
            'created_by' => $owner->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->assertTrue($owner->can('delete', $task));
        $this->assertFalse($assignee->can('delete', $task));
    }

    /*
    |--------------------------------------------------------------------------
    | Queue scoping
    |--------------------------------------------------------------------------
    */

    public function test_the_queue_defaults_to_tasks_assigned_to_the_current_user(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer, 'Mien');
        $other = $this->userWithRole(UserRole::Engineer, 'Autre');

        Task::factory()->create(['assigned_to' => $engineer->id, 'title' => 'Ma tâche à moi']);
        Task::factory()->create(['assigned_to' => $other->id, 'title' => 'Tâche de quelqu’un d’autre']);

        Livewire::actingAs($engineer)
            ->test(TaskIndex::class)
            ->assertSet('scope', 'mine')
            ->assertSee('Ma tâche à moi')
            ->assertDontSee('Tâche de quelqu’un d’autre');
    }

    /**
     * A tampered query string asking for every task must fall back to the
     * user's own, not error and not leak (§39).
     */
    public function test_forcing_the_all_scope_without_permission_falls_back_to_own_tasks(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer, 'Mien');
        $other = $this->userWithRole(UserRole::Engineer, 'Autre');

        Task::factory()->create(['assigned_to' => $engineer->id, 'title' => 'Ma tâche à moi']);
        Task::factory()->create(['assigned_to' => $other->id, 'title' => 'Tâche de quelqu’un d’autre']);

        Livewire::actingAs($engineer)
            ->test(TaskIndex::class)
            ->set('scope', 'all')
            ->assertSee('Ma tâche à moi')
            ->assertDontSee('Tâche de quelqu’un d’autre');
    }

    public function test_a_project_manager_can_widen_the_scope_to_all_tasks(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $engineer = $this->userWithRole(UserRole::Engineer);

        Task::factory()->create(['assigned_to' => $engineer->id, 'title' => 'Tâche de l’ingénieur']);

        Livewire::actingAs($manager)
            ->test(TaskIndex::class)
            ->set('scope', 'all')
            ->assertSee('Tâche de l’ingénieur');
    }

    public function test_the_overdue_filter_narrows_the_list(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        Task::factory()->overdue()->create(['assigned_to' => $engineer->id, 'title' => 'En retard']);
        Task::factory()->create([
            'assigned_to' => $engineer->id,
            'title' => 'Dans les temps',
            'due_date' => now()->addWeek(),
        ]);

        Livewire::actingAs($engineer)
            ->test(TaskIndex::class)
            ->set('filter', 'overdue')
            ->assertSee('En retard')
            ->assertDontSee('Dans les temps');
    }

    public function test_completing_from_the_queue_updates_the_task(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $task = Task::factory()->create(['assigned_to' => $engineer->id, 'created_by' => $engineer->id]);

        Livewire::actingAs($engineer)
            ->test(TaskIndex::class)
            ->call('complete', $task->id);

        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
    }

    public function test_completing_someone_elses_task_is_forbidden(): void
    {
        $owner = $this->userWithRole(UserRole::Engineer, 'Propriétaire');
        $stranger = $this->userWithRole(UserRole::Engineer, 'Tiers');

        $task = Task::factory()->create(['assigned_to' => $owner->id, 'created_by' => $owner->id]);

        Livewire::actingAs($stranger)
            ->test(TaskIndex::class)
            ->call('complete', $task->id)
            ->assertForbidden();

        $this->assertSame(TaskStatus::Open, $task->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Dashboard integration (§17)
    |--------------------------------------------------------------------------
    */

    public function test_upcoming_deadlines_merges_tasks_for_the_current_user(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        Task::factory()->create([
            'assigned_to' => $engineer->id,
            'title' => 'Tâche imminente',
            'due_date' => now()->addDays(2),
        ]);

        $deadlines = DashboardStatsService::for($engineer)->upcomingDeadlines();

        $this->assertCount(1, $deadlines);
        $this->assertSame('Tâche imminente', $deadlines->first()['label']);
    }

    public function test_upcoming_deadlines_excludes_other_peoples_work(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $other = $this->userWithRole(UserRole::Engineer, 'Autre');

        Task::factory()->create(['assigned_to' => $other->id, 'due_date' => now()->addDay()]);

        $this->assertCount(0, DashboardStatsService::for($engineer)->upcomingDeadlines());
    }
}
