<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Discipline;
use App\Models\User;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the Foundation phase: the shell renders, routes resolve, and the
 * permission map actually gates access server-side (§13, §50).
 */
class FoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
    }

    private function userWithRole(UserRole $role): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole($role->value);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_self_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_dashboard_renders_the_app_shell(): void
    {
        $response = $this->actingAs($this->userWithRole(UserRole::Engineer))->get('/dashboard');

        $response->assertOk()
            ->assertSee('DocFlow')
            ->assertSee('Tableau de bord')
            // Prototype disclosure is mandated by §1.
            ->assertSee('Prototype');
    }

    public function test_every_sidebar_destination_resolves_for_an_administrator(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);

        $routes = [
            'dashboard', 'projects.index', 'documents.index', 'reviews.index',
            'approvals.index', 'tasks.index', 'reports.index', 'notifications.index',
            'profile', 'admin.users', 'admin.roles', 'admin.disciplines', 'admin.settings',
        ];

        foreach ($routes as $name) {
            $this->actingAs($admin)->get(route($name))
                ->assertOk("Route [{$name}] did not return 200.");
        }
    }

    public function test_viewer_cannot_reach_administration(): void
    {
        $viewer = $this->userWithRole(UserRole::Viewer);

        foreach (['admin.users', 'admin.roles', 'admin.disciplines', 'admin.settings'] as $name) {
            $this->actingAs($viewer)->get(route($name))
                ->assertForbidden("Route [{$name}] should be forbidden for a viewer.");
        }
    }

    public function test_administrator_bypasses_individual_permission_checks(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);

        // Granted via the Gate::before hook, not via a stored permission row.
        $this->assertTrue($admin->can('some.permission.that.does.not.exist'));
    }

    public function test_role_permission_matrix_is_enforced(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $this->assertTrue($engineer->can('documents.create'));
        $this->assertFalse($engineer->can('documents.approve'));
        $this->assertFalse($engineer->can('users.manage'));

        $reviewer = $this->userWithRole(UserRole::Reviewer);
        $this->assertTrue($reviewer->can('documents.review'));
        $this->assertFalse($reviewer->can('documents.create'));

        $approver = $this->userWithRole(UserRole::Approver);
        $this->assertTrue($approver->can('documents.approve'));
        $this->assertFalse($approver->can('documents.review'));

        $viewer = $this->userWithRole(UserRole::Viewer);
        $this->assertTrue($viewer->can('documents.view'));
        $this->assertFalse($viewer->can('documents.create'));
    }

    public function test_logout_ends_the_session(): void
    {
        $user = $this->userWithRole(UserRole::Engineer);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_disciplines_are_seeded_with_engineering_codes(): void
    {
        $this->assertSame(10, Discipline::count());
        $this->assertNotNull(Discipline::where('code', 'ME')->first());
        $this->assertNotNull(Discipline::where('code', 'PI')->first());
    }

    public function test_user_initials_handle_single_and_compound_names(): void
    {
        $this->assertSame('YA', (new User(['name' => 'Youssef Amrani']))->initials());
        $this->assertSame('S', (new User(['name' => 'Salma']))->initials());
        $this->assertSame('NB', (new User(['name' => 'Nadia El Benchekroun']))->initials());
    }
}
