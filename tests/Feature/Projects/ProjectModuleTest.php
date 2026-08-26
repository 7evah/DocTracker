<?php

namespace Tests\Feature\Projects;

use App\Enums\DocumentStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Projects\Form as ProjectForm;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Projects\Show as ProjectShow;
use App\Models\Approval;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectModuleTest extends TestCase
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

    /*
    |--------------------------------------------------------------------------
    | Listing
    |--------------------------------------------------------------------------
    */

    public function test_index_lists_projects(): void
    {
        Project::factory()->create(['name' => 'OCP Green Ammonia Project', 'project_code' => 'OCP-GA-2026']);

        Livewire::actingAs($this->userWithRole(UserRole::Engineer))
            ->test(ProjectIndex::class)
            ->assertOk()
            ->assertSee('OCP-GA-2026')
            ->assertSee('OCP Green Ammonia Project');
    }

    public function test_index_search_matches_code_name_and_client(): void
    {
        Project::factory()->create(['project_code' => 'OCP-GA-2026', 'name' => 'Green Ammonia', 'client' => 'OCP Group']);
        Project::factory()->create(['project_code' => 'ONEE-IWT-2026', 'name' => 'Water Treatment', 'client' => 'ONEE']);

        $component = Livewire::actingAs($this->userWithRole(UserRole::Engineer))->test(ProjectIndex::class);

        $component->set('search', 'OCP-GA')->assertSee('Green Ammonia')->assertDontSee('Water Treatment');
        $component->set('search', 'Water')->assertSee('Water Treatment')->assertDontSee('Green Ammonia');
        $component->set('search', 'ONEE')->assertSee('Water Treatment')->assertDontSee('Green Ammonia');
    }

    public function test_index_filters_by_status(): void
    {
        Project::factory()->create(['name' => 'Projet actif', 'status' => ProjectStatus::Active]);
        Project::factory()->create(['name' => 'Projet suspendu', 'status' => ProjectStatus::OnHold]);

        Livewire::actingAs($this->userWithRole(UserRole::Engineer))
            ->test(ProjectIndex::class)
            ->set('status', ProjectStatus::Active->value)
            ->assertSee('Projet actif')
            ->assertDontSee('Projet suspendu');
    }

    public function test_changing_a_filter_resets_pagination(): void
    {
        Project::factory()->count(20)->create();

        Livewire::actingAs($this->userWithRole(UserRole::Engineer))
            ->test(ProjectIndex::class)
            ->call('setPage', 2)
            ->assertSet('paginators.page', 2)
            ->set('search', 'zzz-no-match')
            ->assertSet('paginators.page', 1);
    }

    public function test_reset_filters_clears_every_filter(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::Engineer))
            ->test(ProjectIndex::class)
            ->set('search', 'abc')
            ->set('status', ProjectStatus::Active->value)
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('status', '');
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function test_viewer_can_list_but_not_create(): void
    {
        $viewer = $this->userWithRole(UserRole::Viewer);

        $this->actingAs($viewer)->get(route('projects.index'))->assertOk();
        $this->actingAs($viewer)->get(route('projects.create'))->assertForbidden();
    }

    public function test_engineer_cannot_create_a_project(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Engineer))
            ->get(route('projects.create'))
            ->assertForbidden();
    }

    public function test_project_manager_can_create_a_project(): void
    {
        $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('projects.create'))
            ->assertOk();
    }

    public function test_engineer_cannot_edit_a_project(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->userWithRole(UserRole::Engineer))
            ->get(route('projects.edit', $project))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Create / update
    |--------------------------------------------------------------------------
    */

    public function test_a_project_can_be_created(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);

        Livewire::actingAs($manager)
            ->test(ProjectForm::class)
            ->set('project_code', 'ocp-ga-2026')
            ->set('name', 'OCP Green Ammonia Project')
            ->set('client', 'OCP Group')
            ->set('location', 'Jorf Lasfar')
            ->set('status', ProjectStatus::Active->value)
            ->set('manager_id', (string) $manager->id)
            ->call('save')
            ->assertHasNoErrors();

        $project = Project::first();

        $this->assertNotNull($project);
        // Codes are normalised to upper case so lookups stay predictable.
        $this->assertSame('OCP-GA-2026', $project->project_code);
        $this->assertSame(ProjectStatus::Active, $project->status);
        $this->assertSame($manager->id, $project->manager_id);
    }

    public function test_project_code_must_be_unique(): void
    {
        Project::factory()->create(['project_code' => 'OCP-GA-2026']);

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectForm::class)
            ->set('project_code', 'OCP-GA-2026')
            ->set('name', 'Doublon')
            ->call('save')
            ->assertHasErrors(['project_code' => 'unique']);
    }

    public function test_required_fields_are_validated(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectForm::class)
            ->set('project_code', '')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['project_code' => 'required', 'name' => 'required']);
    }

    public function test_end_date_must_not_precede_start_date(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectForm::class)
            ->set('project_code', 'TST-01')
            ->set('name', 'Test')
            ->set('start_date', '2026-06-01')
            ->set('end_date', '2026-01-01')
            ->call('save')
            ->assertHasErrors(['end_date']);
    }

    public function test_a_project_can_be_updated(): void
    {
        $project = Project::factory()->create(['name' => 'Ancien nom']);

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectForm::class, ['project' => $project])
            ->assertSet('name', 'Ancien nom')
            ->set('name', 'Nouveau nom')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nouveau nom', $project->fresh()->name);
    }

    /*
    |--------------------------------------------------------------------------
    | Detail page
    |--------------------------------------------------------------------------
    */

    public function test_show_page_renders_with_counts(): void
    {
        $project = Project::factory()->create(['name' => 'OCP Green Ammonia Project']);

        $this->actingAs($this->userWithRole(UserRole::Engineer))
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('OCP Green Ammonia Project')
            ->assertSee($project->project_code);
    }

    /*
    |--------------------------------------------------------------------------
    | Deletion guard
    |--------------------------------------------------------------------------
    */

    /**
     * The integrity rule must hold for administrators too, who bypass every
     * policy via Gate::before — so it cannot live in the policy.
     */
    public function test_a_project_holding_documents_cannot_be_deleted(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);
        $project = Project::factory()->create();

        Document::factory()->create([
            'project_id' => $project->id,
            'discipline_id' => Discipline::first()->id,
            'created_by' => $admin->id,
        ]);

        $project->refresh();

        $this->assertTrue($admin->can('delete', $project), 'Admin holds the permission…');
        $this->assertFalse($project->canBeDeleted(), '…but the integrity rule still blocks it.');

        Livewire::actingAs($admin)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('delete');

        $this->assertNotSoftDeleted($project);
    }

    public function test_an_empty_project_can_be_deleted(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);
        $project = Project::factory()->create();

        $this->assertTrue($admin->can('delete', $project));
        $this->assertTrue($project->canBeDeleted());

        Livewire::actingAs($admin)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('delete');

        $this->assertSoftDeleted($project);
    }

    public function test_a_user_without_delete_permission_cannot_delete(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $project = Project::factory()->create();

        $this->assertFalse($manager->can('delete', $project));

        Livewire::actingAs($manager)
            ->test(ProjectShow::class, ['project' => $project])
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($project);
    }

    /*
    |--------------------------------------------------------------------------
    | Derived values
    |--------------------------------------------------------------------------
    */

    public function test_document_progress_reflects_approved_share(): void
    {
        $admin = $this->userWithRole(UserRole::Administrator);
        $project = Project::factory()->create();
        $discipline = Discipline::first();

        Document::factory()->count(3)->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'created_by' => $admin->id,
            'status' => DocumentStatus::Approved,
        ]);

        Document::factory()->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'created_by' => $admin->id,
            'status' => DocumentStatus::Draft,
        ]);

        $loaded = Project::query()->withListingCounts()->find($project->id);

        $this->assertSame(4, $loaded->documents_count);
        $this->assertSame(3, $loaded->approved_documents_count);
        $this->assertSame(75, $loaded->documentProgress());
    }

    public function test_overdue_detection_only_applies_to_open_projects(): void
    {
        $overdue = Project::factory()->overdue()->create();
        $this->assertTrue($overdue->isOverdue());

        $completed = Project::factory()->create([
            'status' => ProjectStatus::Completed,
            'end_date' => now()->subMonth(),
        ]);
        $this->assertFalse($completed->isOverdue());
    }

    /*
    |--------------------------------------------------------------------------
    | Detail tabs (§18)
    |--------------------------------------------------------------------------
    */

    /**
     * Builds a project carrying one document, one review and one approval,
     * plus a second project with its own set, so the scoping assertions below
     * have something they could wrongly leak.
     *
     * @return array{0: Project, 1: Document, 2: Review, 3: Approval}
     */
    private function projectWithWorkflow(string $code, string $documentNumber): array
    {
        $user = $this->userWithRole(UserRole::Reviewer);

        $project = Project::factory()->create(['project_code' => $code]);

        $document = Document::factory()->create([
            'project_id' => $project->id,
            'discipline_id' => Discipline::first()->id,
            'created_by' => $user->id,
            'document_number' => $documentNumber,
        ]);

        $version = DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $user->id,
        ]);

        $review = Review::factory()->create([
            'document_version_id' => $version->id,
            'reviewer_id' => $user->id,
            'assigned_by' => $user->id,
        ]);

        $approval = Approval::factory()->create([
            'document_version_id' => $version->id,
            'approver_id' => $user->id,
        ]);

        return [$project, $document, $review, $approval];
    }

    public function test_each_detail_tab_lists_only_its_own_projects_records(): void
    {
        [$project, $document] = $this->projectWithWorkflow('AAA-01', 'PI-1000');
        [, $otherDocument] = $this->projectWithWorkflow('BBB-02', 'PI-2000');

        $component = Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project]);

        foreach (['documents', 'reviews', 'approvals'] as $tab) {
            $component->set('tab', $tab)
                ->assertSee($document->document_number)
                ->assertDontSee($otherDocument->document_number);
        }
    }

    /**
     * The tabs are lazy: a payload is only queried while its tab is open, so
     * five of the six panels cost nothing on any given render (§40).
     */
    public function test_tab_payloads_are_only_loaded_for_the_active_tab(): void
    {
        [$project] = $this->projectWithWorkflow('CCC-03', 'PI-3000');

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->assertSet('tab', 'overview')
            ->assertViewHas('documents', fn ($documents) => $documents->isEmpty())
            ->assertViewHas('reviews', fn ($reviews) => $reviews->isEmpty())
            ->set('tab', 'documents')
            ->assertViewHas('documents', fn ($documents) => $documents->count() === 1)
            ->assertViewHas('reviews', fn ($reviews) => $reviews->isEmpty());
    }

    /**
     * A project logs almost nothing on its own, so the feed merges in its
     * documents' entries — that is what the tab is actually for.
     */
    public function test_the_activity_tab_merges_the_projects_and_its_documents_entries(): void
    {
        [$project, $document] = $this->projectWithWorkflow('DDD-04', 'PI-4000');

        activity('document')
            ->performedOn($document)
            ->event('approved')
            ->log('document.approved');

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->set('tab', 'activity')
            ->assertViewHas('activities', function ($activities) use ($project, $document) {
                $subjects = $activities->map(
                    fn ($activity) => $activity->subject_type.':'.$activity->subject_id,
                );

                return $subjects->contains(Project::class.':'.$project->id)
                    && $subjects->contains(Document::class.':'.$document->id);
            });
    }

    /**
     * A busy project accumulates activity without limit; the feed used to be
     * capped at 50 rows with no way to reach anything older.
     */
    public function test_the_activity_tab_paginates_instead_of_capping(): void
    {
        [$project, $document] = $this->projectWithWorkflow('GGG-07', 'PI-7000');

        // Comfortably more than one page of 25.
        for ($i = 0; $i < 30; $i++) {
            activity('document')
                ->performedOn($document)
                ->event('downloaded')
                ->log('document.downloaded');
        }

        $component = Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->set('tab', 'activity');

        $component->assertViewHas('activities', function ($activities) {
            return $activities instanceof LengthAwarePaginator
                && $activities->perPage() === 25
                && $activities->count() === 25
                && $activities->total() > 25;
        });

        // The overflow is reachable rather than silently dropped.
        $component->set('paginators.page', 2)
            ->assertViewHas('activities', fn ($activities) => $activities->count() > 0);
    }

    public function test_the_list_tabs_paginate(): void
    {
        [$project] = $this->projectWithWorkflow('HHH-08', 'PI-8000');

        Document::factory()->count(20)->create([
            'project_id' => $project->id,
            'discipline_id' => Discipline::first()->id,
            'created_by' => $this->userWithRole(UserRole::Engineer)->id,
        ]);

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->set('tab', 'documents')
            ->assertViewHas('documents', function ($documents) {
                return $documents instanceof LengthAwarePaginator
                    && $documents->count() === 15
                    && $documents->total() === 21;
            });
    }

    /**
     * All the tabs share one `page` query string, so a deep page on a long tab
     * must not carry over to a short one and strand the user on a blank panel.
     */
    public function test_switching_tab_resets_the_page(): void
    {
        [$project] = $this->projectWithWorkflow('III-09', 'PI-9000');

        Document::factory()->count(20)->create([
            'project_id' => $project->id,
            'discipline_id' => Discipline::first()->id,
            'created_by' => $this->userWithRole(UserRole::Engineer)->id,
        ]);

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->set('tab', 'documents')
            ->set('paginators.page', 2)
            ->assertSet('paginators.page', 2)
            ->set('tab', 'approvals')
            ->assertSet('paginators.page', 1);
    }

    public function test_the_activity_tab_excludes_another_projects_documents(): void
    {
        [$project] = $this->projectWithWorkflow('EEE-05', 'PI-5000');
        [, $otherDocument] = $this->projectWithWorkflow('FFF-06', 'PI-6000');

        activity('document')
            ->performedOn($otherDocument)
            ->event('approved')
            ->log('document.approved');

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ProjectShow::class, ['project' => $project])
            ->set('tab', 'activity')
            ->assertViewHas('activities', function ($activities) use ($otherDocument) {
                return $activities->doesntContain(
                    fn ($activity) => $activity->subject_type === Document::class
                        && $activity->subject_id === $otherDocument->id,
                );
            });
    }
}
