<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Approval;
use App\Models\ApprovalWorkflow;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Asserts the default seed run — what `php artisan migrate:fresh --seed`
 * produces out of the box — is genuinely empty of business content: roles,
 * permissions, disciplines and the eight §55 logins, nothing else.
 *
 * The second half proves every page tolerates that empty state rather than
 * only ever being exercised against a populated demo (which is what
 * SeededDataSmokeTest covers instead).
 */
class FreshInstallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /*
    |--------------------------------------------------------------------------
    | The default seed carries no business data
    |--------------------------------------------------------------------------
    */

    public function test_no_business_data_is_seeded_by_default(): void
    {
        $this->assertSame(0, Project::count());
        $this->assertSame(0, Document::count());
        $this->assertSame(0, Review::count());
        $this->assertSame(0, Approval::count());
        $this->assertSame(0, Task::count());
        $this->assertSame(0, ApprovalWorkflow::count());
    }

    public function test_reference_data_and_the_demo_roster_are_still_seeded(): void
    {
        $this->assertSame(6, Role::count());
        $this->assertSame(10, Discipline::count());
        $this->assertSame(8, User::count());

        foreach (UserRole::cases() as $role) {
            $this->assertTrue(
                User::role($role->value)->exists(),
                "Expected at least one seeded user holding the {$role->value} role.",
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Every page tolerates the empty state
    |--------------------------------------------------------------------------
    */

    public function test_the_dashboard_renders_at_zero_for_every_seeded_role(): void
    {
        foreach (User::all() as $user) {
            $this->actingAs($user)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertSee('0');
        }
    }

    public function test_every_index_page_renders_empty_for_an_administrator(): void
    {
        $admin = User::where('email', 'adminjesa@yopmail.com')->firstOrFail();

        $routes = [
            'projects.index', 'documents.index', 'reviews.index', 'approvals.index',
            'tasks.index', 'reports.index', 'notifications.index',
            'admin.users', 'admin.roles', 'admin.disciplines', 'admin.workflows', 'admin.settings',
        ];

        foreach ($routes as $name) {
            $this->actingAs($admin)
                ->get(route($name))
                ->assertOk("Route [{$name}] should render on an empty install.");
        }
    }

    /** The Documents module cannot be exercised at all without a discipline to file under. */
    public function test_the_document_upload_form_has_disciplines_to_choose_from(): void
    {
        $engineer = User::role(UserRole::Engineer->value)->firstOrFail();

        $this->actingAs($engineer)
            ->get(route('documents.create'))
            ->assertOk()
            ->assertSee('ME —', false);
    }
}
