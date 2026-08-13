<?php

namespace Tests\Feature\Approvals;

use App\Enums\ApprovalStatus;
use App\Enums\DocumentStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Documents\Show as DocumentShow;
use App\Models\Approval;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ApprovalRequested;
use App\Notifications\DocumentDecided;
use App\Services\ApprovalService;
use App\Services\DocumentService;
use App\Services\ReviewService;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ApprovalModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $engineer;

    private User $reviewer;

    private User $manager;

    private User $approver;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);

        $this->engineer = $this->userWithRole(UserRole::Engineer, 'Youssef Amrani');
        $this->reviewer = $this->userWithRole(UserRole::Reviewer, 'Karim Oulhaj');
        $this->manager = $this->userWithRole(UserRole::ProjectManager, 'Nadia Benchekroun');
        $this->approver = $this->userWithRole(UserRole::Approver, 'Rachid El Malki');
    }

    private function userWithRole(UserRole $role, string $name = 'Utilisateur'): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    /** The §8 example circuit: reviewer, then project manager, then approver. */
    private function seedWorkflow(): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::create([
            'project_id' => null,
            'name' => 'Circuit standard',
            'is_active' => true,
            'is_default' => true,
        ]);

        foreach ([
            [1, UserRole::Reviewer->value],
            [2, UserRole::ProjectManager->value],
            [3, UserRole::Approver->value],
        ] as [$order, $role]) {
            ApprovalWorkflowStep::create([
                'workflow_id' => $workflow->id,
                'step_order' => $order,
                'role' => $role,
                'required' => true,
                'turnaround_days' => 3,
            ]);
        }

        return $workflow->load('steps');
    }

    private function documentFor(User $author, string $number = 'ME-1023'): Document
    {
        return app(DocumentService::class)->create(
            attributes: [
                'project_id' => Project::factory()->create(['manager_id' => $this->manager->id])->id,
                'discipline_id' => Discipline::first()->id,
                'document_number' => $number,
                'title' => 'Document de test',
                'current_revision' => 'A',
            ],
            file: UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'),
            author: $author,
        );
    }

    /** Take a document all the way through review so approvals kick in. */
    private function documentPastReview(): Document
    {
        $document = $this->documentFor($this->engineer);

        $reviews = app(ReviewService::class);
        $reviews->assign($document->latestVersion, [$this->reviewer], $this->manager);
        $reviews->decide(Review::first(), $this->reviewer, ReviewStatus::Approved);

        return $document->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Handoff from review (§7 -> §8)
    |--------------------------------------------------------------------------
    */

    /**
     * Clearing review is not the same as being approved: the document must
     * wait for the signature circuit.
     */
    public function test_clearing_review_starts_the_circuit_without_approving_the_document(): void
    {
        $this->seedWorkflow();

        $document = $this->documentPastReview();

        $this->assertSame(DocumentStatus::UnderReview, $document->status);
        $this->assertSame(3, $document->latestVersion->approvals()->count());
    }

    /** With no workflow defined there is nothing left to sign. */
    public function test_without_a_workflow_clearing_review_approves_the_document(): void
    {
        $document = $this->documentPastReview();

        $this->assertSame(DocumentStatus::Approved, $document->status);
        $this->assertSame(0, $document->latestVersion->approvals()->count());
    }

    public function test_only_the_first_step_is_active_when_the_circuit_starts(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $approvals = $document->latestVersion->approvals()->orderBy('step')->get();

        $this->assertSame(ApprovalStatus::InProgress, $approvals[0]->status);
        $this->assertSame(ApprovalStatus::Pending, $approvals[1]->status);
        $this->assertSame(ApprovalStatus::Pending, $approvals[2]->status);
    }

    public function test_the_project_manager_step_is_assigned_to_the_projects_own_manager(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $step = $document->latestVersion->approvals()->where('step', 2)->first();

        $this->assertSame($this->manager->id, $step->approver_id);
    }

    /*
    |--------------------------------------------------------------------------
    | Sequencing (§8)
    |--------------------------------------------------------------------------
    */

    public function test_approving_a_step_activates_the_next_one_and_notifies_it(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        app(ApprovalService::class)->decide($first, $this->reviewer, approved: true);

        $approvals = $document->latestVersion->approvals()->orderBy('step')->get();

        $this->assertSame(ApprovalStatus::Approved, $approvals[0]->status);
        $this->assertSame(ApprovalStatus::InProgress, $approvals[1]->status);
        $this->assertSame(ApprovalStatus::Pending, $approvals[2]->status);

        Notification::assertSentTo($this->manager, ApprovalRequested::class);
    }

    public function test_the_document_is_approved_only_after_the_final_step(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();
        $service = app(ApprovalService::class);

        $service->decide($document->latestVersion->approvals()->where('step', 1)->first(), $this->reviewer, true);
        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);

        $service->decide($document->latestVersion->approvals()->where('step', 2)->first(), $this->manager, true);
        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);

        $service->decide($document->latestVersion->approvals()->where('step', 3)->first(), $this->approver, true);
        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);

        Notification::assertSentTo($this->engineer, DocumentDecided::class);
    }

    /** A rejection stops the circuit; later steps are skipped, not left open. */
    public function test_a_rejection_ends_the_circuit_and_skips_remaining_steps(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        app(ApprovalService::class)->decide($first, $this->reviewer, approved: false, comment: 'Non conforme.');

        $approvals = $document->latestVersion->approvals()->orderBy('step')->get();

        $this->assertSame(ApprovalStatus::Rejected, $approvals[0]->status);
        $this->assertSame(ApprovalStatus::Skipped, $approvals[1]->status);
        $this->assertSame(ApprovalStatus::Skipped, $approvals[2]->status);
        $this->assertSame(DocumentStatus::Rejected, $document->fresh()->status);
    }

    /** A step nobody can sign would stall the chain, so it is skipped. */
    public function test_a_step_with_no_eligible_approver_is_skipped(): void
    {
        $this->seedWorkflow();

        // Remove the only Approver, leaving step 3 unassignable.
        $this->approver->forceFill(['status' => UserStatus::Inactive])->save();

        $document = $this->documentPastReview();
        $service = app(ApprovalService::class);

        $third = $document->latestVersion->approvals()->where('step', 3)->first();
        $this->assertSame(ApprovalStatus::Skipped, $third->status);

        $service->decide($document->latestVersion->approvals()->where('step', 1)->first(), $this->reviewer, true);
        $service->decide($document->latestVersion->approvals()->where('step', 2)->first(), $this->manager, true);

        // The chain completes rather than waiting forever on the empty step.
        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization (§13)
    |--------------------------------------------------------------------------
    */

    /**
     * The defining rule: an approver later in the circuit holds the same
     * permission but must not be able to sign ahead of their turn.
     */
    public function test_an_approver_cannot_sign_ahead_of_their_turn(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $finalStep = $document->latestVersion->approvals()->where('step', 3)->first();

        $this->assertTrue($this->approver->can('documents.approve'));
        $this->assertFalse($this->approver->can('approve', $finalStep));
    }

    public function test_a_step_can_only_be_signed_by_its_assigned_approver(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();
        $intruder = $this->userWithRole(UserRole::Approver, 'Intrus');

        $this->assertTrue($this->reviewer->can('approve', $first));
        $this->assertFalse($intruder->can('approve', $first));
    }

    public function test_a_settled_step_cannot_be_signed_again(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();
        app(ApprovalService::class)->decide($first, $this->reviewer, approved: true);

        $this->assertFalse($this->reviewer->can('approve', $first->fresh()));
    }

    public function test_an_engineer_cannot_approve(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        $this->assertFalse($this->engineer->can('approve', $first));
    }

    /*
    |--------------------------------------------------------------------------
    | Component actions
    |--------------------------------------------------------------------------
    */

    public function test_the_active_approver_can_sign_from_the_document_page(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        Livewire::actingAs($this->reviewer)
            ->test(DocumentShow::class, ['document' => $document])
            ->set('tab', 'approvals')
            ->call('approveStep', $first->id)
            ->assertHasNoErrors();

        $this->assertSame(ApprovalStatus::Approved, $first->fresh()->status);
    }

    public function test_signing_someone_elses_step_is_forbidden_through_the_component(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        Livewire::actingAs($this->approver)
            ->test(DocumentShow::class, ['document' => $document])
            ->call('approveStep', $first->id)
            ->assertForbidden();

        $this->assertSame(ApprovalStatus::InProgress, $first->fresh()->status);
    }

    /** A rejection without a reason is unusable to the engineer. */
    public function test_rejecting_requires_a_comment(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();

        Livewire::actingAs($this->reviewer)
            ->test(DocumentShow::class, ['document' => $document])
            ->set('approvalComment', '')
            ->call('rejectStep', $first->id)
            ->assertHasErrors('approvalComment');

        $this->assertSame(ApprovalStatus::InProgress, $first->fresh()->status);
    }

    /**
     * An approval id from another document must not be actionable via a
     * document the user happens to be able to open (§39).
     */
    public function test_an_approval_from_another_document_is_not_reachable(): void
    {
        $this->seedWorkflow();

        $documentA = $this->documentPastReview();

        $documentB = $this->documentFor($this->engineer, 'ME-9999');
        $reviews = app(ReviewService::class);
        $reviews->assign($documentB->latestVersion, [$this->reviewer], $this->manager);
        $reviews->decide(
            Review::where('document_version_id', $documentB->latestVersion->id)->first(),
            $this->reviewer,
            ReviewStatus::Approved,
        );

        $stepOfB = $documentB->fresh()->latestVersion->approvals()->where('step', 1)->first();

        // Resolved through documentA's own relation, so the id simply is not
        // found — it never reaches the policy, let alone the service.
        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($this->reviewer)
            ->test(DocumentShow::class, ['document' => $documentA])
            ->call('approveStep', $stepOfB->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Audit trail (§34)
    |--------------------------------------------------------------------------
    */

    public function test_approval_decisions_are_written_to_the_audit_trail(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();
        app(ApprovalService::class)->decide($first, $this->reviewer, approved: true);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $document->id,
            'causer_id' => $this->reviewer->id,
            'description' => 'document.approval_approved',
        ]);
    }

    public function test_the_overdue_scope_only_counts_open_steps(): void
    {
        $this->seedWorkflow();
        $document = $this->documentPastReview();

        $first = $document->latestVersion->approvals()->where('step', 1)->first();
        $first->forceFill(['deadline' => now()->subDays(2)])->save();

        $this->assertSame(1, Approval::query()->overdue()->count());

        app(ApprovalService::class)->decide($first, $this->reviewer, approved: true);

        // Settled steps are never overdue, however late they were.
        $this->assertSame(0, Approval::query()->overdue()->where('step', 1)->count());
    }
}
