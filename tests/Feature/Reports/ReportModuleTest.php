<?php

namespace Tests\Feature\Reports;

use App\Enums\DocumentStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Reports\Index as ReportIndex;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Permissions;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportModuleTest extends TestCase
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

    /*
    |--------------------------------------------------------------------------
    | Every report builds (§28)
    |--------------------------------------------------------------------------
    */

    /**
     * Each report must return a coherent structure even with no data — the
     * export path assumes headings always exist.
     */
    public function test_every_report_builds_on_an_empty_database(): void
    {
        $reports = app(ReportService::class);

        foreach (ReportType::cases() as $type) {
            $result = $reports->build($type);

            $this->assertNotEmpty($result->headings, "[{$type->value}] has no headings.");
            $this->assertSame($type, $result->type);
        }
    }

    /** Row width must match the heading count, or exports produce ragged files. */
    public function test_every_report_row_matches_its_heading_count(): void
    {
        $this->seedSomeData();

        $reports = app(ReportService::class);

        foreach (ReportType::cases() as $type) {
            $result = $reports->build($type);

            foreach ($result->rows as $index => $row) {
                $this->assertCount(
                    count($result->headings),
                    $row,
                    "[{$type->value}] row {$index} does not match the heading count.",
                );
            }
        }
    }

    public function test_document_status_summary_counts_and_shares(): void
    {
        $project = Project::factory()->create();
        $discipline = Discipline::first();

        Document::factory()->count(3)->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'status' => DocumentStatus::Approved,
        ]);
        Document::factory()->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'status' => DocumentStatus::Draft,
        ]);

        $result = app(ReportService::class)->build(ReportType::DocumentStatusSummary);

        $this->assertSame('4', $result->summary[__('reports.headings.total')]);
        $this->assertSame(3, $result->chart[DocumentStatus::Approved->label()]);
        $this->assertSame(1, $result->chart[DocumentStatus::Draft->label()]);

        // 3 of 4 approved.
        $approvedRow = collect($result->rows)->firstWhere(0, DocumentStatus::Approved->label());
        $this->assertSame('75 %', $approvedRow[2]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filters (§28 "Apply filters before exporting")
    |--------------------------------------------------------------------------
    */

    public function test_the_project_filter_narrows_the_status_summary(): void
    {
        $wanted = Project::factory()->create();
        $other = Project::factory()->create();
        $discipline = Discipline::first();

        Document::factory()->count(2)->create([
            'project_id' => $wanted->id,
            'discipline_id' => $discipline->id,
            'status' => DocumentStatus::Approved,
        ]);
        Document::factory()->count(5)->create([
            'project_id' => $other->id,
            'discipline_id' => $discipline->id,
            'status' => DocumentStatus::Approved,
        ]);

        $result = app(ReportService::class)
            ->build(ReportType::DocumentStatusSummary, ['project' => $wanted->id]);

        $this->assertSame('2', $result->summary[__('reports.headings.total')]);
    }

    public function test_the_discipline_filter_narrows_the_status_summary(): void
    {
        $project = Project::factory()->create();
        $piping = Discipline::where('code', 'PI')->first();
        $civil = Discipline::where('code', 'CV')->first();

        Document::factory()->count(2)->create([
            'project_id' => $project->id,
            'discipline_id' => $piping->id,
            'status' => DocumentStatus::Draft,
        ]);
        Document::factory()->count(3)->create([
            'project_id' => $project->id,
            'discipline_id' => $civil->id,
            'status' => DocumentStatus::Draft,
        ]);

        $result = app(ReportService::class)
            ->build(ReportType::DocumentStatusSummary, ['discipline' => $piping->id]);

        $this->assertSame('2', $result->summary[__('reports.headings.total')]);
    }

    /**
     * A filter the selected report does not honour must be dropped, so the
     * export URL cannot claim a narrowing the table never applied.
     */
    public function test_filters_a_report_ignores_are_not_passed_through(): void
    {
        $project = Project::factory()->create();

        $component = Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ReportIndex::class)
            ->set('report', ReportType::UserWorkload->value)
            ->set('project', (string) $project->id);

        // UserWorkload does not use the project filter.
        $this->assertNull($component->instance()->filters()['project']);
        $this->assertStringNotContainsString('project=', $component->instance()->exportUrl('xlsx'));
    }

    public function test_a_tampered_report_key_falls_back_instead_of_erroring(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(ReportIndex::class)
            ->set('report', 'definitely-not-a-report')
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization (§13)
    |--------------------------------------------------------------------------
    */

    public function test_an_engineer_cannot_open_reports(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Engineer))
            ->get(route('reports.index'))
            ->assertForbidden();
    }

    public function test_a_project_manager_can_open_reports(): void
    {
        $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('reports.index'))
            ->assertOk();
    }

    /**
     * An Approver may read reports but not export them, so viewing must not
     * imply exporting.
     */
    public function test_viewing_reports_does_not_imply_exporting_them(): void
    {
        $approver = $this->userWithRole(UserRole::Approver);

        $this->assertTrue($approver->can(Permissions::REPORTS_VIEW));
        $this->assertFalse($approver->can(Permissions::REPORTS_EXPORT));

        $this->actingAs($approver)->get(route('reports.index'))->assertOk();

        $this->actingAs($approver)
            ->get(route('reports.export', ['report' => ReportType::UserWorkload->value, 'format' => 'xlsx']))
            ->assertForbidden();
    }

    public function test_the_export_button_is_hidden_without_the_permission(): void
    {
        Livewire::actingAs($this->userWithRole(UserRole::Approver))
            ->test(ReportIndex::class)
            ->assertDontSee(__('reports.export.excel'));
    }

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    */

    public function test_the_excel_export_downloads_a_real_workbook(): void
    {
        $this->seedSomeData();

        $response = $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('reports.export', [
                'report' => ReportType::DocumentStatusSummary->value,
                'format' => 'xlsx',
            ]));

        $response->assertOk();
        $response->assertDownload();

        // xlsx is a zip archive; "PK" is its magic number. Asserting on the
        // bytes proves a real workbook rather than an empty 200.
        $this->assertStringStartsWith('PK', $response->streamedContent());
    }

    public function test_the_pdf_export_downloads_a_real_pdf(): void
    {
        $this->seedSomeData();

        $response = $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('reports.export', [
                'report' => ReportType::ProjectProgress->value,
                'format' => 'pdf',
            ]));

        $response->assertOk();
        $response->assertDownload();

        // DomPDF returns a normal response, not a streamed one (unlike Excel),
        // so the bytes are read with getContent(). "%PDF" is the magic number.
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_every_report_exports_to_both_formats(): void
    {
        $this->seedSomeData();

        $manager = $this->userWithRole(UserRole::ProjectManager);

        foreach (ReportType::cases() as $type) {
            foreach (['xlsx', 'pdf'] as $format) {
                $this->actingAs($manager)
                    ->get(route('reports.export', ['report' => $type->value, 'format' => $format]))
                    ->assertOk("[{$type->value}] failed to export as {$format}.");
            }
        }
    }

    public function test_an_unknown_export_format_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('reports.export', [
                'report' => ReportType::UserWorkload->value,
                'format' => 'exe',
            ]))
            ->assertSessionHasErrors('format');
    }

    public function test_an_end_date_before_the_start_date_is_rejected(): void
    {
        $this->actingAs($this->userWithRole(UserRole::ProjectManager))
            ->get(route('reports.export', [
                'report' => ReportType::DocumentStatusSummary->value,
                'format' => 'xlsx',
                'from' => '2026-06-01',
                'to' => '2026-01-01',
            ]))
            ->assertSessionHasErrors('to');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function seedSomeData(): void
    {
        $manager = $this->userWithRole(UserRole::ProjectManager, 'Chef');
        $reviewer = $this->userWithRole(UserRole::Reviewer, 'Vérificateur');

        $project = Project::factory()->create(['manager_id' => $manager->id]);
        $discipline = Discipline::first();

        $documents = Document::factory()->count(3)->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'created_by' => $manager->id,
            'status' => DocumentStatus::Approved,
        ]);

        foreach ($documents as $document) {
            $version = $document->versions()->create([
                'revision' => 'A',
                'file_path' => 'documents/x/y/A/file.pdf',
                'original_filename' => 'file.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 1024,
                'uploaded_by' => $manager->id,
            ]);

            Review::factory()->create([
                'document_version_id' => $version->id,
                'reviewer_id' => $reviewer->id,
                'assigned_by' => $manager->id,
            ]);
        }

        Task::factory()->count(2)->create([
            'project_id' => $project->id,
            'assigned_to' => $reviewer->id,
            'created_by' => $manager->id,
        ]);
    }
}
