<?php

namespace Tests\Feature\Documents;

use App\Enums\DocumentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Documents\Create as DocumentCreate;
use App\Livewire\Documents\Index as DocumentIndex;
use App\Livewire\Documents\Show as DocumentShow;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Project;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
    }

    private function userWithRole(UserRole $role): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);
        $user->assignRole($role->value);

        return $user;
    }

    private function pdf(string $name = 'plan.pdf', int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'application/pdf');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload (§20)
    |--------------------------------------------------------------------------
    */

    public function test_an_engineer_can_upload_a_document(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();
        $discipline = Discipline::where('code', 'PI')->first();

        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $project->id)
            ->set('discipline_id', (string) $discipline->id)
            ->set('document_number', 'pi-1023')
            ->set('title', 'Plan d’implantation tuyauterie')
            ->set('revision', 'A')
            ->set('file', $this->pdf())
            ->call('save')
            ->assertHasNoErrors();

        $document = Document::first();

        $this->assertNotNull($document);
        $this->assertSame('PI-1023', $document->document_number);
        // A new document always starts as a draft (§7).
        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertSame('A', $document->current_revision);
        $this->assertCount(1, $document->versions);
    }

    public function test_upload_stores_the_file_under_project_document_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();

        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $project->id)
            ->set('discipline_id', (string) Discipline::first()->id)
            ->set('document_number', 'ME-1023')
            ->set('title', 'Test')
            ->set('revision', 'A')
            ->set('file', $this->pdf())
            ->call('save')
            ->assertHasNoErrors();

        $version = DocumentVersion::first();

        Storage::disk('documents')->assertExists($version->file_path);
        $this->assertStringStartsWith(
            "documents/{$version->document->project_id}/{$version->document_id}/A/",
            $version->file_path,
        );
    }

    /** The original filename is kept for display but must never become the path (§32). */
    public function test_the_stored_filename_is_randomised(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();

        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $project->id)
            ->set('discipline_id', (string) Discipline::first()->id)
            ->set('document_number', 'ME-1024')
            ->set('title', 'Test')
            ->set('revision', 'A')
            ->set('file', $this->pdf('../../etc/passwd.pdf'))
            ->call('save')
            ->assertHasNoErrors();

        $version = DocumentVersion::first();

        $this->assertStringNotContainsString('passwd', $version->file_path);
        $this->assertStringNotContainsString('..', $version->file_path);
        $this->assertSame('passwd.pdf', $version->original_filename);
    }

    public function test_disallowed_file_types_are_rejected(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();

        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $project->id)
            ->set('discipline_id', (string) Discipline::first()->id)
            ->set('document_number', 'ME-1025')
            ->set('title', 'Test')
            ->set('file', UploadedFile::fake()->create('malware.exe', 10))
            ->call('save')
            ->assertHasErrors('file');

        $this->assertSame(0, Document::count());
    }

    public function test_oversized_files_are_rejected(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();

        $tooBig = config('documents.max_size_kb') + 1024;

        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $project->id)
            ->set('discipline_id', (string) Discipline::first()->id)
            ->set('document_number', 'ME-1026')
            ->set('title', 'Test')
            ->set('file', $this->pdf('huge.pdf', $tooBig))
            ->call('save')
            ->assertHasErrors('file');
    }

    public function test_document_number_is_unique_within_a_project_but_not_across_projects(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $discipline = Discipline::first();

        Document::factory()->create([
            'project_id' => $projectA->id,
            'discipline_id' => $discipline->id,
            'document_number' => 'ME-1023',
        ]);

        // Same number, same project -> rejected.
        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $projectA->id)
            ->set('discipline_id', (string) $discipline->id)
            ->set('document_number', 'ME-1023')
            ->set('title', 'Doublon')
            ->set('file', $this->pdf())
            ->call('save')
            ->assertHasErrors('document_number');

        // Same number, different project -> allowed (§10).
        Livewire::actingAs($engineer)
            ->test(DocumentCreate::class)
            ->set('project_id', (string) $projectB->id)
            ->set('discipline_id', (string) $discipline->id)
            ->set('document_number', 'ME-1023')
            ->set('title', 'Autre projet')
            ->set('revision', 'A')
            ->set('file', $this->pdf())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(2, Document::count());
    }

    public function test_a_reviewer_cannot_upload_a_document(): void
    {
        $this->actingAs($this->userWithRole(UserRole::Reviewer))
            ->get(route('documents.create'))
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Revisions (§6, §22)
    |--------------------------------------------------------------------------
    */

    public function test_uploading_a_revision_never_touches_the_previous_one(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);

        $original = $document->versions()->first();
        $originalPath = $original->file_path;

        app(DocumentService::class)->addRevision(
            document: $document,
            file: $this->pdf('revision-b.pdf'),
            author: $engineer,
        );

        $document->refresh();
        $original->refresh();

        $this->assertSame('B', $document->current_revision);
        $this->assertCount(2, $document->versions);

        // Revision A is untouched and still on disk.
        $this->assertSame($originalPath, $original->file_path);
        $this->assertSame('A', $original->revision);
        Storage::disk('documents')->assertExists($originalPath);
    }

    public function test_a_new_revision_returns_the_document_to_draft(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::NeedsRevision);

        app(DocumentService::class)->addRevision($document, $this->pdf(), $engineer);

        // The new file has not been reviewed, so it cannot inherit a verdict.
        $this->assertSame(DocumentStatus::Draft, $document->fresh()->status);
    }

    public function test_revision_labels_advance_alphabetically(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);

        $service = app(DocumentService::class);

        $this->assertSame('B', $service->addRevision($document, $this->pdf(), $engineer)->revision);
        $this->assertSame('C', $service->addRevision($document->fresh(), $this->pdf(), $engineer)->revision);
    }

    public function test_a_document_under_review_does_not_accept_a_new_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::UnderReview);

        Livewire::actingAs($engineer)
            ->test(DocumentShow::class, ['document' => $document])
            ->set('revisionFile', $this->pdf())
            ->call('uploadRevision');

        $this->assertCount(1, $document->fresh()->versions);
    }

    public function test_a_reviewer_cannot_upload_a_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $reviewer = $this->userWithRole(UserRole::Reviewer);
        $document = $this->documentFor($engineer);

        $this->assertFalse($reviewer->can('uploadRevision', $document));
    }

    /*
    |--------------------------------------------------------------------------
    | Lifecycle (§7)
    |--------------------------------------------------------------------------
    */

    public function test_a_draft_can_be_submitted_for_review(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);

        Livewire::actingAs($engineer)
            ->test(DocumentShow::class, ['document' => $document])
            ->call('submitForReview');

        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);
    }

    public function test_an_approved_document_cannot_be_resubmitted(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::Approved);

        Livewire::actingAs($engineer)
            ->test(DocumentShow::class, ['document' => $document])
            ->call('submitForReview');

        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);
    }

    public function test_lifecycle_actions_are_written_to_the_audit_trail(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);

        app(DocumentService::class)->submitForReview($document, $engineer);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $document->id,
            'subject_type' => Document::class,
            'causer_id' => $engineer->id,
            'description' => 'document.submitted',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Downloads (§32)
    |--------------------------------------------------------------------------
    */

    public function test_an_authorised_user_can_download_a_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $this->actingAs($engineer)
            ->get(route('documents.download', [$document, $version]))
            ->assertOk();
    }

    public function test_a_guest_cannot_download(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $this->get(route('documents.download', [$document, $version]))
            ->assertRedirect(route('login'));
    }

    /**
     * A version id belonging to another document must not be downloadable by
     * pairing it with a document the user may read.
     */
    public function test_a_version_from_another_document_is_not_reachable(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        $documentA = $this->documentFor($engineer);
        $documentB = $this->documentFor($engineer, number: 'ME-9999');

        $versionOfB = $documentB->versions()->first();

        $this->actingAs($engineer)
            ->get(route('documents.download', [$documentA, $versionOfB]))
            ->assertNotFound();
    }

    /*
     * The in-page viewer needs an inline disposition. It used to point at the
     * download route, whose attachment disposition made the browser download
     * the file on every review page load — a Save-As window on Windows — while
     * the preview itself stayed blank, because browsers will not render an
     * attachment inline.
     */
    public function test_the_preview_route_is_served_inline(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $response = $this->actingAs($engineer)
            ->get(route('documents.preview', [$document, $version]))
            ->assertOk();

        $this->assertStringStartsWith(
            'inline;',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_the_download_route_is_served_as_an_attachment(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $response = $this->actingAs($engineer)
            ->get(route('documents.download', [$document, $version]))
            ->assertOk();

        $this->assertStringStartsWith(
            'attachment;',
            $response->headers->get('content-disposition'),
        );
    }

    /**
     * Previewing fires automatically whenever a page holding the viewer is
     * rendered, so logging it would bury the document's real history under one
     * entry per page view — which is exactly what happened before (§34).
     */
    public function test_previewing_is_not_recorded_in_the_audit_trail(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $this->actingAs($engineer)
            ->get(route('documents.preview', [$document, $version]))
            ->assertOk();

        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $document->id,
            'description' => 'document.downloaded',
        ]);
    }

    public function test_the_preview_route_enforces_the_same_guards_as_download(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        $documentA = $this->documentFor($engineer);
        $documentB = $this->documentFor($engineer, number: 'ME-8888');

        // Guests are turned away.
        $this->get(route('documents.preview', [$documentA, $documentA->versions()->first()]))
            ->assertRedirect(route('login'));

        // A version belonging to another document cannot be paired with this one.
        $this->actingAs($engineer)
            ->get(route('documents.preview', [$documentA, $documentB->versions()->first()]))
            ->assertNotFound();
    }

    public function test_downloads_are_recorded_in_the_audit_trail(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);
        $version = $document->versions()->first();

        $this->actingAs($engineer)->get(route('documents.download', [$document, $version]))->assertOk();

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $document->id,
            'causer_id' => $engineer->id,
            'description' => 'document.downloaded',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Listing (§19)
    |--------------------------------------------------------------------------
    */

    public function test_index_filters_by_project_discipline_and_status(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $discipline = Discipline::first();

        Document::factory()->create([
            'project_id' => $project->id,
            'discipline_id' => $discipline->id,
            'document_number' => 'AA-0001',
            'title' => 'Document ciblé',
            'status' => DocumentStatus::Draft,
        ]);

        Document::factory()->create([
            'project_id' => $other->id,
            'discipline_id' => $discipline->id,
            'document_number' => 'BB-0002',
            'title' => 'Document exclu',
            'status' => DocumentStatus::Approved,
        ]);

        $component = Livewire::actingAs($engineer)->test(DocumentIndex::class);

        $component->set('project', (string) $project->id)
            ->assertSee('AA-0001')
            ->assertDontSee('BB-0002');

        $component->set('project', '')
            ->set('status', DocumentStatus::Approved->value)
            ->assertSee('BB-0002')
            ->assertDontSee('AA-0001');
    }

    public function test_index_search_matches_number_and_title(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $discipline = Discipline::first();

        Document::factory()->create([
            'discipline_id' => $discipline->id,
            'document_number' => 'PI-1023',
            'title' => 'Plan tuyauterie',
        ]);
        Document::factory()->create([
            'discipline_id' => $discipline->id,
            'document_number' => 'EL-2250',
            'title' => 'Cheminement câbles',
        ]);

        $component = Livewire::actingAs($engineer)->test(DocumentIndex::class);

        $component->set('search', 'PI-10')->assertSee('Plan tuyauterie')->assertDontSee('Cheminement');
        $component->set('search', 'câbles')->assertSee('Cheminement')->assertDontSee('Plan tuyauterie');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Status-appropriate actions (§13)
    |--------------------------------------------------------------------------
    */

    /**
     * A revision request is answered with a corrected file, not by resending
     * the rejected one. addRevision() already returns the document to Draft,
     * which is the route back into review.
     */
    public function test_a_document_needing_revision_cannot_be_resubmitted_as_is(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::NeedsRevision);

        $this->assertFalse($document->canBeSubmittedForReview());

        Livewire::actingAs($engineer)
            ->test(DocumentShow::class, ['document' => $document])
            ->call('submitForReview');

        // Unchanged: the guard refused rather than moving it into review.
        $this->assertSame(DocumentStatus::NeedsRevision, $document->fresh()->status);
    }

    public function test_uploading_a_revision_reopens_the_route_into_review(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::NeedsRevision);

        app(DocumentService::class)->addRevision($document, $this->pdf(), $engineer);

        $this->assertTrue($document->fresh()->canBeSubmittedForReview());
    }

    /** Only a draft is submittable — every other status has its own next step. */
    public function test_only_a_draft_is_submittable(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        $expected = [
            DocumentStatus::Draft->value => true,
            DocumentStatus::UnderReview->value => false,
            DocumentStatus::NeedsRevision->value => false,
            DocumentStatus::Approved->value => false,
            DocumentStatus::Rejected->value => false,
            DocumentStatus::Archived->value => false,
        ];

        foreach ($expected as $status => $submittable) {
            $document = $this->documentFor($engineer, DocumentStatus::from($status), 'ME-'.crc32($status));

            $this->assertSame(
                $submittable,
                $document->canBeSubmittedForReview(),
                "Submittable mismatch for status {$status}",
            );
        }
    }

    /**
     * Rejection is a verdict, not a dead end.
     *
     * "Rejeté" and "révision requise" differ in what they say about the
     * document — unacceptable versus fixable — but not in the way out: both
     * are answered with a corrected revision, which returns the document to
     * Draft. Were a rejected document to refuse new revisions it could never
     * progress again and the number would have to be abandoned, so
     * DocumentStatus::acceptsNewRevision() deliberately includes it.
     */
    public function test_a_rejected_document_can_still_take_a_new_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::Rejected);

        $this->assertTrue($document->status->acceptsNewRevision());
        // …but not a straight resubmission of the file that was rejected.
        $this->assertFalse($document->canBeSubmittedForReview());

        app(DocumentService::class)->addRevision($document, $this->pdf('rev-b.pdf'), $engineer);

        $document->refresh();

        $this->assertSame('B', $document->current_revision);
        $this->assertSame(DocumentStatus::Draft, $document->status);
        $this->assertTrue($document->canBeSubmittedForReview());
    }

    /** Rejected is not terminal, so the metadata stays correctable too. */
    public function test_a_rejected_documents_metadata_is_still_editable(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::Rejected);

        $this->assertTrue($document->acceptsMetadataEdit());

        $this->actingAs($engineer)
            ->get(route('documents.edit', $document))
            ->assertOk();
    }

    /**
     * The approval was granted for this content, so the metadata is frozen
     * once approved or archived — including for administrators, which is why
     * the rule sits on the model rather than the policy.
     */
    public function test_approved_and_archived_metadata_is_frozen(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        foreach ([DocumentStatus::Approved, DocumentStatus::Archived] as $i => $status) {
            $document = $this->documentFor($engineer, $status, 'ME-200'.$i);

            $this->assertFalse($document->acceptsMetadataEdit());

            // Hiding the menu item is a courtesy; this is the rule (§39).
            $this->actingAs($this->userWithRole(UserRole::Administrator))
                ->get(route('documents.edit', $document))
                ->assertForbidden();
        }
    }

    public function test_an_open_document_can_still_be_edited(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::NeedsRevision);

        $this->assertTrue($document->acceptsMetadataEdit());

        $this->actingAs($engineer)
            ->get(route('documents.edit', $document))
            ->assertOk();
    }

    /*
    |--------------------------------------------------------------------------
    | Deletion (§13)
    |--------------------------------------------------------------------------
    */

    /** There was previously no way to delete a document from the UI at all. */
    public function test_a_manager_can_delete_a_document(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer, DocumentStatus::Approved);

        Livewire::actingAs($this->userWithRole(UserRole::ProjectManager))
            ->test(DocumentShow::class, ['document' => $document])
            ->call('delete')
            ->assertRedirect(route('documents.index'));

        // Soft, so the revisions and signatures it carries stay recoverable.
        $this->assertSoftDeleted($document);
    }

    public function test_an_engineer_cannot_delete_a_document(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $document = $this->documentFor($engineer);

        Livewire::actingAs($engineer)
            ->test(DocumentShow::class, ['document' => $document])
            ->call('delete')
            ->assertForbidden();

        $this->assertNotSoftDeleted($document);
    }

    private function documentFor(
        User $author,
        DocumentStatus $status = DocumentStatus::Draft,
        string $number = 'ME-1023',
    ): Document {
        $document = app(DocumentService::class)->create(
            attributes: [
                'project_id' => Project::factory()->create()->id,
                'discipline_id' => Discipline::first()->id,
                'document_number' => $number,
                'title' => 'Document de test',
                'current_revision' => 'A',
            ],
            file: $this->pdf(),
            author: $author,
        );

        if ($status !== DocumentStatus::Draft) {
            $document->forceFill(['status' => $status])->save();
        }

        return $document->fresh();
    }
}
