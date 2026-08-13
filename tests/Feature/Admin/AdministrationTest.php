<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Admin\Disciplines as AdminDisciplines;
use App\Livewire\Admin\Roles as AdminRoles;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Users\Form as UserForm;
use App\Livewire\Admin\Users\Index as UserIndex;
use App\Livewire\Admin\Workflows as AdminWorkflows;
use App\Models\ApprovalWorkflow;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\User;
use App\Support\Permissions;
use App\Support\Settings;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
    }

    private function userWithRole(UserRole $role, string $name = 'Utilisateur'): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    /** A second admin, so lockout guards are not triggered by every test. */
    private function admins(): array
    {
        return [
            $this->userWithRole(UserRole::Administrator, 'Admin Un'),
            $this->userWithRole(UserRole::Administrator, 'Admin Deux'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Access (§13)
    |--------------------------------------------------------------------------
    */

    public function test_every_admin_screen_is_reachable_by_an_administrator(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);

        foreach (['admin.users', 'admin.roles', 'admin.disciplines', 'admin.workflows', 'admin.settings'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk("[{$route}] should be reachable.");
        }
    }

    public function test_a_non_administrator_is_refused_every_admin_screen(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        foreach (['admin.users', 'admin.roles', 'admin.disciplines', 'admin.workflows', 'admin.settings'] as $route) {
            $this->actingAs($engineer)->get(route($route))->assertForbidden("[{$route}] should be forbidden.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Users (§29)
    |--------------------------------------------------------------------------
    */

    public function test_an_administrator_can_create_a_user_with_roles(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('startNew')
            ->set('name', 'Salma Tazi')
            ->set('email', 'salma.tazi@docflow.test')
            ->set('department', 'Génie civil')
            ->set('password', 'mot-de-passe-solide')
            ->set('roles', [UserRole::Engineer->value])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'salma.tazi@docflow.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(UserRole::Engineer->value));
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue(Hash::check('mot-de-passe-solide', $user->password));
    }

    public function test_editing_a_user_without_a_password_keeps_the_existing_one(): void
    {
        [$admin] = $this->admins();
        $target = $this->userWithRole(UserRole::Engineer, 'Ancien Nom');
        $originalHash = $target->password;

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('edit', $target->id)
            ->set('name', 'Nouveau Nom')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        $target->refresh();

        $this->assertSame('Nouveau Nom', $target->name);
        $this->assertSame($originalHash, $target->password);
    }

    public function test_email_must_be_unique(): void
    {
        [$admin] = $this->admins();
        $existing = $this->userWithRole(UserRole::Engineer);

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('startNew')
            ->set('name', 'Doublon')
            ->set('email', $existing->email)
            ->set('password', 'mot-de-passe-solide')
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }

    /*
    |--------------------------------------------------------------------------
    | Lockout guards
    |--------------------------------------------------------------------------
    |
    | These matter because administrators bypass every policy via
    | Gate::before, so nothing in UserPolicy would stop them.
    */

    public function test_an_administrator_cannot_deactivate_themselves(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('toggleStatus', $admin->id);

        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
    }

    public function test_an_administrator_cannot_delete_themselves(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('delete', $admin->id);

        $this->assertNotNull(User::find($admin->id));
    }

    /**
     * The installation must never be left without an administrator.
     *
     * The actor here is a project manager granted users.manage directly,
     * rather than a second administrator: with two admins the "last admin"
     * condition would not arise, and the guard would go untested.
     */
    public function test_the_last_active_administrator_cannot_be_deactivated(): void
    {
        $onlyAdmin = $this->userWithRole(UserRole::Administrator, 'Seul Admin');

        $delegate = $this->userWithRole(UserRole::ProjectManager, 'Gestionnaire');
        $delegate->givePermissionTo(Permissions::USERS_MANAGE);

        $this->assertTrue($onlyAdmin->isLastActiveAdministrator());

        Livewire::actingAs($delegate)
            ->test(UserIndex::class)
            ->call('toggleStatus', $onlyAdmin->id);

        $this->assertSame(UserStatus::Active, $onlyAdmin->fresh()->status);
    }

    public function test_a_second_administrator_makes_the_first_deactivatable(): void
    {
        [$first, $second] = $this->admins();

        $this->assertFalse($first->isLastActiveAdministrator());

        Livewire::actingAs($second)
            ->test(UserIndex::class)
            ->call('toggleStatus', $first->id);

        $this->assertSame(UserStatus::Inactive, $first->fresh()->status);
    }

    public function test_an_administrator_cannot_remove_their_own_admin_role(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('edit', $admin->id)
            ->set('roles', [UserRole::Engineer->value])
            ->call('save')
            ->assertHasErrors('roles');

        $this->assertTrue($admin->fresh()->hasRole(UserRole::Administrator->value));
    }

    /** Deleting must not destroy authorship of documents (§34). */
    public function test_a_user_with_document_history_cannot_be_deleted(): void
    {
        [$admin] = $this->admins();
        $author = $this->userWithRole(UserRole::Engineer, 'Auteur');

        Document::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'discipline_id' => Discipline::first()->id,
            'created_by' => $author->id,
        ]);

        $this->assertFalse($author->fresh()->canBeDeleted());

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('delete', $author->id);

        $this->assertNotNull(User::find($author->id));
    }

    public function test_a_user_without_history_can_be_deleted(): void
    {
        [$admin] = $this->admins();
        $fresh = $this->userWithRole(UserRole::Viewer, 'Sans Historique');

        Livewire::actingAs($admin)
            ->test(UserIndex::class)
            ->call('delete', $fresh->id);

        $this->assertNull(User::find($fresh->id));
    }

    /*
    |--------------------------------------------------------------------------
    | Roles and permissions
    |--------------------------------------------------------------------------
    */

    public function test_permissions_can_be_granted_to_a_role(): void
    {
        [$admin] = $this->admins();

        $viewer = Role::where('name', UserRole::Viewer->value)->first();
        $this->assertFalse($viewer->hasPermissionTo(Permissions::DOCUMENTS_CREATE));

        $component = Livewire::actingAs($admin)->test(AdminRoles::class);

        $matrix = $component->get('matrix');
        $matrix[UserRole::Viewer->value][] = Permissions::DOCUMENTS_CREATE;

        $component->set('matrix', $matrix)->call('save')->assertHasNoErrors();

        $this->assertTrue(
            Role::where('name', UserRole::Viewer->value)->first()->hasPermissionTo(Permissions::DOCUMENTS_CREATE)
        );
    }

    /** A crafted payload must not be able to invent a permission. */
    public function test_unknown_permissions_are_discarded(): void
    {
        [$admin] = $this->admins();

        $component = Livewire::actingAs($admin)->test(AdminRoles::class);

        $matrix = $component->get('matrix');
        $matrix[UserRole::Viewer->value] = ['definitely.not.a.permission'];

        $component->set('matrix', $matrix)->call('save');

        $this->assertSame(
            0,
            Role::where('name', UserRole::Viewer->value)->first()->permissions()->count()
        );
    }

    /** Restricting the admin role would be a lie: Gate::before grants all. */
    public function test_the_administrator_role_cannot_be_restricted(): void
    {
        [$admin] = $this->admins();

        $before = Role::where('name', UserRole::Administrator->value)->first()->permissions()->count();

        $component = Livewire::actingAs($admin)->test(AdminRoles::class);

        $matrix = $component->get('matrix');
        $matrix[UserRole::Administrator->value] = [];

        $component->set('matrix', $matrix)->call('save');

        $this->assertSame(
            $before,
            Role::where('name', UserRole::Administrator->value)->first()->permissions()->count()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Disciplines
    |--------------------------------------------------------------------------
    */

    public function test_a_discipline_can_be_created_with_an_uppercased_code(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminDisciplines::class)
            ->call('startNew')
            ->set('code', 'tp')
            ->set('name', 'Travaux publics')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull(Discipline::where('code', 'TP')->first());
    }

    public function test_a_discipline_code_must_be_unique(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminDisciplines::class)
            ->call('startNew')
            ->set('code', 'ME')
            ->set('name', 'Doublon')
            ->call('save')
            ->assertHasErrors('code');
    }

    /** The FK is restrictOnDelete, so this must be refused before the query. */
    public function test_a_discipline_in_use_cannot_be_deleted(): void
    {
        [$admin] = $this->admins();
        $discipline = Discipline::where('code', 'ME')->first();

        Document::factory()->create([
            'project_id' => Project::factory()->create()->id,
            'discipline_id' => $discipline->id,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminDisciplines::class)
            ->call('delete', $discipline->id);

        $this->assertNotNull(Discipline::find($discipline->id));
    }

    public function test_an_unused_discipline_can_be_deleted(): void
    {
        [$admin] = $this->admins();
        $discipline = Discipline::where('code', 'XX')->first();

        Livewire::actingAs($admin)
            ->test(AdminDisciplines::class)
            ->call('delete', $discipline->id);

        $this->assertNull(Discipline::find($discipline->id));
    }

    /*
    |--------------------------------------------------------------------------
    | Approval workflows
    |--------------------------------------------------------------------------
    */

    public function test_a_workflow_can_be_created_with_steps(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminWorkflows::class)
            ->call('startNew')
            ->set('name', 'Circuit court')
            ->set('steps', [
                ['step_order' => 1, 'role' => UserRole::Reviewer->value, 'label' => 'Vérification', 'turnaround_days' => 4],
                ['step_order' => 2, 'role' => UserRole::Approver->value, 'label' => 'Approbation', 'turnaround_days' => 2],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $workflow = ApprovalWorkflow::where('name', 'Circuit court')->with('steps')->first();

        $this->assertNotNull($workflow);
        $this->assertCount(2, $workflow->steps);
        $this->assertSame(UserRole::Reviewer->value, $workflow->steps->first()->role);
    }

    /** unique(workflow_id, step_order) — caught before it reaches the database. */
    public function test_duplicate_step_orders_are_rejected(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminWorkflows::class)
            ->call('startNew')
            ->set('name', 'Circuit incohérent')
            ->set('steps', [
                ['step_order' => 1, 'role' => UserRole::Reviewer->value, 'label' => '', 'turnaround_days' => 3],
                ['step_order' => 1, 'role' => UserRole::Approver->value, 'label' => '', 'turnaround_days' => 3],
            ])
            ->call('save')
            ->assertHasErrors('steps');

        $this->assertNull(ApprovalWorkflow::where('name', 'Circuit incohérent')->first());
    }

    public function test_a_workflow_requires_at_least_one_step(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminWorkflows::class)
            ->call('startNew')
            ->set('name', 'Circuit vide')
            ->set('steps', [])
            ->call('save')
            ->assertHasErrors('steps');
    }

    /** Two defaults in one scope would make resolveFor() arbitrary. */
    public function test_marking_a_workflow_default_clears_the_previous_one(): void
    {
        [$admin] = $this->admins();

        $existing = ApprovalWorkflow::create([
            'project_id' => null,
            'name' => 'Ancien défaut',
            'is_active' => true,
            'is_default' => true,
        ]);

        Livewire::actingAs($admin)
            ->test(AdminWorkflows::class)
            ->call('startNew')
            ->set('name', 'Nouveau défaut')
            ->set('is_default', true)
            ->set('steps', [
                ['step_order' => 1, 'role' => UserRole::Approver->value, 'label' => '', 'turnaround_days' => 3],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($existing->fresh()->is_default);
        $this->assertTrue(ApprovalWorkflow::where('name', 'Nouveau défaut')->first()->is_default);
    }

    public function test_removing_a_step_renumbers_the_rest(): void
    {
        [$admin] = $this->admins();

        $component = Livewire::actingAs($admin)
            ->test(AdminWorkflows::class)
            ->call('startNew')
            ->call('addStep')
            ->call('addStep');

        $this->assertCount(3, $component->get('steps'));

        $component->call('removeStep', 1);

        $steps = $component->get('steps');

        $this->assertCount(2, $steps);
        $this->assertSame(1, $steps[0]['step_order']);
        $this->assertSame(2, $steps[1]['step_order']);
    }

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    public function test_settings_can_be_saved_and_read_back(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminSettings::class)
            ->set('values.documents_max_size_kb', 51200)
            ->set('values.notifications_email_enabled', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(51200, Settings::get('documents_max_size_kb'));
        $this->assertFalse(Settings::get('notifications_email_enabled'));
    }

    public function test_settings_are_validated(): void
    {
        [$admin] = $this->admins();

        Livewire::actingAs($admin)
            ->test(AdminSettings::class)
            ->set('values.documents_max_size_kb', 1)
            ->call('save')
            ->assertHasErrors('values.documents_max_size_kb');
    }

    /** Keys outside the schema must not become settings. */
    public function test_unknown_setting_keys_are_ignored(): void
    {
        Settings::setMany(['definitely.not.a.setting' => 'valeur']);

        $this->assertArrayNotHasKey('definitely.not.a.setting', Settings::all());
    }

    public function test_settings_fall_back_to_their_defaults(): void
    {
        $this->assertSame(
            (int) config('documents.max_size_kb'),
            Settings::get('documents_max_size_kb'),
        );
    }
}
